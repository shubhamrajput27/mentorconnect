-- Additional tables for mentor-mentee connections and profiles
USE mentorconnect;

-- Mentor profiles table
CREATE TABLE IF NOT EXISTS mentor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    company VARCHAR(200),
    industry VARCHAR(100),
    experience_years INT DEFAULT 0,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_sessions INT DEFAULT 0,
    total_reviews INT DEFAULT 0,
    languages VARCHAR(500), -- JSON or comma-separated
    availability VARCHAR(1000), -- JSON for availability schedule
    teaching_style ENUM('visual', 'hands-on', 'theoretical', 'collaborative') DEFAULT 'collaborative',
    is_available BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    bio_extended TEXT,
    certifications TEXT,
    achievements TEXT,
    specializations VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mentor_profile (user_id)
);

-- Student profiles table
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    education_level VARCHAR(100),
    field_of_study VARCHAR(200),
    career_goals TEXT,
    experience_level TINYINT DEFAULT 1, -- 1=beginner, 5=expert
    learning_style ENUM('visual', 'hands-on', 'theoretical', 'collaborative') DEFAULT 'collaborative',
    preferred_industry VARCHAR(100),
    available_hours_per_week INT DEFAULT 5,
    budget_range VARCHAR(50),
    languages VARCHAR(500), -- JSON or comma-separated
    motivation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_profile (user_id)
);

-- Mentor-Mentee connections table
CREATE TABLE IF NOT EXISTS mentor_mentee_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'active', 'completed', 'paused', 'cancelled') DEFAULT 'pending',
    connection_type ENUM('one-time', 'ongoing', 'project-based') DEFAULT 'ongoing',
    requested_by ENUM('mentor', 'mentee') NOT NULL,
    request_message TEXT,
    response_message TEXT,
    start_date DATE,
    end_date DATE,
    goals TEXT,
    expectations TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentor_status (mentor_id, status),
    INDEX idx_mentee_status (mentee_id, status),
    INDEX idx_status_created (status, created_at),
    UNIQUE KEY unique_active_connection (mentor_id, mentee_id, status)
);

-- User availability table
CREATE TABLE IF NOT EXISTS user_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0=Sunday, 1=Monday, etc.
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_day (user_id, day_of_week, is_active)
);

-- Mentor match analytics table
CREATE TABLE IF NOT EXISTS mentor_match_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    mentor_id INT NOT NULL,
    match_score DECIMAL(4,3) NOT NULL,
    match_reasons JSON,
    clicked BOOLEAN DEFAULT FALSE,
    contacted BOOLEAN DEFAULT FALSE,
    session_booked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_created (student_id, created_at),
    INDEX idx_mentor_created (mentor_id, created_at)
);

-- Connection activities table (for tracking interactions)
CREATE TABLE IF NOT EXISTS connection_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    connection_id INT NOT NULL,
    activity_type ENUM('message_sent', 'session_booked', 'session_completed', 'milestone_achieved', 'note_added', 'status_changed') NOT NULL,
    actor_id INT NOT NULL, -- user who performed the action
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (connection_id) REFERENCES mentor_mentee_connections(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_connection_created (connection_id, created_at)
);

-- Insert sample mentor profiles for existing mentors
INSERT IGNORE INTO mentor_profiles (user_id, title, company, experience_years, hourly_rate, is_verified, is_available)
SELECT id, 'Senior Software Engineer', 'Tech Corp', 5, 75.00, TRUE, TRUE
FROM users WHERE role = 'mentor';

-- Insert sample student profiles for existing students  
INSERT IGNORE INTO student_profiles (user_id, education_level, field_of_study, experience_level)
SELECT id, 'Bachelor\'s Degree', 'Computer Science', 2
FROM users WHERE role = 'student';

-- Add some sample availability for users
INSERT IGNORE INTO user_availability (user_id, day_of_week, start_time, end_time)
SELECT id, 1, '09:00:00', '17:00:00' FROM users WHERE role IN ('mentor', 'student')
UNION ALL
SELECT id, 2, '09:00:00', '17:00:00' FROM users WHERE role IN ('mentor', 'student')
UNION ALL  
SELECT id, 3, '09:00:00', '17:00:00' FROM users WHERE role IN ('mentor', 'student')
UNION ALL
SELECT id, 4, '09:00:00', '17:00:00' FROM users WHERE role IN ('mentor', 'student')
UNION ALL
SELECT id, 5, '09:00:00', '17:00:00' FROM users WHERE role IN ('mentor', 'student');