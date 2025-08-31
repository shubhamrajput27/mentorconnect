<?php
// Advanced Caching System for MentorConnect
class CacheManager {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour default
    private $compressionEnabled = true;
    private $memcache = null;
    private $redis = null;
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../cache';
        $this->ensureCacheDirectory();
        $this->initializeMemoryStores();
    }
    
    /**
     * Get cached value with multiple storage fallback
     */
    public function get($key, $default = null) {
        // Try Redis first
        if ($this->redis) {
            $value = $this->redis->get($this->prefixKey($key));
            if ($value !== false) {
                return $this->unserializeValue($value);
            }
        }
        
        // Try Memcache
        if ($this->memcache) {
            $value = $this->memcache->get($this->prefixKey($key));
            if ($value !== false) {
                return $value;
            }
        }
        
        // Try file cache
        return $this->getFromFile($key, $default);
    }
    
    /**
     * Store value in cache with TTL
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?: $this->defaultTTL;
        $serializedValue = $this->serializeValue($value);
        
        // Store in Redis
        if ($this->redis) {
            $this->redis->setex($this->prefixKey($key), $ttl, $serializedValue);
        }
        
        // Store in Memcache
        if ($this->memcache) {
            $this->memcache->set($this->prefixKey($key), $value, 0, $ttl);
        }
        
        // Store in file cache
        $this->setToFile($key, $value, $ttl);
        
        return true;
    }
    
    /**
     * Delete from all cache stores
     */
    public function delete($key) {
        $prefixedKey = $this->prefixKey($key);
        
        // Delete from Redis
        if ($this->redis) {
            $this->redis->del($prefixedKey);
        }
        
        // Delete from Memcache
        if ($this->memcache) {
            $this->memcache->delete($prefixedKey);
        }
        
        // Delete from file cache
        $this->deleteFromFile($key);
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        // Clear Redis
        if ($this->redis) {
            $this->redis->flushDB();
        }
        
        // Clear Memcache
        if ($this->memcache) {
            $this->memcache->flush();
        }
        
        // Clear file cache
        $this->clearFileCache();
        
        return true;
    }
    
    /**
     * Get or set cache with callback
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Cache database query results
     */
    public function cacheQuery($key, $sql, $params = [], $ttl = null) {
        return $this->remember($key, function() use ($sql, $params) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, $ttl);
    }
    
    /**
     * Cache HTTP responses
     */
    public function cacheHttpResponse($key, $url, $options = [], $ttl = null) {
        return $this->remember($key, function() use ($url, $options) {
            $context = stream_context_create([
                'http' => array_merge([
                    'timeout' => 30,
                    'user_agent' => 'MentorConnect/1.0'
                ], $options)
            ]);
            
            $response = file_get_contents($url, false, $context);
            return $response !== false ? $response : null;
        }, $ttl);
    }
    
    /**
     * Tag-based cache invalidation
     */
    public function tag($tags, $key, $value, $ttl = null) {
        $tags = is_array($tags) ? $tags : [$tags];
        
        // Store the main cache item
        $this->set($key, $value, $ttl);
        
        // Store tag associations
        foreach ($tags as $tag) {
            $tagKey = "tag:{$tag}";
            $taggedKeys = $this->get($tagKey, []);
            
            if (!in_array($key, $taggedKeys)) {
                $taggedKeys[] = $key;
                $this->set($tagKey, $taggedKeys, $ttl);
            }
        }
        
        return true;
    }
    
    /**
     * Invalidate all cache items with specific tags
     */
    public function invalidateTag($tag) {
        $tagKey = "tag:{$tag}";
        $taggedKeys = $this->get($tagKey, []);
        
        foreach ($taggedKeys as $key) {
            $this->delete($key);
        }
        
        $this->delete($tagKey);
        return count($taggedKeys);
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $stats = [
            'file_cache' => $this->getFileCacheStats(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        if ($this->redis) {
            $stats['redis'] = $this->redis->info();
        }
        
        if ($this->memcache) {
            $stats['memcache'] = $this->memcache->getStats();
        }
        
        return $stats;
    }
    
    /**
     * Cleanup expired cache files
     */
    public function cleanup() {
        $cleaned = 0;
        $pattern = $this->cacheDir . '/*.cache';
        
        foreach (glob($pattern) as $file) {
            $data = $this->readCacheFile($file);
            if (!$data || $data['expires'] < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    // Private methods for file cache operations
    private function getFromFile($key, $default = null) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $data = $this->readCacheFile($filename);
        
        if (!$data || $data['expires'] < time()) {
            unlink($filename);
            return $default;
        }
        
        return $data['value'];
    }
    
    private function setToFile($key, $value, $ttl) {
        $filename = $this->getCacheFilename($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $serialized = serialize($data);
        
        if ($this->compressionEnabled && function_exists('gzcompress')) {
            $serialized = gzcompress($serialized, 6);
        }
        
        return file_put_contents($filename, $serialized, LOCK_EX) !== false;
    }
    
    private function deleteFromFile($key) {
        $filename = $this->getCacheFilename($key);
        return file_exists($filename) ? unlink($filename) : true;
    }
    
    private function readCacheFile($filename) {
        $content = file_get_contents($filename);
        
        if ($this->compressionEnabled && function_exists('gzuncompress')) {
            $decompressed = @gzuncompress($content);
            if ($decompressed !== false) {
                $content = $decompressed;
            }
        }
        
        return @unserialize($content);
    }
    
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    private function clearFileCache() {
        $pattern = $this->cacheDir . '/*.cache';
        $cleared = 0;
        
        foreach (glob($pattern) as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    private function getFileCacheStats() {
        $pattern = $this->cacheDir . '/*.cache';
        $files = glob($pattern);
        $totalSize = 0;
        $expired = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $data = $this->readCacheFile($file);
            if ($data && $data['expires'] < time()) {
                $expired++;
            }
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'expired_files' => $expired,
            'directory' => $this->cacheDir
        ];
    }
    
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Create .htaccess to protect cache directory
        $htaccessFile = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
    
    private function initializeMemoryStores() {
        // Initialize Redis if available
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->redis->select(0); // Use database 0
            } catch (Exception $e) {
                $this->redis = null;
                error_log("Redis connection failed: " . $e->getMessage());
            }
        }
        
        // Initialize Memcache if available
        if (class_exists('Memcache')) {
            try {
                $this->memcache = new Memcache();
                $this->memcache->connect('127.0.0.1', 11211);
            } catch (Exception $e) {
                $this->memcache = null;
                error_log("Memcache connection failed: " . $e->getMessage());
            }
        }
    }
    
    private function prefixKey($key) {
        return 'mentorconnect:' . $key;
    }
    
    private function serializeValue($value) {
        return serialize($value);
    }
    
    private function unserializeValue($value) {
        return unserialize($value);
    }
}

// Static cache helper functions
class Cache {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new CacheManager();
        }
        return self::$instance;
    }
    
    public static function get($key, $default = null) {
        return self::getInstance()->get($key, $default);
    }
    
    public static function set($key, $value, $ttl = null) {
        return self::getInstance()->set($key, $value, $ttl);
    }
    
    public static function delete($key) {
        return self::getInstance()->delete($key);
    }
    
    public static function remember($key, $callback, $ttl = null) {
        return self::getInstance()->remember($key, $callback, $ttl);
    }
    
    public static function clear() {
        return self::getInstance()->clear();
    }
}

// Cache warming script
function warmCache() {
    $cache = Cache::getInstance();
    
    // Warm popular queries
    $popularQueries = [
        'top_mentors' => "SELECT * FROM users u JOIN mentor_profiles mp ON u.id = mp.user_id WHERE u.role = 'mentor' AND u.status = 'active' ORDER BY mp.rating DESC LIMIT 20",
        'active_sessions_count' => "SELECT COUNT(*) as count FROM sessions WHERE status = 'active'",
        'recent_reviews' => "SELECT * FROM reviews ORDER BY created_at DESC LIMIT 10",
        'popular_skills' => "SELECT s.name, COUNT(*) as count FROM skills s JOIN user_skills us ON s.id = us.skill_id GROUP BY s.id ORDER BY count DESC LIMIT 20"
    ];
    
    foreach ($popularQueries as $key => $sql) {
        $cache->cacheQuery($key, $sql, [], 1800); // 30 minutes
    }
    
    return count($popularQueries);
}

// Auto-cleanup old cache files (run via cron)
if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === 'cleanup') {
    $cache = new CacheManager();
    $cleaned = $cache->cleanup();
    echo "Cleaned up {$cleaned} expired cache files.\n";
}
?>
