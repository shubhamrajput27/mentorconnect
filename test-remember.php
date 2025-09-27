<?php
require_once 'config/config.php';

// Debug information
echo "<h2>Remember Me Functionality Test</h2>";

echo "<h3>Current Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID in Session: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "User Role in Session: " . ($_SESSION['user_role'] ?? 'Not set') . "<br>";
echo "Is Logged In: " . (isLoggedIn() ? 'Yes' : 'No') . "<br>";
echo "Auto Login: " . ($_SESSION['auto_login'] ?? 'No') . "<br>";

echo "<h3>Cookie Information:</h3>";
echo "Remember Token Cookie: " . ($_COOKIE['remember_token'] ?? 'Not set') . "<br>";

if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $hashedToken = hash('sha256', $token);
    
    echo "Token Hash: " . $hashedToken . "<br>";
    
    // Check if token exists in database
    $user = fetchOne(
        "SELECT id, username, email, role, remember_token FROM users WHERE remember_token = ?",
        [$hashedToken]
    );
    
    echo "<h3>Database Token Check:</h3>";
    if ($user) {
        echo "Token found in database!<br>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
    } else {
        echo "Token NOT found in database<br>";
        
        // Check if any remember tokens exist
        $allTokens = fetchAll("SELECT id, username, remember_token FROM users WHERE remember_token IS NOT NULL");
        echo "All remember tokens in database:<br>";
        foreach ($allTokens as $tokenUser) {
            echo "- User ID: " . $tokenUser['id'] . ", Username: " . $tokenUser['username'] . ", Token: " . substr($tokenUser['remember_token'], 0, 20) . "...<br>";
        }
    }
}

echo "<h3>Actions:</h3>";
echo '<a href="auth/login.php">Go to Login</a><br>';
echo '<a href="dashboard/">Go to Dashboard</a><br>';
echo '<a href="auth/logout.php">Logout</a><br>';

// Clear test data
if (isset($_GET['clear'])) {
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    session_destroy();
    echo "<br><strong>Session and cookies cleared!</strong>";
    echo '<br><a href="test-remember.php">Refresh page</a>';
}

echo '<br><br><a href="test-remember.php?clear=1">Clear Session & Cookies</a>';
?>