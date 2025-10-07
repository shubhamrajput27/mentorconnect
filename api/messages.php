<?php
require_once '../config/database.php';
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
                "INSERT INTO messages (sender_id, recipient_id, subject, content, message) VALUES (?, ?, ?, ?, ?)",
                [$user['id'], $receiverId, $subject, $message, $message]
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
            
            $whereClause = "((sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?))";
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
            // Get conversation messages
            $conversations = fetchAll(
                "SELECT DISTINCT
                    CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END as contact_id,
                    u.first_name, u.last_name, u.profile_photo,
                    m.created_at as last_message_time,
                    m.message as last_message,
                    COALESCE(unread.unread_count, 0) as unread_count
                FROM messages m
                INNER JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END
                LEFT JOIN (
                    SELECT recipient_id, COUNT(*) as unread_count 
                    FROM messages 
                    WHERE recipient_id = ? AND is_read = 0 
                    GROUP BY recipient_id
                ) unread ON unread.recipient_id = u.id
                WHERE (m.sender_id = ? OR m.recipient_id = ?) 
                AND m.id IN (
                    SELECT MAX(id) FROM messages m2 
                    WHERE (m2.sender_id = ? AND m2.recipient_id = u.id) 
                       OR (m2.sender_id = u.id AND m2.recipient_id = ?)
                )
                ORDER BY m.created_at DESC",
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
