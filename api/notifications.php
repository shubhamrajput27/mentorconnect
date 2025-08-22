<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            $limit = intval($_GET['limit'] ?? 20);
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $whereClause = "user_id = ?";
            $params = [$user['id']];
            
            if ($unreadOnly) {
                $whereClause .= " AND is_read = FALSE";
            }
            
            $notifications = fetchAll(
                "SELECT * FROM notifications 
                 WHERE $whereClause 
                 ORDER BY created_at DESC 
                 LIMIT ?",
                array_merge($params, [$limit])
            );
            
            $unreadCount = fetchOne(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE",
                [$user['id']]
            )['count'];
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
            
        } elseif ($action === 'count') {
            $unreadCount = fetchOne(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE",
                [$user['id']]
            )['count'];
            
            echo json_encode([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
            
        } else {
            throw new Exception('Invalid action');
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'mark_read') {
            $notificationId = intval($input['notification_id'] ?? 0);
            
            if ($notificationId > 0) {
                executeQuery(
                    "UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?",
                    [$notificationId, $user['id']]
                );
            } else {
                // Mark all as read
                executeQuery(
                    "UPDATE notifications SET is_read = TRUE WHERE user_id = ?",
                    [$user['id']]
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Notifications marked as read'
            ]);
            
        } elseif ($action === 'delete') {
            $notificationId = intval($input['notification_id'] ?? 0);
            
            if (!$notificationId) {
                throw new Exception('Notification ID required');
            }
            
            executeQuery(
                "DELETE FROM notifications WHERE id = ? AND user_id = ?",
                [$notificationId, $user['id']]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted'
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
