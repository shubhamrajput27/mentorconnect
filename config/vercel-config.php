<?php
/**
 * Vercel-specific configuration for MentorConnect
 * Optimized for serverless deployment
 */

// Performance: Start output buffering early
if (!ob_get_level()) {
    ob_start();
}

// Application Constants for Vercel
define('APP_NAME', 'MentorConnect');
define('APP_VERSION', '2.1.0');

// Dynamic BASE_URL for Vercel
if (isset($_SERVER['HTTP_HOST'])) {
    define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL', $_ENV['APP_URL'] ?? 'https://mentorconnect-platform.vercel.app');
}

// Environment settings
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('DEBUG_MODE', $_ENV['APP_DEBUG'] === 'true');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_EXPIRE', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// File upload settings (limited for serverless)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB for Vercel
define('UPLOAD_DIR', '/tmp/uploads'); // Temporary storage on Vercel

class VercelConfig {
    public static function getDatabaseConfig() {
        // Use environment variables for Vercel deployment
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'dbname' => $_ENV['DB_NAME'] ?? 'mentorconnect',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    }
    
    public static function isVercelEnvironment() {
        return isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || 
               (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false);
    }
    
    public static function getAppUrl() {
        if (self::isVercelEnvironment()) {
            return 'https://' . $_SERVER['HTTP_HOST'];
        }
        return $_ENV['APP_URL'] ?? 'http://localhost';
    }
}

// Vercel-optimized database connection
class VercelDB {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = VercelConfig::getDatabaseConfig();
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            // Fallback for serverless environment
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            // Return results for SELECT queries
            if (stripos($sql, 'SELECT') === 0) {
                return $stmt->fetchAll();
            }
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Global database function for Vercel
function getDB() {
    return VercelDB::getInstance();
}

// Environment-specific settings
if (VercelConfig::isVercelEnvironment()) {
    // Vercel-specific settings
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    
    // Session configuration for serverless
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    
    // Set timezone
    date_default_timezone_set('UTC');
}

// Include utility functions
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Serverless-specific session configuration
if (VercelConfig::isVercelEnvironment()) {
    // Session configuration for serverless
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    
    // Error handling for production
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    
    // Set timezone
    date_default_timezone_set('UTC');
}

// Cache functions for compatibility (simplified for serverless)
if (!function_exists('cache_stats')) {
    function cache_stats() {
        return [
            'hits' => 0,
            'misses' => 0,
            'uptime' => time(),
            'memory_usage' => memory_get_usage(),
            'enabled' => false
        ];
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting current user: " . $e->getMessage());
            return null;
        }
    }
}

// Start session for Vercel
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>