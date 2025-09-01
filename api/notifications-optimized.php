<?php
/**
 * Optimized Notifications API with Advanced Caching and Performance
 */

require_once '../config/config.php';
require_once '../config/database-optimizer.php';
require_once '../config/advanced-cache.php';
require_once '../config/performance-monitor.php';
require_once 'middleware/optimizer.php';

// Start performance monitoring
$start_time = microtime(true);
PerformanceMonitor::start();

// Check rate limiting
RequestThrottler::checkRateLimit();

// Handle prefetch requests
if (ApiOptimizer::handlePrefetch()) {
    // For prefetch requests, return minimal data
    echo json_encode(['prefetch' => true]);
    exit;
}

requireLogin();

$user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    PerformanceMonitor::mark('auth_complete');
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $offset = max(0, intval($_GET['offset'] ?? 0));
            
            PerformanceMonitor::mark('params_validated');
            
            // Use advanced caching with multiple cache keys
            $cacheKey = "notifications_v2_{$user['id']}_{$limit}_{$offset}_" . ($unreadOnly ? '1' : '0');
            $notifications = AdvancedCacheManager::get($cacheKey);
            
            if ($notifications === null) {
                // Cache miss - fetch from database
                $notifications = DatabaseOptimizer::getNotificationsOptimized($user['id'], $limit, $unreadOnly, $offset);
                
                // Cache with shorter TTL for real-time data
                AdvancedCacheManager::set($cacheKey, $notifications, 30); // 30 seconds
            }
            
            PerformanceMonitor::mark('notifications_fetched');
            
            // Get unread count with separate caching
            $unreadCountKey = "unread_count_v2_{$user['id']}";
            $unreadCount = AdvancedCacheManager::get($unreadCountKey);
            
            if ($unreadCount === null) {
                $unreadCount = fetchOneOptimized(
                    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE",
                    [$user['id']],
                    $unreadCountKey,
                    15 // Cache for 15 seconds
                )['count'] ?? 0;
                
                AdvancedCacheManager::set($unreadCountKey, $unreadCount, 15);
            }
            
            PerformanceMonitor::mark('unread_count_fetched');
            
            // Optimize notification data
            $optimizedNotifications = array_map(function($notification) {
                return [
                    'id' => (int)$notification['id'],
                    'type' => $notification['type'],
                    'title' => $notification['title'],
                    'content' => $notification['content'] ?? '',
                    'action_url' => $notification['action_url'] ?? '',
                    'is_read' => (bool)$notification['is_read'],
                    'created_at' => $notification['created_at'],
                    'priority' => $notification['priority'] ?? 'normal'
                ];
            }, $notifications);
            
            $response = [
                'success' => true,
                'notifications' => $optimizedNotifications,
                'unread_count' => (int)$unreadCount,
                'total_count' => count($optimizedNotifications),
                'has_more' => count($optimizedNotifications) === $limit,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'next_offset' => $offset + $limit
                ]
            ];
            
        } elseif ($action === 'count') {
            // Highly cached count endpoint
            $cacheKey = "notification_count_v2_{$user['id']}";
            $unreadCount = AdvancedCacheManager::get($cacheKey);
            
            if ($unreadCount === null) {
                $unreadCount = fetchOneOptimized(
                    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE",
                    [$user['id']],
                    $cacheKey,
                    10 // Cache for 10 seconds for real-time feel
                )['count'] ?? 0;
                
                AdvancedCacheManager::set($cacheKey, $unreadCount, 10);
            }
            
            $response = [
                'success' => true,
                'unread_count' => (int)$unreadCount
            ];
            
        } elseif ($action === 'recent') {
            // Get recent notifications with aggressive caching
            $cacheKey = "recent_notifications_v2_{$user['id']}";
            $recent = AdvancedCacheManager::get($cacheKey);
            
            if ($recent === null) {
                $recent = fetchOptimized(
                    "SELECT id, type, title, created_at, is_read 
                     FROM notifications 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC 
                     LIMIT 5",
                    [$user['id']],
                    $cacheKey,
                    60 // Cache for 1 minute
                );
                
                AdvancedCacheManager::set($cacheKey, $recent, 60);
            }
            
            $response = [
                'success' => true,
                'recent_notifications' => $recent
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
            
            // Clear all related caches
            $cacheKeys = [
                "notifications_v2_{$user['id']}_*",
                "unread_count_v2_{$user['id']}",
                "notification_count_v2_{$user['id']}",
                "recent_notifications_v2_{$user['id']}"
            ];
            
            foreach ($cacheKeys as $pattern) {
                if (strpos($pattern, '*') !== false) {
                    // Clear pattern-based cache keys
                    $baseKey = str_replace('*', '', $pattern);
                    for ($i = 0; $i < 5; $i++) { // Clear common pagination
                        for ($j = 0; $j < 2; $j++) { // unread_only true/false
                            $fullKey = $baseKey . ($i * 20) . "_{$j}";
                            AdvancedCacheManager::delete($fullKey);
                        }
                    }
                } else {
                    AdvancedCacheManager::delete($pattern);
                }
            }
            
            PerformanceMonitor::mark('notifications_updated');
            
            $response = [
                'success' => true,
                'affected_rows' => $affected,
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
            
            // Clear related caches
            $cacheKeys = [
                "unread_count_v2_{$user['id']}",
                "notification_count_v2_{$user['id']}",
                "recent_notifications_v2_{$user['id']}"
            ];
            
            foreach ($cacheKeys as $key) {
                AdvancedCacheManager::delete($key);
            }
            
            PerformanceMonitor::mark('notification_deleted');
            
            $response = [
                'success' => true,
                'message' => 'Notification deleted successfully'
            ];
            
        } elseif ($action === 'mark_all_read') {
            // Bulk operation with optimized query
            $affected = executeQuery(
                "UPDATE notifications 
                 SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                 WHERE user_id = ? AND is_read = FALSE",
                [$user['id']]
            )->rowCount();
            
            // Clear all caches for this user
            $patterns = [
                "notifications_v2_{$user['id']}_",
                "unread_count_v2_{$user['id']}",
                "notification_count_v2_{$user['id']}",
                "recent_notifications_v2_{$user['id']}"
            ];
            
            foreach ($patterns as $pattern) {
                AdvancedCacheManager::delete($pattern);
            }
            
            $response = [
                'success' => true,
                'affected_rows' => $affected,
                'message' => "All notifications marked as read ({$affected} notifications)"
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
            'database_stats' => DatabaseOptimizer::getCacheStats(),
            'cache_stats' => AdvancedCacheManager::getStats(),
            'execution_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
        ];
    }
    
    // Send optimized response
    ApiOptimizer::sendJsonResponse($response, 200, [
        'include_meta' => defined('DEBUG_MODE') && DEBUG_MODE
    ]);
    
} catch (Exception $e) {
    // Send optimized error response
    ApiOptimizer::sendErrorResponse($e->getMessage(), $e->getCode() ?: 400, [
        'action' => $action ?? 'unknown',
        'method' => $method
    ]);
} finally {
    // Log performance metrics
    $performance = PerformanceMonitor::end();
    if ($performance && $performance['total_time'] > 0.5) { // Log if > 500ms
        error_log("Slow Notifications API: {$performance['total_time']}s - " . ($_SERVER['REQUEST_URI'] ?? ''));
    }
}
?>
