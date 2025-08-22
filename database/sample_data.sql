-- Sample data for Mentor Management System
USE mentorconnect;

-- Insert sample users (mentors and students)
INSERT INTO users (username, email, password_hash, first_name, last_name, role, bio, phone, location, email_verified) VALUES
-- Mentors
('john_mentor', 'john.doe@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'mentor', 'Senior Software Engineer with 8+ years of experience in full-stack development.', '+1-555-0101', 'San Francisco, CA', TRUE),
('sarah_dev', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'mentor', 'UX/UI Designer passionate about creating intuitive user experiences.', '+1-555-0102', 'New York, NY', TRUE),
('mike_data', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Chen', 'mentor', 'Data Scientist specializing in machine learning and analytics.', '+1-555-0103', 'Seattle, WA', TRUE),
('lisa_pm', 'lisa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Rodriguez', 'mentor', 'Product Manager with expertise in agile methodologies and team leadership.', '+1-555-0104', 'Austin, TX', TRUE),
-- Students
('alex_student', 'alex@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex', 'Wilson', 'student', 'Computer Science student eager to learn web development.', '+1-555-0201', 'Boston, MA', TRUE),
('jane_student', 'jane.smith@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'student', 'Career changer looking to transition into tech.', '+1-555-0202', 'Chicago, IL', TRUE),
('david_code', 'david@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Brown', 'student', 'Junior developer seeking guidance in backend technologies.', '+1-555-0203', 'Denver, CO', TRUE);

-- Insert mentor profiles
INSERT INTO mentor_profiles (user_id, title, company, experience_years, hourly_rate, availability, languages, rating, total_sessions, is_verified) VALUES
(1, 'Senior Full Stack Developer', 'TechCorp Inc.', 8, 75.00, 'Mon-Fri 9AM-5PM PST', 'English, Spanish', 4.8, 45, TRUE),
(2, 'Lead UX Designer', 'DesignStudio', 6, 65.00, 'Tue-Thu 10AM-6PM EST', 'English, French', 4.9, 32, TRUE),
(3, 'Data Science Manager', 'DataTech Solutions', 7, 80.00, 'Mon-Wed 8AM-4PM PST', 'English, Mandarin', 4.7, 28, TRUE),
(4, 'Senior Product Manager', 'StartupXYZ', 5, 70.00, 'Mon-Fri 11AM-7PM CST', 'English', 4.6, 38, TRUE);

-- Insert user skills
INSERT INTO user_skills (user_id, skill_id, proficiency_level) VALUES
-- John's skills
(1, 1, 'expert'), (1, 3, 'expert'), (1, 7, 'advanced'), (1, 8, 'advanced'), (1, 10, 'advanced'),
-- Sarah's skills
(2, 10, 'expert'), (2, 11, 'expert'), (2, 5, 'advanced'), (2, 6, 'intermediate'),
-- Mike's skills
(3, 2, 'expert'), (3, 13, 'expert'), (3, 14, 'expert'), (3, 9, 'advanced'),
-- Lisa's skills
(4, 12, 'expert'), (4, 19, 'expert'), (4, 20, 'advanced'),
-- Students' skills (learning)
(5, 1, 'beginner'), (5, 10, 'intermediate'), (5, 5, 'beginner'),
(6, 2, 'beginner'), (6, 13, 'beginner'),
(7, 3, 'intermediate'), (7, 8, 'beginner');

-- Insert sample sessions
INSERT INTO sessions (mentor_id, student_id, title, description, scheduled_at, duration_minutes, status) VALUES
(1, 5, 'JavaScript Fundamentals', 'Introduction to JavaScript basics and ES6 features', '2025-08-25 14:00:00', 60, 'scheduled'),
(2, 6, 'UI/UX Design Principles', 'Learn the fundamentals of user-centered design', '2025-08-26 15:30:00', 90, 'scheduled'),
(3, 7, 'Database Design Workshop', 'MySQL database design and optimization techniques', '2025-08-27 10:00:00', 120, 'scheduled'),
(1, 5, 'React Components Deep Dive', 'Advanced React patterns and component architecture', '2025-08-20 14:00:00', 60, 'completed'),
(4, 6, 'Agile Project Management', 'Introduction to Scrum and Kanban methodologies', '2025-08-19 16:00:00', 75, 'completed');

-- Insert sample messages
INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES
(5, 1, 'Thank you for the session!', 'Hi John, thank you for the excellent JavaScript session yesterday. The concepts are much clearer now!', TRUE),
(1, 5, 'Re: Thank you for the session!', 'You\'re welcome Alex! Keep practicing and feel free to reach out if you have any questions.', FALSE),
(6, 2, 'Question about design tools', 'Hi Sarah, which design tool would you recommend for a beginner - Figma or Sketch?', TRUE),
(2, 6, 'Re: Question about design tools', 'Hi Emma, I\'d definitely recommend Figma for beginners. It\'s free and has great collaboration features!', FALSE);

-- Insert sample notifications
INSERT INTO notifications (user_id, type, title, content, action_url) VALUES
(5, 'message', 'New message from John Smith', 'You have received a new message from your mentor.', '/messages'),
(1, 'meeting', 'Upcoming session reminder', 'You have a session with Alex Wilson in 1 hour.', '/sessions'),
(6, 'message', 'New message from Sarah Johnson', 'Your mentor has replied to your question.', '/messages'),
(2, 'feedback', 'New review received', 'Emma Davis has left a review for your recent session.', '/reviews');

-- Insert sample reviews
INSERT INTO reviews (session_id, reviewer_id, reviewee_id, rating, comment) VALUES
(4, 5, 1, 5, 'Excellent session! John explained React concepts very clearly and provided great examples.'),
(5, 6, 4, 4, 'Lisa was very helpful in explaining Agile methodologies. Looking forward to more sessions!');

-- Insert sample activities
INSERT INTO activities (user_id, activity_type, description, ip_address) VALUES
(1, 'login', 'User logged in', '192.168.1.100'),
(5, 'session_completed', 'Completed session: React Components Deep Dive', '192.168.1.101'),
(2, 'profile_update', 'Updated profile information', '192.168.1.102'),
(6, 'message_sent', 'Sent message to Sarah Johnson', '192.168.1.103'),
(3, 'login', 'User logged in', '192.168.1.104');

-- Insert user preferences
INSERT INTO user_preferences (user_id, theme, language, email_notifications, push_notifications) VALUES
(1, 'dark', 'en', TRUE, TRUE),
(2, 'light', 'en', TRUE, FALSE),
(3, 'dark', 'en', FALSE, TRUE),
(4, 'light', 'en', TRUE, TRUE),
(5, 'light', 'en', TRUE, TRUE),
(6, 'dark', 'en', TRUE, FALSE),
(7, 'light', 'en', FALSE, TRUE);
