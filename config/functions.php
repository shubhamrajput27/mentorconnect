<?php
/**
 * Core Application Functions for MentorConnect
 */

/**
 * Database Helper Functions - simplified but optimized
 */
function fetchOne($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        logError('Database fetch error', ['sql' => $sql, 'error' => $e->getMessage()]);
        return null;
    }
}

function fetchAll($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logError('Database fetchAll error', ['sql' => $sql, 'error' => $e->getMessage()]);
        return [];
    }
}

function insertRecord($table, $data) {
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    
    try {
        $stmt = executeQuery($sql, $data);
        return getConnection()->lastInsertId();
    } catch (Exception $e) {
        logError('Insert error', ['table' => $table, 'error' => $e->getMessage()]);
        return false;
    }
}

function updateRecord($table, $data, $where, $whereParams = []) {
    $set = [];
    foreach (array_keys($data) as $column) {
        $set[] = "{$column} = :{$column}";
    }
    $setClause = implode(', ', $set);
    
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
    $params = array_merge($data, $whereParams);
    
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount();
    } catch (Exception $e) {
        logError('Update error', ['table' => $table, 'error' => $e->getMessage()]);
        return false;
    }
}

function deleteRecord($table, $where, $params = []) {
    $sql = "DELETE FROM {$table} WHERE {$where}";
    
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount();
    } catch (Exception $e) {
        logError('Delete error', ['table' => $table, 'error' => $e->getMessage()]);
        return false;
    }
}

/**
 * User Management Functions
 */
function createUser($userData) {
    // Hash password
    if (isset($userData['password'])) {
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);
    }
    
    // Set defaults
    $userData['is_active'] = $userData['is_active'] ?? true;
    $userData['created_at'] = date('Y-m-d H:i:s');
    
    return insertRecord('users', $userData);
}

function updateUserLastActivity($userId) {
    return updateRecord('users', 
        ['last_activity' => date('Y-m-d H:i:s')], 
        'id = ?', 
        [$userId]
    );
}

/**
 * Session Management
 */
function createSession($sessionData) {
    return insertRecord('sessions', $sessionData);
}

function updateSession($sessionId, $data) {
    return updateRecord('sessions', $data, 'id = ?', [$sessionId]);
}

function getSessionsForUser($userId, $status = null) {
    $sql = "SELECT s.*, u.first_name, u.last_name FROM sessions s 
            JOIN users u ON s.mentor_id = u.id 
            WHERE s.student_id = ?";
    $params = [$userId];
    
    if ($status) {
        $sql .= " AND s.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY s.scheduled_at DESC";
    
    return fetchAll($sql, $params);
}

/**
 * Notification Management
 */
function getNotificationsForUser($userId, $limit = 20, $unreadOnly = false) {
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];
    
    if ($unreadOnly) {
        $sql .= " AND is_read = FALSE";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return fetchAll($sql, $params);
}

function markNotificationAsRead($notificationId, $userId) {
    return updateRecord('notifications', 
        ['is_read' => true], 
        'id = ? AND user_id = ?', 
        [$notificationId, $userId]
    );
}

function markAllNotificationsAsRead($userId) {
    return updateRecord('notifications', 
        ['is_read' => true], 
        'user_id = ? AND is_read = FALSE', 
        [$userId]
    );
}

/**
 * Message Management
 */
function createMessage($senderId, $recipientId, $subject, $content, $sessionId = null) {
    return insertRecord('messages', [
        'sender_id' => $senderId,
        'recipient_id' => $recipientId,
        'subject' => $subject,
        'message' => $content,
        'session_id' => $sessionId
    ]);
}

function getConversation($userId1, $userId2, $limit = 50) {
    $sql = "SELECT m.*, 
                   s.first_name as sender_first_name, s.last_name as sender_last_name,
                   r.first_name as recipient_first_name, r.last_name as recipient_last_name
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.recipient_id = r.id
            WHERE (m.sender_id = ? AND m.recipient_id = ?) 
               OR (m.sender_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at DESC LIMIT ?";
    
    return fetchAll($sql, [$userId1, $userId2, $userId2, $userId1, $limit]);
}

/**
 * Search Functions
 */
function searchMentors($searchTerm, $skills = [], $limit = 20) {
    $sql = "SELECT DISTINCT u.*, mp.title, mp.company, mp.rating, mp.hourly_rate 
            FROM users u 
            JOIN mentor_profiles mp ON u.id = mp.user_id 
            WHERE u.user_type = 'mentor' AND u.is_active = TRUE";
    
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR mp.title LIKE ? OR mp.company LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
        $params = array_merge($params, [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
    }
    
    if (!empty($skills)) {
        $skillPlaceholders = str_repeat('?,', count($skills) - 1) . '?';
        $sql .= " AND u.id IN (
                    SELECT us.user_id FROM user_skills us 
                    JOIN skills s ON us.skill_id = s.id 
                    WHERE s.name IN ({$skillPlaceholders})
                  )";
        $params = array_merge($params, $skills);
    }
    
    $sql .= " ORDER BY mp.rating DESC, mp.total_sessions DESC LIMIT ?";
    $params[] = $limit;
    
    return fetchAll($sql, $params);
}

/**
 * Statistics Functions
 */
function getUserStats($userId) {
    $user = getCurrentUser();
    
    if ($user['user_type'] === 'student') {
        return [
            'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ?", [$userId])['count'] ?? 0,
            'upcoming_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ? AND status = 'scheduled' AND scheduled_at > NOW()", [$userId])['count'] ?? 0,
            'completed_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ? AND status = 'completed'", [$userId])['count'] ?? 0,
            'unread_messages' => fetchOne("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = FALSE", [$userId])['count'] ?? 0
        ];
    } else {
        return [
            'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$userId])['count'] ?? 0,
            'upcoming_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND status = 'scheduled' AND scheduled_at > NOW()", [$userId])['count'] ?? 0,
            'completed_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND status = 'completed'", [$userId])['count'] ?? 0,
            'unread_messages' => fetchOne("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = FALSE", [$userId])['count'] ?? 0
        ];
    }
}

/**
 * File Helper Functions
 */
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

function generateFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Time Helper Functions
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

/**
 * Validation Functions
 */
function validateRequired($data, $requiredFields) {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    
    return empty($missing) ? true : $missing;
}

function validateLength($value, $min = 0, $max = null) {
    $length = strlen($value);
    
    if ($length < $min) {
        return "Must be at least {$min} characters";
    }
    
    if ($max && $length > $max) {
        return "Must not exceed {$max} characters";
    }
    
    return true;
}

/**
 * Response Helper Functions
 */
function successResponse($data = [], $message = 'Success') {
    return respondJSON([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

function errorResponse($message = 'An error occurred', $code = 400, $details = []) {
    return respondJSON([
        'success' => false,
        'message' => $message,
        'details' => $details
    ], $code);
}
?>
