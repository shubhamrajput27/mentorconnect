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

// Database helper functions
function executeQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

// Utility functions
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && 
           !preg_match('/[<>"\']/', $email) &&
           strlen($email) <= 254;
}

function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password) &&
           preg_match('/[^A-Za-z0-9]/', $password);
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
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
