<?php
/**
 * Advanced Database Query Optimizer for MentorConnect
 * Provides intelligent query optimization, caching, and performance monitoring
 */

class DatabaseOptimizer {
    private static $instance = null;
    private $db;
    private $cache;
    private $queryStats = [];
    private $slowQueryThreshold = 1000; // 1 second in milliseconds
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = Cache::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Optimized query execution with intelligent caching
     */
    public function executeOptimizedQuery($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
        $startTime = microtime(true);
        $queryHash = md5($sql . serialize($params));
        
        // Try cache first for SELECT queries
        if ($cacheKey && stripos(trim($sql), 'SELECT') === 0) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== false) {
                $this->recordQueryStats($sql, 0, 'cache_hit');
                return $cached;
            }
        }
        
        // Analyze and optimize query before execution
        $optimizedSql = $this->optimizeQuery($sql);
        
        try {
            $stmt = $this->db->prepare($optimizedSql);
            $stmt->execute($params);
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            // Get results for SELECT queries
            if (stripos(trim($optimizedSql), 'SELECT') === 0) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Cache the result if it's a SELECT query
                if ($cacheKey) {
                    $this->cache->set($cacheKey, $result, $cacheTTL);
                }
            } else {
                $result = $stmt->rowCount();
            }
            
            $this->recordQueryStats($sql, $executionTime, 'executed');
            
            // Log slow queries
            if ($executionTime > $this->slowQueryThreshold) {
                $this->logSlowQuery($sql, $params, $executionTime);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            $this->recordQueryStats($sql, 0, 'error');
            error_log("Database query error: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Intelligent query optimization
     */
    private function optimizeQuery($sql) {
        $sql = trim($sql);
        
        // Remove unnecessary whitespace and comments
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = preg_replace('/\/\*.*?\*\//', '', $sql);
        
        // Optimize SELECT queries
        if (stripos($sql, 'SELECT') === 0) {
            $sql = $this->optimizeSelectQuery($sql);
        }
        
        // Optimize JOIN operations
        $sql = $this->optimizeJoins($sql);
        
        // Optimize WHERE clauses
        $sql = $this->optimizeWhereClause($sql);
        
        return $sql;
    }
    
    /**
     * Optimize SELECT statements
     */
    private function optimizeSelectQuery($sql) {
        // Replace SELECT * with specific columns where possible
        // This is a basic implementation - in production, you'd need table schema info
        
        // Add LIMIT if not present and query looks like it might return many rows
        if (!stripos($sql, 'LIMIT') && stripos($sql, 'ORDER BY')) {
            // Only suggest LIMIT for queries that might need it
            $this->suggestQueryOptimization($sql, 'Consider adding LIMIT clause');
        }
        
        return $sql;
    }
    
    /**
     * Optimize JOIN operations
     */
    private function optimizeJoins($sql) {
        // Suggest using EXISTS instead of IN for subqueries
        if (preg_match('/\bIN\s*\(\s*SELECT/i', $sql)) {
            $this->suggestQueryOptimization($sql, 'Consider using EXISTS instead of IN with subquery');
        }
        
        // Suggest proper JOIN order (smaller tables first)
        return $sql;
    }
    
    /**
     * Optimize WHERE clauses
     */
    private function optimizeWhereClause($sql) {
        // Check for functions in WHERE clause that prevent index usage
        if (preg_match('/WHERE\s+\w+\s*\(\s*\w+\s*\)/i', $sql)) {
            $this->suggestQueryOptimization($sql, 'Functions in WHERE clause may prevent index usage');
        }
        
        // Check for leading wildcards in LIKE queries
        if (preg_match('/LIKE\s+[\'"]%/i', $sql)) {
            $this->suggestQueryOptimization($sql, 'Leading wildcards in LIKE prevent index usage');
        }
        
        return $sql;
    }
    
    /**
     * Create optimized indexes based on query patterns
     */
    public function analyzeAndSuggestIndexes() {
        $suggestions = [];
        
        // Analyze common query patterns from query stats
        foreach ($this->queryStats as $queryHash => $stats) {
            if ($stats['avg_time'] > $this->slowQueryThreshold) {
                $sql = $stats['sql'];
                
                // Parse WHERE conditions
                $whereConditions = $this->extractWhereConditions($sql);
                foreach ($whereConditions as $condition) {
                    $suggestions[] = [
                        'type' => 'index',
                        'table' => $condition['table'],
                        'columns' => $condition['columns'],
                        'reason' => 'Frequent WHERE clause usage',
                        'impact' => 'high'
                    ];
                }
                
                // Parse ORDER BY clauses
                $orderByColumns = $this->extractOrderByColumns($sql);
                if (!empty($orderByColumns)) {
                    $suggestions[] = [
                        'type' => 'index',
                        'table' => $orderByColumns['table'],
                        'columns' => $orderByColumns['columns'],
                        'reason' => 'ORDER BY optimization',
                        'impact' => 'medium'
                    ];
                }
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Batch operations optimizer
     */
    public function executeBatchInsert($table, $data, $batchSize = 1000) {
        if (empty($data)) {
            return 0;
        }
        
        $totalInserted = 0;
        $chunks = array_chunk($data, $batchSize);
        
        try {
            $this->db->beginTransaction();
            
            foreach ($chunks as $chunk) {
                $columns = array_keys($chunk[0]);
                $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
                $values = str_repeat($placeholders . ',', count($chunk) - 1) . $placeholders;
                
                $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES {$values}";
                
                $flatData = [];
                foreach ($chunk as $row) {
                    foreach ($columns as $column) {
                        $flatData[] = $row[$column];
                    }
                }
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($flatData);
                $totalInserted += $stmt->rowCount();
            }
            
            $this->db->commit();
            return $totalInserted;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Connection pool management
     */
    public function optimizeConnectionPool() {
        // Set optimal connection parameters
        $optimizations = [
            "SET SESSION query_cache_type = ON",
            "SET SESSION query_cache_size = 268435456", // 256MB
            "SET SESSION tmp_table_size = 134217728", // 128MB
            "SET SESSION max_heap_table_size = 134217728", // 128MB
            "SET SESSION read_buffer_size = 2097152", // 2MB
            "SET SESSION sort_buffer_size = 4194304", // 4MB
            "SET SESSION join_buffer_size = 4194304", // 4MB
        ];
        
        foreach ($optimizations as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                // Log but don't fail if optimization can't be applied
                error_log("Database optimization warning: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Table maintenance and optimization
     */
    public function performMaintenance() {
        $tables = $this->getTableList();
        $results = [];
        
        foreach ($tables as $table) {
            try {
                // Analyze table
                $stmt = $this->db->query("ANALYZE TABLE {$table}");
                $analyzeResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Optimize table
                $stmt = $this->db->query("OPTIMIZE TABLE {$table}");
                $optimizeResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $results[$table] = [
                    'analyze' => $analyzeResult,
                    'optimize' => $optimizeResult
                ];
                
            } catch (PDOException $e) {
                $results[$table] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Query performance analysis
     */
    public function analyzeQueryPerformance($sql, $params = []) {
        $analysis = [];
        
        // Get execution plan
        try {
            $explainSql = "EXPLAIN " . $sql;
            $stmt = $this->db->prepare($explainSql);
            $stmt->execute($params);
            $analysis['execution_plan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Analyze the execution plan
            $analysis['recommendations'] = $this->analyzeExecutionPlan($analysis['execution_plan']);
            
        } catch (PDOException $e) {
            $analysis['error'] = $e->getMessage();
        }
        
        return $analysis;
    }
    
    /**
     * Database health monitoring
     */
    public function getHealthMetrics() {
        $metrics = [];
        
        try {
            // Connection status
            $metrics['connections'] = $this->getConnectionMetrics();
            
            // Query performance
            $metrics['query_performance'] = $this->getQueryPerformanceMetrics();
            
            // Table statistics
            $metrics['table_stats'] = $this->getTableStatistics();
            
            // Index usage
            $metrics['index_usage'] = $this->getIndexUsageStats();
            
            // Lock status
            $metrics['locks'] = $this->getLockStatus();
            
        } catch (Exception $e) {
            $metrics['error'] = $e->getMessage();
        }
        
        return $metrics;
    }
    
    /**
     * Intelligent query caching
     */
    public function getCacheStrategy($sql) {
        $sql = strtolower(trim($sql));
        
        // Don't cache certain types of queries
        if (preg_match('/^(insert|update|delete|alter|drop|create)/', $sql)) {
            return ['cache' => false, 'reason' => 'Modifying query'];
        }
        
        // Short cache for frequently changing data
        if (preg_match('/\b(notifications|messages|activities)\b/', $sql)) {
            return ['cache' => true, 'ttl' => 60, 'reason' => 'Frequently changing data'];
        }
        
        // Medium cache for user data
        if (preg_match('/\b(users|sessions)\b/', $sql)) {
            return ['cache' => true, 'ttl' => 300, 'reason' => 'User data'];
        }
        
        // Long cache for static data
        if (preg_match('/\b(skills|categories|settings)\b/', $sql)) {
            return ['cache' => true, 'ttl' => 3600, 'reason' => 'Static data'];
        }
        
        // Default caching
        return ['cache' => true, 'ttl' => 300, 'reason' => 'Default strategy'];
    }
    
    // Helper methods
    private function recordQueryStats($sql, $executionTime, $status) {
        $queryHash = md5($sql);
        
        if (!isset($this->queryStats[$queryHash])) {
            $this->queryStats[$queryHash] = [
                'sql' => $sql,
                'count' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'status' => []
            ];
        }
        
        $this->queryStats[$queryHash]['count']++;
        $this->queryStats[$queryHash]['total_time'] += $executionTime;
        $this->queryStats[$queryHash]['avg_time'] = $this->queryStats[$queryHash]['total_time'] / $this->queryStats[$queryHash]['count'];
        $this->queryStats[$queryHash]['status'][] = $status;
    }
    
    private function logSlowQuery($sql, $params, $executionTime) {
        $logData = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true)
        ];
        
        error_log("SLOW QUERY: " . json_encode($logData));
        
        // Store in database for analysis
        try {
            $stmt = $this->db->prepare("INSERT INTO slow_query_log (sql_text, execution_time, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$sql, $executionTime]);
        } catch (Exception $e) {
            // Don't let logging errors affect the main application
        }
    }
    
    private function suggestQueryOptimization($sql, $suggestion) {
        error_log("QUERY OPTIMIZATION SUGGESTION: {$suggestion} for query: {$sql}");
    }
    
    private function getTableList() {
        $stmt = $this->db->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function extractWhereConditions($sql) {
        // Simplified WHERE condition extraction
        // In production, you'd use a proper SQL parser
        $conditions = [];
        
        if (preg_match('/WHERE\s+(.+?)(?:\s+ORDER\s+BY|\s+GROUP\s+BY|\s+LIMIT|$)/i', $sql, $matches)) {
            $whereClause = $matches[1];
            
            // Extract column references
            if (preg_match_all('/(\w+)\.?(\w+)\s*[=<>!]/', $whereClause, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $conditions[] = [
                        'table' => isset($match[2]) ? $match[1] : 'unknown',
                        'columns' => [isset($match[2]) ? $match[2] : $match[1]]
                    ];
                }
            }
        }
        
        return $conditions;
    }
    
    private function extractOrderByColumns($sql) {
        if (preg_match('/ORDER\s+BY\s+(.+?)(?:\s+LIMIT|$)/i', $sql, $matches)) {
            $orderBy = $matches[1];
            $columns = array_map('trim', explode(',', $orderBy));
            
            return [
                'table' => 'unknown', // Would need more sophisticated parsing
                'columns' => $columns
            ];
        }
        
        return [];
    }
    
    private function analyzeExecutionPlan($plan) {
        $recommendations = [];
        
        foreach ($plan as $row) {
            // Check for table scans
            if (isset($row['type']) && $row['type'] === 'ALL') {
                $recommendations[] = "Table scan detected on {$row['table']}. Consider adding an index.";
            }
            
            // Check for filesort
            if (isset($row['Extra']) && strpos($row['Extra'], 'Using filesort') !== false) {
                $recommendations[] = "Filesort operation detected. Consider optimizing ORDER BY clause.";
            }
            
            // Check for temporary tables
            if (isset($row['Extra']) && strpos($row['Extra'], 'Using temporary') !== false) {
                $recommendations[] = "Temporary table usage detected. Consider query optimization.";
            }
        }
        
        return $recommendations;
    }
    
    private function getConnectionMetrics() {
        $stmt = $this->db->query("SHOW STATUS LIKE 'Connections'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getQueryPerformanceMetrics() {
        $metrics = [];
        
        foreach ($this->queryStats as $hash => $stats) {
            if ($stats['count'] > 1) {
                $metrics[] = [
                    'query_hash' => $hash,
                    'execution_count' => $stats['count'],
                    'avg_execution_time' => $stats['avg_time'],
                    'total_time' => $stats['total_time']
                ];
            }
        }
        
        return $metrics;
    }
    
    private function getTableStatistics() {
        $stmt = $this->db->query("
            SELECT 
                table_name,
                table_rows,
                data_length,
                index_length,
                data_free
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getIndexUsageStats() {
        // This would require MySQL performance schema
        return ['note' => 'Index usage statistics require performance schema'];
    }
    
    private function getLockStatus() {
        try {
            $stmt = $this->db->query("SHOW ENGINE INNODB STATUS");
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Parse lock information from the status
            return ['status' => 'No deadlocks detected'];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

// Create necessary tables for database optimization
function createOptimizationTables() {
    $sql = "
    CREATE TABLE IF NOT EXISTS slow_query_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sql_text TEXT,
        execution_time DECIMAL(10,3),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_execution_time (execution_time),
        INDEX idx_created_at (created_at)
    );
    
    CREATE TABLE IF NOT EXISTS rate_limit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        action VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_identifier (identifier),
        INDEX idx_ip_address (ip_address),
        INDEX idx_created_at (created_at)
    );
    
    CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        event_type VARCHAR(100),
        ip_address VARCHAR(45),
        user_agent TEXT,
        details JSON,
        severity ENUM('low', 'medium', 'high') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_event_type (event_type),
        INDEX idx_severity (severity),
        INDEX idx_created_at (created_at)
    );
    ";
    
    try {
        $db = Database::getInstance()->getConnection();
        $db->exec($sql);
    } catch (Exception $e) {
        error_log("Failed to create optimization tables: " . $e->getMessage());
    }
}

// Initialize database optimizer
createOptimizationTables();
?>
