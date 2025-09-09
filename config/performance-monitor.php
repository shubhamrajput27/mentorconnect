<?php
/**
 * Performance Monitor for MentorConnect
 * Tracks application performance metrics and identifies bottlenecks
 */

class PerformanceMonitor {
    private static $instance = null;
    private $metrics = [];
    private $startTime;
    private $queries = [];
    private $memoryUsage = [];
    
    private function __construct() {
        $this->startTime = microtime(true);
        $this->memoryUsage['start'] = memory_get_usage(true);
        
        // Register error handlers for performance issues
        set_exception_handler([$this, 'handleException']);
        
        // Track slow queries if in debug mode
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $this->enableQueryLogging();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start timing a specific operation
     */
    public function startTimer($name) {
        $this->metrics[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true)
        ];
    }
    
    /**
     * End timing and record metrics
     */
    public function endTimer($name) {
        if (!isset($this->metrics[$name])) {
            return false;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $this->metrics[$name]['end_time'] = $endTime;
        $this->metrics[$name]['end_memory'] = $endMemory;
        $this->metrics[$name]['duration'] = $endTime - $this->metrics[$name]['start_time'];
        $this->metrics[$name]['memory_used'] = $endMemory - $this->metrics[$name]['start_memory'];
        
        // Log slow operations
        if ($this->metrics[$name]['duration'] > 1.0) { // 1 second threshold
            $this->logSlowOperation($name, $this->metrics[$name]);
        }
        
        return $this->metrics[$name];
    }
    
    /**
     * Log database query performance
     */
    public function logQuery($sql, $params = [], $duration = 0) {
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration,
            'timestamp' => time(),
            'memory' => memory_get_usage(true)
        ];
        
        // Log slow queries
        if ($duration > 0.1) { // 100ms threshold
            $this->logSlowQuery($sql, $duration);
        }
    }
    
    /**
     * Get comprehensive performance report
     */
    public function getReport() {
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $report = [
            'execution_time' => round($currentTime - $this->startTime, 4),
            'memory_usage' => [
                'current' => $this->formatBytes($currentMemory),
                'peak' => $this->formatBytes($peakMemory),
                'start' => $this->formatBytes($this->memoryUsage['start'])
            ],
            'database_queries' => count($this->queries),
            'slow_queries' => count(array_filter($this->queries, function($q) { return $q['duration'] > 0.1; })),
            'operations' => []
        ];
        
        // Add operation metrics
        foreach ($this->metrics as $name => $metric) {
            if (isset($metric['duration'])) {
                $report['operations'][$name] = [
                    'duration' => round($metric['duration'], 4),
                    'memory_used' => $this->formatBytes($metric['memory_used']),
                    'status' => $metric['duration'] > 1.0 ? 'slow' : 'normal'
                ];
            }
        }
        
        // Calculate performance grade
        $report['performance_grade'] = $this->calculatePerformanceGrade($report);
        
        return $report;
    }
    
    /**
     * Get query analysis
     */
    public function getQueryAnalysis() {
        if (empty($this->queries)) {
            return ['total' => 0, 'slow' => 0, 'average_duration' => 0];
        }
        
        $totalDuration = array_sum(array_column($this->queries, 'duration'));
        $slowQueries = array_filter($this->queries, function($q) { return $q['duration'] > 0.1; });
        
        return [
            'total' => count($this->queries),
            'slow' => count($slowQueries),
            'average_duration' => round($totalDuration / count($this->queries), 4),
            'total_duration' => round($totalDuration, 4),
            'queries' => DEBUG_MODE ? $this->queries : []
        ];
    }
    
    /**
     * Add custom metric
     */
    public function addMetric($name, $value, $unit = '') {
        $this->metrics[$name] = [
            'value' => $value,
            'unit' => $unit,
            'timestamp' => microtime(true)
        ];
    }
    
    /**
     * Check if current performance is acceptable
     */
    public function isPerformanceAcceptable() {
        $currentTime = microtime(true) - $this->startTime;
        $memoryUsage = memory_get_usage(true);
        $queryCount = count($this->queries);
        
        // Performance thresholds
        $maxExecutionTime = 3.0; // 3 seconds
        $maxMemoryUsage = 64 * 1024 * 1024; // 64MB
        $maxQueries = 50;
        
        return $currentTime < $maxExecutionTime && 
               $memoryUsage < $maxMemoryUsage && 
               $queryCount < $maxQueries;
    }
    
    /**
     * Generate performance optimization suggestions
     */
    public function getOptimizationSuggestions() {
        $suggestions = [];
        $report = $this->getReport();
        
        if ($report['execution_time'] > 2.0) {
            $suggestions[] = 'Consider implementing caching to reduce execution time';
        }
        
        if ($report['database_queries'] > 20) {
            $suggestions[] = 'High number of database queries detected. Consider query optimization or eager loading';
        }
        
        if ($report['slow_queries'] > 0) {
            $suggestions[] = 'Slow database queries detected. Review and optimize query performance';
        }
        
        $peakMemory = memory_get_peak_usage(true);
        if ($peakMemory > 32 * 1024 * 1024) { // 32MB
            $suggestions[] = 'High memory usage detected. Consider optimizing data structures';
        }
        
        return $suggestions;
    }
    
    private function enableQueryLogging() {
        // This would typically wrap PDO to log all queries
        // For now, we'll rely on manual logging
    }
    
    private function logSlowOperation($name, $metrics) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("SLOW OPERATION: {$name} took {$metrics['duration']}s");
        }
    }
    
    private function logSlowQuery($sql, $duration) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("SLOW QUERY: {$sql} took {$duration}s");
        }
    }
    
    private function formatBytes($bytes) {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    private function calculatePerformanceGrade($report) {
        $score = 100;
        
        // Deduct points for slow execution
        if ($report['execution_time'] > 1.0) $score -= 20;
        elseif ($report['execution_time'] > 0.5) $score -= 10;
        
        // Deduct points for many queries
        if ($report['database_queries'] > 20) $score -= 15;
        elseif ($report['database_queries'] > 10) $score -= 8;
        
        // Deduct points for slow queries
        $score -= $report['slow_queries'] * 5;
        
        // Deduct points for high memory usage
        $peakMB = memory_get_peak_usage(true) / (1024 * 1024);
        if ($peakMB > 64) $score -= 20;
        elseif ($peakMB > 32) $score -= 10;
        
        return max(0, min(100, $score));
    }
    
    public function handleException($exception) {
        $this->addMetric('exception', $exception->getMessage());
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("PERFORMANCE EXCEPTION: " . $exception->getMessage());
        }
    }
    
    /**
     * Output performance metrics as HTTP headers (disabled to prevent header issues)
     */
    public function outputHeaders() {
        // Disabled to prevent "headers already sent" errors
        // Performance info is available in HTML comments in debug mode
        return;
    }
    
    /**
     * Get headers as array instead of sending them
     */
    public function getPerformanceHeaders() {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $report = $this->getReport();
            return [
                'X-Performance-Time' => $report['execution_time'] . 's',
                'X-Performance-Queries' => $report['database_queries'],
                'X-Performance-Memory' => $report['memory_usage']['peak'],
                'X-Performance-Grade' => $report['performance_grade']
            ];
        }
        return [];
    }
}

/**
 * Global performance monitoring functions
 */
function perf_start($name) {
    return PerformanceMonitor::getInstance()->startTimer($name);
}

function perf_end($name) {
    return PerformanceMonitor::getInstance()->endTimer($name);
}

function perf_log_query($sql, $params = [], $duration = 0) {
    return PerformanceMonitor::getInstance()->logQuery($sql, $params, $duration);
}

function perf_report() {
    return PerformanceMonitor::getInstance()->getReport();
}

function perf_suggestions() {
    return PerformanceMonitor::getInstance()->getOptimizationSuggestions();
}

function perf_headers() {
    return PerformanceMonitor::getInstance()->getPerformanceHeaders();
}
?>
