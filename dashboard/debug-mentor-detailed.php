<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Debug Mentor Dashboard</title></head><body>";
echo "<h1>Debug Mentor Dashboard</h1>";

try {
    echo "<h2>Step 1: Loading Config</h2>";
    require_once '../config/config.php';
    echo "✓ Config loaded successfully<br>";
    
    echo "<h2>Step 2: Checking Session</h2>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<h2>Step 3: Role Check</h2>";
    if (!isset($_SESSION['user_id'])) {
        echo "❌ No user_id in session<br>";
        exit;
    }
    
    if (!isset($_SESSION['user_role'])) {
        echo "❌ No user_role in session<br>";
        exit;
    }
    
    if ($_SESSION['user_role'] !== 'mentor') {
        echo "❌ User role is: " . $_SESSION['user_role'] . " (expected: mentor)<br>";
        exit;
    }
    
    echo "✓ Role check passed<br>";
    
    echo "<h2>Step 4: Getting Current User</h2>";
    $user = getCurrentUser();
    echo "User data: <pre>" . print_r($user, true) . "</pre>";
    
    echo "<h2>Step 5: Testing Database Queries</h2>";
    
    echo "<h3>5.1: Total Sessions Query</h3>";
    try {
        $totalSessions = fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$user['id']]);
        echo "✓ Total sessions: " . $totalSessions['count'] . "<br>";
    } catch (Exception $e) {
        echo "❌ Total sessions error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>5.2: Upcoming Sessions Query</h3>";
    try {
        $upcomingSessions = fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND scheduled_at > NOW() AND status = 'scheduled'", [$user['id']]);
        echo "✓ Upcoming sessions: " . $upcomingSessions['count'] . "<br>";
    } catch (Exception $e) {
        echo "❌ Upcoming sessions error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>5.3: Total Students Query</h3>";
    try {
        $totalStudents = fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM sessions WHERE mentor_id = ?", [$user['id']]);
        echo "✓ Total students: " . $totalStudents['count'] . "<br>";
    } catch (Exception $e) {
        echo "❌ Total students error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>5.4: Average Rating Query</h3>";
    try {
        $avgRating = fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']]);
        echo "✓ Average rating: " . ($avgRating['avg'] ?? 0) . "<br>";
    } catch (Exception $e) {
        echo "❌ Average rating error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>5.5: Recent Sessions Query</h3>";
    try {
        $recentSessions = fetchAll(
            "SELECT s.*, u.first_name, u.last_name, u.profile_photo 
             FROM sessions s 
             JOIN users u ON s.student_id = u.id 
             WHERE s.mentor_id = ? 
             ORDER BY s.scheduled_at DESC 
             LIMIT 5",
            [$user['id']]
        );
        echo "✓ Recent sessions count: " . count($recentSessions) . "<br>";
    } catch (Exception $e) {
        echo "❌ Recent sessions error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>5.6: Recent Messages Query</h3>";
    try {
        $recentMessages = fetchAll(
            "SELECT m.*, u.first_name, u.last_name, u.profile_photo 
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             WHERE m.receiver_id = ? 
             ORDER BY m.created_at DESC 
             LIMIT 5",
            [$user['id']]
        );
        echo "✓ Recent messages count: " . count($recentMessages) . "<br>";
    } catch (Exception $e) {
        echo "❌ Recent messages error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>5.7: Notifications Query</h3>";
    try {
        $notifications = fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = ? AND is_read = FALSE 
             ORDER BY created_at DESC 
             LIMIT 5",
            [$user['id']]
        );
        echo "✓ Notifications count: " . count($notifications) . "<br>";
    } catch (Exception $e) {
        echo "❌ Notifications error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Step 6: Testing CSS File</h2>";
    $cssPath = '../assets/css/style.css';
    if (file_exists($cssPath)) {
        echo "✓ CSS file exists<br>";
    } else {
        echo "❌ CSS file not found: $cssPath<br>";
    }
    
    echo "<h2>All Tests Complete!</h2>";
    echo "<p>If you see this message, the dashboard should work. Let's check what's different...</p>";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>