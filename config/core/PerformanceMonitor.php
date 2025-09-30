<?php
/**
 * Optimized Performance Monitor
 * Real-time performance tracking and optimization
 */

declare(strict_types=1);

class PerformanceMonitor {
    private array $timers = [];
    private array $queries = [];
    private array $memoryCheckpoints = [];
    private float $requestStart;
    private CacheManager $cache;
    private array $metrics = [];
    
    public function __construct(CacheManager $cache) {
        $this->cache = $cache;
        $this->requestStart = microtime(true);
        $this->memoryCheckpoints['start'] = memory_get_usage(true);
        
        // Register shutdown function to log request metrics
        register_shutdown_function([$this, 'logRequestMetrics']);
    }
    
    /**
     * Start timing an operation
     */
    public function startTimer(string $name): void {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true)
        ];
    }
    
    /**
     * Stop timing an operation
     */
    public function stopTimer(string $name): float {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }
        
        $duration = microtime(true) - $this->timers[$name]['start'];
        $memoryUsed = memory_get_usage(true) - $this->timers[$name]['memory_start'];
        
        $this->metrics[$name] = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'timestamp' => time()
        ];
        
        unset($this->timers[$name]);
        
        return $duration;
    }
    
    /**
     * Log database query performance
     */
    public function logQuery(string $query, float $duration, array $params = []): void {
        $this->queries[] = [
            'query' => $query,
            'duration' => $duration,
            'params' => $params,
            'memory' => memory_get_usage(true),
            'timestamp' => microtime(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];
        
        // Log slow queries
        if ($duration > 1.0) { // Queries taking more than 1 second
            $this->logSlowQuery($query, $duration, $params);
        }
    }
    
    /**
     * Log slow query for analysis
     */
    private function logSlowQuery(string $query, float $duration, array $params): void {
        $slowQuery = [
            'query' => $query,
            'duration' => $duration,
            'params' => $params,
            'timestamp' => time(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $slowQueries = $this->cache->get('slow_queries') ?: [];
        $slowQueries[] = $slowQuery;
        
        // Keep only last 100 slow queries
        if (count($slowQueries) > 100) {
            $slowQueries = array_slice($slowQueries, -100);
        }
        
        $this->cache->set('slow_queries', $slowQueries, 3600);
        error_log("SLOW QUERY ({$duration}s): {$query}");
    }
    
    /**
     * Add memory checkpoint
     */
    public function addMemoryCheckpoint(string $name): void {
        $this->memoryCheckpoints[$name] = memory_get_usage(true);
    }
    
    /**
     * Get memory usage between checkpoints
     */
    public function getMemoryUsage(string $from = 'start', string $to = null): int {
        $to = $to ?? 'current';
        
        $startMemory = $this->memoryCheckpoints[$from] ?? $this->memoryCheckpoints['start'];
        $endMemory = $to === 'current' ? memory_get_usage(true) : 
                    ($this->memoryCheckpoints[$to] ?? memory_get_usage(true));
        
        return $endMemory - $startMemory;
    }
    
    /**
     * Get current performance metrics
     */
    public function getMetrics(): array {
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'request_time' => $currentTime - $this->requestStart,
            'memory_usage' => $currentMemory,
            'peak_memory' => $peakMemory,
            'memory_limit' => $this->getMemoryLimit(),
            'query_count' => count($this->queries),
            'query_time' => array_sum(array_column($this->queries, 'duration')),
            'timers' => $this->metrics,
            'slow_queries' => $this->getSlowQueryCount(),
            'cache_stats' => $this->cache->getStats()
        ];
    }
    
    /**
     * Get detailed query analysis
     */
    public function getQueryAnalysis(): array {
        if (empty($this->queries)) {
            return [];
        }
        
        $totalTime = array_sum(array_column($this->queries, 'duration'));
        $avgTime = $totalTime / count($this->queries);
        $slowQueries = array_filter($this->queries, fn($q) => $q['duration'] > $avgTime * 2);
        
        // Group similar queries
        $queryGroups = [];
        foreach ($this->queries as $query) {
            $normalized = $this->normalizeQuery($query['query']);
            if (!isset($queryGroups[$normalized])) {
                $queryGroups[$normalized] = [
                    'count' => 0,
                    'total_time' => 0,
                    'example' => $query['query']
                ];
            }
            $queryGroups[$normalized]['count']++;
            $queryGroups[$normalized]['total_time'] += $query['duration'];
        }
        
        // Sort by total time
        uasort($queryGroups, fn($a, $b) => $b['total_time'] <=> $a['total_time']);
        
        return [
            'total_queries' => count($this->queries),
            'total_time' => $totalTime,
            'average_time' => $avgTime,
            'slow_queries' => count($slowQueries),
            'query_groups' => array_slice($queryGroups, 0, 10), // Top 10
            'recommendations' => $this->generateQueryRecommendations($queryGroups)
        ];
    }
    
    /**
     * Get performance recommendations
     */
    public function getRecommendations(): array {
        $metrics = $this->getMetrics();
        $recommendations = [];
        
        // Memory recommendations
        $memoryUsagePercent = ($metrics['memory_usage'] / $metrics['memory_limit']) * 100;
        if ($memoryUsagePercent > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'high',
                'message' => 'Memory usage is high (' . round($memoryUsagePercent, 1) . '%). Consider optimizing memory-intensive operations.'
            ];
        }
        
        // Query recommendations
        if ($metrics['query_count'] > 50) {
            $recommendations[] = [
                'type' => 'query',
                'priority' => 'medium',
                'message' => 'High number of database queries (' . $metrics['query_count'] . '). Consider using eager loading or caching.'
            ];
        }
        
        if ($metrics['query_time'] > 2.0) {
            $recommendations[] = [
                'type' => 'query',
                'priority' => 'high',
                'message' => 'Total query time is high (' . round($metrics['query_time'], 2) . 's). Review slow queries and add indexes.'
            ];
        }
        
        // Request time recommendations
        if ($metrics['request_time'] > 3.0) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Request processing time is slow (' . round($metrics['request_time'], 2) . 's). Consider optimization.'
            ];
        }
        
        // Cache recommendations
        $cacheStats = $metrics['cache_stats'];
        if ($cacheStats['hit_rate'] < 50 && $cacheStats['hits'] + $cacheStats['misses'] > 10) {
            $recommendations[] = [
                'type' => 'cache',
                'priority' => 'medium',
                'message' => 'Low cache hit rate (' . $cacheStats['hit_rate'] . '%). Review caching strategy.'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Log request metrics at shutdown
     */
    public function logRequestMetrics(): void {
        $metrics = $this->getMetrics();
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => time(),
            'url' => $url,
            'method' => $method,
            'request_time' => $metrics['request_time'],
            'memory_usage' => $metrics['memory_usage'],
            'peak_memory' => $metrics['peak_memory'],
            'query_count' => $metrics['query_count'],
            'query_time' => $metrics['query_time'],
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        // Store in cache for analysis
        $logKey = 'performance_log:' . date('Y-m-d');
        $dailyLog = $this->cache->get($logKey) ?: [];
        $dailyLog[] = $logEntry;
        
        // Keep only last 1000 entries per day
        if (count($dailyLog) > 1000) {
            $dailyLog = array_slice($dailyLog, -1000);
        }
        
        $this->cache->set($logKey, $dailyLog, 86400);
        
        // Log slow requests
        if ($metrics['request_time'] > 5.0) {
            error_log("SLOW REQUEST ({$metrics['request_time']}s): {$method} {$url}");
        }
    }
    
    /**
     * Get performance statistics for dashboard
     */
    public function getPerformanceStats(int $days = 7): array {
        $stats = [
            'avg_request_time' => 0,
            'avg_memory_usage' => 0,
            'avg_query_count' => 0,
            'slow_requests' => 0,
            'total_requests' => 0,
            'daily_stats' => []
        ];
        
        $totalRequestTime = 0;
        $totalMemoryUsage = 0;
        $totalQueryCount = 0;
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $logKey = 'performance_log:' . $date;
            $dailyLog = $this->cache->get($logKey) ?: [];
            
            $dailyRequestTime = 0;
            $dailyMemoryUsage = 0;
            $dailyQueryCount = 0;
            $dailySlowRequests = 0;
            
            foreach ($dailyLog as $entry) {
                $dailyRequestTime += $entry['request_time'];
                $dailyMemoryUsage += $entry['memory_usage'];
                $dailyQueryCount += $entry['query_count'];
                
                if ($entry['request_time'] > 3.0) {
                    $dailySlowRequests++;
                }
            }
            
            $requestCount = count($dailyLog);
            $stats['daily_stats'][$date] = [
                'requests' => $requestCount,
                'avg_request_time' => $requestCount > 0 ? $dailyRequestTime / $requestCount : 0,
                'avg_memory_usage' => $requestCount > 0 ? $dailyMemoryUsage / $requestCount : 0,
                'avg_query_count' => $requestCount > 0 ? $dailyQueryCount / $requestCount : 0,
                'slow_requests' => $dailySlowRequests
            ];
            
            $totalRequestTime += $dailyRequestTime;
            $totalMemoryUsage += $dailyMemoryUsage;
            $totalQueryCount += $dailyQueryCount;
            $stats['slow_requests'] += $dailySlowRequests;
            $stats['total_requests'] += $requestCount;
        }
        
        if ($stats['total_requests'] > 0) {
            $stats['avg_request_time'] = $totalRequestTime / $stats['total_requests'];
            $stats['avg_memory_usage'] = $totalMemoryUsage / $stats['total_requests'];
            $stats['avg_query_count'] = $totalQueryCount / $stats['total_requests'];
        }
        
        return $stats;
    }
    
    /**
     * Clear performance logs
     */
    public function clearLogs(int $olderThanDays = 30): int {
        $cleared = 0;
        
        for ($i = $olderThanDays; $i < 365; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $logKey = 'performance_log:' . $date;
            
            if ($this->cache->get($logKey) !== null) {
                $this->cache->delete($logKey);
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Private helper methods
     */
    private function getMemoryLimit(): int {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $unit = strtoupper(substr($limit, -1));
        $size = (int) substr($limit, 0, -1);
        
        return match ($unit) {
            'G' => $size * 1024 * 1024 * 1024,
            'M' => $size * 1024 * 1024,
            'K' => $size * 1024,
            default => $size
        };
    }
    
    private function normalizeQuery(string $query): string {
        // Remove parameter values for grouping similar queries
        $normalized = preg_replace('/\s+/', ' ', trim($query));
        $normalized = preg_replace('/\b\d+\b/', '?', $normalized);
        $normalized = preg_replace("/'[^']*'/", '?', $normalized);
        return $normalized;
    }
    
    private function getSlowQueryCount(): int {
        return count(array_filter($this->queries, fn($q) => $q['duration'] > 1.0));
    }
    
    private function generateQueryRecommendations(array $queryGroups): array {
        $recommendations = [];
        
        foreach ($queryGroups as $normalized => $data) {
            if ($data['count'] > 10 && $data['total_time'] > 2.0) {
                $recommendations[] = [
                    'type' => 'frequent_query',
                    'message' => "Query executed {$data['count']} times, consider caching",
                    'example' => substr($data['example'], 0, 100) . '...'
                ];
            }
            
            if ($data['total_time'] / $data['count'] > 0.5) {
                $recommendations[] = [
                    'type' => 'slow_query',
                    'message' => "Slow query detected, consider adding indexes",
                    'example' => substr($data['example'], 0, 100) . '...'
                ];
            }
        }
        
        return array_slice($recommendations, 0, 5); // Top 5 recommendations
    }
}
?>
