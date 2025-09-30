<?php
// Main entry point for Vercel serverless function
// This handles all requests and routes them appropriately

// Set proper headers to prevent file download
header('Content-Type: text/html; charset=UTF-8');

// Use Vercel configuration if available
if (file_exists('../config/vercel-config.php')) {
    require_once '../config/vercel-config.php';
} else if (file_exists('../config/optimized-config.php')) {
    require_once '../config/optimized-config.php';
} else {
    // Fallback configuration
    define('APP_NAME', 'MentorConnect');
    define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST']);
}

// Get the request URI
$request = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

// Handle different routes
switch ($path) {
    case '':
    case 'index.php':
        include '../index.php';
        break;
    
    case 'login.php':
        include '../login.php';
        break;
    
    case 'register.php':
        include '../register.php';
        break;
    
    default:
        // Check if file exists in parent directory
        $file = '../' . $path;
        if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            include $file;
        } else {
            // Default to homepage
            include '../index.php';
        }
        break;
}
?>