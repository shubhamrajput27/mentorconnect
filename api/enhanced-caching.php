<?php
/**
 * Enhanced API Response Caching System
 * Intelligent caching with automatic invalidation and compression
 */

class EnhancedAPICaching {
    private static $instance = null;
    private $cache = [];
    private $cacheDir;
    private $compressionEnabled = true;
    private $stats = ['hits' => 0, 'misses' => 0, 'invalidations' => 0];
    
    private function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/api/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Cache API response with intelligent TTL and compression
     */
    public function cacheResponse($endpoint, $params, $data, $ttl = null) {
        $cacheKey = $this->generateCacheKey($endpoint, $params);
        
        if ($ttl === null) {
            $ttl = $this->getIntelligentTTL($endpoint, $data);
        }
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl,
            'endpoint' => $endpoint,
            'params' => $params,
            'created' => time(),
            'size' => strlen(json_encode($data))
        ];
        
        // Compress large responses
        if ($this->compressionEnabled && $cacheData['size'] > 1024) {
            $cacheData['data'] = gzcompress(json_encode($data), 6);
            $cacheData['compressed'] = true;
        } else {
            $cacheData['compressed'] = false;
        }
        
        // Memory cache
        $this->cache[$cacheKey] = $cacheData;
        
        // File cache for persistence
        $filename = $this->getCacheFilename($cacheKey);
        file_put_contents($filename, serialize($cacheData), LOCK_EX);
        
        return true;
    }
    
    /**
     * Get cached response with automatic decompression
     */
    public function getCachedResponse($endpoint, $params) {
        $cacheKey = $this->generateCacheKey($endpoint, $params);
        
        // Check memory cache first
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if ($cached['expires'] > time()) {
                $this->stats['hits']++;
                return $this->decompressData($cached);
            } else {
                unset($this->cache[$cacheKey]);
            }
        }
        
        // Check file cache
        $filename = $this->getCacheFilename($cacheKey);
        if (file_exists($filename)) {
            $cached = unserialize(file_get_contents($filename));
            if ($cached && $cached['expires'] > time()) {
                $this->cache[$cacheKey] = $cached;
                $this->stats['hits']++;
                return $this->decompressData($cached);
            } else {
                unlink($filename);
            }
        }
        
        $this->stats['misses']++;
        return null;
    }
    
    /**
     * Invalidate cache based on patterns
     */
    public function invalidateCache($pattern = null, $userId = null) {
        $invalidated = 0;
        
        if ($pattern === null && $userId === null) {
            // Clear all cache
            $this->cache = [];
            $files = glob($this->cacheDir . '*.cache');
            foreach ($files as $file) {
                unlink($file);
                $invalidated++;
            }
        } else {
            // Pattern-based invalidation
            foreach ($this->cache as $key => $cached) {
                if ($this->shouldInvalidate($cached, $pattern, $userId)) {
                    unset($this->cache[$key]);
                    $filename = $this->getCacheFilename($key);
                    if (file_exists($filename)) {
                        unlink($filename);
                    }
                    $invalidated++;
                }
            }
        }
        
        $this->stats['invalidations'] += $invalidated;
        return $invalidated;
    }
    
    /**
     * Smart cache warming for frequently accessed endpoints
     */
    public function warmCache($endpoints) {
        foreach ($endpoints as $endpoint => $config) {
            $params = $config['params'] ?? [];
            $ttl = $config['ttl'] ?? null;
            
            // Only warm if not already cached
            if (!$this->getCachedResponse($endpoint, $params)) {
                try {
                    $data = $this->fetchEndpointData($endpoint, $params);
                    if ($data) {
                        $this->cacheResponse($endpoint, $params, $data, $ttl);
                    }
                } catch (Exception $e) {
                    error_log("Cache warming failed for {$endpoint}: " . $e->getMessage());
                }
            }
        }
    }
    
    private function generateCacheKey($endpoint, $params) {
        $keyData = $endpoint . '|' . serialize($params);
        return 'api_' . md5($keyData);
    }
    
    private function getCacheFilename($cacheKey) {
        return $this->cacheDir . $cacheKey . '.cache';
    }
    
    private function getIntelligentTTL($endpoint, $data) {
        // User profiles - cache longer
        if (strpos($endpoint, 'profile') !== false) {
            return 1800; // 30 minutes
        }
        
        // Search results - cache shorter
        if (strpos($endpoint, 'search') !== false) {
            return 300; // 5 minutes
        }
        
        // Notifications - very short cache
        if (strpos($endpoint, 'notifications') !== false) {
            return 60; // 1 minute
        }
        
        // Analytics - medium cache
        if (strpos($endpoint, 'analytics') !== false) {
            return 900; // 15 minutes
        }
        
        // Static data - long cache
        if (strpos($endpoint, 'skills') !== false || strpos($endpoint, 'categories') !== false) {
            return 3600; // 1 hour
        }
        
        // Default
        return 600; // 10 minutes
    }
    
    private function decompressData($cached) {
        if ($cached['compressed']) {
            $decompressed = gzuncompress($cached['data']);
            return json_decode($decompressed, true);
        }
        return $cached['data'];
    }
    
    private function shouldInvalidate($cached, $pattern, $userId) {
        if ($pattern && strpos($cached['endpoint'], $pattern) === false) {
            return false;
        }
        
        if ($userId && (!isset($cached['params']['user_id']) || $cached['params']['user_id'] != $userId)) {
            return false;
        }
        
        return true;
    }
    
    private function fetchEndpointData($endpoint, $params) {
        // This would typically make an internal API call
        // For now, return null to indicate no data fetched
        return null;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;
        
        $memorySize = 0;
        foreach ($this->cache as $cached) {
            $memorySize += $cached['size'];
        }
        
        $fileCount = count(glob($this->cacheDir . '*.cache'));
        
        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'invalidations' => $this->stats['invalidations'],
            'hit_rate' => round($hitRate, 2),
            'memory_entries' => count($this->cache),
            'memory_size' => $memorySize,
            'file_entries' => $fileCount,
            'compression_enabled' => $this->compressionEnabled
        ];
    }
    
    /**
     * Cleanup expired cache entries
     */
    public function cleanup() {
        $now = time();
        $cleaned = 0;
        
        // Clean memory cache
        foreach ($this->cache as $key => $cached) {
            if ($cached['expires'] <= $now) {
                unset($this->cache[$key]);
                $cleaned++;
            }
        }
        
        // Clean file cache
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            $cached = unserialize(file_get_contents($file));
            if (!$cached || $cached['expires'] <= $now) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}

/**
 * Middleware for automatic API response caching
 */
class APICacheMiddleware {
    private $cache;
    
    public function __construct() {
        $this->cache = EnhancedAPICaching::getInstance();
    }
    
    public function handle($request, $response, $next) {
        $endpoint = $request->getUri()->getPath();
        $params = $request->getQueryParams();
        
        // Skip caching for POST/PUT/DELETE requests
        if (!in_array($request->getMethod(), ['GET', 'HEAD'])) {
            return $next($request, $response);
        }
        
        // Check cache first
        $cached = $this->cache->getCachedResponse($endpoint, $params);
        if ($cached !== null) {
            return $response->withJson($cached);
        }
        
        // Execute request
        $response = $next($request, $response);
        
        // Cache successful responses
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            if ($data) {
                $this->cache->cacheResponse($endpoint, $params, $data);
            }
        }
        
        return $response;
    }
}

// Global helper functions
function cacheAPIResponse($endpoint, $params, $data, $ttl = null) {
    return EnhancedAPICaching::getInstance()->cacheResponse($endpoint, $params, $data, $ttl);
}

function getCachedAPIResponse($endpoint, $params) {
    return EnhancedAPICaching::getInstance()->getCachedResponse($endpoint, $params);
}

function invalidateAPICache($pattern = null, $userId = null) {
    return EnhancedAPICaching::getInstance()->invalidateCache($pattern, $userId);
}

function getAPICacheStats() {
    return EnhancedAPICaching::getInstance()->getStats();
}

// Auto-cleanup on shutdown
register_shutdown_function(function() {
    if (class_exists('EnhancedAPICaching')) {
        EnhancedAPICaching::getInstance()->cleanup();
    }
});
?>
