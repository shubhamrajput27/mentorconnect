<?php
/**
 * API Response Optimization Middleware
 * Handles compression, caching, and response optimization
 */

class ApiOptimizer {
    private static $compressionThreshold = 1024; // Compress responses larger than 1KB
    private static $cacheHeaders = [];
    
    public static function init() {
        // Set up compression
        self::setupCompression();
        
        // Set common headers
        self::setCommonHeaders();
        
        // Register shutdown function for cleanup
        register_shutdown_function([self::class, 'cleanup']);
    }
    
    public static function setupCompression() {
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        // Check if client accepts compression
        if (strpos($acceptEncoding, 'gzip') !== false && function_exists('gzencode')) {
            ini_set('zlib.output_compression', 'Off');
            ob_start([self::class, 'compressOutput']);
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
        } elseif (strpos($acceptEncoding, 'deflate') !== false && function_exists('gzdeflate')) {
            ini_set('zlib.output_compression', 'Off');
            ob_start([self::class, 'deflateOutput']);
            header('Content-Encoding: deflate');
            header('Vary: Accept-Encoding');
        }
    }
    
    public static function setCommonHeaders() {
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Performance headers
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        // Content type
        header('Content-Type: application/json; charset=utf-8');
    }
    
    public static function compressOutput($buffer) {
        if (strlen($buffer) >= self::$compressionThreshold) {
            return gzencode($buffer, 6);
        }
        return $buffer;
    }
    
    public static function deflateOutput($buffer) {
        if (strlen($buffer) >= self::$compressionThreshold) {
            return gzdeflate($buffer, 6);
        }
        return $buffer;
    }
    
    public static function optimizeResponse($data, $options = []) {
        $optimized = self::removeNullValues($data);
        $optimized = self::compressTimestamps($optimized);
        $optimized = self::optimizeArrays($optimized);
        
        // Add metadata if enabled
        if (isset($options['include_meta']) && $options['include_meta']) {
            $optimized = self::addMetadata($optimized);
        }
        
        return $optimized;
    }
    
    private static function removeNullValues($data) {
        if (is_array($data)) {
            $filtered = [];
            foreach ($data as $key => $value) {
                if ($value !== null && $value !== '') {
                    $filtered[$key] = is_array($value) ? self::removeNullValues($value) : $value;
                }
            }
            return $filtered;
        }
        return $data;
    }
    
    private static function compressTimestamps($data) {
        if (is_array($data)) {
            $compressed = [];
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $compressed[$key] = self::compressTimestamps($value);
                } elseif (preg_match('/.*_(at|time|date)$/', $key) && is_string($value)) {
                    // Convert timestamp strings to Unix timestamps
                    $timestamp = strtotime($value);
                    if ($timestamp !== false) {
                        $compressed[$key . '_ts'] = $timestamp;
                        $compressed[$key . '_formatted'] = self::formatTimeAgo($value);
                        continue; // Skip original timestamp
                    }
                }
                $compressed[$key] = $value;
            }
            return $compressed;
        }
        return $data;
    }
    
    private static function optimizeArrays($data) {
        if (is_array($data)) {
            // Remove empty arrays
            $data = array_filter($data, function($value) {
                return !is_array($value) || !empty($value);
            });
            
            // Recursively optimize nested arrays
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = self::optimizeArrays($value);
                }
            }
        }
        return $data;
    }
    
    private static function addMetadata($data) {
        global $start_time;
        
        $metadata = [
            'execution_time' => round((microtime(true) - ($start_time ?? microtime(true))) * 1000, 2) . 'ms',
            'memory_usage' => self::formatBytes(memory_get_peak_usage(true)),
            'timestamp' => time(),
            'version' => APP_VERSION ?? '1.0.0'
        ];
        
        if (class_exists('DatabaseOptimizer')) {
            $metadata['cache_stats'] = DatabaseOptimizer::getCacheStats();
        }
        
        return [
            'data' => $data,
            'meta' => $metadata
        ];
    }
    
    private static function formatTimeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . 'm';
        if ($time < 86400) return floor($time/3600) . 'h';
        if ($time < 2592000) return floor($time/86400) . 'd';
        if ($time < 31536000) return floor($time/2592000) . 'mo';
        return floor($time/31536000) . 'y';
    }
    
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public static function sendJsonResponse($data, $statusCode = 200, $options = []) {
        http_response_code($statusCode);
        
        // Optimize the response
        $optimizedData = self::optimizeResponse($data, $options);
        
        // Generate ETag for caching
        $etag = md5(json_encode($optimizedData));
        header("ETag: \"{$etag}\"");
        
        // Check if client has cached version
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === "\"{$etag}\"") {
            http_response_code(304);
            exit;
        }
        
        // Add response time header for debugging
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $executionTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
            header("X-Response-Time: {$executionTime}ms");
        }
        
        // Output the JSON
        echo json_encode($optimizedData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    
    public static function sendErrorResponse($message, $statusCode = 400, $details = []) {
        http_response_code($statusCode);
        
        $errorResponse = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $statusCode
            ]
        ];
        
        if (!empty($details)) {
            $errorResponse['error']['details'] = $details;
        }
        
        // Add debug info in development
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $errorResponse['debug'] = [
                'file' => $backtrace[0]['file'] ?? 'unknown',
                'line' => $backtrace[0]['line'] ?? 'unknown',
                'function' => $backtrace[1]['function'] ?? 'unknown'
            ];
        }
        
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    }
    
    public static function handlePrefetch() {
        // Handle prefetch requests differently
        if (isset($_SERVER['HTTP_X_PREFETCH'])) {
            header('Cache-Control: public, max-age=300'); // Cache prefetch for 5 minutes
            return true;
        }
        return false;
    }
    
    public static function cleanup() {
        // Log slow requests
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            if ($executionTime > 1.0) { // Log requests taking more than 1 second
                $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
                error_log("Slow API request: {$requestUri} - {$executionTime}s");
            }
        }
    }
}

/**
 * Request Throttling and Rate Limiting
 */
class RequestThrottler {
    private static $requestCounts = [];
    private static $maxRequestsPerMinute = 60;
    private static $maxRequestsPerHour = 1000;
    
    public static function checkRateLimit($identifier = null) {
        if (!$identifier) {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        $now = time();
        $minuteWindow = $now - 60;
        $hourWindow = $now - 3600;
        
        // Initialize if not exists
        if (!isset(self::$requestCounts[$identifier])) {
            self::$requestCounts[$identifier] = [];
        }
        
        // Clean old requests
        self::$requestCounts[$identifier] = array_filter(
            self::$requestCounts[$identifier],
            function($timestamp) use ($hourWindow) {
                return $timestamp > $hourWindow;
            }
        );
        
        // Count requests in last minute and hour
        $requestsInMinute = count(array_filter(
            self::$requestCounts[$identifier],
            function($timestamp) use ($minuteWindow) {
                return $timestamp > $minuteWindow;
            }
        ));
        
        $requestsInHour = count(self::$requestCounts[$identifier]);
        
        // Check limits
        if ($requestsInMinute >= self::$maxRequestsPerMinute) {
            header('X-RateLimit-Limit: ' . self::$maxRequestsPerMinute);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . ($now + 60));
            http_response_code(429);
            ApiOptimizer::sendErrorResponse('Too many requests per minute', 429);
            exit;
        }
        
        if ($requestsInHour >= self::$maxRequestsPerHour) {
            header('X-RateLimit-Limit: ' . self::$maxRequestsPerHour);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . ($now + 3600));
            http_response_code(429);
            ApiOptimizer::sendErrorResponse('Too many requests per hour', 429);
            exit;
        }
        
        // Add current request
        self::$requestCounts[$identifier][] = $now;
        
        // Add rate limit headers
        header('X-RateLimit-Limit: ' . self::$maxRequestsPerMinute);
        header('X-RateLimit-Remaining: ' . (self::$maxRequestsPerMinute - $requestsInMinute - 1));
        header('X-RateLimit-Reset: ' . ($now + 60));
        
        return true;
    }
}

// Auto-initialize
ApiOptimizer::init();
?>
