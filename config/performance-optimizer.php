<?php
/**
 * MentorConnect Performance Optimization Configuration
 * Implements comprehensive performance optimizations
 */

class PerformanceOptimizer {
    private static $instance = null;
    private $cache = [];
    private $queryCache = [];
    private $compressionEnabled = false;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initializeOptimizations();
    }

    private function initializeOptimizations() {
        // Enable output compression
        if (extension_loaded('zlib') && !ob_get_length()) {
            ob_start('ob_gzhandler');
            $this->compressionEnabled = true;
        }

        // Set performance headers
        $this->setPerformanceHeaders();
        
        // Initialize caching
        $this->initializeCache();
    }

    private function setPerformanceHeaders() {
        // Cache control for static assets
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$/i', $_SERVER['REQUEST_URI'])) {
            header('Cache-Control: public, max-age=31536000'); // 1 year
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        } else {
            // Dynamic content
            header('Cache-Control: private, max-age=0, must-revalidate');
        }

        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Performance hints
        header('Link: </assets/css/critical-optimized.css>; rel=preload; as=style');
        header('Link: </assets/js/optimized-core.js>; rel=preload; as=script');
    }

    private function initializeCache() {
        // Simple file-based cache for development
        $cacheDir = __DIR__ . '/../cache/performance';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }

    public function cacheQuery($key, $query, $params = []) {
        $cacheKey = md5($query . serialize($params));
        $cacheFile = __DIR__ . '/../cache/performance/query_' . $cacheKey . '.cache';
        
        // Check if cache exists and is valid (5 minutes)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            return unserialize(file_get_contents($cacheFile));
        }

        // Execute query and cache result
        try {
            $database = Database::getInstance();
            $stmt = $database->getConnection()->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cache the result
            file_put_contents($cacheFile, serialize($result));
            
            return $result;
        } catch (Exception $e) {
            error_log("Query cache error: " . $e->getMessage());
            return false;
        }
    }

    public function optimizeImages($imagePath, $quality = 85) {
        $pathInfo = pathinfo($imagePath);
        $optimizedPath = $pathInfo['dirname'] . '/optimized_' . $pathInfo['filename'] . '.webp';
        
        if (file_exists($optimizedPath) && filemtime($optimizedPath) > filemtime($imagePath)) {
            return $optimizedPath;
        }

        // Convert to WebP if supported
        if (extension_loaded('gd')) {
            $sourceImage = null;
            
            switch (strtolower($pathInfo['extension'])) {
                case 'jpg':
                case 'jpeg':
                    $sourceImage = imagecreatefromjpeg($imagePath);
                    break;
                case 'png':
                    $sourceImage = imagecreatefrompng($imagePath);
                    break;
                case 'gif':
                    $sourceImage = imagecreatefromgif($imagePath);
                    break;
            }
            
            if ($sourceImage && function_exists('imagewebp')) {
                imagewebp($sourceImage, $optimizedPath, $quality);
                imagedestroy($sourceImage);
                return $optimizedPath;
            }
        }
        
        return $imagePath; // Return original if optimization fails
    }

    public function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove unnecessary spaces
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*/', ';', $css);
        $css = preg_replace('/,\s*/', ',', $css);
        
        return trim($css);
    }

    public function minifyJS($js) {
        // Simple JavaScript minification
        // Remove comments
        $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/', '', $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/\s*([{}();,:])\s*/', '$1', $js);
        
        return trim($js);
    }

    public function generateCriticalCSS($url) {
        // This would typically use a headless browser to extract critical CSS
        // For now, return the critical CSS file we created
        return file_get_contents(__DIR__ . '/../assets/css/critical-optimized.css');
    }

    public function preloadResources() {
        $preloadResources = [
            ['href' => '/assets/css/critical-optimized.css', 'as' => 'style'],
            ['href' => '/assets/js/optimized-core.js', 'as' => 'script'],
            ['href' => '/assets/fonts/inter-var.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
        ];

        foreach ($preloadResources as $resource) {
            $preload = '<link rel="preload" href="' . $resource['href'] . '" as="' . $resource['as'] . '"';
            if (isset($resource['type'])) {
                $preload .= ' type="' . $resource['type'] . '"';
            }
            if (isset($resource['crossorigin'])) {
                $preload .= ' crossorigin="' . $resource['crossorigin'] . '"';
            }
            $preload .= '>';
            
            echo $preload . "\n";
        }
    }

    public function cleanupCache($maxAge = 3600) {
        $cacheDir = __DIR__ . '/../cache/performance';
        if (!is_dir($cacheDir)) return;

        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }

    public function getPerformanceMetrics() {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'compression_enabled' => $this->compressionEnabled,
            'cached_queries' => count($this->queryCache),
            'cache_hits' => array_sum($this->queryCache)
        ];
    }
}

// Database optimization functions
function fetchOptimized($query, $params = []) {
    $optimizer = PerformanceOptimizer::getInstance();
    $cacheKey = md5($query . serialize($params));
    
    // Try cache first
    $result = $optimizer->cacheQuery($cacheKey, $query, $params);
    if ($result !== false) {
        return $result;
    }
    
    // Fallback to regular fetch
    return fetchAll($query, $params);
}

function fetchOneOptimized($query, $params = []) {
    $result = fetchOptimized($query, $params);
    return !empty($result) ? $result[0] : null;
}

// Resource optimization middleware
function optimizeAndServeAsset($assetPath) {
    $optimizer = PerformanceOptimizer::getInstance();
    $pathInfo = pathinfo($assetPath);
    
    switch (strtolower($pathInfo['extension'])) {
        case 'css':
            header('Content-Type: text/css');
            $css = file_get_contents($assetPath);
            echo $optimizer->minifyCSS($css);
            break;
            
        case 'js':
            header('Content-Type: application/javascript');
            $js = file_get_contents($assetPath);
            echo $optimizer->minifyJS($js);
            break;
            
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            $optimizedPath = $optimizer->optimizeImages($assetPath);
            if (file_exists($optimizedPath)) {
                header('Content-Type: image/webp');
                readfile($optimizedPath);
            } else {
                readfile($assetPath);
            }
            break;
            
        default:
            readfile($assetPath);
    }
}

// Initialize performance optimizer
$performanceOptimizer = PerformanceOptimizer::getInstance();

// Cleanup old cache files periodically
if (rand(1, 100) === 1) {
    $performanceOptimizer->cleanupCache();
}
?>
