-- MentorConnect Database - Complete Schema and Data
-- This is the single comprehensive database file for the MentorConnect application

CREATE DATABASE IF NOT EXISTS mentorconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mentorconnect;

-- Drop tables if they exist (in reverse order due to foreign key constraints)
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS files;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS user_skills;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS mentor_mentee_connections;
DROP TABLE IF EXISTS mentor_profiles;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('student', 'mentor', 'admin') DEFAULT 'student',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    bio TEXT,
    profile_photo VARCHAR(255),
    phone VARCHAR(20),
    location VARCHAR(200),
    timezone VARCHAR(50) DEFAULT 'UTC',
    remember_token VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Mentor profiles table
CREATE TABLE mentor_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    title VARCHAR(200),
    company VARCHAR(200),
    experience_years INT,
    hourly_rate DECIMAL(10,2),
    rating DECIMAL(3,2) DEFAULT 0.00,
    bio TEXT,
    specialties TEXT,
    availability JSON,
    is_verified BOOLEAN DEFAULT TRUE,
    total_sessions INT DEFAULT 0,
    languages VARCHAR(255),
    education TEXT,
    certifications TEXT,
    linkedin_url VARCHAR(255),
    github_url VARCHAR(255),
    portfolio_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mentor-mentee connections table
CREATE TABLE mentor_mentee_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    status ENUM('pending', 'active', 'inactive', 'rejected') DEFAULT 'pending',
    connection_type VARCHAR(50) DEFAULT 'mentorship',
    request_message TEXT,
    response_message TEXT,
    goals TEXT,
    start_date DATE,
    requested_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_connection (mentor_id, mentee_id),
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Skills table
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User skills table
CREATE TABLE user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    years_experience INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_skill (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sessions table
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    session_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    meeting_link VARCHAR(500),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    session_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Files table
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User preferences table
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_preference (user_id, preference_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User sessions table
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Activities table
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert sample data
INSERT INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified) VALUES
('admin', 'admin@mentorconnect.com', '.PV.rH7j0/kdvLQcTjX13Ba', 'Admin', 'User', 'admin', 'active', 1),
('mentor1', 'mentor@mentorconnect.com', '.rBsyfjJHNOW24/IdWFo9M3uT0NjPXrFUI2LYnz5Gq', 'John', 'Mentor', 'mentor', 'active', 1),
('student1', 'student@mentorconnect.com', '/LS/aTMR911uuYh7aobbnPyxFe5HnW', 'Jane', 'Student', 'student', 'active', 1);

-- Insert sample skills
INSERT INTO skills (name, description, category) VALUES
('JavaScript', 'Programming language for web development', 'Programming'),
('Python', 'High-level programming language', 'Programming'),
('React', 'JavaScript library for building user interfaces', 'Frontend'),
('Node.js', 'JavaScript runtime for server-side development', 'Backend'),
('PHP', 'Server-side scripting language', 'Backend'),
('MySQL', 'Relational database management system', 'Database'),
('HTML/CSS', 'Markup and styling for web pages', 'Frontend'),
('Git', 'Version control system', 'Tools'),
('Docker', 'Containerization platform', 'DevOps'),
('AWS', 'Amazon Web Services cloud platform', 'Cloud'),
('Machine Learning', 'Artificial intelligence and data science', 'Data Science'),
('UI/UX Design', 'User interface and experience design', 'Design'),
('Project Management', 'Managing projects and teams', 'Management'),
('Digital Marketing', 'Online marketing strategies', 'Marketing'),
('Data Analysis', 'Analyzing and interpreting data', 'Data Science'),
('Mobile Development', 'iOS and Android app development', 'Mobile'),
('Cybersecurity', 'Information security and protection', 'Security'),
('Blockchain', 'Distributed ledger technology', 'Technology'),
('DevOps', 'Development and operations practices', 'DevOps'),
('API Development', 'Building and integrating APIs', 'Backend');

-- Performance indexes
CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_users_email_verified ON users(email_verified);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_users_remember_token ON users(remember_token);

CREATE INDEX idx_connections_mentor ON mentor_mentee_connections(mentor_id);
CREATE INDEX idx_connections_mentee ON mentor_mentee_connections(mentee_id);
CREATE INDEX idx_connections_status ON mentor_mentee_connections(status);

CREATE INDEX idx_sessions_mentor_date ON sessions(mentor_id, session_date);
CREATE INDEX idx_sessions_mentee_date ON sessions(mentee_id, session_date);
CREATE INDEX idx_sessions_status_date ON sessions(status, session_date);

CREATE INDEX idx_messages_conversation ON messages(sender_id, receiver_id, created_at);
CREATE INDEX idx_messages_unread ON messages(receiver_id, is_read, created_at);

CREATE INDEX idx_reviews_reviewee_rating ON reviews(reviewee_id, rating);
CREATE INDEX idx_reviews_session ON reviews(session_id);

CREATE INDEX idx_files_user_created ON files(user_id, created_at);
CREATE INDEX idx_files_mime_type ON files(mime_type);

CREATE INDEX idx_user_skills_skill_proficiency ON user_skills(skill_id, proficiency_level);
CREATE INDEX idx_user_skills_user_proficiency ON user_skills(user_id, proficiency_level);

CREATE INDEX idx_activities_user_type_date ON activities(user_id, activity_type, created_at);

CREATE INDEX idx_notifications_user_read_date ON notifications(user_id, is_read, created_at);

CREATE INDEX idx_user_sessions_user ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_activity ON user_sessions(last_activity);

-- Full-text search indexes
ALTER TABLE users ADD FULLTEXT(first_name, last_name, bio);
ALTER TABLE sessions ADD FULLTEXT(title, description);
ALTER TABLE skills ADD FULLTEXT(name, description);

-- Database setup completed successfully!
