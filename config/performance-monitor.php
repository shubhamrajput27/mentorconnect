<?php
/**
 * Performance Monitoring and Optimization Class
 */

class PerformanceMonitor {
    private static $metrics = [];
    private static $startTime;
    private static $memoryUsage = [];
    
    public static function start() {
        self::$startTime = microtime(true);
        self::$memoryUsage['start'] = memory_get_usage(true);
        self::$metrics = [];
    }
    
    public static function mark($label) {
        self::$metrics[$label] = [
            'time' => microtime(true) - self::$startTime,
            'memory' => memory_get_usage(true)
        ];
    }
    
    public static function end() {
        self::$memoryUsage['end'] = memory_get_usage(true);
        self::$memoryUsage['peak'] = memory_get_peak_usage(true);
        
        return [
            'total_time' => microtime(true) - self::$startTime,
            'memory_usage' => self::$memoryUsage,
            'metrics' => self::$metrics
        ];
    }
    
    public static function getPagePerformance() {
        $performance = self::end();
        
        return [
            'page_load_time' => round($performance['total_time'] * 1000, 2) . 'ms',
            'memory_used' => self::formatBytes($performance['memory_usage']['end'] - $performance['memory_usage']['start']),
            'peak_memory' => self::formatBytes($performance['memory_usage']['peak']),
            'database_stats' => DatabaseOptimizer::getCacheStats(),
            'metrics' => $performance['metrics']
        ];
    }
    
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public static function profileFunction($callback, $args = []) {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = call_user_func_array($callback, $args);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        return [
            'result' => $result,
            'execution_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'memory_used' => self::formatBytes($endMemory - $startMemory)
        ];
    }
}

/**
 * Asset Optimization Class
 */
class AssetOptimizer {
    private static $cssFiles = [];
    private static $jsFiles = [];
    
    public static function addCss($file, $priority = 10) {
        self::$cssFiles[] = [
            'file' => $file,
            'priority' => $priority,
            'version' => filemtime($file)
        ];
    }
    
    public static function addJs($file, $priority = 10, $defer = true) {
        self::$jsFiles[] = [
            'file' => $file,
            'priority' => $priority,
            'defer' => $defer,
            'version' => filemtime($file)
        ];
    }
    
    public static function getCssLinks() {
        // Sort by priority
        usort(self::$cssFiles, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        $links = '';
        foreach (self::$cssFiles as $css) {
            $links .= sprintf(
                '<link rel="stylesheet" href="%s?v=%s">' . "\n",
                $css['file'],
                $css['version']
            );
        }
        
        return $links;
    }
    
    public static function getJsLinks() {
        // Sort by priority
        usort(self::$jsFiles, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        $scripts = '';
        foreach (self::$jsFiles as $js) {
            $defer = $js['defer'] ? ' defer' : '';
            $scripts .= sprintf(
                '<script src="%s?v=%s"%s></script>' . "\n",
                $js['file'],
                $js['version'],
                $defer
            );
        }
        
        return $scripts;
    }
    
    public static function minifyCss($content) {
        // Remove comments
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        
        // Remove unnecessary whitespace
        $content = str_replace(["\r\n", "\r", "\n", "\t"], '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = str_replace(['; ', ' {', '{ ', ' }', '} ', ': ', ', '], [';', '{', '{', '}', '}', ':', ','], $content);
        
        return trim($content);
    }
    
    public static function minifyJs($content) {
        // Basic JS minification (for production, use a proper minifier)
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content); // Remove multi-line comments
        $content = preg_replace('/\/\/.*$/', '', $content); // Remove single-line comments
        $content = preg_replace('/\s+/', ' ', $content); // Compress whitespace
        
        return trim($content);
    }
}

/**
 * Cache Management Class
 */
class CacheManager {
    private static $cacheDir = 'cache/';
    
    public static function init() {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    public static function set($key, $data, $expiration = 3600) {
        self::init();
        
        $cacheFile = self::$cacheDir . md5($key) . '.cache';
        $cacheData = [
            'data' => $data,
            'expiration' => time() + $expiration
        ];
        
        return file_put_contents($cacheFile, serialize($cacheData)) !== false;
    }
    
    public static function get($key) {
        self::init();
        
        $cacheFile = self::$cacheDir . md5($key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $cacheData = unserialize(file_get_contents($cacheFile));
        
        if (time() > $cacheData['expiration']) {
            unlink($cacheFile);
            return null;
        }
        
        return $cacheData['data'];
    }
    
    public static function delete($key) {
        self::init();
        
        $cacheFile = self::$cacheDir . md5($key) . '.cache';
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    public static function clear() {
        self::init();
        
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    public static function getStats() {
        self::init();
        
        $files = glob(self::$cacheDir . '*.cache');
        $totalSize = 0;
        $expiredCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $cacheData = unserialize(file_get_contents($file));
            if (time() > $cacheData['expiration']) {
                $expiredCount++;
            }
        }
        
        return [
            'total_files' => count($files),
            'total_size' => AssetOptimizer::formatBytes($totalSize),
            'expired_files' => $expiredCount
        ];
    }
}

/**
 * Image Optimization Class
 */
class ImageOptimizer {
    public static function optimize($imagePath, $quality = 80) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Create optimized version
        $optimizedPath = pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . 
                        pathinfo($imagePath, PATHINFO_FILENAME) . '_optimized.' . 
                        pathinfo($imagePath, PATHINFO_EXTENSION);
        
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $optimizedPath, $quality);
                break;
            case 'image/png':
                imagepng($image, $optimizedPath, 9 - (int)($quality / 10));
                break;
            case 'image/gif':
                imagegif($image, $optimizedPath);
                break;
        }
        
        imagedestroy($image);
        
        return $optimizedPath;
    }
    
    public static function generateWebP($imagePath) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        $webpPath = pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . 
                   pathinfo($imagePath, PATHINFO_FILENAME) . '.webp';
        
        $result = imagewebp($image, $webpPath, 80);
        imagedestroy($image);
        
        return $result ? $webpPath : false;
    }
}

/**
 * Security Optimizer
 */
class SecurityOptimizer {
    public static function setSecurityHeaders() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
            
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }
    
    public static function generateCSP() {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'none'"
        ];
        
        return 'Content-Security-Policy: ' . implode('; ', $csp);
    }
}
?>
