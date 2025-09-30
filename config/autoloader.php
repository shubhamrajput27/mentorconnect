<?php
/**
 * Modern PHP Autoloader for MentorConnect
 * PSR-4 compliant autoloading system
 */

class MentorConnectAutoloader {
    private static $classMap = [];
    private static $namespaces = [];
    private static $registered = false;
    
    public static function register() {
        if (!self::$registered) {
            spl_autoload_register([self::class, 'loadClass'], true, true);
            self::$registered = true;
            self::initializeClassMap();
        }
    }
    
    private static function initializeClassMap() {
        $baseDir = dirname(__DIR__);
        
        // Core namespace mappings
        self::$namespaces = [
            'MentorConnect\\Core\\' => $baseDir . '/config/core/',
            'MentorConnect\\Controllers\\' => $baseDir . '/controllers/',
            'MentorConnect\\Api\\' => $baseDir . '/api/',
            'MentorConnect\\Utils\\' => $baseDir . '/includes/',
            'MentorConnect\\' => $baseDir . '/src/'
        ];
        
        // Critical class mappings for performance
        self::$classMap = [
            'DatabaseManager' => $baseDir . '/config/core/DatabaseManager.php',
            'CacheManager' => $baseDir . '/config/core/CacheManager.php',
            'SecurityManager' => $baseDir . '/config/core/SecurityManager.php',
            'SessionManager' => $baseDir . '/config/core/SessionManager.php',
            'PerformanceMonitor' => $baseDir . '/config/core/PerformanceMonitor.php',
            'UserController' => $baseDir . '/controllers/UserController.php'
        ];
    }
    
    public static function loadClass($className) {
        // Try direct class mapping first (fastest)
        if (isset(self::$classMap[$className])) {
            $file = self::$classMap[$className];
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        
        // Try namespace-based loading
        foreach (self::$namespaces as $prefix => $baseDir) {
            if (strncmp($prefix, $className, strlen($prefix)) === 0) {
                $relativeClass = substr($className, strlen($prefix));
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }
        
        // Legacy fallback for existing classes
        $legacyMappings = [
            'EnhancedDatabase' => '/config/enhanced-database.php',
            'PerformanceOptimizer' => '/config/performance-optimizer.php',
            'ConnectionManager' => '/api/connections.php'
        ];
        
        if (isset($legacyMappings[$className])) {
            $file = dirname(__DIR__) . $legacyMappings[$className];
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        
        return false;
    }
    
    public static function addClassMapping($className, $filePath) {
        self::$classMap[$className] = $filePath;
    }
    
    public static function addNamespace($namespace, $directory) {
        self::$namespaces[$namespace] = rtrim($directory, '/\\') . '/';
    }
}

// Register the autoloader
MentorConnectAutoloader::register();
