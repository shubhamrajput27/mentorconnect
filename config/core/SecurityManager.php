<?php
/**
 * Optimized Security Manager
 * Comprehensive security validation and protection
 */

declare(strict_types=1);

class SecurityManager {
    private array $rateLimits = [];
    private CacheManager $cache;
    private array $securityLog = [];
    
    public function __construct(CacheManager $cache) {
        $this->cache = $cache;
    }
    
    /**
     * Generate secure CSRF token
     */
    public function generateCSRFToken(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_timestamp'] = time();
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken(string $token): bool {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_timestamp'])) {
            $this->logSecurityEvent('csrf_validation_failed', 'No token in session');
            return false;
        }
        
        // Check token age (5 minutes max)
        if (time() - $_SESSION['csrf_timestamp'] > 300) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_timestamp']);
            $this->logSecurityEvent('csrf_validation_failed', 'Token expired');
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        
        if (!$valid) {
            $this->logSecurityEvent('csrf_validation_failed', 'Token mismatch');
        }
        
        return $valid;
    }
    
    /**
     * Rate limiting with multiple strategies
     */
    public function checkRateLimit(string $identifier, string $action, int $limit = 10, int $window = 60): bool {
        $key = "rate_limit:{$action}:{$identifier}";
        $attempts = $this->cache->get($key) ?: [];
        $now = time();
        
        // Clean old attempts
        $attempts = array_filter($attempts, fn($timestamp) => $now - $timestamp < $window);
        
        if (count($attempts) >= $limit) {
            $this->logSecurityEvent('rate_limit_exceeded', [
                'identifier' => $identifier,
                'action' => $action,
                'attempts' => count($attempts),
                'limit' => $limit
            ]);
            return false;
        }
        
        // Add current attempt
        $attempts[] = $now;
        $this->cache->set($key, $attempts, $window);
        
        return true;
    }
    
    /**
     * Advanced password validation
     */
    public function validatePassword(string $password): array {
        $errors = [];
        $score = 0;
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        } else {
            $score += 1;
        }
        
        if (strlen($password) >= 12) {
            $score += 1;
        }
        
        if (preg_match('/[A-Z]/', $password)) {
            $score += 1;
        } else {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (preg_match('/[a-z]/', $password)) {
            $score += 1;
        } else {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (preg_match('/[0-9]/', $password)) {
            $score += 1;
        } else {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 1;
        } else {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check against common passwords
        if ($this->isCommonPassword($password)) {
            $errors[] = 'Password is too common';
            $score = max(0, $score - 2);
        }
        
        // Calculate strength
        $strength = match (true) {
            $score >= 5 => 'strong',
            $score >= 3 => 'medium',
            default => 'weak'
        };
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => $score,
            'strength' => $strength
        ];
    }
    
    /**
     * Secure password hashing
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    /**
     * Verify password hash
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput(mixed $input, string $type = 'string'): mixed {
        if (is_array($input)) {
            return array_map(fn($item) => $this->sanitizeInput($item, $type), $input);
        }
        
        if (!is_string($input)) {
            return $input;
        }
        
        return match ($type) {
            'email' => filter_var(trim($input), FILTER_SANITIZE_EMAIL),
            'int' => (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT),
            'float' => (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'url' => filter_var(trim($input), FILTER_SANITIZE_URL),
            'html' => htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            default => trim(strip_tags($input))
        };
    }
    
    /**
     * Validate input data
     */
    public function validateInput(mixed $input, array $rules): array {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $input[$field] ?? null;
            
            // Required check
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field][] = "{$field} is required";
                continue;
            }
            
            if (empty($value)) {
                continue;
            }
            
            // Type validation
            if (isset($rule['type'])) {
                if (!$this->validateType($value, $rule['type'])) {
                    $errors[$field][] = "{$field} must be a valid {$rule['type']}";
                }
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field][] = "{$field} must be at least {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field][] = "{$field} must not exceed {$rule['max_length']} characters";
            }
            
            // Pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field][] = "{$field} has invalid format";
            }
            
            // Custom validation
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $customResult = $rule['custom']($value);
                if ($customResult !== true) {
                    $errors[$field][] = $customResult;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * XSS Protection
     */
    public function escapeOutput(string $output, string $context = 'html'): string {
        return match ($context) {
            'html' => htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'attr' => htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'js' => json_encode($output, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            'css' => preg_replace('/[^a-zA-Z0-9\-_]/', '', $output),
            'url' => urlencode($output),
            default => htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        };
    }
    
    /**
     * SQL Injection Prevention Helper
     */
    public function preparePlaceholders(array $data): string {
        return str_repeat('?,', count($data) - 1) . '?';
    }
    
    /**
     * Secure file upload validation
     */
    public function validateFileUpload(array $file, array $options = []): array {
        $errors = [];
        $allowedTypes = $options['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $maxSize = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB
        
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf'
        ];
        
        if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
            $errors[] = 'File content does not match extension';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'safe_name' => $this->generateSafeFilename($file['name']),
            'mime_type' => $mimeType
        ];
    }
    
    /**
     * Generate secure filename
     */
    public function generateSafeFilename(string $filename): string {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Sanitize filename
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
        $name = substr($name, 0, 50); // Limit length
        
        return $name . '_' . uniqid() . '.' . $extension;
    }
    
    /**
     * Check IP against blacklist/whitelist
     */
    public function checkIPSecurity(string $ip): bool {
        // Check cache first
        $cacheKey = "ip_check:{$ip}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $allowed = true;
        
        // Check if IP is in blacklist
        $blacklist = $this->cache->get('ip_blacklist') ?: [];
        if (in_array($ip, $blacklist)) {
            $allowed = false;
        }
        
        // Cache result for 1 hour
        $this->cache->set($cacheKey, $allowed, 3600);
        
        if (!$allowed) {
            $this->logSecurityEvent('blocked_ip_access', ['ip' => $ip]);
        }
        
        return $allowed;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, mixed $data = null): void {
        $logEntry = [
            'timestamp' => time(),
            'event' => $event,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id()
        ];
        
        $this->securityLog[] = $logEntry;
        
        // Save to cache for monitoring
        $logKey = 'security_log:' . date('Y-m-d');
        $dailyLog = $this->cache->get($logKey) ?: [];
        $dailyLog[] = $logEntry;
        $this->cache->set($logKey, $dailyLog, 86400); // 24 hours
        
        // Critical events should be logged immediately
        $criticalEvents = ['csrf_validation_failed', 'rate_limit_exceeded', 'blocked_ip_access'];
        if (in_array($event, $criticalEvents)) {
            error_log("SECURITY EVENT: {$event} - " . json_encode($logEntry));
        }
    }
    
    /**
     * Get security statistics
     */
    public function getSecurityStats(): array {
        $today = date('Y-m-d');
        $logKey = 'security_log:' . $today;
        $dailyLog = $this->cache->get($logKey) ?: [];
        
        $stats = [
            'total_events' => count($dailyLog),
            'events_by_type' => []
        ];
        
        foreach ($dailyLog as $entry) {
            $stats['events_by_type'][$entry['event']] = 
                ($stats['events_by_type'][$entry['event']] ?? 0) + 1;
        }
        
        return $stats;
    }
    
    /**
     * Private helper methods
     */
    private function isCommonPassword(string $password): bool {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    private function validateType(mixed $value, string $type): bool {
        return match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT) !== false,
            'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'date' => strtotime($value) !== false,
            'phone' => preg_match('/^[\+]?[1-9][\d]{0,15}$/', $value),
            default => true
        };
    }
}
?>
