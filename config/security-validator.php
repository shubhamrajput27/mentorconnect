<?php
/**
 * Enhanced Security Validator for MentorConnect
 * Advanced input validation and security checks
 */

class SecurityValidator {
    private static $instance = null;
    private $suspiciousPatterns = [
        // SQL Injection patterns
        'sql_injection' => [
            '/(\bunion\b.*\bselect\b)|(\bselect\b.*\bunion\b)/i',
            '/(\bor\b\s+\d+\s*=\s*\d+)|(\bor\b\s+[\'"]\w+[\'\"]\s*=\s*[\'"]\w+[\'"]\s*)/i',
            '/(\bdrop\b\s+table)|(\bdelete\b\s+from)|(\btruncate\b\s+table)/i',
            '/(;\s*drop\b)|(;\s*delete\b)|(;\s*update\b)|(;\s*insert\b)/i',
            '/(\bhaving\b\s+\d+\s*=\s*\d+)|(\bgroup\s+by\b.*\bhaving\b)/i'
        ],
        // XSS patterns
        'xss' => [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript\s*:/i',
            '/on\w+\s*=\s*["\'][^"\']*["\'][^>]*>/i',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is',
            '/expression\s*\(/i'
        ],
        // Directory traversal
        'path_traversal' => [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/\.\.\%2f/i',
            '/\.\.\%5c/i'
        ],
        // Command injection
        'command_injection' => [
            '/[;&|`$(){}]/i',
            '/\b(cat|ls|pwd|whoami|id|ps|kill|rm|cp|mv|chmod|chown)\b/i'
        ]
    ];
    
    private $securityLog = [];
    
    private function __construct() {
        // Initialize security logging
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Comprehensive input validation
     */
    public function validateInput($input, $type = 'general', $maxLength = null) {
        if ($input === null || $input === '') {
            return ['valid' => true, 'cleaned' => $input, 'issues' => []];
        }
        
        $issues = [];
        $cleaned = $input;
        
        // Check for suspicious patterns
        foreach ($this->suspiciousPatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    $issues[] = "Suspicious {$category} pattern detected";
                    $this->logSecurityEvent($category, $input);
                }
            }
        }
        
        // Type-specific validation
        switch ($type) {
            case 'email':
                $validation = $this->validateEmail($input);
                break;
            case 'url':
                $validation = $this->validateUrl($input);
                break;
            case 'phone':
                $validation = $this->validatePhone($input);
                break;
            case 'filename':
                $validation = $this->validateFilename($input);
                break;
            case 'username':
                $validation = $this->validateUsername($input);
                break;
            case 'password':
                $validation = $this->validatePassword($input);
                break;
            case 'html':
                $validation = $this->validateHtml($input);
                break;
            default:
                $validation = $this->validateGeneral($input, $maxLength);
        }
        
        return [
            'valid' => empty($issues) && $validation['valid'],
            'cleaned' => $validation['cleaned'],
            'issues' => array_merge($issues, $validation['issues']),
            'type' => $type
        ];
    }
    
    /**
     * Email validation with enhanced security
     */
    private function validateEmail($email) {
        $issues = [];
        $cleaned = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            $issues[] = 'Invalid email format';
        }
        
        if (strlen($cleaned) > 254) {
            $issues[] = 'Email too long';
        }
        
        // Check for suspicious patterns in email
        if (preg_match('/[<>"\'\\\]/', $cleaned)) {
            $issues[] = 'Invalid characters in email';
        }
        
        return ['valid' => empty($issues), 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * URL validation
     */
    private function validateUrl($url) {
        $issues = [];
        $cleaned = filter_var(trim($url), FILTER_SANITIZE_URL);
        
        if (!filter_var($cleaned, FILTER_VALIDATE_URL)) {
            $issues[] = 'Invalid URL format';
        }
        
        // Check for dangerous protocols
        $allowedProtocols = ['http', 'https', 'ftp', 'ftps'];
        $protocol = parse_url($cleaned, PHP_URL_SCHEME);
        if ($protocol && !in_array(strtolower($protocol), $allowedProtocols)) {
            $issues[] = 'Dangerous protocol detected';
        }
        
        return ['valid' => empty($issues), 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * Phone number validation
     */
    private function validatePhone($phone) {
        $issues = [];
        $cleaned = preg_replace('/[^\d+\-\(\)\s]/', '', trim($phone));
        
        // Basic phone format validation
        if (!preg_match('/^[\+]?[\d\-\(\)\s]{10,20}$/', $cleaned)) {
            $issues[] = 'Invalid phone number format';
        }
        
        return ['valid' => empty($issues), 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * Filename validation
     */
    private function validateFilename($filename) {
        $issues = [];
        $cleaned = trim($filename);
        
        // Remove dangerous characters
        $cleaned = preg_replace('/[<>:"/\\|?*\x00-\x1f]/', '', $cleaned);
        
        // Check for reserved names (Windows)
        $reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 
                    'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 
                    'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        
        $nameOnly = pathinfo($cleaned, PATHINFO_FILENAME);
        if (in_array(strtoupper($nameOnly), $reserved)) {
            $issues[] = 'Reserved filename';
        }
        
        if (strlen($cleaned) > 255) {
            $issues[] = 'Filename too long';
        }
        
        return ['valid' => empty($issues), 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * Username validation
     */
    private function validateUsername($username) {
        $issues = [];
        $cleaned = trim($username);
        
        if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,50}$/', $cleaned)) {
            $issues[] = 'Username must be 3-50 characters, alphanumeric, underscore, dash, or dot only';
        }
        
        if (preg_match('/^[._-]|[._-]$/', $cleaned)) {
            $issues[] = 'Username cannot start or end with special characters';
        }
        
        return ['valid' => empty($issues), 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * Password strength validation
     */
    private function validatePassword($password) {
        $issues = [];
        $cleaned = $password; // Don't modify passwords
        
        if (strlen($password) < 12) {
            $issues[] = 'Password must be at least 12 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $issues[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $issues[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $issues[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $issues[] = 'Password must contain at least one special character';
        }
        
        // Check against common passwords
        $commonPasswords = ['password', '123456', 'qwerty', 'abc123', 'password123'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $issues[] = 'Password is too common';
        }
        
        return ['valid' => empty($issues), 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * HTML content validation
     */
    private function validateHtml($html) {
        $issues = [];
        
        // Use HTML Purifier-like cleaning
        $cleaned = strip_tags($html, '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>');
        
        // Remove dangerous attributes
        $cleaned = preg_replace('/\s*(on\w+|javascript:|vbscript:|data:)\s*=\s*[\'"][^\'"]*[\'"]/i', '', $cleaned);
        
        if ($html !== $cleaned) {
            $issues[] = 'Potentially dangerous HTML content removed';
        }
        
        return ['valid' => true, 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * General input validation
     */
    private function validateGeneral($input, $maxLength = null) {
        $issues = [];
        $cleaned = htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        if ($maxLength && strlen($cleaned) > $maxLength) {
            $issues[] = "Input too long (max {$maxLength} characters)";
            $cleaned = substr($cleaned, 0, $maxLength);
        }
        
        return ['valid' => empty($issues), 'cleaned' => $cleaned, 'issues' => $issues];
    }
    
    /**
     * Rate limiting check
     */
    public function checkRateLimit($identifier, $action = 'general', $maxAttempts = 10, $timeWindow = 300) {
        $key = "rate_limit_{$action}_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window has passed
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time()];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            $this->logSecurityEvent('rate_limit_exceeded', $identifier . ' - ' . $action);
            return false;
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * CSRF token validation with enhanced security
     */
    public function validateCSRFToken($token, $action = null) {
        if (!isset($_SESSION['csrf_tokens'])) {
            return false;
        }
        
        // Check for action-specific token if provided
        if ($action && isset($_SESSION['csrf_tokens'][$action])) {
            $storedToken = $_SESSION['csrf_tokens'][$action];
        } else {
            $storedToken = $_SESSION['csrf_token'] ?? null;
        }
        
        if (!$storedToken) {
            return false;
        }
        
        // Time-based validation
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        if (time() - $tokenTime > 1800) { // 30 minutes
            $this->clearCSRFTokens();
            return false;
        }
        
        return hash_equals($storedToken, $token);
    }
    
    /**
     * Generate secure CSRF token
     */
    public function generateCSRFToken($action = null) {
        $token = bin2hex(random_bytes(32));
        
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        if ($action) {
            $_SESSION['csrf_tokens'][$action] = $token;
        } else {
            $_SESSION['csrf_token'] = $token;
        }
        
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
    
    private function clearCSRFTokens() {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_tokens'], $_SESSION['csrf_token_time']);
    }
    
    /**
     * Log security events
     */
    private function logSecurityEvent($type, $details) {
        $event = [
            'type' => $type,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time(),
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $this->securityLog[] = $event;
        
        // Log to file if in debug mode
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("SECURITY EVENT: {$type} - {$details}");
        }
    }
    
    /**
     * Get security log
     */
    public function getSecurityLog() {
        return $this->securityLog;
    }
    
    /**
     * Check if IP is suspicious based on activity
     */
    public function isSuspiciousIP($ip) {
        // Count recent security events from this IP
        $recentEvents = array_filter($this->securityLog, function($event) use ($ip) {
            return $event['ip'] === $ip && (time() - $event['timestamp']) < 3600; // Last hour
        });
        
        return count($recentEvents) > 5; // More than 5 security events in an hour
    }
}

/**
 * Global security helper functions
 */
function validate_input($input, $type = 'general', $maxLength = null) {
    return SecurityValidator::getInstance()->validateInput($input, $type, $maxLength);
}

function check_rate_limit($identifier, $action = 'general', $maxAttempts = 10, $timeWindow = 300) {
    return SecurityValidator::getInstance()->checkRateLimit($identifier, $action, $maxAttempts, $timeWindow);
}

function validate_csrf($token, $action = null) {
    return SecurityValidator::getInstance()->validateCSRFToken($token, $action);
}

function generate_csrf($action = null) {
    return SecurityValidator::getInstance()->generateCSRFToken($action);
}

function is_suspicious_ip($ip) {
    return SecurityValidator::getInstance()->isSuspiciousIP($ip);
}
?>
