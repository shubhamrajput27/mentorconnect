-- Enhanced Database Schema Optimizations for MentorConnect
-- Performance improvements, additional indexes, and new features

-- Add missing tables for enhanced functionality
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Add student profiles table (if not exists)
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interests TEXT,
    goals TEXT,
    preferred_learning_style ENUM('visual', 'auditory', 'kinesthetic', 'reading') DEFAULT 'visual',
    experience_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    preferred_industry VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_experience_level (experience_level),
    INDEX idx_preferred_industry (preferred_industry)
);

-- Add availability scheduling table
CREATE TABLE IF NOT EXISTS user_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0=Sunday, 1=Monday, etc.
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_active (is_active)
);

-- Add mentor matching analytics table
CREATE TABLE IF NOT EXISTS mentor_match_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    mentor_id INT NOT NULL,
    match_score DECIMAL(4,3) NOT NULL,
    match_reasons JSON,
    was_contacted BOOLEAN DEFAULT FALSE,
    contact_date DATETIME NULL,
    session_booked BOOLEAN DEFAULT FALSE,
    booking_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_mentor_id (mentor_id),
    INDEX idx_match_score (match_score),
    INDEX idx_created_at (created_at)
);

-- Add payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    mentor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_student_id (student_id),
    INDEX idx_mentor_id (mentor_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
);

-- Add request logs for performance monitoring
CREATE TABLE IF NOT EXISTS request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_method VARCHAR(10),
    request_uri TEXT,
    response_time DECIMAL(8,3),
    status_code INT,
    memory_usage INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_response_time (response_time),
    INDEX idx_status_code (status_code),
    INDEX idx_created_at (created_at)
);

-- Add user activity log for detailed tracking
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    activity_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
);

-- Enhance existing tables with additional fields and indexes

-- Add fields to users table (if not exists)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS country VARCHAR(100),
ADD COLUMN IF NOT EXISTS date_of_birth DATE,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS login_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(255),
ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(255),
ADD COLUMN IF NOT EXISTS password_reset_expires DATETIME NULL;

-- Add fields to mentor_profiles table (if not exists)
ALTER TABLE mentor_profiles 
ADD COLUMN IF NOT EXISTS is_available BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS response_time_hours INT DEFAULT 24,
ADD COLUMN IF NOT EXISTS specializations TEXT,
ADD COLUMN IF NOT EXISTS certifications TEXT,
ADD COLUMN IF NOT EXISTS portfolio_url VARCHAR(255),
ADD COLUMN IF NOT EXISTS linkedin_url VARCHAR(255),
ADD COLUMN IF NOT EXISTS teaching_style ENUM('hands-on', 'theoretical', 'collaborative', 'structured') DEFAULT 'collaborative';

-- Add fields to sessions table (if not exists)
ALTER TABLE sessions 
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'USD',
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS recording_url VARCHAR(255),
ADD COLUMN IF NOT EXISTS feedback_student TEXT,
ADD COLUMN IF NOT EXISTS feedback_mentor TEXT,
ADD COLUMN IF NOT EXISTS rating_student TINYINT CHECK (rating_student >= 1 AND rating_student <= 5),
ADD COLUMN IF NOT EXISTS rating_mentor TINYINT CHECK (rating_mentor >= 1 AND rating_mentor <= 5);

-- Add skill_type to user_skills (if not exists)
ALTER TABLE user_skills 
ADD COLUMN IF NOT EXISTS skill_type ENUM('learning', 'teaching', 'both') DEFAULT 'both',
ADD COLUMN IF NOT EXISTS years_experience TINYINT DEFAULT 0,
ADD COLUMN IF NOT EXISTS verified BOOLEAN DEFAULT FALSE;

-- Enhanced indexes for better performance

-- Users table indexes
CREATE INDEX IF NOT EXISTS idx_users_country ON users(country);
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login);
CREATE INDEX IF NOT EXISTS idx_users_email_token ON users(email_verification_token);
CREATE INDEX IF NOT EXISTS idx_users_reset_token ON users(password_reset_token);
CREATE INDEX IF NOT EXISTS idx_users_name_search ON users(first_name, last_name);

-- Mentor profiles indexes
CREATE INDEX IF NOT EXISTS idx_mentor_available ON mentor_profiles(is_available);
CREATE INDEX IF NOT EXISTS idx_mentor_rating_sessions ON mentor_profiles(rating, total_sessions);
CREATE INDEX IF NOT EXISTS idx_mentor_hourly_rate ON mentor_profiles(hourly_rate);
CREATE INDEX IF NOT EXISTS idx_mentor_teaching_style ON mentor_profiles(teaching_style);

-- Sessions enhanced indexes
CREATE INDEX IF NOT EXISTS idx_sessions_price ON sessions(price);
CREATE INDEX IF NOT EXISTS idx_sessions_payment_status ON sessions(payment_status);
CREATE INDEX IF NOT EXISTS idx_sessions_mentor_date_status ON sessions(mentor_id, scheduled_at, status);
CREATE INDEX IF NOT EXISTS idx_sessions_student_date_status ON sessions(student_id, scheduled_at, status);

-- User skills enhanced indexes
CREATE INDEX IF NOT EXISTS idx_user_skills_type ON user_skills(skill_type);
CREATE INDEX IF NOT EXISTS idx_user_skills_verified ON user_skills(verified);
CREATE INDEX IF NOT EXISTS idx_user_skills_experience ON user_skills(years_experience);

-- Messages enhanced indexes
CREATE INDEX IF NOT EXISTS idx_messages_thread ON messages(sender_id, receiver_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_unread ON messages(receiver_id, is_read, created_at);

-- Files enhanced indexes
CREATE INDEX IF NOT EXISTS idx_files_type ON files(mime_type);
CREATE INDEX IF NOT EXISTS idx_files_size ON files(file_size);

-- Notifications enhanced indexes
CREATE INDEX IF NOT EXISTS idx_notifications_type_user ON notifications(type, user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_unread_recent ON notifications(user_id, is_read, created_at);

-- Activities enhanced indexes
CREATE INDEX IF NOT EXISTS idx_activities_user_date ON activities(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_activities_type_date ON activities(activity_type, created_at);

-- Reviews enhanced indexes
CREATE INDEX IF NOT EXISTS idx_reviews_mentor_rating ON reviews(reviewee_id, rating, created_at);
CREATE INDEX IF NOT EXISTS idx_reviews_session_date ON reviews(session_id, created_at);

-- Full-text search indexes for better search performance
ALTER TABLE users ADD FULLTEXT(first_name, last_name, bio);
ALTER TABLE mentor_profiles ADD FULLTEXT(title, company, specializations);
ALTER TABLE sessions ADD FULLTEXT(title, description, notes);
ALTER TABLE messages ADD FULLTEXT(subject, message);

-- Optimize table storage engines and character sets
ALTER TABLE users ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE mentor_profiles ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE student_profiles ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE sessions ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE messages ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE notifications ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE files ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE reviews ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE activities ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE user_preferences ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE user_skills ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE skills ENGINE=InnoDB, DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views for common queries

-- Active mentors view
CREATE OR REPLACE VIEW active_mentors AS
SELECT 
    u.id, u.username, u.first_name, u.last_name, u.profile_photo, u.bio,
    mp.title, mp.company, mp.hourly_rate, mp.rating, mp.total_sessions,
    mp.experience_years, mp.is_available, mp.teaching_style,
    GROUP_CONCAT(DISTINCT s.name ORDER BY s.name) as skills
FROM users u
JOIN mentor_profiles mp ON u.id = mp.user_id
LEFT JOIN user_skills us ON u.id = us.user_id
LEFT JOIN skills s ON us.skill_id = s.id
WHERE u.role = 'mentor' AND u.status = 'active' AND mp.is_available = TRUE
GROUP BY u.id;

-- Session statistics view
CREATE OR REPLACE VIEW session_statistics AS
SELECT 
    s.mentor_id,
    s.student_id,
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN s.status = 'completed' THEN 1 END) as completed_sessions,
    COUNT(CASE WHEN s.status = 'cancelled' THEN 1 END) as cancelled_sessions,
    AVG(CASE WHEN s.status = 'completed' THEN s.duration_minutes END) as avg_duration,
    SUM(CASE WHEN s.status = 'completed' THEN s.duration_minutes ELSE 0 END) as total_duration,
    MAX(s.scheduled_at) as last_session_date
FROM sessions s
GROUP BY s.mentor_id, s.student_id;

-- User engagement view
CREATE OR REPLACE VIEW user_engagement AS
SELECT 
    u.id as user_id,
    u.role,
    u.created_at as registration_date,
    u.last_login,
    DATEDIFF(NOW(), u.created_at) as days_since_registration,
    COALESCE(msg_stats.message_count, 0) as message_count,
    COALESCE(session_stats.session_count, 0) as session_count,
    COALESCE(activity_stats.activity_count, 0) as activity_count
FROM users u
LEFT JOIN (
    SELECT 
        CASE WHEN sender_id = receiver_id THEN sender_id ELSE sender_id END as user_id,
        COUNT(*) as message_count
    FROM messages 
    GROUP BY user_id
) msg_stats ON u.id = msg_stats.user_id
LEFT JOIN (
    SELECT 
        CASE WHEN mentor_id = student_id THEN mentor_id ELSE mentor_id END as user_id,
        COUNT(*) as session_count
    FROM sessions 
    GROUP BY user_id
) session_stats ON u.id = session_stats.user_id
LEFT JOIN (
    SELECT user_id, COUNT(*) as activity_count
    FROM activities 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY user_id
) activity_stats ON u.id = activity_stats.user_id;

-- Add stored procedures for common operations

DELIMITER //

-- Procedure to update mentor rating
CREATE PROCEDURE UpdateMentorRating(IN mentor_id INT)
BEGIN
    DECLARE new_rating DECIMAL(3,2);
    DECLARE review_count INT;
    
    SELECT AVG(rating), COUNT(*) 
    INTO new_rating, review_count
    FROM reviews 
    WHERE reviewee_id = mentor_id;
    
    UPDATE mentor_profiles 
    SET rating = COALESCE(new_rating, 0),
        total_sessions = review_count
    WHERE user_id = mentor_id;
END //

-- Procedure to clean up old data
CREATE PROCEDURE CleanupOldData()
BEGIN
    -- Clean up old notifications (older than 90 days)
    DELETE FROM notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND is_read = TRUE;
    
    -- Clean up old activities (older than 1 year)
    DELETE FROM activities 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Clean up old request logs (older than 30 days)
    DELETE FROM request_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Clean up expired remember tokens
    DELETE FROM remember_tokens 
    WHERE expires_at < NOW();
    
    -- Clean up old user sessions (older than 30 days)
    DELETE FROM user_sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //

DELIMITER ;

-- Create triggers for automatic updates

-- Trigger to update user's last_login
DELIMITER //
CREATE TRIGGER update_last_login 
AFTER INSERT ON user_sessions
FOR EACH ROW
BEGIN
    UPDATE users 
    SET last_login = NOW(), login_count = login_count + 1 
    WHERE id = NEW.user_id;
END //
DELIMITER ;

-- Trigger to update mentor rating when review is added
DELIMITER //
CREATE TRIGGER update_rating_after_review 
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    IF NEW.reviewee_id IN (SELECT user_id FROM mentor_profiles) THEN
        CALL UpdateMentorRating(NEW.reviewee_id);
    END IF;
END //
DELIMITER ;

-- Add database constraints for data integrity
ALTER TABLE sessions 
ADD CONSTRAINT chk_duration_positive CHECK (duration_minutes > 0),
ADD CONSTRAINT chk_price_positive CHECK (price >= 0);

ALTER TABLE reviews 
ADD CONSTRAINT chk_rating_range CHECK (rating >= 1 AND rating <= 5);

ALTER TABLE user_skills 
ADD CONSTRAINT chk_years_experience CHECK (years_experience >= 0 AND years_experience <= 50);

-- Add additional indexes for analytics queries
CREATE INDEX idx_analytics_mentor_performance ON sessions(mentor_id, status, scheduled_at, duration_minutes);
CREATE INDEX idx_analytics_student_activity ON sessions(student_id, status, scheduled_at);
CREATE INDEX idx_analytics_revenue ON payments(status, payment_date, amount);
CREATE INDEX idx_analytics_user_growth ON users(role, created_at, status);

-- Insert some sample enhanced data
INSERT IGNORE INTO skills (name, category) VALUES
('React Native', 'Mobile Development'),
('Flutter', 'Mobile Development'),
('Kubernetes', 'DevOps'),
('Microservices', 'Architecture'),
('GraphQL', 'API Development'),
('TypeScript', 'Programming'),
('Rust', 'Programming'),
('Go', 'Programming'),
('Blockchain', 'Emerging Tech'),
('AI/ML', 'Artificial Intelligence'),
('Data Analysis', 'Data Science'),
('Cybersecurity', 'Security'),
('Product Management', 'Management'),
('Digital Marketing', 'Marketing'),
('Content Strategy', 'Marketing');

-- Create event to automatically clean up old data
CREATE EVENT IF NOT EXISTS auto_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL CleanupOldData();

-- Enable the event scheduler (if not already enabled)
SET GLOBAL event_scheduler = ON;
