<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    // Log activity
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    
    // Clear session
    session_destroy();
    
    // Clear remember me cookie if exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// Redirect to login page
header('Location: ' . BASE_URL . '/auth/login.php');
exit();
?>
