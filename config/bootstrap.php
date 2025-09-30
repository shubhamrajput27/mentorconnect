<?php
/**
 * Optimized Configuration Bootstrap v2.1
 * Fast, secure, and efficient configuration with new core architecture
 */

declare(strict_types=1);

// Performance: Start output buffering early
if (!ob_get_level()) {
    ob_start();
}

// Constants - Define once, use everywhere
const APP_NAME = 'MentorConnect';
const APP_VERSION = '2.1.0';
const BASE_URL = 'http://localhost/mentorconnect';
const ENVIRONMENT = 'development';
const DEBUG_MODE = ENVIRONMENT === 'development';
const APP_ROOT = __DIR__ . '/..';

// Create logs directory if it doesn't exist
$logsDir = APP_ROOT . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Database Configuration - Updated for new DatabaseManager
const DB_CONFIG = [
    'host' => 'localhost',
    'dbname' => 'mentorconnect',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'
    ]
];

// Security Configuration - Enhanced
const SECURITY_CONFIG = [
    'session_lifetime' => 3600,
    'csrf_token_lifetime' => 300,
    'password_min_length' => 8,
    'max_login_attempts' => 10,
    'login_lockout_time' => 60,
    'session_name' => 'MENTORCONNECT_SESSION',
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
];

// Performance Configuration - Enhanced
const PERF_CONFIG = [
    'enable_query_cache' => true,
    'cache_lifetime' => 300,
    'enable_gzip' => true,
    'enable_opcache' => true,
    'max_memory_usage' => '256M',
    'max_execution_time' => 30
];

// Set timezone and error reporting
date_default_timezone_set('UTC');
ini_set('memory_limit', PERF_CONFIG['max_memory_usage']);
ini_set('max_execution_time', (string)PERF_CONFIG['max_execution_time']);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', $logsDir . '/error.log');
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $logsDir . '/error.log');
}

// Session configuration for security
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', SECURITY_CONFIG['cookie_secure'] ? '1' : '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', SECURITY_CONFIG['cookie_samesite']);

// Autoloader - PSR-4 compliant with new core classes
spl_autoload_register(function (string $className): void {
    $baseDir = __DIR__ . '/';
    $classMap = [
        'DatabaseManager' => 'core/DatabaseManager.php',
        'SessionManager' => 'core/SessionManager.php',
        'SecurityManager' => 'core/SecurityManager.php',
        'CacheManager' => 'core/CacheManager.php',
        'PerformanceMonitor' => 'core/PerformanceMonitor.php'
    ];
    
    if (isset($classMap[$className])) {
        $file = $baseDir . $classMap[$className];
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Enhanced Application Container with Dependency Injection
class App {
    private static ?DatabaseManager $db = null;
    private static ?SessionManager $session = null;
    private static ?SecurityManager $security = null;
    private static ?CacheManager $cache = null;
    private static ?PerformanceMonitor $monitor = null;
    
    public static function db(): DatabaseManager {
        return self::$db ??= new DatabaseManager(DB_CONFIG);
    }
    
    public static function session(): SessionManager {
        return self::$session ??= new SessionManager(self::db());
    }
    
    public static function security(): SecurityManager {
        return self::$security ??= new SecurityManager(self::cache());
    }
    
    public static function cache(): CacheManager {
        return self::$cache ??= new CacheManager();
    }
    
    public static function monitor(): PerformanceMonitor {
        return self::$monitor ??= new PerformanceMonitor(self::cache());
    }
    
    /**
     * Get configuration value
     */
    public static function config(string $section, string $key = null): mixed {
        $configs = [
            'database' => DB_CONFIG,
            'security' => SECURITY_CONFIG,
            'performance' => PERF_CONFIG
        ];
        
        if (!isset($configs[$section])) {
            return null;
        }
        
        return $key ? ($configs[$section][$key] ?? null) : $configs[$section];
    }
}

// Initialize critical components with performance monitoring
App::monitor()->startTimer('app_init');
App::monitor()->addMemoryCheckpoint('init_start');

// Start session
App::session()->start();

// Security headers - Enhanced CSP
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    // Enhanced Content Security Policy
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
           "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self'; " .
           "frame-ancestors 'none'";
    
    header("Content-Security-Policy: {$csp}");
    
    if (!DEBUG_MODE && SECURITY_CONFIG['cookie_secure']) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

// Complete initialization
App::monitor()->addMemoryCheckpoint('init_end');
App::monitor()->stopTimer('app_init');

// Global error handler with enhanced logging
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error = [
        'type' => 'error',
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'timestamp' => time(),
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'memory_usage' => memory_get_usage(true)
    ];
    
    error_log('PHP Error: ' . json_encode($error));
    
    // Log to security manager for monitoring
    if (class_exists('App')) {
        App::security()->logSecurityEvent('php_error', $error);
    }
    
    if (DEBUG_MODE) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px; font-family: monospace; font-size: 12px;'>";
        echo "<strong>Error:</strong> {$message}<br>";
        echo "<strong>File:</strong> {$file}<br>";
        echo "<strong>Line:</strong> {$line}<br>";
        echo "<strong>Memory:</strong> " . number_format(memory_get_usage(true)) . " bytes";
        echo "</div>";
    }
    
    return true;
});

// Exception handler
set_exception_handler(function($exception) {
    $error = [
        'type' => 'exception',
        'class' => get_class($exception),
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'timestamp' => time(),
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'memory_usage' => memory_get_usage(true)
    ];
    
    error_log('PHP Exception: ' . json_encode($error));
    
    // Log to security manager for monitoring
    if (class_exists('App')) {
        App::security()->logSecurityEvent('php_exception', $error);
    }
    
    if (DEBUG_MODE) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px; font-family: monospace;'>";
        echo "<h3 style='color: #d32f2f; margin: 0 0 10px 0;'>Exception: " . get_class($exception) . "</h3>";
        echo "<strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Memory:</strong> " . number_format(memory_get_usage(true)) . " bytes<br>";
        echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre></details>";
        echo "</div>";
    } else {
        http_response_code(500);
        echo "<h1>Application Error</h1><p>An error occurred. Please try again later.</p>";
    }
});

?>
