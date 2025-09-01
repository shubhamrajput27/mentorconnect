<?php
/**
 * Database Maintenance and Cleanup Utility
 * Handles routine database maintenance, optimization, and cleanup tasks
 */

require_once '../config/config.php';

class DatabaseMaintenance {
    private $db;
    private $optimizer;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->optimizer = DatabaseOptimizer::getInstance();
    }
    
    /**
     * Run comprehensive database maintenance
     */
    public function runMaintenance($options = []) {
        $results = [
            'started_at' => date('Y-m-d H:i:s'),
            'tasks' => [],
            'errors' => [],
            'summary' => []
        ];
        
        try {
            // 1. Clean up old data
            if ($options['cleanup'] ?? true) {
                $results['tasks']['cleanup'] = $this->cleanupOldData();
            }
            
            // 2. Optimize tables
            if ($options['optimize'] ?? true) {
                $results['tasks']['optimize'] = $this->optimizeTables();
            }
            
            // 3. Update statistics
            if ($options['analyze'] ?? true) {
                $results['tasks']['analyze'] = $this->analyzeTableStatistics();
            }
            
            // 4. Check for unused indexes
            if ($options['index_check'] ?? true) {
                $results['tasks']['index_check'] = $this->checkUnusedIndexes();
            }
            
            // 5. Rebuild fragmented indexes
            if ($options['rebuild_indexes'] ?? false) {
                $results['tasks']['rebuild_indexes'] = $this->rebuildFragmentedIndexes();
            }
            
            // 6. Update mentor ratings
            if ($options['update_ratings'] ?? true) {
                $results['tasks']['update_ratings'] = $this->updateMentorRatings();
            }
            
            // 7. Generate maintenance report
            $results['summary'] = $this->generateMaintenanceReport();
            $results['completed_at'] = date('Y-m-d H:i:s');
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            error_log("Database maintenance error: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Clean up old and unnecessary data
     */
    private function cleanupOldData() {
        $results = ['cleaned' => 0, 'details' => []];
        
        try {
            // Clean up old notifications (read notifications older than 90 days)
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            $results['details']['old_notifications'] = $deleted;
            $results['cleaned'] += $deleted;
            
            // Clean up old activities (older than 1 year)
            $stmt = $this->db->prepare("DELETE FROM activities WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            $results['details']['old_activities'] = $deleted;
            $results['cleaned'] += $deleted;
            
            // Clean up old user sessions (inactive for 30+ days)
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            $results['details']['old_sessions'] = $deleted;
            $results['cleaned'] += $deleted;
            
            // Clean up expired remember tokens
            $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            $results['details']['expired_tokens'] = $deleted;
            $results['cleaned'] += $deleted;
            
            // Clean up old request logs (older than 30 days)
            if ($this->tableExists('request_logs')) {
                $stmt = $this->db->prepare("DELETE FROM request_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stmt->execute();
                $deleted = $stmt->rowCount();
                $results['details']['old_request_logs'] = $deleted;
                $results['cleaned'] += $deleted;
            }
            
            // Clean up old security logs (older than 6 months)
            if ($this->tableExists('security_logs')) {
                $stmt = $this->db->prepare("DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH) AND severity = 'low'");
                $stmt->execute();
                $deleted = $stmt->rowCount();
                $results['details']['old_security_logs'] = $deleted;
                $results['cleaned'] += $deleted;
            }
            
            // Clean up orphaned files (files not referenced in any session or message)
            $stmt = $this->db->prepare("
                DELETE FROM files 
                WHERE session_id IS NULL 
                AND id NOT IN (
                    SELECT DISTINCT file_id 
                    FROM file_permissions 
                    WHERE file_id IS NOT NULL
                )
                AND created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)
            ");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            $results['details']['orphaned_files'] = $deleted;
            $results['cleaned'] += $deleted;
            
        } catch (Exception $e) {
            throw new Exception("Cleanup failed: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Optimize database tables
     */
    private function optimizeTables() {
        return $this->optimizer->performMaintenance();
    }
    
    /**
     * Analyze table statistics
     */
    private function analyzeTableStatistics() {
        $results = ['tables_analyzed' => 0, 'statistics' => []];
        
        try {
            $tables = $this->getTableList();
            
            foreach ($tables as $table) {
                $stmt = $this->db->query("ANALYZE TABLE {$table}");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $results['statistics'][$table] = $result;
                $results['tables_analyzed']++;
            }
            
        } catch (Exception $e) {
            throw new Exception("Table analysis failed: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Check for unused indexes
     */
    private function checkUnusedIndexes() {
        $results = ['unused_indexes' => [], 'recommendations' => []];
        
        try {
            // This would require MySQL Performance Schema
            // For now, provide general recommendations
            $results['recommendations'] = [
                'Enable performance schema for detailed index usage statistics',
                'Monitor slow query log for index optimization opportunities',
                'Consider removing indexes on rarely queried columns',
                'Review composite index order for optimal performance'
            ];
            
        } catch (Exception $e) {
            throw new Exception("Index check failed: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Rebuild fragmented indexes
     */
    private function rebuildFragmentedIndexes() {
        $results = ['indexes_rebuilt' => 0, 'tables_processed' => []];
        
        try {
            $tables = $this->getTableList();
            
            foreach ($tables as $table) {
                // Check table fragmentation
                $stmt = $this->db->prepare("
                    SELECT data_free, data_length 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE() AND table_name = ?
                ");
                $stmt->execute([$table]);
                $info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($info && $info['data_free'] > 0) {
                    $fragmentation = ($info['data_free'] / ($info['data_length'] + $info['data_free'])) * 100;
                    
                    // Rebuild if fragmentation > 10%
                    if ($fragmentation > 10) {
                        $this->db->exec("ALTER TABLE {$table} ENGINE=InnoDB");
                        $results['indexes_rebuilt']++;
                        $results['tables_processed'][] = [
                            'table' => $table,
                            'fragmentation' => round($fragmentation, 2)
                        ];
                    }
                }
            }
            
        } catch (Exception $e) {
            throw new Exception("Index rebuild failed: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Update mentor ratings
     */
    private function updateMentorRatings() {
        $results = ['mentors_updated' => 0, 'ratings_updated' => []];
        
        try {
            // Get all mentors
            $stmt = $this->db->query("SELECT user_id FROM mentor_profiles");
            $mentors = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($mentors as $mentorId) {
                // Calculate new rating
                $stmt = $this->db->prepare("
                    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                    FROM reviews 
                    WHERE reviewee_id = ?
                ");
                $stmt->execute([$mentorId]);
                $ratingData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Count completed sessions
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as session_count 
                    FROM sessions 
                    WHERE mentor_id = ? AND status = 'completed'
                ");
                $stmt->execute([$mentorId]);
                $sessionData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update mentor profile
                $stmt = $this->db->prepare("
                    UPDATE mentor_profiles 
                    SET rating = ?, total_sessions = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $ratingData['avg_rating'] ?? 0,
                    $sessionData['session_count'] ?? 0,
                    $mentorId
                ]);
                
                $results['mentors_updated']++;
                $results['ratings_updated'][] = [
                    'mentor_id' => $mentorId,
                    'rating' => round($ratingData['avg_rating'] ?? 0, 2),
                    'sessions' => $sessionData['session_count'] ?? 0
                ];
            }
            
        } catch (Exception $e) {
            throw new Exception("Rating update failed: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Generate maintenance report
     */
    private function generateMaintenanceReport() {
        $report = [
            'database_size' => $this->getDatabaseSize(),
            'table_count' => count($this->getTableList()),
            'index_count' => $this->getIndexCount(),
            'user_statistics' => $this->getUserStatistics(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];
        
        return $report;
    }
    
    /**
     * Get database size information
     */
    private function getDatabaseSize() {
        $stmt = $this->db->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as total_size_mb,
                ROUND(SUM(data_length) / 1024 / 1024, 2) as data_size_mb,
                ROUND(SUM(index_length) / 1024 / 1024, 2) as index_size_mb,
                ROUND(SUM(data_free) / 1024 / 1024, 2) as free_space_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get index count
     */
    private function getIndexCount() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as index_count
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE()
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['index_count'];
    }
    
    /**
     * Get user statistics
     */
    private function getUserStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN role = 'mentor' THEN 1 END) as mentors,
                COUNT(CASE WHEN role = 'student' THEN 1 END) as students,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_last_30_days
            FROM users
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics() {
        return $this->optimizer->getHealthMetrics();
    }
    
    /**
     * Get list of tables
     */
    private function getTableList() {
        $stmt = $this->db->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Check if table exists
     */
    private function tableExists($tableName) {
        $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $options = [
        'cleanup' => true,
        'optimize' => true,
        'analyze' => true,
        'index_check' => true,
        'rebuild_indexes' => false, // Disabled by default as it's intensive
        'update_ratings' => true
    ];
    
    // Parse command line arguments
    $args = array_slice($argv, 1);
    foreach ($args as $arg) {
        if ($arg === '--rebuild-indexes') {
            $options['rebuild_indexes'] = true;
        } elseif ($arg === '--no-cleanup') {
            $options['cleanup'] = false;
        } elseif ($arg === '--no-optimize') {
            $options['optimize'] = false;
        }
    }
    
    echo "Starting database maintenance...\n";
    
    $maintenance = new DatabaseMaintenance();
    $results = $maintenance->runMaintenance($options);
    
    echo "Maintenance completed!\n";
    echo "Started: " . $results['started_at'] . "\n";
    echo "Completed: " . $results['completed_at'] . "\n";
    
    if (!empty($results['errors'])) {
        echo "\nErrors encountered:\n";
        foreach ($results['errors'] as $error) {
            echo "- " . $error . "\n";
        }
    }
    
    echo "\nSummary:\n";
    echo json_encode($results['summary'], JSON_PRETTY_PRINT);
}
?>
