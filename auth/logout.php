<?php
require_once '../config/optimized-config.php';

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    
    // Log activity
    logActivity($userId, 'logout', 'User logged out');
    
    // Clear remember token from database if cookie exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $hashedToken = hash('sha256', $token);
        
        // Clear the remember token from database
        executeQuery(
            "UPDATE users SET remember_token = NULL WHERE id = ? AND remember_token = ?",
            [$userId, $hashedToken]
        );
        
        // Clear the cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Clear user session from database
    executeQuery(
        "DELETE FROM user_sessions WHERE id = ?",
        [session_id()]
    );
    
    // Clear session
    session_destroy();
}

// Redirect to login page
header('Location: ' . BASE_URL . '/auth/login.php');
exit();
?>


