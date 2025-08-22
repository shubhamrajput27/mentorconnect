<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'send') {
            $receiverId = intval($input['receiver_id'] ?? 0);
            $message = sanitizeInput($input['message'] ?? '');
            $subject = sanitizeInput($input['subject'] ?? '');
            
            if (!$receiverId || !$message) {
                throw new Exception('Missing required fields');
            }
            
            // Verify receiver exists
            $receiver = fetchOne("SELECT id FROM users WHERE id = ?", [$receiverId]);
            if (!$receiver) {
                throw new Exception('Recipient not found');
            }
            
            // Insert message
            executeQuery(
                "INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)",
                [$user['id'], $receiverId, $subject, $message]
            );
            
            $messageId = fetchOne("SELECT LAST_INSERT_ID() as id")['id'];
            
            // Log activity
            logActivity($user['id'], 'message_sent', "Message sent to user ID: $receiverId");
            
            // Create notification for receiver
            createNotification(
                $receiverId,
                'message',
                'New message from ' . $user['first_name'] . ' ' . $user['last_name'],
                substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                '/messages/index.php?contact=' . $user['id']
            );
            
            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message sent successfully'
            ]);
            
        } else {
            throw new Exception('Invalid action');
        }
        
    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'get') {
            $contactId = intval($_GET['contact_id'] ?? 0);
            $after = intval($_GET['after'] ?? 0);
            
            if (!$contactId) {
                throw new Exception('Contact ID required');
            }
            
            $whereClause = "((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))";
            $params = [$user['id'], $contactId, $contactId, $user['id']];
            
            if ($after > 0) {
                $whereClause .= " AND created_at > FROM_UNIXTIME(?)";
                $params[] = $after / 1000; // Convert from milliseconds
            }
            
            $messages = fetchAll(
                "SELECT * FROM messages WHERE $whereClause ORDER BY created_at ASC",
                $params
            );
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            
        } elseif ($action === 'conversations') {
            // Get user's conversations
            $conversations = fetchAll(
                "SELECT 
                    CASE 
                        WHEN m.sender_id = ? THEN m.receiver_id 
                        ELSE m.sender_id 
                    END as contact_id,
                    u.first_name, u.last_name, u.profile_photo,
                    MAX(m.created_at) as last_message_time,
                    (SELECT message FROM messages m2 
                     WHERE (m2.sender_id = ? AND m2.receiver_id = contact_id) 
                        OR (m2.sender_id = contact_id AND m2.receiver_id = ?)
                     ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                    (SELECT COUNT(*) FROM messages m3 
                     WHERE m3.sender_id = contact_id AND m3.receiver_id = ? AND m3.is_read = FALSE) as unread_count
                 FROM messages m
                 JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
                 WHERE m.sender_id = ? OR m.receiver_id = ?
                 GROUP BY contact_id, u.first_name, u.last_name, u.profile_photo
                 ORDER BY last_message_time DESC",
                [$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]
            );
            
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
            
        } else {
            throw new Exception('Invalid action');
        }
        
    } else {
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
