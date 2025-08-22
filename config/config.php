<?php
// Application configuration
define('APP_NAME', 'MentorConnect');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/mentorconnect');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Enhanced Security settings
define('SESSION_LIFETIME', 3600 * 8); // 8 hours (reduced from 24)
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes (reduced from 1 hour)
define('PASSWORD_MIN_LENGTH', 12); // Increased from 8
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Email settings (configure as needed)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@mentorconnect.com');
define('FROM_NAME', 'MentorConnect');

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enhanced Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.entropy_length', 32);
ini_set('session.hash_function', 'sha256');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Utility functions
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitizeForDatabase($data) {
    return trim(strip_tags($data));
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

function checkRateLimit($identifier, $action = 'general') {
    $key = $action . '_' . $identifier;
    $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($key);
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data && time() - $data['timestamp'] < RATE_LIMIT_WINDOW) {
            if ($data['count'] >= RATE_LIMIT_REQUESTS) {
                return false;
            }
            $data['count']++;
        } else {
            $data = ['count' => 1, 'timestamp' => time()];
        }
    } else {
        $data = ['count' => 1, 'timestamp' => time()];
    }
    
    file_put_contents($cacheFile, json_encode($data));
    return true;
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

function createNotification($userId, $type, $title, $content = '', $actionUrl = '') {
    executeQuery(
        "INSERT INTO notifications (user_id, type, title, content, action_url) 
         VALUES (?, ?, ?, ?, ?)",
        [$userId, $type, $title, $content, $actionUrl]
    );
}

function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    // Enhanced security checks
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize || $file['size'] <= 0) {
        throw new Exception('Invalid file size');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('File type not allowed');
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
        throw new Exception('File type validation failed');
    }
    
    // Sanitize filename
    $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create uploads directory');
        }
    }
    
    // Generate secure filename
    $fileName = hash('sha256', uniqid() . time() . $originalName) . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Set secure permissions
    chmod($filePath, 0644);
    
    return [
        'original_name' => $originalName,
        'stored_name' => $fileName,
        'file_path' => 'uploads/' . $fileName,
        'file_size' => $file['size'],
        'mime_type' => $mimeType
    ];
}
?>
