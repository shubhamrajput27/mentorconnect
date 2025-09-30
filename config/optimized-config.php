<?php
/**
 * Optimized Configuration for MentorConnect
 * Modern, secure, and performant configuration system
 */

// Performance: Start output buffering early
if (!ob_get_level()) {
    ob_start();
}

// Load autoloader first for optimal performance
require_once __DIR__ . '/autoloader.php';

// Load utility functions
require_once __DIR__ . '/../includes/functions.php';

// Application Constants
define('APP_NAME', 'MentorConnect');
define('APP_VERSION', '2.1.0');
define('BASE_URL', 'http://localhost/mentorconnect');
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Database Configuration - Enhanced Security
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'mentorconnect');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Security Configuration - Hardened
define('SESSION_LIFETIME', 28800); // 8 hours
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes
define('PASSWORD_MIN_LENGTH', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('SECURE_COOKIES', isset($_SERVER['HTTPS']));
define('SESSION_NAME', 'MENTORCONNECT_SESSION');

// Performance Configuration - Optimized
define('ENABLE_QUERY_CACHE', true);
define('CACHE_LIFETIME', 300); // 5 minutes
define('ENABLE_GZIP', true);
define('ENABLE_OPCACHE', function_exists('opcache_get_status'));
define('MAX_UPLOAD_SIZE', '10M');
define('MEMORY_LIMIT', '256M');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'txt', 'rtf']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Optimize PHP settings
ini_set('memory_limit', MEMORY_LIMIT);
ini_set('max_execution_time', 30);
ini_set('upload_max_filesize', MAX_UPLOAD_SIZE);
ini_set('post_max_size', MAX_UPLOAD_SIZE);
ini_set('max_input_time', 30);

// Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (SECURE_COOKIES) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Enhanced Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    
    $sessionConfig = [
        'cookie_lifetime' => SESSION_LIFETIME,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => SECURE_COOKIES,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_cookies' => true,
        'use_only_cookies' => true,
        'cache_limiter' => 'nocache'
    ];
    
    foreach ($sessionConfig as $key => $value) {
        ini_set("session.$key", $value);
    }
    
    session_start();
    
    // Regenerate session ID for security
    if (!isset($_SESSION['last_regeneration']) || 
        time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Optimized Database Connection with Connection Pooling
class OptimizedDB {
    private static $instance = null;
    private static $connections = [];
    private static $maxConnections = 5;
    private static $connectionCount = 0;
    public static $queryStats = ['total' => 0, 'time' => 0];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // Reuse existing connection if available
        foreach (self::$connections as $id => $conn) {
            if (!$conn['in_use']) {
                $conn['in_use'] = true;
                $conn['last_used'] = time();
                return $conn['pdo'];
            }
        }
        
        // Create new connection if under limit
        if (self::$connectionCount < self::$maxConnections) {
            return $this->createConnection();
        }
        
        // Wait for available connection (blocking)
        usleep(10000); // 10ms
        return $this->getConnection();
    }
    
    private function createConnection() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET . ' COLLATE ' . DB_COLLATION,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            $connId = uniqid();
            self::$connections[$connId] = [
                'pdo' => $pdo,
                'in_use' => true,
                'created' => time(),
                'last_used' => time()
            ];
            
            self::$connectionCount++;
            return $pdo;
            
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Database connection failed: " . $e->getMessage());
                die('Database connection failed: ' . $e->getMessage());
            } else {
                die('Database connection failed. Please try again later.');
            }
        }
    }
    
    public function releaseConnection($pdo) {
        foreach (self::$connections as $id => $conn) {
            if ($conn['pdo'] === $pdo) {
                $conn['in_use'] = false;
                $conn['last_used'] = time();
                break;
            }
        }
    }
    
    public function getStats() {
        return [
            'active_connections' => self::$connectionCount,
            'total_queries' => self::$queryStats['total'],
            'total_query_time' => self::$queryStats['time']
        ];
    }
}

// Initialize database connection
$db = OptimizedDB::getInstance();
$pdo = $db->getConnection();

// Enhanced Database Functions with Prepared Statement Caching
$preparedStatements = [];

function executeQuery($sql, $params = []) {
    global $pdo, $preparedStatements, $db;
    
    $startTime = microtime(true);
    OptimizedDB::$queryStats['total']++;
    
    try {
        // Use cached prepared statement if available
        $stmtHash = md5($sql);
        if (!isset($preparedStatements[$stmtHash])) {
            $preparedStatements[$stmtHash] = $pdo->prepare($sql);
        }
        
        $stmt = $preparedStatements[$stmtHash];
        $stmt->execute($params);
        
        $duration = microtime(true) - $startTime;
        OptimizedDB::$queryStats['time'] += $duration;
        
        // Log slow queries
        if ($duration > 0.1 && DEBUG_MODE) {
            error_log("SLOW QUERY ({$duration}s): {$sql}");
        }
        
        return $stmt;
        
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            error_log("Database Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        }
        throw $e;
    }
}

function fetchOne($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
    // Try cache first if enabled
    if ($cacheKey && ENABLE_QUERY_CACHE && function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    }
    
    $stmt = executeQuery($sql, $params);
    $result = $stmt->fetch();
    
    // Cache the result
    if ($cacheKey && $result && ENABLE_QUERY_CACHE && function_exists('apcu_store')) {
        apcu_store($cacheKey, $result, $cacheTTL);
    }
    
    return $result ?: null;
}

function fetchAll($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
    // Try cache first if enabled
    if ($cacheKey && ENABLE_QUERY_CACHE && function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    }
    
    $stmt = executeQuery($sql, $params);
    $result = $stmt->fetchAll();
    
    // Cache the result
    if ($cacheKey && ENABLE_QUERY_CACHE && function_exists('apcu_store')) {
        apcu_store($cacheKey, $result, $cacheTTL);
    }
    
    return $result;
}

function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

// Enhanced Security Functions
function requireLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php?timeout=1');
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}

function requireRole($role) {
    requireLogin();
    $user = getCurrentUser();
    
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        die('Access denied: Insufficient privileges');
    }
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $cacheKey = 'user_' . $_SESSION['user_id'];
    return fetchOne(
        "SELECT id, username, email, first_name, last_name, role, status, profile_photo 
         FROM users WHERE id = ? AND status = 'active'",
        [$_SESSION['user_id']],
        $cacheKey,
        600 // Cache for 10 minutes
    );
}

// Enhanced Input Validation
function sanitizeInput($data, $type = 'general', $maxLength = null) {
    if (is_array($data)) {
        return array_map(function($item) use ($type, $maxLength) {
            return sanitizeInput($item, $type, $maxLength);
        }, $data);
    }
    
    // Remove null bytes
    $data = str_replace(chr(0), '', $data);
    
    // Trim whitespace
    $data = trim($data);
    
    // Apply type-specific sanitization
    switch ($type) {
        case 'email':
            $data = filter_var($data, FILTER_SANITIZE_EMAIL);
            break;
        case 'url':
            $data = filter_var($data, FILTER_SANITIZE_URL);
            break;
        case 'int':
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            break;
        case 'float':
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            break;
        case 'html':
            $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            break;
        default:
            // General sanitization - remove potentially dangerous characters
            $data = preg_replace('/[<>"\']/', '', $data);
    }
    
    // Apply length limit if specified
    if ($maxLength && strlen($data) > $maxLength) {
        $data = substr($data, 0, $maxLength);
    }
    
    return $data;
}

// Performance Monitoring
function logPerformanceMetrics() {
    if (!DEBUG_MODE) return;
    
    $metrics = [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        'queries_executed' => OptimizedDB::getInstance()->getStats()['total_queries']
    ];
    
    if ($metrics['execution_time'] > 2.0) {
        error_log("SLOW REQUEST (" . $metrics['execution_time'] . "s): " . $_SERVER['REQUEST_URI']);
    }
}

// Register shutdown function for cleanup
register_shutdown_function(function() {
    logPerformanceMetrics();
    
    // Clean up prepared statements
    global $preparedStatements;
    $preparedStatements = [];
    
    // Enable output compression if available
    if (ENABLE_GZIP && extension_loaded('zlib') && !headers_sent()) {
        if (ob_get_level()) {
            ob_end_flush();
        }
    }
});

// Error Handling
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Create necessary directories
$dirs = [
    __DIR__ . '/../logs',
    __DIR__ . '/../cache/performance',
    UPLOAD_DIR
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
