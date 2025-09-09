<?php
/**
 * MentorConnect Main Configuration File
 * Optimized and consolidated configuration
 */

// Application Constants
define('APP_NAME', 'MentorConnect');
define('APP_VERSION', '2.0.0');
define('BASE_URL', 'http://localhost/mentorconnect');
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mentorconnect');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('SESSION_LIFETIME', 28800); // 8 hours
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes
define('PASSWORD_MIN_LENGTH', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Performance Configuration
define('ENABLE_QUERY_CACHE', true);
define('CACHE_LIFETIME', 300);
define('ENABLE_GZIP', true);
define('ENABLE_OPCACHE', true);

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

// Include optimized components
require_once __DIR__ . '/cache-optimizer.php';
require_once __DIR__ . '/performance-monitor.php';
require_once __DIR__ . '/security-validator.php';

// Initialize performance monitoring
$performanceMonitor = PerformanceMonitor::getInstance();
$performanceMonitor->startTimer('page_load');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die('Database connection failed: ' . $e->getMessage());
    } else {
        die('Database connection failed. Please try again later.');
    }
}

// Enhanced Database helper functions with caching and performance monitoring
function executeQuery($sql, $params = []) {
    global $pdo;
    
    // Start query timing
    $startTime = microtime(true);
    perf_start('db_query');
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log query performance
        $duration = microtime(true) - $startTime;
        perf_log_query($sql, $params, $duration);
        perf_end('db_query');
        
        return $stmt;
    } catch (PDOException $e) {
        perf_end('db_query');
        
        // Log database error
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Database Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
        throw $e;
    }
}

function fetchOne($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
    // Try cache first if cache key provided
    if ($cacheKey && ENABLE_QUERY_CACHE) {
        $cached = cache_get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    }
    
    $stmt = executeQuery($sql, $params);
    $result = $stmt->fetch();
    
    // Cache the result if cache key provided
    if ($cacheKey && $result && ENABLE_QUERY_CACHE) {
        cache_set($cacheKey, $result, $cacheTTL);
    }
    
    return $result;
}

function fetchAll($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
    // Try cache first if cache key provided
    if ($cacheKey && ENABLE_QUERY_CACHE) {
        $cached = cache_get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    }
    
    $stmt = executeQuery($sql, $params);
    $result = $stmt->fetchAll();
    
    // Cache the result if cache key provided
    if ($cacheKey && $result && ENABLE_QUERY_CACHE) {
        cache_set($cacheKey, $result, $cacheTTL);
    }
    
    return $result;
}

function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

// New optimized query functions
function fetchCached($sql, $params = [], $ttl = 300) {
    $cacheKey = 'query_' . md5($sql . serialize($params));
    return fetchAll($sql, $params, $cacheKey, $ttl);
}

function fetchOneCached($sql, $params = [], $ttl = 300) {
    $cacheKey = 'query_one_' . md5($sql . serialize($params));
    return fetchOne($sql, $params, $cacheKey, $ttl);
}

// Enhanced Utility functions with security validation
function sanitizeInput($data, $type = 'general', $maxLength = null) {
    if (is_array($data)) {
        return array_map(function($item) use ($type, $maxLength) {
            return sanitizeInput($item, $type, $maxLength);
        }, $data);
    }
    
    // Use enhanced security validator
    $validation = validate_input($data, $type, $maxLength);
    
    if (!$validation['valid'] && defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Input validation issues: " . implode(', ', $validation['issues']));
    }
    
    return $validation['cleaned'];
}

function validateEmail($email) {
    $validation = validate_input($email, 'email');
    return $validation['valid'];
}

function validatePassword($password) {
    $validation = validate_input($password, 'password');
    return $validation['valid'];
}

// New enhanced validation functions
function validateAndSanitize($input, $type = 'general', $required = false, $maxLength = null) {
    if ($required && empty($input)) {
        return ['valid' => false, 'error' => 'This field is required', 'value' => ''];
    }
    
    if (empty($input)) {
        return ['valid' => true, 'error' => null, 'value' => ''];
    }
    
    $validation = validate_input($input, $type, $maxLength);
    
    return [
        'valid' => $validation['valid'],
        'error' => $validation['valid'] ? null : implode(', ', $validation['issues']),
        'value' => $validation['cleaned']
    ];
}

function secureUpload($file, $allowedTypes = [], $maxSize = 5242880) { // 5MB default
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    // Validate filename
    $filenameValidation = validate_input($file['name'], 'filename');
    if (!$filenameValidation['valid']) {
        return ['success' => false, 'error' => 'Invalid filename'];
    }
    
    return [
        'success' => true,
        'filename' => $filenameValidation['cleaned'],
        'mime_type' => $mimeType,
        'size' => $file['size']
    ];
}

function generateCSRFToken($action = null) {
    return generate_csrf($action);
}

function validateCSRFToken($token, $action = null) {
    return validate_csrf($token, $action);
}

// Enhanced CSRF with action-specific tokens
function generateActionCSRF($action) {
    return generate_csrf($action);
}

function validateActionCSRF($token, $action) {
    return validate_csrf($token, $action);
}

// Rate limiting helpers
function checkRateLimit($identifier, $action = 'general', $maxAttempts = 10, $timeWindow = 300) {
    return check_rate_limit($identifier, $action, $maxAttempts, $timeWindow);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    if ($user === null) {
        $user = fetchOne(
            "SELECT u.*, up.theme, up.language FROM users u 
             LEFT JOIN user_preferences up ON u.id = up.user_id 
             WHERE u.id = ?",
            [$_SESSION['user_id']]
        );
    }
    return $user;
}

function logActivity($userId, $activityType, $description = '', $metadata = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    executeQuery(
        "INSERT INTO activities (user_id, activity_type, description, metadata, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?, ?)",
        [$userId, $activityType, $description, json_encode($metadata), $ipAddress, $userAgent]
    );
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
