<?php
/**
 * Rate Limiting for MentorConnect API
 * Prevents abuse and protects against DDoS attacks
 */

class RateLimiter {
    private static $defaultLimits = [
        'api' => ['requests' => 100, 'window' => 3600], // 100 requests per hour
        'login' => ['requests' => 5, 'window' => 900],   // 5 attempts per 15 minutes
        'search' => ['requests' => 50, 'window' => 3600], // 50 searches per hour
        'upload' => ['requests' => 10, 'window' => 3600], // 10 uploads per hour
        'message' => ['requests' => 20, 'window' => 3600] // 20 messages per hour
    ];
    
    /**
     * Check and enforce rate limits
     */
    public static function checkLimit($identifier, $action = 'api', $customLimits = null) {
        $limits = $customLimits ?: (self::$defaultLimits[$action] ?? self::$defaultLimits['api']);
        
        $key = "rate_limit_{$action}_{$identifier}";
        $now = time();
        
        // Get or initialize rate limit data
        $data = self::getData($key);
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $limits) {
            return ($now - $timestamp) < $limits['window'];
        });
        
        // Check if limit exceeded
        if (count($data) >= $limits['requests']) {
            self::logRateLimitExceeded($identifier, $action, count($data));
            return false;
        }
        
        // Add current request
        $data[] = $now;
        self::setData($key, $data, $limits['window']);
        
        return true;
    }
    
    /**
     * Get rate limit status
     */
    public static function getStatus($identifier, $action = 'api') {
        $limits = self::$defaultLimits[$action] ?? self::$defaultLimits['api'];
        $key = "rate_limit_{$action}_{$identifier}";
        $now = time();
        
        $data = self::getData($key);
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $limits) {
            return ($now - $timestamp) < $limits['window'];
        });
        
        $remaining = max(0, $limits['requests'] - count($data));
        $resetTime = count($data) > 0 ? min($data) + $limits['window'] : $now;
        
        return [
            'limit' => $limits['requests'],
            'remaining' => $remaining,
            'used' => count($data),
            'reset_time' => $resetTime,
            'window' => $limits['window']
        ];
    }
    
    /**
     * Handle rate limiting for incoming requests
     */
    public static function handleRateLimit($server, $action = 'api') {
        $identifier = self::getIdentifier($server);
        
        if (!self::checkLimit($identifier, $action)) {
            $status = self::getStatus($identifier, $action);
            
            http_response_code(429); // Too Many Requests
            header('Content-Type: application/json');
            header('X-RateLimit-Limit: ' . $status['limit']);
            header('X-RateLimit-Remaining: ' . $status['remaining']);
            header('X-RateLimit-Reset: ' . $status['reset_time']);
            header('Retry-After: ' . ($status['reset_time'] - time()));
            
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $status['reset_time'] - time()
            ]);
            exit;
        }
        
        // Add rate limit headers for successful requests
        $status = self::getStatus($identifier, $action);
        header('X-RateLimit-Limit: ' . $status['limit']);
        header('X-RateLimit-Remaining: ' . $status['remaining']);
        header('X-RateLimit-Reset: ' . $status['reset_time']);
    }
    
    /**
     * Get unique identifier for rate limiting
     */
    private static function getIdentifier($server) {
        // Prefer authenticated user ID if available
        if (isset($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }
        
        // Fallback to IP address
        $ip = $server['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Handle proxy headers
        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $server['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwarded[0]);
        } elseif (isset($server['HTTP_X_REAL_IP'])) {
            $ip = $server['HTTP_X_REAL_IP'];
        }
        
        return 'ip_' . $ip;
    }
    
    /**
     * Store rate limit data (uses file system for simplicity)
     */
    private static function setData($key, $data, $ttl = 3600) {
        $filename = sys_get_temp_dir() . '/rate_limit_' . md5($key);
        $payload = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        file_put_contents($filename, json_encode($payload), LOCK_EX);
    }
    
    /**
     * Retrieve rate limit data
     */
    private static function getData($key) {
        $filename = sys_get_temp_dir() . '/rate_limit_' . md5($key);
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $content = file_get_contents($filename);
        $payload = json_decode($content, true);
        
        if (!$payload || time() > $payload['expires']) {
            unlink($filename);
            return [];
        }
        
        return $payload['data'] ?: [];
    }
    
    /**
     * Clear rate limit data for an identifier
     */
    public static function clearLimit($identifier, $action = 'api') {
        $key = "rate_limit_{$action}_{$identifier}";
        $filename = sys_get_temp_dir() . '/rate_limit_' . md5($key);
        
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    
    /**
     * Log rate limit exceeded events
     */
    private static function logRateLimitExceeded($identifier, $action, $count) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("RATE LIMIT EXCEEDED: {$identifier} for action {$action} (attempt #{$count})");
        }
        
        // Could also log to database or external service
        if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'rate_limit_exceeded', 
                       "Rate limit exceeded for action: {$action}");
        }
    }
    
    /**
     * Get all rate limit configurations
     */
    public static function getLimits() {
        return self::$defaultLimits;
    }
    
    /**
     * Update rate limit configuration
     */
    public static function setLimit($action, $requests, $window) {
        self::$defaultLimits[$action] = [
            'requests' => $requests,
            'window' => $window
        ];
    }
    
    /**
     * Check if an identifier is currently rate limited
     */
    public static function isLimited($identifier, $action = 'api') {
        $limits = self::$defaultLimits[$action] ?? self::$defaultLimits['api'];
        $key = "rate_limit_{$action}_{$identifier}";
        $now = time();
        
        $data = self::getData($key);
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $limits) {
            return ($now - $timestamp) < $limits['window'];
        });
        
        return count($data) >= $limits['requests'];
    }
    
    /**
     * Get rate limit statistics
     */
    public static function getStatistics() {
        $stats = [];
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/rate_limit_*');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $payload = json_decode($content, true);
            
            if ($payload && time() <= $payload['expires']) {
                $key = basename($file, '.tmp');
                $stats[$key] = [
                    'requests' => count($payload['data']),
                    'expires' => $payload['expires']
                ];
            }
        }
        
        return $stats;
    }
}
?>