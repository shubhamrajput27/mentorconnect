<?php
// Advanced Analytics and Reporting System
require_once '../config/optimized-config.php';
require_once '../config/cache-manager.php';

class AnalyticsEngine {
    private $db;
    private $cache;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = Cache::getInstance();
    }
    
    /**
     * Get comprehensive dashboard analytics
     */
    public function getDashboardAnalytics($dateRange = '30_days') {
        $cacheKey = "dashboard_analytics_{$dateRange}";
        
        return $this->cache->remember($cacheKey, function() use ($dateRange) {
            $dateFilter = $this->getDateFilter($dateRange);
            
            return [
                'overview' => $this->getOverviewMetrics($dateFilter),
                'user_growth' => $this->getUserGrowthMetrics($dateFilter),
                'session_analytics' => $this->getSessionAnalytics($dateFilter),
                'revenue_metrics' => $this->getRevenueMetrics($dateFilter),
                'engagement_metrics' => $this->getEngagementMetrics($dateFilter),
                'performance_metrics' => $this->getPerformanceMetrics($dateFilter),
                'geographic_data' => $this->getGeographicData($dateFilter),
                'top_skills' => $this->getTopSkills($dateFilter),
                'mentor_performance' => $this->getMentorPerformance($dateFilter),
                'completion_rates' => $this->getCompletionRates($dateFilter)
            ];
        }, 900); // Cache for 15 minutes
    }
    
    /**
     * Get overview metrics
     */
    private function getOverviewMetrics($dateFilter) {
        // Optimized: Use separate queries instead of complex JOINs for better performance
        $cacheKey = "overview_metrics_" . md5($dateFilter['start_date']);
        
        return $this->cache->remember($cacheKey, function() use ($dateFilter) {
            // Users count
            $usersSql = "SELECT 
                COUNT(CASE WHEN user_type = 'student' THEN 1 END) as total_students,
                COUNT(CASE WHEN user_type = 'mentor' THEN 1 END) as total_mentors
                FROM users WHERE created_at >= ? AND is_active = 1";
            $stmt = $this->db->prepare($usersSql);
            $stmt->execute([$dateFilter['start_date']]);
            $users = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Sessions count
            $sessionsSql = "SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                SUM(CASE WHEN status = 'completed' THEN duration_minutes ELSE 0 END) as total_session_hours
                FROM sessions WHERE scheduled_at >= ?";
            $stmt = $this->db->prepare($sessionsSql);
            $stmt->execute([$dateFilter['start_date']]);
            $sessions = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Messages count
            $messagesSql = "SELECT COUNT(*) as total_messages FROM messages WHERE created_at >= ?";
            $stmt = $this->db->prepare($messagesSql);
            $stmt->execute([$dateFilter['start_date']]);
            $messages = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Reviews count and average
            $reviewsSql = "SELECT COUNT(*) as total_reviews, AVG(rating) as average_rating FROM reviews WHERE created_at >= ?";
            $stmt = $this->db->prepare($reviewsSql);
            $stmt->execute([$dateFilter['start_date']]);
            $reviews = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return array_merge($users, $sessions, $messages, $reviews);
        }, 600); // Cache for 10 minutes
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
     * Generate custom report
     */
    public function generateCustomReport($reportConfig) {
        $reportId = uniqid('report_');
        $reportData = [
            'id' => $reportId,
            'config' => $reportConfig,
            'generated_at' => date('c'),
            'data' => []
        ];
        
        // Process each metric in the report config
        foreach ($reportConfig['metrics'] as $metric) {
            $reportData['data'][$metric] = $this->getMetricData($metric, $reportConfig);
        }
        
        // Save report to cache for later retrieval
        $this->cache->set("report_{$reportId}", $reportData, 3600); // 1 hour
        
        return $reportData;
    }
    
    /**
     * Export analytics data
     */
    public function exportAnalytics($format = 'csv', $dateRange = '30_days') {
        $data = $this->getDashboardAnalytics($dateRange);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCSV($data);
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'xlsx':
                return $this->exportToExcel($data);
            default:
                throw new Exception("Unsupported export format: {$format}");
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
     * Predictive analytics using simple trend analysis
     */
    public function getPredictiveInsights($dateRange = '90_days') {
        $historicalData = $this->getDashboardAnalytics($dateRange);
        
        return [
            'user_growth_trend' => $this->calculateGrowthTrend($historicalData['user_growth']),
            'revenue_projection' => $this->projectRevenue($historicalData['revenue_metrics']),
            'session_demand_forecast' => $this->forecastSessionDemand($historicalData['session_analytics']),
            'churn_prediction' => $this->predictChurn(),
            'capacity_planning' => $this->analyzeCapacity($historicalData)
        ];
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
            $this->cache->set('health_check', 'ok', 10);
            return $this->cache->get('health_check') === 'ok' ? 'healthy' : 'error';
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
