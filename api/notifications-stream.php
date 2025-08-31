<?php
// Real-time Notification Stream using Server-Sent Events
require_once '../config/config.php';
require_once '../config/rate-limiter.php';

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

// Rate limiting
RateLimiter::handleRateLimit($_SERVER);

// Ensure user is authenticated
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Function to send SSE message
function sendSSE($data, $event = 'message', $id = null) {
    if ($id !== null) {
        echo "id: $id\n";
    }
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

// Send initial connection confirmation
sendSSE([
    'type' => 'connected',
    'timestamp' => time(),
    'user_id' => $user['id']
], 'connection');

$lastCheck = time();
$heartbeatInterval = 30; // Send heartbeat every 30 seconds
$notificationCheckInterval = 5; // Check for new notifications every 5 seconds

while (true) {
    $currentTime = time();
    
    // Check for connection timeout
    if (connection_aborted()) {
        break;
    }
    
    // Send heartbeat
    if ($currentTime - $lastCheck >= $heartbeatInterval) {
        sendSSE([
            'type' => 'heartbeat',
            'timestamp' => $currentTime
        ], 'heartbeat');
        $lastCheck = $currentTime;
    }
    
    // Check for new notifications
    $notifications = fetchAll(
        "SELECT * FROM notifications 
         WHERE user_id = ? AND created_at > ? 
         ORDER BY created_at DESC",
        [$user['id'], date('Y-m-d H:i:s', $currentTime - $notificationCheckInterval)]
    );
    
    foreach ($notifications as $notification) {
        sendSSE([
            'type' => 'notification',
            'id' => $notification['id'],
            'title' => $notification['title'],
            'content' => $notification['content'],
            'notification_type' => $notification['type'],
            'action_url' => $notification['action_url'],
            'created_at' => $notification['created_at']
        ], 'notification', $notification['id']);
    }
    
    // Check for new messages
    $newMessages = fetchAll(
        "SELECT m.*, u.first_name, u.last_name, u.profile_photo 
         FROM messages m 
         JOIN users u ON m.sender_id = u.id 
         WHERE m.receiver_id = ? AND m.created_at > ? 
         ORDER BY m.created_at DESC",
        [$user['id'], date('Y-m-d H:i:s', $currentTime - $notificationCheckInterval)]
    );
    
    foreach ($newMessages as $message) {
        sendSSE([
            'type' => 'message',
            'id' => $message['id'],
            'sender' => [
                'id' => $message['sender_id'],
                'name' => $message['first_name'] . ' ' . $message['last_name'],
                'photo' => $message['profile_photo']
            ],
            'subject' => $message['subject'],
            'message' => substr($message['message'], 0, 100) . '...',
            'created_at' => $message['created_at']
        ], 'message', $message['id']);
    }
    
    // Check for session updates
    $sessionUpdates = fetchAll(
        "SELECT s.*, u.first_name, u.last_name 
         FROM sessions s 
         JOIN users u ON (s.mentor_id = u.id OR s.student_id = u.id)
         WHERE (s.mentor_id = ? OR s.student_id = ?) 
         AND s.updated_at > ? 
         AND u.id != ?
         ORDER BY s.updated_at DESC",
        [$user['id'], $user['id'], date('Y-m-d H:i:s', $currentTime - $notificationCheckInterval), $user['id']]
    );
    
    foreach ($sessionUpdates as $session) {
        sendSSE([
            'type' => 'session_update',
            'id' => $session['id'],
            'title' => $session['title'],
            'status' => $session['status'],
            'other_user' => $session['first_name'] . ' ' . $session['last_name'],
            'scheduled_at' => $session['scheduled_at'],
            'updated_at' => $session['updated_at']
        ], 'session', $session['id']);
    }
    
    sleep(2); // Wait 2 seconds before next check
}
?>
