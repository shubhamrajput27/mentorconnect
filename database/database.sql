-- MentorConnect Database - Clean Version
-- Compatible with MySQL 8.0+
-- Created: September 1, 2025

CREATE DATABASE IF NOT EXISTS mentorconnect;
USE mentorconnect;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    user_type ENUM('student', 'mentor', 'admin') DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    bio TEXT,
    profile_picture VARCHAR(255),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Skills table
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User skills junction table
CREATE TABLE IF NOT EXISTS user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_skill (user_id, skill_id)
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    scheduled_at DATETIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    meeting_link VARCHAR(255),
    notes TEXT,
    mentor_rating TINYINT NULL,
    student_rating TINYINT NULL,
    price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    session_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_reviewer (session_id, reviewer_id)
);

-- Files table
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uploader_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    session_id INT DEFAULT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL
);

-- User preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme ENUM('light', 'dark') DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    email_notifications BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
);

-- Insert sample skills
INSERT IGNORE INTO skills (name, category, description) VALUES
('JavaScript', 'Programming', 'Popular web development language for frontend and backend'),
('Python', 'Programming', 'Versatile language for web development, data science, and AI'),
('React', 'Frontend', 'Popular JavaScript library for building user interfaces'),
('Node.js', 'Backend', 'JavaScript runtime for server-side development'),
('MySQL', 'Database', 'Popular open-source relational database'),
('HTML/CSS', 'Frontend', 'Fundamental web technologies for structure and styling'),
('PHP', 'Backend', 'Server-side scripting language for web development'),
('Java', 'Programming', 'Enterprise-level object-oriented programming language'),
('UI/UX Design', 'Design', 'User interface and user experience design'),
('Project Management', 'Management', 'Planning and executing projects effectively'),
('Data Science', 'Analytics', 'Data analysis and statistical modeling'),
('Machine Learning', 'AI/ML', 'Algorithms that learn from data'),
('DevOps', 'Infrastructure', 'Development and operations practices'),
('Angular', 'Frontend', 'TypeScript-based web application framework'),
('Vue.js', 'Frontend', 'Progressive JavaScript framework');

-- Insert sample users
INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, user_type, bio) VALUES
('admin', 'admin@mentorconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'System administrator'),
('john_mentor', 'john@mentorconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'mentor', 'Experienced full-stack developer with 8+ years in web development'),
('sarah_mentor', 'sarah@mentorconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'mentor', 'UI/UX designer passionate about creating beautiful user experiences'),
('mike_student', 'mike@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Wilson', 'student', 'Computer science student eager to learn web development'),
('jane_student', 'jane@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Davis', 'student', 'Career changer looking to transition into tech');

-- Link users with skills
INSERT IGNORE INTO user_skills (user_id, skill_id, proficiency_level) VALUES
(2, 1, 'expert'),
(2, 3, 'expert'),
(2, 4, 'advanced'),
(2, 5, 'advanced'),
(2, 7, 'expert'),
(3, 9, 'expert'),
(3, 6, 'expert'),
(3, 1, 'intermediate'),
(4, 1, 'beginner'),
(4, 6, 'beginner'),
(5, 2, 'beginner'),
(5, 11, 'beginner');

-- Create user preferences for all users
INSERT IGNORE INTO user_preferences (user_id) VALUES (1), (2), (3), (4), (5);
