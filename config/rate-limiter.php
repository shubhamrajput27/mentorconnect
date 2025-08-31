<?php
// Rate Limiting System for API Protection
class RateLimiter {
    private static $storage = [];
    private static $redis = null;
    
    public static function init() {
        // Try to connect to Redis if available, fallback to file storage
        if (class_exists('Redis')) {
            try {
                self::$redis = new Redis();
                self::$redis->connect('127.0.0.1', 6379);
            } catch (Exception $e) {
                error_log("Redis connection failed: " . $e->getMessage());
            }
        }
    }
    
    public static function checkLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
        $key = "rate_limit:" . md5($identifier);
        $now = time();
        
        if (self::$redis) {
            return self::checkRedisLimit($key, $maxRequests, $timeWindow, $now);
        } else {
            return self::checkFileLimit($key, $maxRequests, $timeWindow, $now);
        }
    }
    
    private static function checkRedisLimit($key, $maxRequests, $timeWindow, $now) {
        $pipe = self::$redis->multi(Redis::PIPELINE);
        $pipe->zRemRangeByScore($key, 0, $now - $timeWindow);
        $pipe->zCard($key);
        $pipe->zAdd($key, $now, $now);
        $pipe->expire($key, $timeWindow);
        $results = $pipe->exec();
        
        $currentRequests = $results[1];
        
        return [
            'allowed' => $currentRequests < $maxRequests,
            'count' => $currentRequests,
            'remaining' => max(0, $maxRequests - $currentRequests),
            'reset_time' => $now + $timeWindow
        ];
    }
    
    private static function checkFileLimit($key, $maxRequests, $timeWindow, $now) {
        $file = sys_get_temp_dir() . "/rate_limit_" . $key . ".json";
        
        $data = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true) ?: [];
        }
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $timeWindow) {
            return $timestamp > ($now - $timeWindow);
        });
        
        $currentRequests = count($data);
        
        if ($currentRequests < $maxRequests) {
            $data[] = $now;
            file_put_contents($file, json_encode($data));
            $allowed = true;
        } else {
            $allowed = false;
        }
        
        return [
            'allowed' => $allowed,
            'count' => $currentRequests,
            'remaining' => max(0, $maxRequests - $currentRequests),
            'reset_time' => $now + $timeWindow
        ];
    }
    
    public static function handleRateLimit($request) {
        $identifier = self::getIdentifier($request);
        $result = self::checkLimit($identifier);
        
        if (!$result['allowed']) {
            http_response_code(429);
            header('Content-Type: application/json');
            header('X-RateLimit-Limit: 100');
            header('X-RateLimit-Remaining: ' . $result['remaining']);
            header('X-RateLimit-Reset: ' . $result['reset_time']);
            header('Retry-After: ' . ($result['reset_time'] - time()));
            
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $result['reset_time'] - time()
            ]);
            exit;
        }
        
        header('X-RateLimit-Limit: 100');
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset_time']);
    }
    
    private static function getIdentifier($request) {
        // Use user ID if logged in, otherwise IP address
        if (isset($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }
        
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'ip_' . $ip;
    }
}

// Initialize rate limiter
RateLimiter::init();
?>
