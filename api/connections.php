<?php
// Mentor-Mentee Connection Management API
require_once '../config/database.php';

class ConnectionManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Send a connection request from mentee to mentor or vice versa
     */
    public function sendConnectionRequest($senderId, $recipientId, $message = '', $connectionType = 'ongoing', $goals = '') {
        // Validate users exist and have different roles
        $sender = $this->getUser($senderId);
        $recipient = $this->getUser($recipientId);
        
        if (!$sender || !$recipient) {
            throw new Exception('User not found');
        }
        
        // Determine mentor and mentee
        if ($sender['role'] === 'mentor' && $recipient['role'] === 'student') {
            $mentorId = $senderId;
            $menteeId = $recipientId;
            $requestedBy = 'mentor';
        } elseif ($sender['role'] === 'student' && $recipient['role'] === 'mentor') {
            $mentorId = $recipientId;
            $menteeId = $senderId;
            $requestedBy = 'mentee';
        } else {
            throw new Exception('Connections can only be made between mentors and students');
        }
        
        // Check if connection already exists
        $existing = $this->getExistingConnection($mentorId, $menteeId);
        if ($existing) {
            if ($existing['status'] === 'pending') {
                throw new Exception('Connection request already pending');
            } elseif ($existing['status'] === 'active') {
                throw new Exception('You are already connected');
            }
        }
        
        // Create connection request
        $sql = "INSERT INTO mentor_mentee_connections 
                (mentor_id, mentee_id, status, connection_type, requested_by, request_message, goals) 
                VALUES (?, ?, 'pending', ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$mentorId, $menteeId, $connectionType, $requestedBy, $message, $goals]);
        
        $connectionId = $this->db->lastInsertId();
        
        // Log activity
        $this->logConnectionActivity($connectionId, 'status_changed', $senderId, 
                                   "Connection request sent by {$sender['role']}");
        
        // Send notification
        $this->createNotification($recipientId, 'connection_request', 
                                'New Connection Request', 
                                "You have a new connection request from {$sender['first_name']} {$sender['last_name']}", 
                                ['connection_id' => $connectionId, 'sender_id' => $senderId]);
        
        return $connectionId;
    }
    
    /**
     * Respond to a connection request (accept/reject)
     */
    public function respondToConnectionRequest($connectionId, $userId, $action, $responseMessage = '') {
        $connection = $this->getConnection($connectionId);
        if (!$connection) {
            throw new Exception('Connection not found');
        }
        
        if ($connection['status'] !== 'pending') {
            throw new Exception('Connection request is no longer pending');
        }
        
        // Verify user is the recipient
        $recipientId = ($connection['requested_by'] === 'mentor') ? $connection['mentee_id'] : $connection['mentor_id'];
        if ($userId != $recipientId) {
            throw new Exception('You are not authorized to respond to this request');
        }
        
        $newStatus = ($action === 'accept') ? 'active' : 'rejected';
        $startDate = ($action === 'accept') ? date('Y-m-d') : null;
        
        $sql = "UPDATE mentor_mentee_connections 
                SET status = ?, response_message = ?, responded_at = NOW(), start_date = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newStatus, $responseMessage, $startDate, $connectionId]);
        
        // Log activity
        $user = $this->getUser($userId);
        $this->logConnectionActivity($connectionId, 'status_changed', $userId, 
                                   "Connection request {$action}ed by {$user['first_name']}");
        
        // Notify the original requester
        $requesterId = ($connection['requested_by'] === 'mentor') ? $connection['mentor_id'] : $connection['mentee_id'];
        $actionText = ($action === 'accept') ? 'accepted' : 'rejected';
        $this->createNotification($requesterId, 'connection_response', 
                                "Connection Request {ucfirst($actionText)}", 
                                "Your connection request has been {$actionText}", 
                                ['connection_id' => $connectionId, 'action' => $action]);
        
        return $this->getConnection($connectionId);
    }
    
    /**
     * Get user's connections with optimized query
     */
    public function getUserConnections($userId, $status = null, $limit = 50, $offset = 0) {
        $user = $this->getUser($userId);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        $conditions = [];
        $params = [];
        
        // Optimize with specific role-based queries
        if ($user['role'] === 'mentor') {
            $conditions[] = "c.mentor_id = ?";
            $params[] = $userId;
        } elseif ($user['role'] === 'student') {
            $conditions[] = "c.mentee_id = ?";
            $params[] = $userId;
        } else {
            // Admin - add user filter to prevent full table scan
            if ($userId) {
                $conditions[] = "(c.mentor_id = ? OR c.mentee_id = ?)";
                $params = [$userId, $userId];
            }
        }
        
        if ($status) {
            $conditions[] = "c.status = ?";
            $params[] = $status;
        }
        
        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        
        // Optimized query with only needed fields
        $sql = "SELECT c.id, c.status, c.connection_type, c.request_message, c.goals, 
                       c.created_at, c.start_date, c.requested_by,
                       mentor.first_name as mentor_first_name, 
                       mentor.last_name as mentor_last_name,
                       mentor.profile_photo as mentor_photo,
                       mentee.first_name as mentee_first_name, 
                       mentee.last_name as mentee_last_name,
                       mentee.profile_photo as mentee_photo,
                       mp.title as mentor_title,
                       mp.company as mentor_company,
                       mp.rating as mentor_rating
                FROM mentor_mentee_connections c
                INNER JOIN users mentor ON c.mentor_id = mentor.id
                INNER JOIN users mentee ON c.mentee_id = mentee.id
                LEFT JOIN mentor_profiles mp ON mentor.id = mp.user_id
                {$whereClause}
                ORDER BY 
                    CASE WHEN c.status = 'pending' THEN 1 
                         WHEN c.status = 'active' THEN 2 
                         ELSE 3 END,
                    c.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update connection status or details
     */
    public function updateConnection($connectionId, $userId, $updates) {
        $connection = $this->getConnection($connectionId);
        if (!$connection) {
            throw new Exception('Connection not found');
        }
        
        // Verify user is part of this connection
        if ($userId != $connection['mentor_id'] && $userId != $connection['mentee_id']) {
            throw new Exception('You are not authorized to update this connection');
        }
        
        $allowedUpdates = ['status', 'notes', 'goals', 'expectations', 'end_date'];
        $setClauses = [];
        $params = [];
        
        foreach ($updates as $field => $value) {
            if (in_array($field, $allowedUpdates)) {
                $setClauses[] = "{$field} = ?";
                $params[] = $value;
            }
        }
        
        if (empty($setClauses)) {
            throw new Exception('No valid fields to update');
        }
        
        $setClauses[] = "updated_at = NOW()";
        $params[] = $connectionId;
        
        $sql = "UPDATE mentor_mentee_connections SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // Log activity if status changed
        if (isset($updates['status'])) {
            $user = $this->getUser($userId);
            $this->logConnectionActivity($connectionId, 'status_changed', $userId, 
                                       "Status changed to {$updates['status']} by {$user['first_name']}");
        }
        
        return $this->getConnection($connectionId);
    }
    
    /**
     * Get connection statistics for a user with caching
     */
    public function getConnectionStats($userId) {
        $user = $this->getUser($userId);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Simple caching mechanism
        $cacheKey = "connection_stats_{$userId}";
        $cacheFile = sys_get_temp_dir() . "/" . $cacheKey . ".cache";
        
        // Check cache (5 minutes)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $cached = file_get_contents($cacheFile);
            if ($cached) {
                return json_decode($cached, true);
            }
        }
        
        // Single optimized query for both roles
        $sql = "SELECT 
                    COUNT(*) as total_connections,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_connections,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_connections,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_connections
                FROM mentor_mentee_connections 
                WHERE " . ($user['role'] === 'mentor' ? 'mentor_id = ?' : 'mentee_id = ?');
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Cache the result
        file_put_contents($cacheFile, json_encode($stats));
        
        return $stats;
    }
    
    /**
     * Get connection activities/history
     */
    public function getConnectionActivities($connectionId, $limit = 50) {
        $sql = "SELECT ca.*, u.first_name, u.last_name, u.profile_photo
                FROM connection_activities ca
                JOIN users u ON ca.actor_id = u.id
                WHERE ca.connection_id = ?
                ORDER BY ca.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$connectionId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Helper methods
    private function getUser($userId) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getConnection($connectionId) {
        $sql = "SELECT * FROM mentor_mentee_connections WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$connectionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getExistingConnection($mentorId, $menteeId) {
        $sql = "SELECT * FROM mentor_mentee_connections 
                WHERE mentor_id = ? AND mentee_id = ? 
                AND status IN ('pending', 'active', 'paused')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$mentorId, $menteeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function logConnectionActivity($connectionId, $activityType, $actorId, $description, $metadata = null) {
        $sql = "INSERT INTO connection_activities 
                (connection_id, activity_type, actor_id, description, metadata) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$connectionId, $activityType, $actorId, $description, 
                       $metadata ? json_encode($metadata) : null]);
    }
    
    private function createNotification($userId, $type, $title, $message, $data = null) {
        $sql = "INSERT INTO notifications (user_id, type, title, message, data) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $type, $title, $message, 
                       $data ? json_encode($data) : null]);
    }
}

// Handle API requests
header('Content-Type: application/json');

try {
    $manager = new ConnectionManager();
    $currentUser = getCurrentUser();
    
    if (!$currentUser) {
        throw new Exception('Authentication required');
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'POST':
            if ($action === 'send_request') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $recipientId = intval($data['recipient_id']);
                $message = sanitizeInput($data['message'] ?? '');
                $connectionType = sanitizeInput($data['connection_type'] ?? 'ongoing');
                $goals = sanitizeInput($data['goals'] ?? '');
                
                $connectionId = $manager->sendConnectionRequest(
                    $currentUser['id'], 
                    $recipientId, 
                    $message, 
                    $connectionType, 
                    $goals
                );
                
                echo json_encode([
                    'success' => true,
                    'connection_id' => $connectionId,
                    'message' => 'Connection request sent successfully'
                ]);
                
            } elseif ($action === 'respond') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $connectionId = intval($data['connection_id']);
                $response = sanitizeInput($data['action']); // 'accept' or 'reject'
                $responseMessage = sanitizeInput($data['message'] ?? '');
                
                $connection = $manager->respondToConnectionRequest(
                    $connectionId, 
                    $currentUser['id'], 
                    $response, 
                    $responseMessage
                );
                
                echo json_encode([
                    'success' => true,
                    'connection' => $connection,
                    'message' => 'Response sent successfully'
                ]);
                
            } elseif ($action === 'update') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $connectionId = intval($data['connection_id']);
                unset($data['connection_id'], $data['action']);
                
                $connection = $manager->updateConnection($connectionId, $currentUser['id'], $data);
                
                echo json_encode([
                    'success' => true,
                    'connection' => $connection,
                    'message' => 'Connection updated successfully'
                ]);
            }
            break;
            
        case 'GET':
            if ($action === 'list') {
                $status = sanitizeInput($_GET['status'] ?? null);
                $connections = $manager->getUserConnections($currentUser['id'], $status);
                
                echo json_encode([
                    'success' => true,
                    'connections' => $connections
                ]);
                
            } elseif ($action === 'stats') {
                $stats = $manager->getConnectionStats($currentUser['id']);
                
                echo json_encode([
                    'success' => true,
                    'stats' => $stats
                ]);
                
            } elseif ($action === 'activities') {
                $connectionId = intval($_GET['connection_id']);
                $limit = min(intval($_GET['limit'] ?? 50), 100);
                
                $activities = $manager->getConnectionActivities($connectionId, $limit);
                
                echo json_encode([
                    'success' => true,
                    'activities' => $activities
                ]);
            }
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>