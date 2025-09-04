-- Database Optimization Indexes for MentorConnect
-- Performance-critical indexes for improved query execution

-- Users table indexes
CREATE INDEX IF NOT EXISTS idx_users_type_active ON users(user_type, is_active);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);
CREATE INDEX IF NOT EXISTS idx_users_email_active ON users(email, is_active);
CREATE INDEX IF NOT EXISTS idx_users_username_active ON users(username, is_active);

-- Full-text search index for users
ALTER TABLE users ADD FULLTEXT(first_name, last_name, bio);

-- Sessions table indexes
CREATE INDEX IF NOT EXISTS idx_sessions_mentor_scheduled ON sessions(mentor_id, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_sessions_student_scheduled ON sessions(student_id, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_sessions_status_scheduled ON sessions(status, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_sessions_scheduled_at ON sessions(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_sessions_created_at ON sessions(created_at);

-- Messages table indexes
CREATE INDEX IF NOT EXISTS idx_messages_sender_created ON messages(sender_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_recipient_created ON messages(recipient_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_recipient_read ON messages(recipient_id, is_read);
CREATE INDEX IF NOT EXISTS idx_messages_conversation ON messages(sender_id, recipient_id, created_at);

-- Reviews table indexes
CREATE INDEX IF NOT EXISTS idx_reviews_reviewee_created ON reviews(reviewee_id, created_at);
CREATE INDEX IF NOT EXISTS idx_reviews_session_reviewer ON reviews(session_id, reviewer_id);
CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(rating);

-- User skills indexes
CREATE INDEX IF NOT EXISTS idx_user_skills_user_skill ON user_skills(user_id, skill_id);
CREATE INDEX IF NOT EXISTS idx_user_skills_skill_proficiency ON user_skills(skill_id, proficiency_level);

-- Skills table indexes
CREATE INDEX IF NOT EXISTS idx_skills_category ON skills(category);
CREATE INDEX IF NOT EXISTS idx_skills_name ON skills(name);

-- Files table indexes
CREATE INDEX IF NOT EXISTS idx_files_uploader_created ON files(uploader_id, created_at);
CREATE INDEX IF NOT EXISTS idx_files_session_id ON files(session_id);
CREATE INDEX IF NOT EXISTS idx_files_public ON files(is_public);

-- User preferences indexes
CREATE INDEX IF NOT EXISTS idx_user_preferences_user_id ON user_preferences(user_id);

-- Composite indexes for common query patterns
CREATE INDEX IF NOT EXISTS idx_sessions_mentor_status_date ON sessions(mentor_id, status, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_sessions_student_status_date ON sessions(student_id, status, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_messages_users_date ON messages(sender_id, recipient_id, created_at);

-- Performance monitoring tables (if they exist)
CREATE INDEX IF NOT EXISTS idx_user_activity_log_user_created ON user_activity_log(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_request_logs_created_response ON request_logs(created_at, response_time);

-- Mentor-specific indexes for search optimization
CREATE INDEX IF NOT EXISTS idx_mentor_profiles_rating ON mentor_profiles(rating DESC);
CREATE INDEX IF NOT EXISTS idx_mentor_profiles_sessions ON mentor_profiles(total_sessions DESC);
CREATE INDEX IF NOT EXISTS idx_mentor_profiles_rate ON mentor_profiles(hourly_rate);

-- Notification indexes
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created ON notifications(created_at);

-- Analytics optimization indexes
CREATE INDEX IF NOT EXISTS idx_analytics_date_type ON user_activity_log(DATE(created_at), activity_type);
CREATE INDEX IF NOT EXISTS idx_payments_date_status ON payments(DATE(created_at), status);

-- Add missing tables if they don't exist (based on the analytics queries)
CREATE TABLE IF NOT EXISTS mentor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    company VARCHAR(200),
    hourly_rate DECIMAL(10,2),
    rating DECIMAL(3,2) DEFAULT 0,
    experience_years INT DEFAULT 0,
    total_sessions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mentor_profile (user_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    action_url VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    mentor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    method VARCHAR(10) NOT NULL,
    response_time INT NOT NULL,
    status_code INT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Update existing tables to match the optimized queries
ALTER TABLE users MODIFY COLUMN user_type ENUM('student', 'mentor', 'admin') DEFAULT 'student';
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Ensure messages table has the correct column names
ALTER TABLE messages CHANGE COLUMN receiver_id recipient_id INT NOT NULL;

-- Add indexes for the new tables
CREATE INDEX IF NOT EXISTS idx_mentor_profiles_user_rating ON mentor_profiles(user_id, rating);
CREATE INDEX IF NOT EXISTS idx_notifications_user_type ON notifications(user_id, type);
CREATE INDEX IF NOT EXISTS idx_payments_session_status ON payments(session_id, status);
CREATE INDEX IF NOT EXISTS idx_request_logs_url_method ON request_logs(url(100), method);

-- Optimize table storage engines and settings
ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE sessions ENGINE=InnoDB;
ALTER TABLE messages ENGINE=InnoDB;
ALTER TABLE reviews ENGINE=InnoDB;
ALTER TABLE skills ENGINE=InnoDB;
ALTER TABLE user_skills ENGINE=InnoDB;
ALTER TABLE files ENGINE=InnoDB;
ALTER TABLE user_preferences ENGINE=InnoDB;

-- Update table statistics for better query planning
ANALYZE TABLE users, sessions, messages, reviews, skills, user_skills, files, user_preferences;

-- Create views for common queries
CREATE OR REPLACE VIEW active_mentors AS
SELECT u.*, mp.title, mp.company, mp.hourly_rate, mp.rating, mp.total_sessions
FROM users u
JOIN mentor_profiles mp ON u.id = mp.user_id
WHERE u.user_type = 'mentor' AND u.is_active = 1;

CREATE OR REPLACE VIEW session_summary AS
SELECT 
    s.*,
    mentor.first_name as mentor_first_name,
    mentor.last_name as mentor_last_name,
    student.first_name as student_first_name,
    student.last_name as student_last_name
FROM sessions s
JOIN users mentor ON s.mentor_id = mentor.id
JOIN users student ON s.student_id = student.id;

-- Performance monitoring
CREATE OR REPLACE VIEW performance_metrics AS
SELECT 
    DATE(created_at) as date,
    AVG(response_time) as avg_response_time,
    COUNT(*) as total_requests,
    COUNT(CASE WHEN response_time > 2000 THEN 1 END) as slow_requests,
    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests
FROM request_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
