<?php
/**
 * Optimized Database Manager
 * Connection pooling, prepared statement caching, query optimization
 */

declare(strict_types=1);

class DatabaseManager {
    private static ?PDO $connection = null;
    private static array $preparedStatements = [];
    private static array $queryStats = [];
    private static int $queryCount = 0;
    
    private array $config;
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    /**
     * Get database connection with connection pooling
     */
    public function getConnection(): PDO {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['name'],
                $this->config['charset']
            );
            
            try {
                self::$connection = new PDO(
                    $dsn,
                    $this->config['user'],
                    $this->config['pass'],
                    $this->config['options']
                );
                
                // Set MySQL session variables for performance
                self::$connection->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
                self::$connection->exec("SET SESSION innodb_lock_wait_timeout = 5");
                
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    throw new RuntimeException('Database connection failed: ' . $e->getMessage());
                }
                throw new RuntimeException('Database connection failed. Please try again later.');
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Execute query with prepared statement caching
     */
    public function execute(string $sql, array $params = []): PDOStatement {
        $startTime = microtime(true);
        self::$queryCount++;
        
        try {
            $connection = $this->getConnection();
            
            // Use prepared statement cache
            $stmtHash = md5($sql);
            if (!isset(self::$preparedStatements[$stmtHash])) {
                self::$preparedStatements[$stmtHash] = $connection->prepare($sql);
            }
            
            $stmt = self::$preparedStatements[$stmtHash];
            $stmt->execute($params);
            
            // Track query performance
            $duration = microtime(true) - $startTime;
            $this->logQuery($sql, $params, $duration);
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logError($sql, $params, $e);
            throw $e;
        }
    }
    
    /**
     * Fetch single row with caching
     */
    public function fetchOne(string $sql, array $params = [], ?string $cacheKey = null, int $ttl = 300): ?array {
        // Check cache first
        if ($cacheKey && PERF_CONFIG['enable_query_cache']) {
            $cached = App::cache()->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        
        // Cache the result
        if ($cacheKey && $result && PERF_CONFIG['enable_query_cache']) {
            App::cache()->set($cacheKey, $result, $ttl);
        }
        
        return $result ?: null;
    }
    
    /**
     * Fetch multiple rows with caching
     */
    public function fetchAll(string $sql, array $params = [], ?string $cacheKey = null, int $ttl = 300): array {
        // Check cache first
        if ($cacheKey && PERF_CONFIG['enable_query_cache']) {
            $cached = App::cache()->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetchAll();
        
        // Cache the result
        if ($cacheKey && PERF_CONFIG['enable_query_cache']) {
            App::cache()->set($cacheKey, $result, $ttl);
        }
        
        return $result;
    }
    
    /**
     * Transaction wrapper with automatic rollback
     */
    public function transaction(callable $callback): mixed {
        $connection = $this->getConnection();
        
        try {
            $connection->beginTransaction();
            $result = $callback($this);
            $connection->commit();
            return $result;
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId(): string {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Bulk insert for better performance
     */
    public function bulkInsert(string $table, array $columns, array $rows): int {
        if (empty($rows)) {
            return 0;
        }
        
        $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $table,
            implode(',', $columns),
            str_repeat($placeholders . ',', count($rows) - 1) . $placeholders
        );
        
        $params = [];
        foreach ($rows as $row) {
            $params = array_merge($params, array_values($row));
        }
        
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Get query statistics
     */
    public function getStats(): array {
        return [
            'query_count' => self::$queryCount,
            'prepared_statements' => count(self::$preparedStatements),
            'query_stats' => self::$queryStats
        ];
    }
    
    /**
     * Log query performance
     */
    private function logQuery(string $sql, array $params, float $duration): void {
        if ($duration > 0.1) { // Log slow queries
            self::$queryStats[] = [
                'sql' => $sql,
                'params' => $params,
                'duration' => $duration,
                'timestamp' => time()
            ];
            
            if (DEBUG_MODE) {
                error_log(sprintf('Slow query (%.4fs): %s', $duration, $sql));
            }
        }
    }
    
    /**
     * Log database errors
     */
    private function logError(string $sql, array $params, PDOException $e): void {
        if (DEBUG_MODE) {
            error_log(sprintf(
                'Database Error: %s | SQL: %s | Params: %s',
                $e->getMessage(),
                $sql,
                json_encode($params)
            ));
        }
    }
    
    /**
     * Close connection and cleanup
     */
    public function cleanup(): void {
        self::$connection = null;
        self::$preparedStatements = [];
    }
}
?>
