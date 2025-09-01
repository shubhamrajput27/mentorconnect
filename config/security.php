<?php
/**
 * Optimized Security Functions for MentorConnect
 */

/**
 * Enhanced CSRF Token Management
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
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

/**
 * Rate Limiting
 */
function checkRateLimit($identifier, $action = 'general') {
    $cacheKey = "rate_limit_{$action}_{$identifier}";
    $requests = getFromCache($cacheKey, 0);
    
    if ($requests >= RATE_LIMIT_REQUESTS) {
        logSecurityEvent($identifier, 'rate_limit_exceeded', ['action' => $action, 'requests' => $requests]);
        return false;
    }
    
    setInCache($cacheKey, $requests + 1, RATE_LIMIT_WINDOW);
    return true;
}

/**
 * Input Sanitization
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

/**
 * Authentication Functions
 */
function requireLogin() {
    if (!isLoggedIn()) {
        if (isAjaxRequest()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit();
        } else {
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit();
        }
    }
}

function requireRole($role) {
    requireLogin();
    
    $user = getCurrentUser();
    if ($user['user_type'] !== $role) {
        http_response_code(403);
        if (isAjaxRequest()) {
            echo json_encode(['error' => 'Insufficient permissions']);
        } else {
            header('Location: ' . BASE_URL . '/auth/login.php');
        }
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    
    if ($user === null) {
        $user = fetchOne(
            "SELECT id, username, email, first_name, last_name, user_type, is_active FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    return $user;
}

/**
 * Security Logging
 */
function logSecurityEvent($identifier, $event, $details = []) {
    $logData = [
        'identifier' => $identifier,
        'event' => $event,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s'),
        'details' => $details
    ];
    
    error_log('SECURITY: ' . json_encode($logData));
    
    // Store in database if available
    try {
        executeQuery(
            "INSERT INTO security_logs (event_type, ip_address, user_agent, details, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$event, $logData['ip_address'], $logData['user_agent'], json_encode($details)]
        );
    } catch (Exception $e) {
        // Silent fail for logging
    }
}

/**
 * Activity Logging
 */
function logActivity($userId, $activityType, $description = '', $metadata = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        executeQuery(
            "INSERT INTO activities (user_id, activity_type, description, metadata, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $activityType, $description, json_encode($metadata), $ipAddress, $userAgent]
        );
    } catch (Exception $e) {
        logError('Failed to log activity', ['error' => $e->getMessage(), 'user_id' => $userId]);
    }
}

/**
 * Utility Functions
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function respondJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function redirectTo($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Cache Helper Functions
 */
function getFromCache($key, $default = null) {
    if (class_exists('CacheManager')) {
        return CacheManager::get($key, $default);
    }
    
    // Fallback to session cache
    return $_SESSION['cache'][$key] ?? $default;
}

function setInCache($key, $value, $ttl = 300) {
    if (class_exists('CacheManager')) {
        return CacheManager::set($key, $value, $ttl);
    }
    
    // Fallback to session cache
    $_SESSION['cache'][$key] = $value;
    return true;
}

/**
 * File Upload Security
 */
function validateUpload($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Invalid upload parameters'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true];
}

/**
 * Notification Functions
 */
function createNotification($userId, $type, $title, $content = '', $actionUrl = '') {
    try {
        executeQuery(
            "INSERT INTO notifications (user_id, type, title, content, action_url) VALUES (?, ?, ?, ?, ?)",
            [$userId, $type, $title, $content, $actionUrl]
        );
        return true;
    } catch (Exception $e) {
        logError('Failed to create notification', ['error' => $e->getMessage(), 'user_id' => $userId]);
        return false;
    }
}
?>
