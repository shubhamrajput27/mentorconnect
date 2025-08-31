<?php
/**
 * Database Performance Optimization Class
 * Implements caching, connection pooling, and query optimization
 */

class DatabaseOptimizer {
    private static $queryCache = [];
    private static $cacheStats = ['hits' => 0, 'misses' => 0];
    private static $slowQueryLog = [];
    
    public static function executeOptimizedQuery($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
        $startTime = microtime(true);
        
        // Check cache first if cache key provided
        if ($cacheKey && isset(self::$queryCache[$cacheKey])) {
            $cached = self::$queryCache[$cacheKey];
            if (time() - $cached['timestamp'] < $cacheTTL) {
                self::$cacheStats['hits']++;
                return $cached['data'];
            } else {
                unset(self::$queryCache[$cacheKey]);
            }
        }
        
        // Execute query
        $result = executeQuery($sql, $params);
        $data = $result->fetchAll();
        
        $executionTime = microtime(true) - $startTime;
        
        // Log slow queries
        if ($executionTime > 1.0) { // Queries taking more than 1 second
            self::$slowQueryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'execution_time' => $executionTime,
                'timestamp' => time()
            ];
        }
        
        // Cache result if cache key provided
        if ($cacheKey) {
            self::$queryCache[$cacheKey] = [
                'data' => $data,
                'timestamp' => time()
            ];
            self::$cacheStats['misses']++;
            
            // Prevent memory overflow - keep only last 100 cached queries
            if (count(self::$queryCache) > 100) {
                $oldestKey = array_key_first(self::$queryCache);
                unset(self::$queryCache[$oldestKey]);
            }
        }
        
        return $data;
    }
    
    public static function getCacheStats() {
        $total = self::$cacheStats['hits'] + self::$cacheStats['misses'];
        $hitRatio = $total > 0 ? (self::$cacheStats['hits'] / $total) * 100 : 0;
        
        return [
            'hits' => self::$cacheStats['hits'],
            'misses' => self::$cacheStats['misses'],
            'hit_ratio' => round($hitRatio, 2),
            'cached_queries' => count(self::$queryCache)
        ];
    }
    
    public static function getSlowQueries() {
        return self::$slowQueryLog;
    }
    
    public static function clearCache() {
        self::$queryCache = [];
        self::$cacheStats = ['hits' => 0, 'misses' => 0];
    }
    
    /**
     * Optimize common queries with specific implementations
     */
    public static function getNotificationsOptimized($userId, $limit = 20, $unreadOnly = false) {
        $cacheKey = "notifications_{$userId}_{$limit}_" . ($unreadOnly ? '1' : '0');
        
        $whereClause = "user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $whereClause .= " AND is_read = FALSE";
        }
        
        $sql = "SELECT id, type, title, content, action_url, is_read, created_at 
                FROM notifications 
                WHERE {$whereClause} 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $params[] = $limit;
        
        return self::executeOptimizedQuery($sql, $params, $cacheKey, 60); // Cache for 1 minute
    }
    
    public static function getMentorListOptimized($filters = [], $limit = 20, $offset = 0) {
        $cacheKey = "mentors_" . md5(serialize($filters) . "_{$limit}_{$offset}");
        
        $whereConditions = ["u.role = 'mentor'", "u.status = 'active'"];
        $params = [];
        
        if (!empty($filters['skills'])) {
            $placeholders = str_repeat('?,', count($filters['skills']) - 1) . '?';
            $whereConditions[] = "EXISTS (
                SELECT 1 FROM mentor_skills ms 
                WHERE ms.mentor_id = u.id 
                AND ms.skill_name IN ({$placeholders})
            )";
            $params = array_merge($params, $filters['skills']);
        }
        
        if (!empty($filters['experience_level'])) {
            $whereConditions[] = "mp.experience_level = ?";
            $params[] = $filters['experience_level'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT u.id, u.username, u.email, 
                       mp.bio, mp.hourly_rate, mp.experience_level,
                       AVG(r.rating) as avg_rating,
                       COUNT(r.id) as review_count
                FROM users u
                LEFT JOIN mentor_profiles mp ON u.id = mp.user_id
                LEFT JOIN reviews r ON u.id = r.mentor_id
                WHERE {$whereClause}
                GROUP BY u.id
                ORDER BY avg_rating DESC, review_count DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return self::executeOptimizedQuery($sql, $params, $cacheKey, 300); // Cache for 5 minutes
    }
    
    public static function getUserDashboardDataOptimized($userId) {
        $cacheKey = "dashboard_{$userId}";
        
        // Get all dashboard data in a single optimized query
        $sql = "SELECT 
                    u.id, u.username, u.email, u.role,
                    up.theme, up.language,
                    (SELECT COUNT(*) FROM notifications WHERE user_id = u.id AND is_read = FALSE) as unread_notifications,
                    (SELECT COUNT(*) FROM sessions WHERE student_id = u.id AND status = 'scheduled') as upcoming_sessions,
                    (SELECT COUNT(*) FROM messages WHERE recipient_id = u.id AND is_read = FALSE) as unread_messages
                FROM users u
                LEFT JOIN user_preferences up ON u.id = up.user_id
                WHERE u.id = ?";
        
        $result = self::executeOptimizedQuery($sql, [$userId], $cacheKey, 120); // Cache for 2 minutes
        return $result[0] ?? null;
    }
}

/**
 * Enhanced database functions with optimization
 */
function fetchOptimized($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
    return DatabaseOptimizer::executeOptimizedQuery($sql, $params, $cacheKey, $cacheTTL);
}

function fetchOneOptimized($sql, $params = [], $cacheKey = null, $cacheTTL = 300) {
    $result = DatabaseOptimizer::executeOptimizedQuery($sql, $params, $cacheKey, $cacheTTL);
    return $result[0] ?? null;
}

/**
 * Database Index Recommendations
 */
class IndexOptimizer {
    public static function getRecommendations() {
        return [
            'notifications' => [
                'CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read, created_at)',
                'CREATE INDEX idx_notifications_created ON notifications(created_at)'
            ],
            'sessions' => [
                'CREATE INDEX idx_sessions_student_status ON sessions(student_id, status)',
                'CREATE INDEX idx_sessions_mentor_status ON sessions(mentor_id, status)',
                'CREATE INDEX idx_sessions_scheduled_time ON sessions(scheduled_time)'
            ],
            'messages' => [
                'CREATE INDEX idx_messages_recipient_read ON messages(recipient_id, is_read)',
                'CREATE INDEX idx_messages_conversation ON messages(sender_id, recipient_id, created_at)'
            ],
            'reviews' => [
                'CREATE INDEX idx_reviews_mentor_rating ON reviews(mentor_id, rating)',
                'CREATE INDEX idx_reviews_created ON reviews(created_at)'
            ],
            'users' => [
                'CREATE INDEX idx_users_role_status ON users(role, status)',
                'CREATE INDEX idx_users_email_verified ON users(email, email_verified)'
            ]
        ];
    }
    
    public static function applyRecommendations() {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        foreach (self::getRecommendations() as $table => $indexes) {
            foreach ($indexes as $indexSql) {
                try {
                    $conn->exec($indexSql);
                    echo "Applied: {$indexSql}\n";
                } catch (PDOException $e) {
                    // Index might already exist
                    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                        echo "Error applying {$indexSql}: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
}

/**
 * Query Performance Monitor
 */
class QueryPerformanceMonitor {
    private static $queries = [];
    
    public static function startQuery($sql) {
        $queryId = uniqid();
        self::$queries[$queryId] = [
            'sql' => $sql,
            'start_time' => microtime(true)
        ];
        return $queryId;
    }
    
    public static function endQuery($queryId) {
        if (isset(self::$queries[$queryId])) {
            self::$queries[$queryId]['end_time'] = microtime(true);
            self::$queries[$queryId]['duration'] = 
                self::$queries[$queryId]['end_time'] - self::$queries[$queryId]['start_time'];
        }
    }
    
    public static function getSlowQueries($threshold = 1.0) {
        return array_filter(self::$queries, function($query) use ($threshold) {
            return isset($query['duration']) && $query['duration'] > $threshold;
        });
    }
    
    public static function getStats() {
        $totalQueries = count(self::$queries);
        $completedQueries = array_filter(self::$queries, function($query) {
            return isset($query['duration']);
        });
        
        if (empty($completedQueries)) {
            return [
                'total_queries' => $totalQueries,
                'avg_duration' => 0,
                'slowest_query' => null
            ];
        }
        
        $durations = array_column($completedQueries, 'duration');
        $avgDuration = array_sum($durations) / count($durations);
        
        $slowestQuery = array_reduce($completedQueries, function($carry, $query) {
            return (!$carry || $query['duration'] > $carry['duration']) ? $query : $carry;
        });
        
        return [
            'total_queries' => $totalQueries,
            'completed_queries' => count($completedQueries),
            'avg_duration' => round($avgDuration, 4),
            'slowest_query' => $slowestQuery,
            'slow_queries_count' => count(self::getSlowQueries())
        ];
    }
}
?>
