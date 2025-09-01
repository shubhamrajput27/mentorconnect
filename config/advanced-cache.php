<?php
/**
 * Advanced Cache Manager with Multi-Layer Caching Strategy
 * Implements Memory, Redis, and File-based caching
 */

class AdvancedCacheManager {
    private static $memoryCache = [];
    private static $redisClient = null;
    private static $cacheStats = [
        'memory_hits' => 0,
        'redis_hits' => 0,
        'file_hits' => 0,
        'misses' => 0
    ];
    private static $maxMemoryItems = 100;
    
    public static function init() {
        // Try to connect to Redis if available
        if (extension_loaded('redis')) {
            try {
                self::$redisClient = new Redis();
                self::$redisClient->connect('127.0.0.1', 6379);
                self::$redisClient->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
            } catch (Exception $e) {
                error_log('Redis connection failed: ' . $e->getMessage());
                self::$redisClient = null;
            }
        }
    }
    
    public static function get($key) {
        $startTime = microtime(true);
        
        // Level 1: Memory cache (fastest - ~0.001ms)
        if (isset(self::$memoryCache[$key])) {
            $cached = self::$memoryCache[$key];
            if (time() < $cached['expires']) {
                self::$cacheStats['memory_hits']++;
                return $cached['data'];
            }
            unset(self::$memoryCache[$key]);
        }
        
        // Level 2: Redis cache (fast - ~1-2ms)
        if (self::$redisClient) {
            try {
                $data = self::$redisClient->get($key);
                if ($data !== false) {
                    $decoded = json_decode($data, true);
                    if ($decoded && time() < $decoded['expires']) {
                        // Store in memory cache for next access
                        self::setMemoryCache($key, $decoded);
                        self::$cacheStats['redis_hits']++;
                        return $decoded['data'];
                    }
                    // Expired in Redis, remove it
                    self::$redisClient->del($key);
                }
            } catch (Exception $e) {
                error_log('Redis get error: ' . $e->getMessage());
            }
        }
        
        // Level 3: File cache (slower - ~5-10ms)
        $file = self::getCacheFilePath($key);
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $cached = json_decode($content, true);
                if ($cached && time() < $cached['expires']) {
                    // Store in higher level caches
                    self::setMemoryCache($key, $cached);
                    if (self::$redisClient) {
                        try {
                            self::$redisClient->setex($key, $cached['expires'] - time(), $content);
                        } catch (Exception $e) {
                            error_log('Redis set error: ' . $e->getMessage());
                        }
                    }
                    self::$cacheStats['file_hits']++;
                    return $cached['data'];
                }
                // Expired file cache, remove it
                unlink($file);
            }
        }
        
        self::$cacheStats['misses']++;
        return null;
    }
    
    public static function set($key, $data, $ttl = 300) {
        $expires = time() + $ttl;
        $cached = [
            'data' => $data,
            'expires' => $expires,
            'created' => time()
        ];
        
        // Store in memory cache
        self::setMemoryCache($key, $cached);
        
        // Store in Redis cache
        if (self::$redisClient) {
            try {
                self::$redisClient->setex($key, $ttl, json_encode($cached));
            } catch (Exception $e) {
                error_log('Redis set error: ' . $e->getMessage());
            }
        }
        
        // Store in file cache
        $file = self::getCacheFilePath($key);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode($cached), LOCK_EX);
    }
    
    public static function delete($key) {
        // Remove from memory
        unset(self::$memoryCache[$key]);
        
        // Remove from Redis
        if (self::$redisClient) {
            try {
                self::$redisClient->del($key);
            } catch (Exception $e) {
                error_log('Redis delete error: ' . $e->getMessage());
            }
        }
        
        // Remove from file cache
        $file = self::getCacheFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    public static function clear() {
        // Clear memory cache
        self::$memoryCache = [];
        
        // Clear Redis cache
        if (self::$redisClient) {
            try {
                self::$redisClient->flushDB();
            } catch (Exception $e) {
                error_log('Redis flush error: ' . $e->getMessage());
            }
        }
        
        // Clear file cache
        $cacheDir = sys_get_temp_dir() . '/mentorconnect_cache';
        if (is_dir($cacheDir)) {
            self::removeDirectory($cacheDir);
        }
        
        // Reset stats
        self::$cacheStats = [
            'memory_hits' => 0,
            'redis_hits' => 0,
            'file_hits' => 0,
            'misses' => 0
        ];
    }
    
    public static function getStats() {
        $total = array_sum(self::$cacheStats);
        $hitRatio = $total > 0 ? (($total - self::$cacheStats['misses']) / $total) * 100 : 0;
        
        return [
            'stats' => self::$cacheStats,
            'hit_ratio' => round($hitRatio, 2),
            'memory_items' => count(self::$memoryCache),
            'redis_connected' => self::$redisClient !== null
        ];
    }
    
    private static function setMemoryCache($key, $cached) {
        // Implement LRU eviction if memory cache is full
        if (count(self::$memoryCache) >= self::$maxMemoryItems) {
            // Remove oldest item
            $oldestKey = array_key_first(self::$memoryCache);
            unset(self::$memoryCache[$oldestKey]);
        }
        
        self::$memoryCache[$key] = $cached;
    }
    
    private static function getCacheFilePath($key) {
        $hash = md5($key);
        $dir = substr($hash, 0, 2);
        return sys_get_temp_dir() . "/mentorconnect_cache/{$dir}/{$hash}.cache";
    }
    
    private static function removeDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    /**
     * Warm up cache with critical data
     */
    public static function warmUp() {
        // Pre-load critical data that's frequently accessed
        $criticalKeys = [
            'app_config',
            'user_roles',
            'skill_categories',
            'popular_mentors'
        ];
        
        foreach ($criticalKeys as $key) {
            // Trigger loading of critical data
            switch ($key) {
                case 'popular_mentors':
                    DatabaseOptimizer::getMentorListOptimized(['popular' => true], 10);
                    break;
                // Add other critical data loading here
            }
        }
    }
}

// Auto-initialize on include
AdvancedCacheManager::init();
?>
