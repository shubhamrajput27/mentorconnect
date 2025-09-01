<?php
/**
 * Advanced Security Enhancement Module for MentorConnect
 * Provides comprehensive security features beyond basic authentication
 */

class SecurityEnhancement {
    private static $instance = null;
    private $db;
    private $config;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->initializeSecurityConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeSecurityConfig() {
        $this->config = [
            'max_failed_logins' => 5,
            'lockout_duration' => 15 * 60, // 15 minutes
            'session_timeout' => 30 * 60, // 30 minutes
            'password_expiry_days' => 90,
            'enable_2fa' => true,
            'suspicious_activity_threshold' => 10,
            'ip_whitelist_enabled' => false,
            'maintenance_mode' => false
        ];
    }
    
    /**
     * Enhanced input validation and sanitization
     */
    public function validateAndSanitize($input, $type = 'general', $options = []) {
        if (is_array($input)) {
            return array_map(function($item) use ($type, $options) {
                return $this->validateAndSanitize($item, $type, $options);
            }, $input);
        }
        
        // Trim and remove null bytes
        $input = trim(str_replace(chr(0), '', $input));
        
        switch ($type) {
            case 'email':
                $input = filter_var($input, FILTER_SANITIZE_EMAIL);
                if (!filter_var($input, FILTER_VALIDATE_EMAIL) || strlen($input) > 254) {
                    throw new InvalidArgumentException('Invalid email format');
                }
                break;
                
            case 'username':
                $input = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
                if (strlen($input) < 3 || strlen($input) > 30) {
                    throw new InvalidArgumentException('Username must be 3-30 characters');
                }
                break;
                
            case 'password':
                if (!$this->validatePasswordStrength($input)) {
                    throw new InvalidArgumentException('Password does not meet security requirements');
                }
                break;
                
            case 'phone':
                $input = preg_replace('/[^0-9+\-\(\)\s]/', '', $input);
                break;
                
            case 'url':
                $input = filter_var($input, FILTER_SANITIZE_URL);
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException('Invalid URL format');
                }
                break;
                
            case 'numeric':
                $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                if (!is_numeric($input)) {
                    throw new InvalidArgumentException('Invalid numeric value');
                }
                break;
                
            case 'html':
                // For content that allows some HTML
                $allowed_tags = $options['allowed_tags'] ?? '<p><br><strong><em><ul><ol><li>';
                $input = strip_tags($input, $allowed_tags);
                break;
                
            default:
                $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        // Check for common XSS patterns
        if ($this->containsXSS($input)) {
            throw new SecurityException('Potential XSS attack detected');
        }
        
        // Check for SQL injection patterns
        if ($this->containsSQLInjection($input)) {
            throw new SecurityException('Potential SQL injection detected');
        }
        
        return $input;
    }
    
    /**
     * Advanced password strength validation
     */
    public function validatePasswordStrength($password) {
        $minLength = $this->config['password_min_length'] ?? 12;
        
        if (strlen($password) < $minLength) return false;
        if (!preg_match('/[A-Z]/', $password)) return false; // Uppercase
        if (!preg_match('/[a-z]/', $password)) return false; // Lowercase
        if (!preg_match('/[0-9]/', $password)) return false; // Numbers
        if (!preg_match('/[^A-Za-z0-9]/', $password)) return false; // Special chars
        
        // Check against common passwords
        if ($this->isCommonPassword($password)) return false;
        
        // Check for patterns
        if ($this->hasWeakPatterns($password)) return false;
        
        return true;
    }
    
    /**
     * Detect potential XSS attacks
     */
    private function containsXSS($input) {
        $xss_patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i',
            '/onmouseover=/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<applet[^>]*>/i',
            '/<meta[^>]*>/i'
        ];
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect potential SQL injection
     */
    private function containsSQLInjection($input) {
        $sql_patterns = [
            '/(\s|^)(select|insert|update|delete|drop|create|alter|exec|execute)\s/i',
            '/union\s+select/i',
            '/\'\s*or\s*\'/i',
            '/\'\s*and\s*\'/i',
            '/\'\s*;/i',
            '/--/i',
            '/\/\*/i',
            '/xp_/i',
            '/sp_/i'
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enhanced rate limiting with IP tracking
     */
    public function checkAdvancedRateLimit($identifier, $action = 'general', $maxAttempts = null, $window = null) {
        $maxAttempts = $maxAttempts ?? $this->config['max_attempts_per_hour'] ?? 100;
        $window = $window ?? 3600; // 1 hour
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check rate limit
        $sql = "SELECT COUNT(*) as attempts FROM rate_limit_log 
                WHERE (identifier = ? OR ip_address = ?) 
                AND action = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $ip, $action, $window]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['attempts'] >= $maxAttempts) {
            $this->logSecurityEvent('rate_limit_exceeded', [
                'identifier' => $identifier,
                'ip' => $ip,
                'action' => $action,
                'attempts' => $result['attempts']
            ]);
            return false;
        }
        
        // Log this attempt
        $sql = "INSERT INTO rate_limit_log (identifier, ip_address, user_agent, action) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $ip, $userAgent, $action]);
        
        return true;
    }
    
    /**
     * Session security management
     */
    public function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->config['session_timeout']) {
                $this->destroySession();
                return false;
            }
        }
        
        // Validate session fingerprint
        $fingerprint = $this->generateSessionFingerprint();
        if (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== $fingerprint) {
            $this->logSecurityEvent('session_hijacking_attempt', [
                'user_id' => $_SESSION['user_id'],
                'expected_fingerprint' => $_SESSION['fingerprint'],
                'actual_fingerprint' => $fingerprint
            ]);
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        $_SESSION['fingerprint'] = $fingerprint;
        
        return true;
    }
    
    /**
     * Generate unique session fingerprint
     */
    private function generateSessionFingerprint() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        return hash('sha256', $ip . $userAgent . $acceptLanguage . session_id());
    }
    
    /**
     * Secure session destruction
     */
    public function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear all session variables
            $_SESSION = [];
            
            // Destroy session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            // Destroy session
            session_destroy();
        }
    }
    
    /**
     * File upload security validation
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = null) {
        $maxSize = $maxSize ?? (10 * 1024 * 1024); // 10MB default
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new SecurityException('File upload error: ' . $file['error']);
        }
        
        if ($file['size'] > $maxSize || $file['size'] <= 0) {
            throw new SecurityException('Invalid file size');
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
            throw new SecurityException('File type not allowed');
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
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain'
        ];
        
        if (isset($allowedMimes[$extension]) && $mimeType !== $allowedMimes[$extension]) {
            throw new SecurityException('File type validation failed');
        }
        
        // Scan for malware patterns (basic)
        if ($this->containsMalwareSignatures($file['tmp_name'])) {
            throw new SecurityException('Potentially malicious file detected');
        }
        
        return true;
    }
    
    /**
     * Basic malware signature detection
     */
    private function containsMalwareSignatures($filePath) {
        $malwarePatterns = [
            '/<\?php.*eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/shell_exec\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i',
            '/file_get_contents\s*\(\s*["\']http/i'
        ];
        
        $content = file_get_contents($filePath, false, null, 0, 8192); // First 8KB
        
        foreach ($malwarePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($eventType, $details = []) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        
        $sql = "INSERT INTO security_logs (user_id, event_type, ip_address, user_agent, details, severity) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $severity = $this->getEventSeverity($eventType);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $eventType,
            $ip,
            $userAgent,
            json_encode($details),
            $severity
        ]);
        
        // Send alert for high severity events
        if ($severity === 'high') {
            $this->sendSecurityAlert($eventType, $details);
        }
    }
    
    /**
     * Determine event severity
     */
    private function getEventSeverity($eventType) {
        $highSeverityEvents = [
            'session_hijacking_attempt',
            'sql_injection_attempt',
            'xss_attempt',
            'malware_upload_attempt',
            'unauthorized_access_attempt'
        ];
        
        return in_array($eventType, $highSeverityEvents) ? 'high' : 'medium';
    }
    
    /**
     * Send security alerts
     */
    private function sendSecurityAlert($eventType, $details) {
        // Implementation for sending security alerts (email, SMS, etc.)
        error_log("HIGH SEVERITY SECURITY EVENT: {$eventType} - " . json_encode($details));
    }
    
    /**
     * Check for common passwords
     */
    private function isCommonPassword($password) {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey',
            'dragon', 'master', 'superman', 'hello'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * Check for weak password patterns
     */
    private function hasWeakPatterns($password) {
        // Check for repeated characters
        if (preg_match('/(.)\1{2,}/', $password)) return true;
        
        // Check for sequential patterns
        if (preg_match('/(123|abc|qwe|asd|zxc)/i', $password)) return true;
        
        // Check for keyboard patterns
        if (preg_match('/(qwerty|asdf|zxcv)/i', $password)) return true;
        
        return false;
    }
    
    /**
     * Generate secure token
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encryptData($data, $key = null) {
        $key = $key ?? $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decryptData($encryptedData, $key = null) {
        $key = $key ?? $this->getEncryptionKey();
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private function getEncryptionKey() {
        $keyFile = __DIR__ . '/.encryption_key';
        
        if (!file_exists($keyFile)) {
            $key = random_bytes(32);
            file_put_contents($keyFile, base64_encode($key));
            chmod($keyFile, 0600);
        } else {
            $key = base64_decode(file_get_contents($keyFile));
        }
        
        return $key;
    }
}

// Custom exception classes
class SecurityException extends Exception {}
class ValidationException extends Exception {}

// Initialize security enhancement
$security = SecurityEnhancement::getInstance();
?>
