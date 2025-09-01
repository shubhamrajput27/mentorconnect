<?php
/**
 * MentorConnect Optimized Configuration
 * Consolidated performance, security, and application settings
 */

// Application Constants
define('APP_NAME', 'MentorConnect');
define('APP_VERSION', '2.0.0');
define('BASE_URL', 'http://localhost/mentorconnect');
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Security Configuration
define('SESSION_LIFETIME', 28800); // 8 hours
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes
define('PASSWORD_MIN_LENGTH', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600);

// Performance Configuration
define('ENABLE_QUERY_CACHE', true);
define('CACHE_LIFETIME', 300);
define('ENABLE_GZIP', true);
define('ENABLE_OPCACHE', true);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mentorconnect');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Email Configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('FROM_EMAIL', 'noreply@mentorconnect.com');
define('FROM_NAME', 'MentorConnect');

// Set timezone
date_default_timezone_set('UTC');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}

// Performance optimizations
if (ENABLE_OPCACHE && function_exists('opcache_get_status')) {
    $opcacheStatus = opcache_get_status();
    if (!$opcacheStatus['opcache_enabled']) {
        ini_set('opcache.enable', 1);
    }
}

// Session configuration - optimized for performance and security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include core components
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';

// Auto-include optimization components only if not disabled
if (!defined('DISABLE_OPTIMIZATIONS')) {
    if (file_exists(__DIR__ . '/cache-manager.php')) {
        require_once __DIR__ . '/cache-manager.php';
    }
    if (file_exists(__DIR__ . '/performance-monitor.php')) {
        require_once __DIR__ . '/performance-monitor.php';
    }
}

/**
 * Global helper functions
 */
function getBaseUrl() {
    return BASE_URL;
}

function isDebugMode() {
    return DEBUG_MODE;
}

function getEnvironment() {
    return ENVIRONMENT;
}

function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    
    error_log($logMessage);
}

// Set security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (!DEBUG_MODE) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
?>
