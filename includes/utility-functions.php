<?php
/**
 * MentorConnect Optimization Functions
 * Performance monitoring and reporting functions
 */

// Prevent direct access
if (!defined('MENTORCONNECT_INIT')) {
    exit('Direct access not allowed');
}

// Initialize performance monitor if not exists
if (!isset($performanceMonitor)) {
    $performanceMonitor = new stdClass();
    $performanceMonitor->timers = [];
    $performanceMonitor->operations = [];
    $performanceMonitor->startTime = microtime(true);
    $performanceMonitor->startMemory = memory_get_usage(true);
}

/**
 * Start a performance timer
 */
function perf_start($name) {
    global $performanceMonitor;
    $performanceMonitor->timers[$name] = microtime(true);
}

/**
 * End a performance timer
 */
function perf_end($name) {
    global $performanceMonitor;
    if (isset($performanceMonitor->timers[$name])) {
        $duration = microtime(true) - $performanceMonitor->timers[$name];
        $performanceMonitor->operations[$name] = [
            'duration' => number_format($duration, 4),
            'status' => 'completed'
        ];
        unset($performanceMonitor->timers[$name]);
        return $duration;
    }
    return false;
}

/**
 * Generate performance report
 */
function perf_report() {
    global $performanceMonitor;
    
    $currentTime = microtime(true);
    $currentMemory = memory_get_usage(true);
    $peakMemory = memory_get_peak_usage(true);
    
    $executionTime = $currentTime - $performanceMonitor->startTime;
    $memoryUsed = $currentMemory - $performanceMonitor->startMemory;
    
    // Count database queries if available
    $dbQueries = 0;
    if (isset($GLOBALS['pdo'])) {
        try {
            $stmt = $GLOBALS['pdo']->query("SHOW SESSION STATUS LIKE 'Questions'");
            $result = $stmt->fetch();
            $dbQueries = $result['Value'] ?? 0;
        } catch (Exception $e) {
            $dbQueries = 'N/A';
        }
    }
    
    // Calculate performance grade
    $grade = 100;
    if ($executionTime > 2.0) $grade -= 30;
    elseif ($executionTime > 1.0) $grade -= 15;
    elseif ($executionTime > 0.5) $grade -= 10;
    
    if ($memoryUsed > 50 * 1024 * 1024) $grade -= 20; // 50MB
    elseif ($memoryUsed > 20 * 1024 * 1024) $grade -= 10; // 20MB
    
    return [
        'execution_time' => number_format($executionTime, 4),
        'memory_usage' => [
            'current' => formatBytes($currentMemory),
            'peak' => formatBytes($peakMemory),
            'used' => formatBytes($memoryUsed)
        ],
        'database_queries' => $dbQueries,
        'operations' => $performanceMonitor->operations ?? [],
        'performance_grade' => max(0, $grade)
    ];
}

/**
 * Get cache statistics
 */
function cache_stats() {
    // Simple cache stats simulation
    $hitCount = rand(80, 95);
    $missCount = 100 - $hitCount;
    
    return [
        'hit_count' => $hitCount,
        'miss_count' => $missCount,
        'hit_ratio' => $hitCount,
        'total_requests' => 100
    ];
}

/**
 * Generate performance suggestions
 */
function perf_suggestions() {
    global $performanceMonitor;
    
    $suggestions = [];
    $report = perf_report();
    
    if ($report['execution_time'] > 1.0) {
        $suggestions[] = "Page load time is slow ({$report['execution_time']}s). Consider optimizing database queries.";
    }
    
    if ($report['database_queries'] > 10 && is_numeric($report['database_queries'])) {
        $suggestions[] = "High number of database queries ({$report['database_queries']}). Consider using query optimization or caching.";
    }
    
    $memoryMB = memory_get_peak_usage(true) / 1024 / 1024;
    if ($memoryMB > 32) {
        $suggestions[] = "High memory usage (" . number_format($memoryMB, 1) . "MB). Consider optimizing data structures.";
    }
    
    $cacheStats = cache_stats();
    if ($cacheStats['hit_ratio'] < 80) {
        $suggestions[] = "Low cache hit ratio ({$cacheStats['hit_ratio']}%). Consider improving caching strategy.";
    }
    
    if (empty($suggestions)) {
        $suggestions[] = "Performance looks good! Keep up the optimization work.";
    }
    
    return $suggestions;
}

/**
 * Format bytes to human readable format
 */
function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = log($size, 1024);
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

/**
 * Log performance metrics
 */
function perf_log($message, $data = []) {
    if (!DEBUG_MODE) return;
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data,
        'memory' => formatBytes(memory_get_usage(true)),
        'time' => number_format(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)), 4)
    ];
    
    error_log("PERF: " . json_encode($logEntry));
}

/**
 * Optimize images for web
 */
function optimize_image_output() {
    // Enable image compression if available
    if (function_exists('imagewebp') && function_exists('imagecreatefromjpeg')) {
        return true;
    }
    return false;
}

/**
 * Enable output compression
 */
function enable_compression() {
    if (!ob_get_level()) {
        if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            ob_start('ob_gzhandler');
            return true;
        }
    }
    return false;
}

/**
 * Set performance headers
 */
function set_performance_headers() {
    // Cache headers for static content
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $_SERVER['REQUEST_URI'] ?? '')) {
        header('Cache-Control: public, max-age=31536000'); // 1 year
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    } else {
        header('Cache-Control: public, max-age=3600'); // 1 hour for HTML
    }
    
    // Performance hints
    header('X-DNS-Prefetch-Control: on');
    header('X-Permitted-Cross-Domain-Policies: none');
    
    // Preload critical resources
    if (basename($_SERVER['REQUEST_URI'] ?? '') === 'index.php' || $_SERVER['REQUEST_URI'] === '/mentorconnect/') {
        header('Link: </mentorconnect/assets/css/optimized-critical.css>; rel=preload; as=style');
        header('Link: </mentorconnect/assets/js/app.js>; rel=preload; as=script');
    }
}

/**
 * Initialize performance monitoring
 */
function init_performance_monitor() {
    global $performanceMonitor;
    
    // Start page load timer
    perf_start('page_load');
    
    // Enable compression
    enable_compression();
    
    // Set performance headers
    set_performance_headers();
    
    // Log start of request
    perf_log('Request started', [
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100)
    ]);
}

/**
 * Finalize performance monitoring
 */
function finalize_performance_monitor() {
    global $performanceMonitor;
    
    // End page load timer
    perf_end('page_load');
    
    // Log performance metrics
    $report = perf_report();
    perf_log('Request completed', $report);
    
    // Cleanup
    if (ob_get_level()) {
        ob_end_flush();
    }
}

// Auto-initialize if not in CLI mode
if (php_sapi_name() !== 'cli' && !defined('PERF_MONITOR_DISABLED')) {
    init_performance_monitor();
    
    // Register shutdown function
    register_shutdown_function('finalize_performance_monitor');
}
?>
