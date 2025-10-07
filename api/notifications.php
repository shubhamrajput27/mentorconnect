<?php
require_once '../config/database.php';
require_once '../config/database-optimizer.php';
require_once '../config/performance-monitor.php';

// Start performance monitoring
PerformanceMonitor::start();

// Set security headers
SecurityOptimizer::setSecurityHeaders();

requireLogin();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    PerformanceMonitor::mark('auth_complete');
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            $limit = max(1, min(100, intval($_GET['limit'] ?? 20))); // Limit between 1-100
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            PerformanceMonitor::mark('params_validated');
            
            // Use optimized query with caching
            $notifications = DatabaseOptimizer::getNotificationsOptimized($user['id'], $limit, $unreadOnly);
            
            PerformanceMonitor::mark('notifications_fetched');
            
            // Get unread count with caching
            $unreadCount = fetchOneOptimized(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE",
                [$user['id']],
                "unread_count_{$user['id']}",
                30 // Cache for 30 seconds
            )['count'];
            
            PerformanceMonitor::mark('unread_count_fetched');
            
            // Format timestamps for better frontend handling
            foreach ($notifications as &$notification) {
                $notification['created_at_formatted'] = formatTimeAgo($notification['created_at']);
                $notification['created_at_timestamp'] = strtotime($notification['created_at']);
            }
            
            $response = [
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount,
                    'total_count' => count($notifications),
                    'has_more' => count($notifications) === $limit
                ],
                'meta' => [
                    'limit' => $limit,
                    'unread_only' => $unreadOnly
                ]
            ];
            
        } elseif ($action === 'count') {
            // Use optimized query with caching
            $unreadCount = fetchOneOptimized(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE",
                [$user['id']],
                "unread_count_{$user['id']}",
                30 // Cache for 30 seconds
            )['count'];
            
            $response = [
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ];
            
        } else {
            throw new Exception('Invalid action parameter');
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data');
        }
        
        $action = $input['action'] ?? '';
        
        PerformanceMonitor::mark('input_parsed');
        
        if ($action === 'mark_read') {
            $notificationId = intval($input['notification_id'] ?? 0);
            
            if ($notificationId > 0) {
                // Mark specific notification as read
                $affected = executeQuery(
                    "UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                     WHERE id = ? AND user_id = ? AND is_read = FALSE",
                    [$notificationId, $user['id']]
                )->rowCount();
                
                if ($affected === 0) {
                    throw new Exception('Notification not found or already read');
                }
                
                $message = 'Notification marked as read';
            } else {
                // Mark all notifications as read
                $affected = executeQuery(
                    "UPDATE notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                     WHERE user_id = ? AND is_read = FALSE",
                    [$user['id']]
                )->rowCount();
                
                $message = "All notifications marked as read ({$affected} notifications)";
            }
            
            // Clear cache for this user
            DatabaseOptimizer::clearCache();
            
            PerformanceMonitor::mark('notifications_updated');
            
            $response = [
                'success' => true,
                'data' => [
                    'affected_rows' => $affected
                ],
                'message' => $message
            ];
            
        } elseif ($action === 'delete') {
            $notificationId = intval($input['notification_id'] ?? 0);
            
            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }
            
            $affected = executeQuery(
                "DELETE FROM notifications WHERE id = ? AND user_id = ?",
                [$notificationId, $user['id']]
            )->rowCount();
            
            if ($affected === 0) {
                throw new Exception('Notification not found');
            }
            
            // Clear cache for this user
            DatabaseOptimizer::clearCache();
            
            PerformanceMonitor::mark('notification_deleted');
            
            $response = [
                'success' => true,
                'message' => 'Notification deleted successfully'
            ];
            
        } else {
            throw new Exception('Invalid action parameter');
        }
        
    } else {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }
    
    // Add performance data in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $response['debug'] = [
            'performance' => PerformanceMonitor::getPagePerformance(),
            'database_stats' => DatabaseOptimizer::getCacheStats()
        ];
    }
    
    // Add ETag for caching
    $etag = md5(json_encode($response));
    header("ETag: \"{$etag}\"");
    
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === "\"{$etag}\"") {
        http_response_code(304);
        exit;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    
    $errorResponse = [
        'success' => false,
        'error' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode() ?: 400
        ]
    ];
    
    // Add debug info in development
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $errorResponse['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
} finally {
    // Log slow API calls
    $performance = PerformanceMonitor::end();
    if ($performance['total_time'] > 1.0) { // Log if > 1 second
        error_log("Slow API call: /api/notifications.php - {$performance['total_time']}s");
    }
}
?>
