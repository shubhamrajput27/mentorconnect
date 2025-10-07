<?php
/**
 * Analytics system for MentorConnect platform
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

class AnalyticsEngine {
    private $db;
    private $cachePrefix = 'analytics_';
    private $defaultCacheTTL = 900; // 15 minutes
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log('Analytics Engine DB Connection Error: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    /**
     * Get comprehensive dashboard analytics with improved caching
     * 
     * @param string $dateRange Date range filter (7_days, 30_days, 90_days, 1_year)
     * @return array Analytics data array
     * @throws Exception If data retrieval fails
     */
    public function getDashboardAnalytics($dateRange = '30_days') {
        $cacheKey = $this->cachePrefix . "dashboard_{$dateRange}";
        
        // Try to get cached data first
        $cachedData = $this->getCachedData($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        try {
            $dateFilter = $this->getDateFilter($dateRange);
            
            $analytics = [
                'overview' => $this->getOverviewMetrics($dateFilter),
                'user_growth' => $this->getUserGrowthMetrics($dateFilter),
                'session_analytics' => $this->getSessionAnalytics($dateFilter),
                'engagement_metrics' => $this->getEngagementMetrics($dateFilter),
                'completion_rates' => $this->getCompletionRates($dateFilter),
                'generated_at' => date('c'),
                'cache_ttl' => $this->defaultCacheTTL
            ];
            
            // Cache the results
            $this->setCachedData($cacheKey, $analytics, $this->defaultCacheTTL);
            
            return $analytics;
            
        } catch (Exception $e) {
            error_log('Dashboard Analytics Error: ' . $e->getMessage());
            throw new Exception('Failed to retrieve dashboard analytics');
        }
    }
    
    /**
     * Simple file-based caching for analytics data
     */
    private function getCachedData($key) {
        $cacheFile = sys_get_temp_dir() . '/mc_' . md5($key) . '.cache';
        
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $cacheData = json_decode($data, true);
            
            if ($cacheData && isset($cacheData['expires']) && $cacheData['expires'] > time()) {
                return $cacheData['data'];
            }
            
            // Clean up expired cache
            @unlink($cacheFile);
        }
        
        return null;
    }
    
    private function setCachedData($key, $data, $ttl = 900) {
        $cacheFile = sys_get_temp_dir() . '/mc_' . md5($key) . '.cache';
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        @file_put_contents($cacheFile, json_encode($cacheData));
    }

    /**
     * Get overview metrics with improved error handling
     */
    private function getOverviewMetrics($dateFilter) {
        $cacheKey = $this->cachePrefix . "overview_" . md5($dateFilter['start_date']);
        
        $cached = $this->getCachedData($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            // Check if users table exists and has the expected columns
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'users'");
            if (!$tableCheck->fetch()) {
                throw new Exception('Users table not found');
            }
            
            // Users count with safe column handling
            $usersSql = "SELECT 
                COUNT(CASE WHEN role = 'student' THEN 1 END) as total_students,
                COUNT(CASE WHEN role = 'mentor' THEN 1 END) as total_mentors,
                COUNT(*) as total_users
                FROM users WHERE created_at >= ? AND (status = 'active' OR status IS NULL)";
            $stmt = $this->db->prepare($usersSql);
            $stmt->execute([$dateFilter['start_date']]);
            $users = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_students' => 0, 'total_mentors' => 0, 'total_users' => 0];
            
            // Sessions count with table existence check
            $sessions = ['total_sessions' => 0, 'completed_sessions' => 0, 'total_session_hours' => 0];
            $sessionTableCheck = $this->db->query("SHOW TABLES LIKE 'sessions'");
            if ($sessionTableCheck->fetch()) {
                $sessionsSql = "SELECT 
                    COUNT(*) as total_sessions,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                    SUM(CASE WHEN status = 'completed' THEN COALESCE(duration, 60) ELSE 0 END) as total_session_minutes
                    FROM sessions WHERE created_at >= ?";
                $stmt = $this->db->prepare($sessionsSql);
                $stmt->execute([$dateFilter['start_date']]);
                $sessions = $stmt->fetch(PDO::FETCH_ASSOC) ?: $sessions;
                $sessions['total_session_hours'] = round(($sessions['total_session_minutes'] ?? 0) / 60, 2);
            }
            
            // Messages count with table existence check
            $messages = ['total_messages' => 0];
            $messageTableCheck = $this->db->query("SHOW TABLES LIKE 'messages'");
            if ($messageTableCheck->fetch()) {
                $messagesSql = "SELECT COUNT(*) as total_messages FROM messages WHERE created_at >= ?";
                $stmt = $this->db->prepare($messagesSql);
                $stmt->execute([$dateFilter['start_date']]);
                $messages = $stmt->fetch(PDO::FETCH_ASSOC) ?: $messages;
            }
            
            // Reviews count and average with table existence check
            $reviews = ['total_reviews' => 0, 'average_rating' => 0];
            $reviewTableCheck = $this->db->query("SHOW TABLES LIKE 'reviews'");
            if ($reviewTableCheck->fetch()) {
                $reviewsSql = "SELECT COUNT(*) as total_reviews, COALESCE(AVG(rating), 0) as average_rating FROM reviews WHERE created_at >= ?";
                $stmt = $this->db->prepare($reviewsSql);
                $stmt->execute([$dateFilter['start_date']]);
                $reviews = $stmt->fetch(PDO::FETCH_ASSOC) ?: $reviews;
                $reviews['average_rating'] = round($reviews['average_rating'], 2);
            }
            
            $result = array_merge($users, $sessions, $messages, $reviews);
            $this->setCachedData($cacheKey, $result, 600);
            return $result;
            
        } catch (Exception $e) {
            error_log('Overview Metrics Error: ' . $e->getMessage());
            // Return default values on error
            return [
                'total_students' => 0, 'total_mentors' => 0, 'total_users' => 0,
                'total_sessions' => 0, 'completed_sessions' => 0, 'total_session_hours' => 0,
                'total_messages' => 0, 'total_reviews' => 0, 'average_rating' => 0
            ];
        }
    }
    
    /**
     * Get user growth metrics
     */
    private function getUserGrowthMetrics($dateFilter) {
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(CASE WHEN role = 'student' THEN 1 END) as new_students,
                COUNT(CASE WHEN role = 'mentor' THEN 1 END) as new_mentors,
                COUNT(*) as total_new_users
            FROM users 
            WHERE created_at >= ? AND created_at <= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get session analytics
     */
    private function getSessionAnalytics($dateFilter) {
        $sql = "
            SELECT 
                DATE(scheduled_at) as date,
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_sessions,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_sessions,
                AVG(duration) as avg_duration,
                SUM(CASE WHEN status = 'completed' THEN duration ELSE 0 END) as total_hours
            FROM sessions 
            WHERE scheduled_at >= ? AND scheduled_at <= ?
            GROUP BY DATE(scheduled_at)
            ORDER BY date
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get revenue metrics
     */
    private function getRevenueMetrics($dateFilter) {
        $sql = "
            SELECT 
                DATE(p.created_at) as date,
                COUNT(*) as total_payments,
                SUM(p.amount) as total_revenue,
                AVG(p.amount) as avg_payment,
                COUNT(DISTINCT p.student_id) as paying_students,
                COUNT(DISTINCT s.mentor_id) as earning_mentors
            FROM payments p
            JOIN sessions s ON p.session_id = s.id
            WHERE p.created_at >= ? AND p.created_at <= ? AND p.status = 'completed'
            GROUP BY DATE(p.created_at)
            ORDER BY date
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get engagement metrics
     */
    private function getEngagementMetrics($dateFilter) {
        // Active users (users who performed any action)
        $activeUsersSql = "
            SELECT COUNT(DISTINCT user_id) as active_users
            FROM user_activity_log 
            WHERE created_at >= ? AND created_at <= ?
        ";
        
        $stmt = $this->db->prepare($activeUsersSql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Message activity
        $messagesSql = "
            SELECT 
                COUNT(*) as total_messages,
                COUNT(DISTINCT sender_id) as active_senders,
                COUNT(DISTINCT receiver_id) as active_receivers,
                AVG(LENGTH(message)) as avg_message_length
            FROM messages 
            WHERE created_at >= ? AND created_at <= ?
        ";
        
        $stmt = $this->db->prepare($messagesSql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        $messages = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // File sharing activity
        $filesSql = "
            SELECT 
                COUNT(*) as files_shared,
                COUNT(DISTINCT uploader_id) as active_uploaders,
                SUM(file_size) as total_file_size
            FROM files 
            WHERE created_at >= ? AND created_at <= ?
        ";
        
        $stmt = $this->db->prepare($filesSql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        $files = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return array_merge($activeUsers, $messages, $files);
    }
    
    /**
     * Get system performance metrics
     */
    private function getPerformanceMetrics($dateFilter) {
        // Average response times, error rates, etc.
        $performanceSql = "
            SELECT 
                AVG(response_time) as avg_response_time,
                COUNT(CASE WHEN response_time > 2000 THEN 1 END) as slow_requests,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                COUNT(*) as total_requests
            FROM request_logs 
            WHERE created_at >= ? AND created_at <= ?
        ";
        
        $stmt = $this->db->prepare($performanceSql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'avg_response_time' => 0,
            'slow_requests' => 0,
            'error_requests' => 0,
            'total_requests' => 0
        ];
    }
    
    /**
     * Get geographic distribution
     */
    private function getGeographicData($dateFilter) {
        $sql = "
            SELECT 
                country,
                COUNT(*) as user_count,
                COUNT(CASE WHEN role = 'mentor' THEN 1 END) as mentor_count,
                COUNT(CASE WHEN role = 'student' THEN 1 END) as student_count
            FROM users 
            WHERE created_at >= ? AND country IS NOT NULL
            GROUP BY country
            ORDER BY user_count DESC
            LIMIT 20
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFilter['start_date']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top skills
     */
    private function getTopSkills($dateFilter) {
        $sql = "
            SELECT 
                s.name as skill_name,
                COUNT(DISTINCT us.user_id) as total_users,
                COUNT(DISTINCT CASE WHEN u.role = 'mentor' THEN us.user_id END) as mentor_count,
                COUNT(DISTINCT CASE WHEN u.role = 'student' THEN us.user_id END) as student_count,
                COUNT(DISTINCT sess.id) as sessions_count
            FROM skills s
            JOIN user_skills us ON s.id = us.skill_id
            JOIN users u ON us.user_id = u.id
            LEFT JOIN sessions sess ON u.id = sess.mentor_id AND sess.scheduled_at >= ?
            WHERE u.created_at >= ?
            GROUP BY s.id, s.name
            ORDER BY total_users DESC
            LIMIT 20
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['start_date']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get mentor performance metrics
     */
    private function getMentorPerformance($dateFilter) {
        $sql = "
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                COUNT(s.id) as total_sessions,
                COUNT(CASE WHEN s.status = 'completed' THEN 1 END) as completed_sessions,
                AVG(r.rating) as avg_rating,
                COUNT(r.id) as review_count,
                SUM(CASE WHEN s.status = 'completed' THEN s.duration ELSE 0 END) as total_hours,
                SUM(p.amount) as total_earnings
            FROM users u
            JOIN mentor_profiles mp ON u.id = mp.user_id
            LEFT JOIN sessions s ON u.id = s.mentor_id AND s.scheduled_at >= ?
            LEFT JOIN reviews r ON u.id = r.mentor_id AND r.created_at >= ?
            LEFT JOIN payments p ON s.id = p.session_id AND p.created_at >= ?
            WHERE u.role = 'mentor' AND u.status = 'active'
            GROUP BY u.id
            HAVING total_sessions > 0
            ORDER BY avg_rating DESC, total_sessions DESC
            LIMIT 50
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['start_date'], $dateFilter['start_date']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get completion rates and success metrics
     */
    private function getCompletionRates($dateFilter) {
        $sql = "
            SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_sessions,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_sessions,
                ROUND(COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2) as completion_rate,
                ROUND(COUNT(CASE WHEN status = 'cancelled' THEN 1 END) * 100.0 / COUNT(*), 2) as cancellation_rate
            FROM sessions 
            WHERE scheduled_at >= ? AND scheduled_at <= ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFilter['start_date'], $dateFilter['end_date']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate custom report with basic metrics
     */
    public function generateCustomReport($reportConfig) {
        $reportId = uniqid('report_');
        $reportData = [
            'id' => $reportId,
            'config' => $reportConfig,
            'generated_at' => date('c'),
            'data' => []
        ];
        
        try {
            $dateFilter = $this->getDateFilter($reportConfig['date_range'] ?? '30_days');
            
            // Process basic metrics only
            $reportData['data'] = [
                'overview' => $this->getOverviewMetrics($dateFilter),
                'user_growth' => $this->getUserGrowthMetrics($dateFilter),
                'completion_rates' => $this->getCompletionRates($dateFilter)
            ];
            
            // Save report to cache
            $this->setCachedData($this->cachePrefix . "report_{$reportId}", $reportData, 3600);
            
            return $reportData;
            
        } catch (Exception $e) {
            error_log('Custom Report Error: ' . $e->getMessage());
            throw new Exception('Failed to generate custom report');
        }
    }
    
    /**
     * Export analytics data in supported formats
     */
    public function exportAnalytics($format = 'csv', $dateRange = '30_days') {
        try {
            $data = $this->getDashboardAnalytics($dateRange);
            
            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($data);
                case 'json':
                    return json_encode($data, JSON_PRETTY_PRINT);
                default:
                    throw new Exception("Unsupported export format: {$format}. Supported formats: csv, json");
            }
        } catch (Exception $e) {
            error_log('Export Analytics Error: ' . $e->getMessage());
            throw new Exception('Failed to export analytics data');
        }
    }
    
    /**
     * Real-time analytics stream
     */
    public function getRealtimeMetrics() {
        return [
            'active_sessions' => $this->getActiveSessionsCount(),
            'online_users' => $this->getOnlineUsersCount(),
            'recent_signups' => $this->getRecentSignups(24), // Last 24 hours
            'pending_notifications' => $this->getPendingNotificationsCount(),
            'system_health' => $this->getSystemHealth()
        ];
    }
    
    /**
     * Basic trend analysis and insights
     */
    public function getPredictiveInsights($dateRange = '90_days') {
        try {
            $historicalData = $this->getDashboardAnalytics($dateRange);
            
            return [
                'user_growth_trend' => $this->calculateBasicTrend($historicalData['user_growth'] ?? []),
                'completion_trend' => $this->calculateBasicTrend($historicalData['completion_rates'] ?? []),
                'insights_generated_at' => date('c')
            ];
        } catch (Exception $e) {
            error_log('Predictive Insights Error: ' . $e->getMessage());
            return [
                'user_growth_trend' => 'stable',
                'completion_trend' => 'stable',
                'error' => 'Unable to calculate trends'
            ];
        }
    }
    
    /**
     * Calculate basic trend from data array
     */
    private function calculateBasicTrend($data) {
        if (empty($data) || !is_array($data)) {
            return 'stable';
        }
        
        $values = array_column($data, 'total_new_users');
        if (empty($values)) {
            return 'stable';
        }
        
        $firstHalf = array_slice($values, 0, floor(count($values) / 2));
        $secondHalf = array_slice($values, floor(count($values) / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $change = ($secondAvg - $firstAvg) / max($firstAvg, 1) * 100;
        
        if ($change > 10) return 'increasing';
        if ($change < -10) return 'decreasing';
        return 'stable';
    }
    
    // Helper methods
    private function getDateFilter($dateRange) {
        $endDate = date('Y-m-d 23:59:59');
        
        switch ($dateRange) {
            case '7_days':
                $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case '30_days':
                $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case '90_days':
                $startDate = date('Y-m-d 00:00:00', strtotime('-90 days'));
                break;
            case '1_year':
                $startDate = date('Y-m-d 00:00:00', strtotime('-1 year'));
                break;
            default:
                $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
        
        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sessions' => "AND s.scheduled_at >= '{$startDate}' AND s.scheduled_at <= '{$endDate}'",
            'messages' => "AND m.created_at >= '{$startDate}' AND m.created_at <= '{$endDate}'",
            'reviews' => "AND r.created_at >= '{$startDate}' AND r.created_at <= '{$endDate}'"
        ];
    }
    
    private function getActiveSessionsCount() {
        $sql = "SELECT COUNT(*) as count FROM sessions WHERE status = 'active' AND scheduled_at <= NOW() AND DATE_ADD(scheduled_at, INTERVAL duration MINUTE) >= NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    private function getOnlineUsersCount() {
        // Users active in the last 5 minutes
        $sql = "SELECT COUNT(DISTINCT user_id) as count FROM user_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    private function getRecentSignups($hours) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hours]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    private function getPendingNotificationsCount() {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    private function getSystemHealth() {
        return [
            'database_status' => $this->checkDatabaseHealth(),
            'cache_status' => $this->checkCacheHealth(),
            'disk_usage' => $this->getDiskUsage(),
            'memory_usage' => $this->getMemoryUsage()
        ];
    }
    
    private function checkDatabaseHealth() {
        try {
            $this->db->query("SELECT 1");
            return 'healthy';
        } catch (Exception $e) {
            return 'error';
        }
    }
    
    private function checkCacheHealth() {
        try {
            $testKey = $this->cachePrefix . 'health_check';
            $testData = 'ok';
            
            $this->setCachedData($testKey, $testData, 10);
            $result = $this->getCachedData($testKey);
            
            return $result === $testData ? 'healthy' : 'error';
        } catch (Exception $e) {
            return 'error';
        }
    }
    
    private function getDiskUsage() {
        $freeBytes = disk_free_space(__DIR__);
        $totalBytes = disk_total_space(__DIR__);
        return [
            'free_space' => $freeBytes,
            'total_space' => $totalBytes,
            'usage_percentage' => round(($totalBytes - $freeBytes) / $totalBytes * 100, 2)
        ];
    }
    
    private function getMemoryUsage() {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
    }
    
    private function exportToCSV($data) {
        $csv = "Metric,Value\n";
        
        function flattenArray($array, $prefix = '') {
            $result = [];
            foreach ($array as $key => $value) {
                $newKey = $prefix ? $prefix . '.' . $key : $key;
                if (is_array($value)) {
                    $result = array_merge($result, flattenArray($value, $newKey));
                } else {
                    $result[$newKey] = $value;
                }
            }
            return $result;
        }
        
        $flatData = flattenArray($data);
        foreach ($flatData as $key => $value) {
            $csv .= "\"{$key}\",\"{$value}\"\n";
        }
        
        return $csv;
    }
}

// API endpoint for analytics
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    try {
        $analytics = new AnalyticsEngine();
        $action = $_GET['action'] ?? 'dashboard';
        $dateRange = $_GET['range'] ?? '30_days';
        
        switch ($action) {
            case 'dashboard':
                $data = $analytics->getDashboardAnalytics($dateRange);
                break;
            case 'realtime':
                $data = $analytics->getRealtimeMetrics();
                break;
            case 'predictive':
                $data = $analytics->getPredictiveInsights($dateRange);
                break;
            case 'export':
                $format = $_GET['format'] ?? 'json';
                $data = $analytics->exportAnalytics($format, $dateRange);
                if ($format === 'csv') {
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="analytics.csv"');
                    echo $data;
                    exit;
                }
                break;
            default:
                throw new Exception('Invalid action');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'generated_at' => date('c')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
