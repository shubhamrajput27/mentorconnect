<?php
// Vercel-specific configuration for MentorConnect

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
        return isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']);
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
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}
?>