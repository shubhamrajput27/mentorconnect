<?php
/**
 * Debug Mentor Dashboard - Simplified version to identify issues
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';

echo "<h1>Debug Dashboard</h1>";

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<p style='color: red;'>Not logged in - redirecting to login</p>";
    header('Location: ../auth/login.php');
    exit();
}

echo "<p style='color: green;'>✓ User is logged in</p>";

// Check role
if ($_SESSION['user_role'] !== 'mentor') {
    echo "<p style='color: red;'>Not a mentor - role is: " . $_SESSION['user_role'] . "</p>";
    exit();
}

echo "<p style='color: green;'>✓ User is a mentor</p>";

// Get current user
try {
    $user = getCurrentUser();
    if ($user) {
        echo "<p style='color: green;'>✓ User data loaded: " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to load user data</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading user: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test database queries one by one
echo "<h2>Testing Database Queries:</h2>";

// Test sessions table
try {
    $sessionCount = fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$user['id']]);
    echo "<p style='color: green;'>✓ Sessions table accessible - count: " . $sessionCount['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Sessions table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test reviews table
try {
    $reviewAvg = fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']]);
    echo "<p style='color: green;'>✓ Reviews table accessible - avg rating: " . ($reviewAvg['avg'] ?? 'No reviews') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Reviews table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test messages table
try {
    $messageCount = fetchOne("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ?", [$user['id']]);
    echo "<p style='color: green;'>✓ Messages table accessible - count: " . $messageCount['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Messages table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test notifications table
try {
    $notificationCount = fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?", [$user['id']]);
    echo "<p style='color: green;'>✓ Notifications table accessible - count: " . $notificationCount['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Notifications table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Available Tables:</h2>";
try {
    $tables = fetchAll("SHOW TABLES");
    echo "<ul>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li>" . htmlspecialchars($tableName) . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error listing tables: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <hr>
    <h2>Simple Dashboard Test</h2>
    <p>If you see this, PHP is working and the basic authentication is functional.</p>
    
    <div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3>Welcome, <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>!</h3>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role'] ?? 'Unknown'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'Unknown'); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username'] ?? 'Unknown'); ?></p>
    </div>
    
    <p><a href="../auth/logout.php">Logout</a></p>
    <p><a href="../test-login.php">Back to Test Login</a></p>
    <p><a href="mentor.php">Try Original Dashboard</a></p>
</body>
</html>