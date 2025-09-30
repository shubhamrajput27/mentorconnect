<?php
/**
 * Smart Dashboard Router - MentorConnect
 * Automatically routes users to their appropriate dashboard based on role
 */

require_once '../config/optimized-config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = getCurrentUser();

// Route based on user role
switch ($user['role']) {
    case 'student':
        header('Location: student.php');
        exit;
    
    case 'mentor':
        header('Location: mentor.php');
        exit;
    
    case 'admin':
        // Future admin dashboard
        header('Location: admin.php');
        exit;
    
    default:
        // Fallback - redirect to login
        header('Location: ../auth/login.php?error=invalid_role');
        exit;
}
?>
