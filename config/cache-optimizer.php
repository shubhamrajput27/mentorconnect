<?php
/**
 * Advanced Cache Optimizer for MentorConnect
 * Implements intelligent caching with performance monitoring
 */

class CacheOptimizer {
    private static $instance = null;
    private $cache = [];
    private $cacheStats = ['hits' => 0, 'misses' => 0];
    private $cacheDirectory;
    private $maxCacheSize = 50 * 1024 * 1024; // 50MB
    
    private function __construct() {
        $this->cacheDirectory = __DIR__ . '/../cache/';
        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cached data with intelligent TTL
     */
    public function get($key, $defaultTTL = 300) {
        // Memory cache first
        if (isset($this->cache[$key])) {
            if ($this->cache[$key]['expires'] > time()) {
                $this->cacheStats['hits']++;
                return $this->cache[$key]['data'];
            } else {
                unset($this->cache[$key]);
            }
        }
        
        // File cache second
        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            $data = file_get_contents($filename);
            $cached = unserialize($data);
            
            if ($cached && $cached['expires'] > time()) {
                $this->cache[$key] = $cached;
                $this->cacheStats['hits']++;
                return $cached['data'];
            } else {
                unlink($filename);
            }
        }
        
        $this->cacheStats['misses']++;
        return false;
    }
    
    /**
     * Set cache with intelligent TTL based on data type
     */
    public function set($key, $data, $ttl = null) {
        if ($ttl === null) {
            $ttl = $this->getIntelligentTTL($key, $data);
        }
        
        $expires = time() + $ttl;
        $cached = ['data' => $data, 'expires' => $expires];
        
        // Memory cache
        $this->cache[$key] = $cached;
        
        // File cache for persistence
        $filename = $this->getCacheFilename($key);
        file_put_contents($filename, serialize($cached), LOCK_EX);
        
        // Cleanup if cache is too large
        $this->cleanupCache();
        
        return true;
    }
    
    /**
     * Intelligent TTL based on data patterns
     */
    private function getIntelligentTTL($key, $data) {
        // User profiles - cache longer
        if (strpos($key, 'user_profile_') === 0) {
            return 1800; // 30 minutes
        }
        
        // Search results - cache shorter
        if (strpos($key, 'search_') === 0) {
            return 300; // 5 minutes
        }
        
        // Static data - cache very long
        if (strpos($key, 'skills_') === 0 || strpos($key, 'categories_') === 0) {
            return 3600; // 1 hour
        }
        
        // Analytics data - cache medium
        if (strpos($key, 'analytics_') === 0) {
            return 900; // 15 minutes
        }
        
        // Default
        return 300; // 5 minutes
    }
    
    /**
     * Delete cache entry
     */
    public function delete($key) {
        unset($this->cache[$key]);
        $filename = $this->getCacheFilename($key);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $this->cache = [];
        $files = glob($this->cacheDirectory . 'cache_*.tmp');
        foreach ($files as $file) {
            unlink($file);
        }
        $this->cacheStats = ['hits' => 0, 'misses' => 0];
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $total = $this->cacheStats['hits'] + $this->cacheStats['misses'];
        $hitRatio = $total > 0 ? ($this->cacheStats['hits'] / $total) * 100 : 0;
        
        return [
            'hits' => $this->cacheStats['hits'],
            'misses' => $this->cacheStats['misses'],
            'hit_ratio' => round($hitRatio, 2),
            'memory_entries' => count($this->cache),
            'cache_size' => $this->getCacheSize()
        ];
    }
    
    /**
     * Cache with callback for miss
     */
    public function remember($key, $callback, $ttl = null) {
        $data = $this->get($key);
        if ($data !== false) {
            return $data;
        }
        
        $data = $callback();
        $this->set($key, $data, $ttl);
        return $data;
    }
    
    private function getCacheFilename($key) {
        return $this->cacheDirectory . 'cache_' . md5($key) . '.tmp';
    }
    
    private function getCacheSize() {
        $size = 0;
        $files = glob($this->cacheDirectory . 'cache_*.tmp');
        foreach ($files as $file) {
            $size += filesize($file);
        }
        return $size;
    }
    
    private function cleanupCache() {
        $currentSize = $this->getCacheSize();
        
        if ($currentSize > $this->maxCacheSize) {
            $files = glob($this->cacheDirectory . 'cache_*.tmp');
            
            // Sort by access time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files until under limit
            $targetSize = $this->maxCacheSize * 0.8; // Remove to 80% of limit
            foreach ($files as $file) {
                if ($currentSize <= $targetSize) break;
                
                $currentSize -= filesize($file);
                unlink($file);
            }
        }
        
        // Remove expired entries
        $files = glob($this->cacheDirectory . 'cache_*.tmp');
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cached = unserialize($data);
            
            if (!$cached || $cached['expires'] <= time()) {
                unlink($file);
            }
        }
    }
}

/**
 * Global cache helper functions
 */
function cache_get($key, $ttl = 300) {
    return CacheOptimizer::getInstance()->get($key, $ttl);
}

function cache_set($key, $data, $ttl = null) {
    return CacheOptimizer::getInstance()->set($key, $data, $ttl);
}

function cache_remember($key, $callback, $ttl = null) {
    return CacheOptimizer::getInstance()->remember($key, $callback, $ttl);
}

function cache_delete($key) {
    return CacheOptimizer::getInstance()->delete($key);
}

function cache_clear() {
    return CacheOptimizer::getInstance()->clear();
}

function cache_stats() {
    return CacheOptimizer::getInstance()->getStats();
}
?>
