-- Advanced Database Optimization for MentorConnect
-- Comprehensive index optimization and performance tuning

USE mentorconnect;

-- ====================================
-- ANALYSIS: Current Query Patterns
-- ====================================

-- Drop existing suboptimal indexes
ALTER TABLE users DROP INDEX IF EXISTS idx_email;
ALTER TABLE users DROP INDEX IF EXISTS idx_role;
ALTER TABLE users DROP INDEX IF EXISTS idx_status;

-- ====================================
-- OPTIMIZED COMPOSITE INDEXES
-- ====================================

-- Users table - Optimized for common query patterns
ALTER TABLE users ADD INDEX idx_role_status_active (role, status, id);
ALTER TABLE users ADD INDEX idx_email_status (email, status);
ALTER TABLE users ADD INDEX idx_username_status (username, status);
ALTER TABLE users ADD INDEX idx_status_created (status, created_at);
ALTER TABLE users ADD INDEX idx_remember_token_active (remember_token, status) WHERE remember_token IS NOT NULL;

-- Sessions table - Optimized for booking and scheduling
ALTER TABLE sessions ADD INDEX idx_mentor_status_date (mentor_id, status, scheduled_at);
ALTER TABLE sessions ADD INDEX idx_student_status_date (student_id, status, scheduled_at);
ALTER TABLE sessions ADD INDEX idx_status_scheduled (status, scheduled_at);
ALTER TABLE sessions ADD INDEX idx_mentor_student (mentor_id, student_id);

-- Messages table - Optimized for conversation queries
ALTER TABLE messages ADD INDEX idx_conversation_ordered (sender_id, recipient_id, created_at DESC);
ALTER TABLE messages ADD INDEX idx_recipient_unread (recipient_id, is_read, created_at DESC);
ALTER TABLE messages ADD INDEX idx_sender_time (sender_id, created_at DESC);

-- Notifications table - Optimized for user notification queries
ALTER TABLE notifications ADD INDEX idx_user_unread_priority (user_id, is_read, type, created_at DESC);
ALTER TABLE notifications ADD INDEX idx_user_recent (user_id, created_at DESC);

-- Reviews table - Optimized for rating and review queries
ALTER TABLE reviews ADD INDEX idx_reviewee_rating (reviewee_id, rating, created_at DESC);
ALTER TABLE reviews ADD INDEX idx_reviewer_session (reviewer_id, session_id);
ALTER TABLE reviews ADD INDEX idx_rating_recent (rating, created_at DESC);

-- Mentor-Mentee connections - Optimized for connection management
ALTER TABLE mentor_mentee_connections ADD INDEX idx_mentee_status (mentee_id, status, created_at DESC);
ALTER TABLE mentor_mentee_connections ADD INDEX idx_mentor_status (mentor_id, status, created_at DESC);
ALTER TABLE mentor_mentee_connections ADD INDEX idx_status_created (status, created_at DESC);

-- Files table - Optimized for user file queries
ALTER TABLE files ADD INDEX idx_user_category (user_id, category, created_at DESC);
ALTER TABLE files ADD INDEX idx_public_category (is_public, category, created_at DESC);

-- User skills - Optimized for skill matching
ALTER TABLE user_skills ADD INDEX idx_skill_proficiency (skill_id, proficiency_level, skill_type);
ALTER TABLE user_skills ADD INDEX idx_user_type (user_id, skill_type, proficiency_level);

-- ====================================
-- FULL-TEXT SEARCH INDEXES
-- ====================================

-- Enable full-text search for better user discovery
ALTER TABLE users ADD FULLTEXT idx_ft_user_search (first_name, last_name, bio);
ALTER TABLE skills ADD FULLTEXT idx_ft_skill_search (name, description);
ALTER TABLE sessions ADD FULLTEXT idx_ft_session_search (title, description);

-- ====================================
-- QUERY OPTIMIZATION VIEWS
-- ====================================

-- Create optimized view for active mentors with ratings
CREATE OR REPLACE VIEW active_mentors_with_ratings AS
SELECT 
    u.id,
    u.username,
    u.first_name,
    u.last_name,
    u.bio,
    u.profile_photo,
    u.location,
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(r.id) as total_reviews,
    COUNT(DISTINCT mmc.mentee_id) as total_connections
FROM users u
LEFT JOIN reviews r ON u.id = r.reviewee_id
LEFT JOIN mentor_mentee_connections mmc ON u.id = mmc.mentor_id AND mmc.status = 'active'
WHERE u.role = 'mentor' AND u.status = 'active'
GROUP BY u.id, u.username, u.first_name, u.last_name, u.bio, u.profile_photo, u.location;

-- Create optimized view for user conversations
CREATE OR REPLACE VIEW user_conversations AS
SELECT DISTINCT
    CASE 
        WHEN m1.sender_id = ? THEN m1.recipient_id
        ELSE m1.sender_id 
    END as other_user_id,
    GREATEST(m1.sender_id, m1.recipient_id) as user1_id,
    LEAST(m1.sender_id, m1.recipient_id) as user2_id,
    (SELECT message FROM messages m2 
     WHERE (m2.sender_id = m1.sender_id AND m2.recipient_id = m1.recipient_id) 
        OR (m2.sender_id = m1.recipient_id AND m2.recipient_id = m1.sender_id)
     ORDER BY m2.created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM messages m2 
     WHERE (m2.sender_id = m1.sender_id AND m2.recipient_id = m1.recipient_id) 
        OR (m2.sender_id = m1.recipient_id AND m2.recipient_id = m1.sender_id)
     ORDER BY m2.created_at DESC LIMIT 1) as last_message_time,
    (SELECT COUNT(*) FROM messages m3 
     WHERE m3.recipient_id = ? AND m3.sender_id = other_user_id AND m3.is_read = 0) as unread_count
FROM messages m1
WHERE m1.sender_id = ? OR m1.recipient_id = ?
ORDER BY last_message_time DESC;

-- ====================================
-- PERFORMANCE TUNING
-- ====================================

-- Optimize MySQL settings for better performance
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB (adjust based on available RAM)
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;
SET GLOBAL max_connections = 200;
SET GLOBAL innodb_log_file_size = 50331648; -- 48MB

-- Enable slow query log for monitoring
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 1.0; -- Log queries taking more than 1 second
SET GLOBAL log_queries_not_using_indexes = 1;

-- ====================================
-- TABLE OPTIMIZATIONS
-- ====================================

-- Optimize table structure and reclaim space
OPTIMIZE TABLE users;
OPTIMIZE TABLE sessions;
OPTIMIZE TABLE messages; 
OPTIMIZE TABLE notifications;
OPTIMIZE TABLE reviews;
OPTIMIZE TABLE mentor_mentee_connections;
OPTIMIZE TABLE files;
OPTIMIZE TABLE user_skills;
OPTIMIZE TABLE skills;
OPTIMIZE TABLE activities;

-- ====================================
-- ANALYZE TABLES FOR STATISTICS
-- ====================================

-- Update table statistics for better query planning
ANALYZE TABLE users;
ANALYZE TABLE sessions;
ANALYZE TABLE messages;
ANALYZE TABLE notifications;
ANALYZE TABLE reviews;
ANALYZE TABLE mentor_mentee_connections;
ANALYZE TABLE files;
ANALYZE TABLE user_skills;
ANALYZE TABLE skills;
ANALYZE TABLE activities;

-- ====================================
-- PERFORMANCE MONITORING QUERIES
-- ====================================

-- Query to identify slow queries
-- SELECT * FROM mysql.slow_log WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Query to check index usage
-- SELECT DISTINCT table_name, index_name 
-- FROM information_schema.statistics 
-- WHERE table_schema = 'mentorconnect' AND index_name != 'PRIMARY'
-- ORDER BY table_name, index_name;

-- Query to find unused indexes
-- SELECT OBJECT_SCHEMA, OBJECT_NAME, INDEX_NAME
-- FROM performance_schema.table_io_waits_summary_by_index_usage
-- WHERE OBJECT_SCHEMA = 'mentorconnect' AND COUNT_READ = 0 AND COUNT_WRITE = 0;