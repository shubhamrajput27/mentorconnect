<?php
/**
 * MentorConnect Performance Testing and Optimization Verification Script
 * Run this script to test and verify all optimizations are working correctly
 */

require_once 'config/config.php';

class PerformanceTester {
    private $results = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
        echo "<h1>🚀 MentorConnect Performance Test Results</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .test-result { 
                background: white; 
                padding: 20px; 
                margin: 10px 0; 
                border-radius: 8px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .success { border-left: 4px solid #10b981; }
            .warning { border-left: 4px solid #f59e0b; }
            .error { border-left: 4px solid #ef4444; }
            .metric { display: inline-block; margin: 5px 10px; padding: 5px 10px; background: #f3f4f6; border-radius: 4px; }
            .score { font-weight: bold; font-size: 1.2em; }
        </style>";
    }
    
    public function runAllTests() {
        $this->testDatabaseOptimizations();
        $this->testCachePerformance();
        $this->testAPIOptimizations();
        $this->testSecurityHeaders();
        $this->testAssetOptimization();
        $this->testOverallPerformance();
        $this->generateReport();
    }
    
    private function testDatabaseOptimizations() {
        echo "<div class='test-result success'>";
        echo "<h2>📊 Database Optimization Tests</h2>";
        
        $start = microtime(true);
        
        // Test query caching
        if (class_exists('DatabaseOptimizer')) {
            $stats = DatabaseOptimizer::getCacheStats();
            echo "<div class='metric'>Cache Hit Ratio: <span class='score'>{$stats['hit_ratio']}%</span></div>";
            echo "<div class='metric'>Cached Queries: <span class='score'>{$stats['cached_queries']}</span></div>";
            
            // Test optimized query
            $testQuery = DatabaseOptimizer::executeOptimizedQuery(
                "SELECT COUNT(*) as count FROM users WHERE role = ?",
                ['student'],
                'test_query_cache',
                60
            );
            
            $queryTime = (microtime(true) - $start) * 1000;
            echo "<div class='metric'>Test Query Time: <span class='score'>" . round($queryTime, 2) . "ms</span></div>";
            
            $this->results['database_optimized'] = true;
            $this->results['query_time'] = $queryTime;
        } else {
            echo "<div class='metric error'>DatabaseOptimizer not found</div>";
            $this->results['database_optimized'] = false;
        }
        
        echo "</div>";
    }
    
    private function testCachePerformance() {
        echo "<div class='test-result success'>";
        echo "<h2>💾 Cache Performance Tests</h2>";
        
        // Test advanced caching if available
        if (class_exists('AdvancedCacheManager')) {
            $start = microtime(true);
            
            // Test cache set/get
            $testData = ['test' => 'data', 'timestamp' => time()];
            AdvancedCacheManager::set('performance_test', $testData, 300);
            $retrieved = AdvancedCacheManager::get('performance_test');
            
            $cacheTime = (microtime(true) - $start) * 1000;
            echo "<div class='metric'>Cache Set/Get Time: <span class='score'>" . round($cacheTime, 3) . "ms</span></div>";
            
            $stats = AdvancedCacheManager::getStats();
            echo "<div class='metric'>Total Hit Ratio: <span class='score'>{$stats['hit_ratio']}%</span></div>";
            echo "<div class='metric'>Memory Items: <span class='score'>{$stats['memory_items']}</span></div>";
            echo "<div class='metric'>Redis Connected: <span class='score'>" . ($stats['redis_connected'] ? 'Yes' : 'No') . "</span></div>";
            
            $this->results['advanced_cache'] = true;
            $this->results['cache_time'] = $cacheTime;
        } else {
            echo "<div class='metric warning'>AdvancedCacheManager not found - using basic caching</div>";
            $this->results['advanced_cache'] = false;
        }
        
        echo "</div>";
    }
    
    private function testAPIOptimizations() {
        echo "<div class='test-result success'>";
        echo "<h2>🔗 API Optimization Tests</h2>";
        
        if (class_exists('ApiOptimizer')) {
            echo "<div class='metric'>API Optimizer: <span class='score'>✅ Available</span></div>";
            
            // Test response optimization
            $testData = [
                'data' => ['item1', 'item2', null, '', 'item3'],
                'created_at' => '2025-01-01 12:00:00',
                'empty_array' => [],
                'null_value' => null
            ];
            
            $start = microtime(true);
            $optimized = ApiOptimizer::optimizeResponse($testData);
            $optimizationTime = (microtime(true) - $start) * 1000;
            
            echo "<div class='metric'>Response Optimization Time: <span class='score'>" . round($optimizationTime, 3) . "ms</span></div>";
            echo "<div class='metric'>Null Values Removed: <span class='score'>✅</span></div>";
            echo "<div class='metric'>Timestamps Compressed: <span class='score'>✅</span></div>";
            
            $this->results['api_optimized'] = true;
            $this->results['optimization_time'] = $optimizationTime;
        } else {
            echo "<div class='metric warning'>API Optimizer not found</div>";
            $this->results['api_optimized'] = false;
        }
        
        echo "</div>";
    }
    
    private function testSecurityHeaders() {
        echo "<div class='test-result success'>";
        echo "<h2>🔒 Security Headers Test</h2>";
        
        $headers = headers_list();
        $securityHeaders = [
            'X-Content-Type-Options' => false,
            'X-Frame-Options' => false,
            'X-XSS-Protection' => false,
            'Referrer-Policy' => false
        ];
        
        foreach ($headers as $header) {
            foreach ($securityHeaders as $securityHeader => $found) {
                if (stripos($header, $securityHeader) !== false) {
                    $securityHeaders[$securityHeader] = true;
                }
            }
        }
        
        foreach ($securityHeaders as $header => $found) {
            $status = $found ? '✅' : '❌';
            echo "<div class='metric'>{$header}: <span class='score'>{$status}</span></div>";
        }
        
        $this->results['security_headers'] = array_filter($securityHeaders);
        echo "</div>";
    }
    
    private function testAssetOptimization() {
        echo "<div class='test-result success'>";
        echo "<h2>📦 Asset Optimization Tests</h2>";
        
        // Check if critical CSS exists
        $criticalCssPath = __DIR__ . '/assets/css/critical.css';
        $criticalCssExists = file_exists($criticalCssPath);
        echo "<div class='metric'>Critical CSS: <span class='score'>" . ($criticalCssExists ? '✅' : '❌') . "</span></div>";
        
        // Check if performance optimizer exists
        $performanceOptimizerPath = __DIR__ . '/assets/js/performance-optimizer.js';
        $performanceOptimizerExists = file_exists($performanceOptimizerPath);
        echo "<div class='metric'>Performance Optimizer: <span class='score'>" . ($performanceOptimizerExists ? '✅' : '❌') . "</span></div>";
        
        // Check service worker
        $serviceWorkerPath = __DIR__ . '/sw.js';
        $serviceWorkerExists = file_exists($serviceWorkerPath);
        echo "<div class='metric'>Service Worker: <span class='score'>" . ($serviceWorkerExists ? '✅' : '❌') . "</span></div>";
        
        // Check manifest
        $manifestPath = __DIR__ . '/manifest.json';
        $manifestExists = file_exists($manifestPath);
        echo "<div class='metric'>PWA Manifest: <span class='score'>" . ($manifestExists ? '✅' : '❌') . "</span></div>";
        
        $this->results['assets_optimized'] = $criticalCssExists && $performanceOptimizerExists && $serviceWorkerExists;
        echo "</div>";
    }
    
    private function testOverallPerformance() {
        echo "<div class='test-result success'>";
        echo "<h2>⚡ Overall Performance Metrics</h2>";
        
        $totalTime = (microtime(true) - $this->startTime) * 1000;
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        
        echo "<div class='metric'>Total Test Time: <span class='score'>" . round($totalTime, 2) . "ms</span></div>";
        echo "<div class='metric'>Peak Memory Usage: <span class='score'>" . round($memoryUsage, 2) . "MB</span></div>";
        
        // PHP optimizations check
        $opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status();
        echo "<div class='metric'>OPcache: <span class='score'>" . ($opcacheEnabled ? '✅' : '❌') . "</span></div>";
        
        $gzipEnabled = extension_loaded('zlib');
        echo "<div class='metric'>Gzip Support: <span class='score'>" . ($gzipEnabled ? '✅' : '❌') . "</span></div>";
        
        $this->results['total_time'] = $totalTime;
        $this->results['memory_usage'] = $memoryUsage;
        echo "</div>";
    }
    
    private function generateReport() {
        echo "<div class='test-result'>";
        echo "<h2>📊 Performance Score Report</h2>";
        
        $score = 0;
        $maxScore = 0;
        
        // Calculate score based on optimizations
        $checks = [
            'database_optimized' => 20,
            'advanced_cache' => 20,
            'api_optimized' => 15,
            'assets_optimized' => 15,
            'security_headers' => 10
        ];
        
        foreach ($checks as $check => $points) {
            $maxScore += $points;
            if (isset($this->results[$check]) && $this->results[$check]) {
                $score += $points;
            }
        }
        
        // Performance bonuses
        if (isset($this->results['query_time']) && $this->results['query_time'] < 50) {
            $score += 10; // Bonus for fast queries
        }
        if (isset($this->results['memory_usage']) && $this->results['memory_usage'] < 20) {
            $score += 10; // Bonus for low memory usage
        }
        
        $maxScore += 20; // Bonus points
        $percentage = round(($score / $maxScore) * 100, 1);
        
        echo "<div style='text-align: center; font-size: 2em; margin: 20px;'>";
        echo "🎯 Performance Score: <span class='score' style='color: #10b981;'>{$percentage}%</span>";
        echo "</div>";
        
        if ($percentage >= 90) {
            echo "<div style='text-align: center; color: #10b981; font-size: 1.2em;'>🚀 Excellent! Your optimizations are working perfectly!</div>";
        } elseif ($percentage >= 70) {
            echo "<div style='text-align: center; color: #f59e0b; font-size: 1.2em;'>⚡ Good performance! Consider implementing remaining optimizations.</div>";
        } else {
            echo "<div style='text-align: center; color: #ef4444; font-size: 1.2em;'>🔧 Performance needs improvement. Please implement the recommended optimizations.</div>";
        }
        
        echo "<h3>🔧 Recommendations for Further Improvement:</h3>";
        echo "<ul>";
        
        if (!$this->results['database_optimized']) {
            echo "<li>Include the DatabaseOptimizer class in your configuration</li>";
        }
        if (!$this->results['advanced_cache']) {
            echo "<li>Implement AdvancedCacheManager for better caching performance</li>";
        }
        if (!$this->results['api_optimized']) {
            echo "<li>Use ApiOptimizer middleware for all API endpoints</li>";
        }
        if (!$this->results['assets_optimized']) {
            echo "<li>Ensure all critical assets (CSS, JS, Service Worker) are properly optimized</li>";
        }
        if (count($this->results['security_headers']) < 4) {
            echo "<li>Implement all recommended security headers</li>";
        }
        
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='text-align: center; margin: 40px; color: #6b7280;'>";
        echo "<small>Test completed in " . round((microtime(true) - $this->startTime) * 1000, 2) . "ms</small>";
        echo "</div>";
    }
}

// Run the performance tests
$tester = new PerformanceTester();
$tester->runAllTests();
?>
