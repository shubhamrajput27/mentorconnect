<?php
/**
 * MentorConnect Core Functions
 * Essential functions for the mentorship platform
 */

// Prevent direct access
if (!defined('MENTORCONNECT_INIT')) {
    exit('Direct access not allowed');
}

/**
 * Authentication Functions
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user information
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

// Require user to be logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

// Require specific role
function requireRole($role) {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
    
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        header('Location: ../auth/login.php?error=access_denied');
        exit();
    }
}

// Login user
function loginUser($userId, $remember = false) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
    
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + REMEMBER_ME_LIFETIME;
        
        // Store remember token in database
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $userId]);
            
            // Set cookie
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
        } catch (PDOException $e) {
            error_log("Error setting remember token: " . $e->getMessage());
        }
    }
    
    return true;
}

// Logout user
function logoutUser() {
    // Clear remember token from database
    if (isLoggedIn()) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Error clearing remember token: " . $e->getMessage());
        }
    }
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Clear remember cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// Check remember me token
function checkRememberToken() {
    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        try {
            $pdo = getDB();
            if (!$pdo) {
                return false;
            }
            $stmt = $pdo->prepare("SELECT id FROM users WHERE remember_token = ? AND status = 'active'");
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch();
            
            if ($user) {
                loginUser($user['id']);
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error checking remember token: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error checking remember token: " . $e->getMessage());
        }
    }
    return false;
}

/**
 * Security Functions
 */

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Validate CSRF token (alias for compatibility)
function validateCSRFToken($token) {
    return verifyCSRFToken($token);
}

// Rate limiting function
function checkRateLimit($clientIP, $action = 'general', $maxAttempts = 10, $timeWindow = 300) {
    $key = 'rate_limit_' . $action . '_' . md5($clientIP);
    $file = sys_get_temp_dir() . '/' . $key;
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && isset($data['attempts'], $data['timestamp'])) {
            // Check if time window has passed
            if (time() - $data['timestamp'] < $timeWindow) {
                if ($data['attempts'] >= $maxAttempts) {
                    return false; // Rate limit exceeded
                }
                // Increment attempts
                $data['attempts']++;
                file_put_contents($file, json_encode($data));
            } else {
                // Reset counter after time window
                file_put_contents($file, json_encode(['attempts' => 1, 'timestamp' => time()]));
            }
        }
    } else {
        // First attempt
        file_put_contents($file, json_encode(['attempts' => 1, 'timestamp' => time()]));
    }
    
    return true;
}

// Validate email (enhanced)
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Log activity function
function logActivity($userId, $action, $description = '', $metadata = []) {
    try {
        $pdo = getDB();
        
        // Check if activities table exists
        $tableCheck = fetchOne("SHOW TABLES LIKE 'activities'");
        if (!$tableCheck) {
            // Create activities table if it doesn't exist
            $createTable = "
                CREATE TABLE activities (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    activity_type VARCHAR(100) NOT NULL,
                    description TEXT,
                    metadata JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_activity_type (activity_type),
                    INDEX idx_created_at (created_at)
                )
            ";
            executeQuery($createTable);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO activities (user_id, activity_type, description, metadata, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            json_encode($metadata),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength
function isStrongPassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

/**
 * Utility Functions
 */

// Redirect with message
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit();
}

// Get and clear flash message
function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? '';
    $type = $_SESSION['flash_type'] ?? 'info';
    
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    
    return ['message' => $message, 'type' => $type];
}

// Format date for display
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return formatDate($datetime, 'M j, Y');
}

// Alias for consistency with other parts of the application
function formatTimeAgo($datetime) {
    return timeAgo($datetime);
}

// Generate random string
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

// Check if string contains only alphanumeric characters
function isAlphanumeric($string) {
    return ctype_alnum($string);
}

/**
 * Database Helper Functions
 */

// Execute query and return result
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            error_log("Database connection not available in executeQuery");
            return false;
        }
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

// Fetch a single row
function fetchOne($sql, $params = []) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            error_log("Database connection not available in fetchOne");
            return null;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Database fetch error: " . $e->getMessage());
        return null;
    } catch (Exception $e) {
        error_log("Database fetch error: " . $e->getMessage());
        return null;
    }
}

// Fetch all rows
function fetchAll($sql, $params = []) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            error_log("Database connection not available in fetchAll");
            return [];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database fetch error: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("Database fetch error: " . $e->getMessage());
        return [];
    }
}

// Get user by ID
function getUserById($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user by ID: " . $e->getMessage());
        return null;
    }
}

// Get user by email
function getUserByEmail($email) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user by email: " . $e->getMessage());
        return null;
    }
}

// Get user by username
function getUserByUsername($username) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user by username: " . $e->getMessage());
        return null;
    }
}

/**
 * Template Functions
 */

// Include header
function includeHeader($title = '', $additionalCSS = []) {
    $pageTitle = !empty($title) ? $title . ' - ' . APP_NAME : APP_NAME;
    include __DIR__ . '/../templates/header.php';
}

// Include footer
function includeFooter($additionalJS = []) {
    include __DIR__ . '/../templates/footer.php';
}

/**
 * Notification Functions
 */

// Create a notification for a user
function createNotification($userId, $type, $title, $message, $relatedId = null) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            error_log("Database connection not available in createNotification");
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([$userId, $type, $title, $message, $relatedId]);
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * File Upload Functions
 */

// Upload a file with validation
function uploadFile($file, $allowedTypes = [], $maxSize = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed'];
    }
    
    // Use defined constants or defaults
    $maxFileSize = $maxSize ?? UPLOAD_MAX_SIZE;
    $allowedFileTypes = !empty($allowedTypes) ? $allowedTypes : UPLOAD_ALLOWED_TYPES;
    
    // Check file size
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed size'];
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check file type
    if (!in_array($fileExtension, $allowedFileTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadPath = UPLOAD_PATH . $newFilename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'filename' => $newFilename,
            'path' => $uploadPath,
            'size' => $file['size'],
            'type' => $fileExtension
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}
