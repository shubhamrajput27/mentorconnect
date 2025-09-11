<?php
/**
 * Enhanced Database Connection and Query Optimization
 * Advanced connection pooling and query caching for MentorConnect
 */

class EnhancedDatabase {
    private static $instance = null;
    private $connections = [];
    private $queryCache = [];
    private $connectionPool = [];
    private $maxConnections = 10;
    private $currentConnections = 0;
    private $queryStats = ['total' => 0, 'cached' => 0, 'slow' => 0];
    
    private function __construct() {
        $this->initializeConnectionPool();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeConnectionPool() {
        // Create initial connections
        for ($i = 0; $i < 3; $i++) {
            $this->createConnection();
        }
    }
    
    private function createConnection() {
        if ($this->currentConnections >= $this->maxConnections) {
            return null;
        }
        
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_TIMEOUT => 30
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Optimize connection settings
            $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            $pdo->exec("SET SESSION innodb_lock_wait_timeout = 50");
            $pdo->exec("SET SESSION query_cache_type = ON");
            
            $connectionId = uniqid('conn_');
            $this->connectionPool[$connectionId] = [
                'pdo' => $pdo,
                'in_use' => false,
                'created_at' => time(),
                'last_used' => time(),
                'query_count' => 0
            ];
            
            $this->currentConnections++;
            return $connectionId;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    public function getConnection() {
        // Find available connection
        foreach ($this->connectionPool as $id => $conn) {
            if (!$conn['in_use']) {
                $this->connectionPool[$id]['in_use'] = true;
                $this->connectionPool[$id]['last_used'] = time();
                return ['id' => $id, 'pdo' => $conn['pdo']];
            }
        }
        
        // Create new connection if pool not full
        $newConnId = $this->createConnection();
        if ($newConnId) {
            $this->connectionPool[$newConnId]['in_use'] = true;
            return ['id' => $newConnId, 'pdo' => $this->connectionPool[$newConnId]['pdo']];
        }
        
        // Wait for available connection (with timeout)
        $timeout = time() + 5;
        while (time() < $timeout) {
            foreach ($this->connectionPool as $id => $conn) {
                if (!$conn['in_use']) {
                    $this->connectionPool[$id]['in_use'] = true;
                    $this->connectionPool[$id]['last_used'] = time();
                    return ['id' => $id, 'pdo' => $conn['pdo']];
                }
            }
            usleep(100000); // 100ms
        }
        
        throw new Exception("No database connections available");
    }
    
    public function releaseConnection($connectionId) {
        if (isset($this->connectionPool[$connectionId])) {
            $this->connectionPool[$connectionId]['in_use'] = false;
            $this->connectionPool[$connectionId]['query_count']++;
        }
    }
    
    /**
     * Execute optimized query with automatic caching
     */
    public function executeOptimized($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
        $startTime = microtime(true);
        $this->queryStats['total']++;
        
        // Check cache first
        if ($cacheKey && isset($this->queryCache[$cacheKey])) {
            $cached = $this->queryCache[$cacheKey];
            if ($cached['expires'] > time()) {
                $this->queryStats['cached']++;
                return $cached['data'];
            } else {
                unset($this->queryCache[$cacheKey]);
            }
        }
        
        $connection = $this->getConnection();
        
        try {
            $stmt = $connection['pdo']->prepare($sql);
            $stmt->execute($params);
            
            $duration = microtime(true) - $startTime;
            
            // Log slow queries
            if ($duration > 0.1) {
                $this->queryStats['slow']++;
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("SLOW QUERY ({$duration}s): {$sql}");
                }
            }
            
            // Cache result if requested
            if ($cacheKey && $duration < 1.0) { // Don't cache slow queries
                $result = $stmt->fetchAll();
                $this->queryCache[$cacheKey] = [
                    'data' => $result,
                    'expires' => time() + $cacheTTL
                ];
                $this->releaseConnection($connection['id']);
                return $result;
            }
            
            $this->releaseConnection($connection['id']);
            return $stmt;
            
        } catch (PDOException $e) {
            $this->releaseConnection($connection['id']);
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Batch execute multiple queries in transaction
     */
    public function executeBatch($queries) {
        $connection = $this->getConnection();
        
        try {
            $connection['pdo']->beginTransaction();
            $results = [];
            
            foreach ($queries as $query) {
                $stmt = $connection['pdo']->prepare($query['sql']);
                $stmt->execute($query['params'] ?? []);
                $results[] = $stmt;
            }
            
            $connection['pdo']->commit();
            $this->releaseConnection($connection['id']);
            return $results;
            
        } catch (Exception $e) {
            $connection['pdo']->rollBack();
            $this->releaseConnection($connection['id']);
            throw $e;
        }
    }
    
    /**
     * Get optimized query statistics
     */
    public function getQueryStats() {
        $hitRate = $this->queryStats['total'] > 0 ? 
                   ($this->queryStats['cached'] / $this->queryStats['total']) * 100 : 0;
        
        return [
            'total_queries' => $this->queryStats['total'],
            'cached_queries' => $this->queryStats['cached'],
            'slow_queries' => $this->queryStats['slow'],
            'cache_hit_rate' => round($hitRate, 2),
            'active_connections' => count(array_filter($this->connectionPool, function($conn) {
                return $conn['in_use'];
            })),
            'total_connections' => $this->currentConnections,
            'cache_size' => count($this->queryCache)
        ];
    }
    
    /**
     * Cleanup expired cache and idle connections
     */
    public function cleanup() {
        $now = time();
        
        // Clean expired cache
        foreach ($this->queryCache as $key => $cached) {
            if ($cached['expires'] <= $now) {
                unset($this->queryCache[$key]);
            }
        }
        
        // Close idle connections (keep minimum of 2)
        $idleConnections = array_filter($this->connectionPool, function($conn) use ($now) {
            return !$conn['in_use'] && ($now - $conn['last_used']) > 300; // 5 minutes idle
        });
        
        if (count($this->connectionPool) - count($idleConnections) >= 2) {
            foreach ($idleConnections as $id => $conn) {
                unset($this->connectionPool[$id]);
                $this->currentConnections--;
            }
        }
    }
    
    /**
     * Prepare optimized statement with query analysis
     */
    public function prepareOptimized($sql) {
        // Analyze query for optimization opportunities
        $analysis = $this->analyzeQuery($sql);
        
        if ($analysis['needs_index']) {
            error_log("Query may benefit from index: " . $sql);
        }
        
        $connection = $this->getConnection();
        $stmt = $connection['pdo']->prepare($sql);
        $this->releaseConnection($connection['id']);
        
        return $stmt;
    }
    
    private function analyzeQuery($sql) {
        $analysis = [
            'needs_index' => false,
            'is_complex' => false,
            'estimated_cost' => 'low'
        ];
        
        // Simple heuristics for query analysis
        if (preg_match('/SELECT.*FROM.*WHERE.*AND.*OR/i', $sql)) {
            $analysis['is_complex'] = true;
            $analysis['estimated_cost'] = 'high';
        }
        
        if (preg_match('/WHERE.*LIKE.*%.*%/i', $sql)) {
            $analysis['needs_index'] = true;
        }
        
        return $analysis;
    }
    
    public function __destruct() {
        $this->cleanup();
    }
}

// Enhanced global database functions
function getOptimizedConnection() {
    return EnhancedDatabase::getInstance()->getConnection();
}

function executeOptimizedQuery($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
    return EnhancedDatabase::getInstance()->executeOptimized($sql, $params, $cacheKey, $cacheTTL);
}

function executeBatchQueries($queries) {
    return EnhancedDatabase::getInstance()->executeBatch($queries);
}

function getDatabaseStats() {
    return EnhancedDatabase::getInstance()->getQueryStats();
}

// Automatic cleanup on shutdown
register_shutdown_function(function() {
    if (class_exists('EnhancedDatabase')) {
        EnhancedDatabase::getInstance()->cleanup();
    }
});
?>
