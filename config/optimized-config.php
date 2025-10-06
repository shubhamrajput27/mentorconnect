<?php
/**
 * MentorConnect Configuration File
 * Main configuration for the mentorship platform
 */

// Prevent direct access
if (!defined('MENTORCONNECT_INIT')) {
    define('MENTORCONNECT_INIT', true);
}

// Application Configuration (define first)
define('APP_NAME', 'MentorConnect');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, staging, production

// Error reporting (disable in production)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mentorconnect');
define('DB_USER', 'root');
define('DB_PASS', '');  // WAMP default password is empty
define('DB_CHARSET', 'utf8mb4');

// URL Configuration - Auto-detect base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('BASE_URL', $protocol . $host . $path);

// Security Configuration
define('SECRET_KEY', 'your-secret-key-change-this-in-production');
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_ME_LIFETIME', 2592000); // 30 days
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('DEBUG_MODE', APP_ENV === 'development');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Email Configuration (for future use)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@mentorconnect.local');
define('FROM_NAME', APP_NAME);

// Performance Settings
define('ENABLE_CACHE', true);
define('CACHE_LIFETIME', 3600); // 1 hour
define('ENABLE_COMPRESSION', true);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Time settings
date_default_timezone_set('UTC');

// Include required files
require_once __DIR__ . '/autoloader.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Store in global variable for easy access
    $GLOBALS['pdo'] = $pdo;
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    
    if (APP_ENV === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Sorry, we're experiencing technical difficulties. Please try again later.");
    }
}

// Helper function to get database connection
function getDB() {
    return $GLOBALS['pdo'];
}
