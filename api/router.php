<?php
/**
 * MentorConnect API Router - Consolidated API Handler
 * Handles routing and optimization for all API endpoints
 */

require_once '../config/optimized-config.php';
require_once '../config/security.php';
require_once '../config/functions.php';

// Start performance monitoring
$startTime = microtime(true);

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Rate limiting
$identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($identifier, 'api')) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

// CSRF protection for non-GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

// Route handling
$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

// Extract endpoint from path
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);
$endpoint = str_replace('.php', '', $endpoint);

try {
    switch ($endpoint) {
        case 'notifications':
            handleNotifications();
            break;
        case 'messages':
            handleMessages();
            break;
        case 'search':
            handleSearch();
            break;
        case 'user-preferences':
            handleUserPreferences();
            break;
        case 'mentor-matching':
            handleMentorMatching();
            break;
        case 'analytics':
            handleAnalytics();
            break;
        case 'files':
            handleFiles();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    error_log("API Error [{$endpoint}]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} finally {
    // Log performance metrics
    $executionTime = microtime(true) - $startTime;
    if (DEBUG_MODE && $executionTime > 1.0) {
        error_log("Slow API request [{$endpoint}]: {$executionTime}s");
    }
}

function handleNotifications() {
    requireLogin();
    $user = getCurrentUser();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'count':
                $count = fetchOne(
                    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
                    [$user['id']]
                )['count'] ?? 0;
                
                echo json_encode([
                    'success' => true,
                    'unread_count' => (int)$count
                ]);
                break;
                
            case 'list':
                $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
                $unreadOnly = ($_GET['unread_only'] ?? 'false') === 'true';
                
                $whereClause = "user_id = ?";
                $params = [$user['id']];
                
                if ($unreadOnly) {
                    $whereClause .= " AND is_read = 0";
                }
                
                $notifications = fetchAll(
                    "SELECT id, type, title, content, action_url, is_read, created_at 
                     FROM notifications 
                     WHERE {$whereClause} 
                     ORDER BY created_at DESC 
                     LIMIT ?",
                    [...$params, $limit]
                );
                
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications
                ]);
                break;
        }
    } elseif ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'mark_read') {
            $notificationId = intval($_POST['id'] ?? 0);
            
            executeQuery(
                "UPDATE notifications SET is_read = 1, read_at = NOW() 
                 WHERE id = ? AND user_id = ?",
                [$notificationId, $user['id']]
            );
            
            echo json_encode(['success' => true]);
        }
    }
}

function handleMessages() {
    requireLogin();
    $user = getCurrentUser();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $conversations = fetchAll(
            "SELECT DISTINCT 
                CASE 
                    WHEN sender_id = ? THEN receiver_id 
                    ELSE sender_id 
                END as other_user_id,
                (SELECT CONCAT(first_name, ' ', last_name) 
                 FROM users 
                 WHERE id = other_user_id) as other_user_name,
                (SELECT content 
                 FROM messages m2 
                 WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                    OR (m2.sender_id = other_user_id AND m2.receiver_id = ?) 
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                (SELECT created_at 
                 FROM messages m2 
                 WHERE (m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                    OR (m2.sender_id = other_user_id AND m2.receiver_id = ?) 
                 ORDER BY m2.created_at DESC LIMIT 1) as last_message_time
             FROM messages 
             WHERE sender_id = ? OR receiver_id = ? 
             ORDER BY last_message_time DESC",
            [$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]
        );
        
        echo json_encode([
            'success' => true,
            'conversations' => $conversations
        ]);
    }
}

function handleSearch() {
    requireLogin();
    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'results' => []]);
        return;
    }
    
    $searchTerm = '%' . $query . '%';
    
    // Search mentors
    $mentors = fetchAll(
        "SELECT u.id, u.first_name, u.last_name, u.email, mp.expertise_areas, mp.bio
         FROM users u 
         JOIN mentor_profiles mp ON u.id = mp.user_id 
         WHERE u.user_type = 'mentor' 
         AND u.is_active = 1 
         AND (u.first_name LIKE ? OR u.last_name LIKE ? OR mp.expertise_areas LIKE ?)
         LIMIT 10",
        [$searchTerm, $searchTerm, $searchTerm]
    );
    
    echo json_encode([
        'success' => true,
        'results' => [
            'mentors' => $mentors
        ]
    ]);
}

function handleUserPreferences() {
    requireLogin();
    $user = getCurrentUser();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $preferences = fetchOne(
            "SELECT theme, language, email_notifications, push_notifications 
             FROM user_preferences 
             WHERE user_id = ?",
            [$user['id']]
        );
        
        if (!$preferences) {
            $preferences = [
                'theme' => 'light',
                'language' => 'en',
                'email_notifications' => 1,
                'push_notifications' => 1
            ];
        }
        
        echo json_encode([
            'success' => true,
            'preferences' => $preferences
        ]);
    } elseif ($method === 'POST') {
        $theme = $_POST['theme'] ?? 'light';
        $language = $_POST['language'] ?? 'en';
        $emailNotifications = intval($_POST['email_notifications'] ?? 1);
        $pushNotifications = intval($_POST['push_notifications'] ?? 1);
        
        executeQuery(
            "INSERT INTO user_preferences (user_id, theme, language, email_notifications, push_notifications) 
             VALUES (?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             theme = VALUES(theme), 
             language = VALUES(language), 
             email_notifications = VALUES(email_notifications), 
             push_notifications = VALUES(push_notifications)",
            [$user['id'], $theme, $language, $emailNotifications, $pushNotifications]
        );
        
        echo json_encode(['success' => true]);
    }
}

function handleMentorMatching() {
    requireLogin();
    requireRole('student');
    
    $user = getCurrentUser();
    $expertise = $_GET['expertise'] ?? '';
    $limit = min(20, max(1, intval($_GET['limit'] ?? 10)));
    
    $mentors = fetchAll(
        "SELECT u.id, u.first_name, u.last_name, mp.expertise_areas, mp.bio, mp.hourly_rate, mp.rating
         FROM users u 
         JOIN mentor_profiles mp ON u.id = mp.user_id 
         WHERE u.user_type = 'mentor' 
         AND u.is_active = 1 
         AND mp.is_available = 1
         " . ($expertise ? "AND mp.expertise_areas LIKE ?" : "") . "
         ORDER BY mp.rating DESC, mp.created_at DESC 
         LIMIT ?",
        $expertise ? ["%$expertise%", $limit] : [$limit]
    );
    
    echo json_encode([
        'success' => true,
        'mentors' => $mentors
    ]);
}

function handleAnalytics() {
    requireLogin();
    requireRole('mentor');
    
    $user = getCurrentUser();
    
    $stats = [
        'total_sessions' => fetchOne(
            "SELECT COUNT(*) as count FROM mentor_sessions WHERE mentor_id = ?",
            [$user['id']]
        )['count'] ?? 0,
        
        'completed_sessions' => fetchOne(
            "SELECT COUNT(*) as count FROM mentor_sessions WHERE mentor_id = ? AND status = 'completed'",
            [$user['id']]
        )['count'] ?? 0,
        
        'average_rating' => fetchOne(
            "SELECT AVG(rating) as avg_rating FROM session_reviews sr 
             JOIN mentor_sessions ms ON sr.session_id = ms.id 
             WHERE ms.mentor_id = ?",
            [$user['id']]
        )['avg_rating'] ?? 0,
        
        'total_earnings' => fetchOne(
            "SELECT SUM(amount) as total FROM payments p 
             JOIN mentor_sessions ms ON p.session_id = ms.id 
             WHERE ms.mentor_id = ? AND p.status = 'completed'",
            [$user['id']]
        )['total'] ?? 0
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

function handleFiles() {
    requireLogin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    if (!isset($_FILES['file'])) {
        echo json_encode(['error' => 'No file uploaded']);
        return;
    }
    
    try {
        $fileInfo = uploadFile($_FILES['file']);
        
        echo json_encode([
            'success' => true,
            'file' => $fileInfo
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
