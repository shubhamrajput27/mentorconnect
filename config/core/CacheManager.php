<?php
/**
 * Optimized Cache Manager
 * Multi-tier caching: Memory -> Redis -> File
 */

declare(strict_types=1);

class CacheManager {
    private array $memoryCache = [];
    private ?Redis $redis = null;
    private string $cacheDir;
    private array $stats = ['hits' => 0, 'misses' => 0, 'sets' => 0];
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../../cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Try to connect to Redis
        if (extension_loaded('redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                $this->redis->setOption(Redis::OPT_PREFIX, 'mentorconnect:');
            } catch (Exception $e) {
                $this->redis = null;
                if (DEBUG_MODE) {
                    error_log('Redis connection failed: ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Get cached value with multi-tier lookup
     */
    public function get(string $key): mixed {
        // 1. Check memory cache first (fastest)
        if (isset($this->memoryCache[$key])) {
            $item = $this->memoryCache[$key];
            if ($item['expires'] > time()) {
                $this->stats['hits']++;
                return $item['data'];
            }
            unset($this->memoryCache[$key]);
        }
        
        // 2. Check Redis cache (fast)
        if ($this->redis) {
            try {
                $cached = $this->redis->get($key);
                if ($cached !== false) {
                    // Store in memory cache for next request
                    $this->memoryCache[$key] = [
                        'data' => $cached,
                        'expires' => time() + 60 // 1 minute in memory
                    ];
                    $this->stats['hits']++;
                    return $cached;
                }
            } catch (Exception $e) {
                if (DEBUG_MODE) {
                    error_log('Redis get error: ' . $e->getMessage());
                }
            }
        }
        
        // 3. Check file cache (slower)
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $data = unserialize($content);
            
            if ($data !== false && $data['expires'] > time()) {
                // Store in higher tier caches
                if ($this->redis) {
                    try {
                        $this->redis->setex($key, $data['expires'] - time(), $data['data']);
                    } catch (Exception $e) {
                        // Ignore Redis errors
                    }
                }
                
                $this->memoryCache[$key] = [
                    'data' => $data['data'],
                    'expires' => time() + 60
                ];
                
                $this->stats['hits']++;
                return $data['data'];
            } else {
                // Expired, delete file
                unlink($filePath);
            }
        }
        
        $this->stats['misses']++;
        return null;
    }
    
    /**
     * Set cached value across all tiers
     */
    public function set(string $key, mixed $value, int $ttl = 300): bool {
        $expires = time() + $ttl;
        
        // 1. Set in memory cache
        $this->memoryCache[$key] = [
            'data' => $value,
            'expires' => $expires
        ];
        
        // 2. Set in Redis cache
        if ($this->redis) {
            try {
                $this->redis->setex($key, $ttl, $value);
            } catch (Exception $e) {
                if (DEBUG_MODE) {
                    error_log('Redis set error: ' . $e->getMessage());
                }
            }
        }
        
        // 3. Set in file cache
        $filePath = $this->getCacheFilePath($key);
        $data = serialize([
            'data' => $value,
            'expires' => $expires,
            'created' => time()
        ]);
        
        $result = file_put_contents($filePath, $data, LOCK_EX) !== false;
        
        if ($result) {
            $this->stats['sets']++;
        }
        
        return $result;
    }
    
    /**
     * Delete from all cache tiers
     */
    public function delete(string $key): bool {
        $success = true;
        
        // Remove from memory
        unset($this->memoryCache[$key]);
        
        // Remove from Redis
        if ($this->redis) {
            try {
                $this->redis->del($key);
            } catch (Exception $e) {
                $success = false;
                if (DEBUG_MODE) {
                    error_log('Redis delete error: ' . $e->getMessage());
                }
            }
        }
        
        // Remove from file
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            $success = unlink($filePath) && $success;
        }
        
        return $success;
    }
    
    /**
     * Clear all caches
     */
    public function clear(): bool {
        $success = true;
        
        // Clear memory
        $this->memoryCache = [];
        
        // Clear Redis
        if ($this->redis) {
            try {
                $this->redis->flushDB();
            } catch (Exception $e) {
                $success = false;
            }
        }
        
        // Clear file cache
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = unlink($file) && $success;
            }
        }
        
        return $success;
    }
    
    /**
     * Cache with tags for easy invalidation
     */
    public function setWithTags(string $key, mixed $value, array $tags, int $ttl = 300): bool {
        $result = $this->set($key, $value, $ttl);
        
        if ($result) {
            foreach ($tags as $tag) {
                $tagKey = 'tag:' . $tag;
                $taggedKeys = $this->get($tagKey) ?: [];
                $taggedKeys[] = $key;
                $this->set($tagKey, array_unique($taggedKeys), $ttl);
            }
        }
        
        return $result;
    }
    
    /**
     * Invalidate cache by tag
     */
    public function invalidateTag(string $tag): bool {
        $tagKey = 'tag:' . $tag;
        $taggedKeys = $this->get($tagKey) ?: [];
        
        foreach ($taggedKeys as $key) {
            $this->delete($key);
        }
        
        return $this->delete($tagKey);
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total * 100) : 0;
        
        return array_merge($this->stats, [
            'hit_rate' => round($hitRate, 2),
            'memory_items' => count($this->memoryCache),
            'redis_connected' => $this->redis !== null
        ]);
    }
    
    /**
     * Cleanup expired cache entries
     */
    public function cleanup(): int {
        $cleaned = 0;
        
        // Cleanup memory cache
        foreach ($this->memoryCache as $key => $item) {
            if ($item['expires'] <= time()) {
                unset($this->memoryCache[$key]);
                $cleaned++;
            }
        }
        
        // Cleanup file cache
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $content = file_get_contents($file);
                $data = unserialize($content);
                
                if ($data === false || $data['expires'] <= time()) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFilePath(string $key): string {
        return $this->cacheDir . md5($key) . '.cache';
    }
}
?>
