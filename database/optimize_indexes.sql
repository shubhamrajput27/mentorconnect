-- MentorConnect Database Optimization
-- Add indexes and optimize existing tables for better performance

USE mentorconnect;

-- Add indexes for better query performance
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_role (role);
ALTER TABLE users ADD INDEX idx_status (status);
ALTER TABLE users ADD INDEX idx_created_at (created_at);

ALTER TABLE user_skills ADD INDEX idx_user_skill (user_id, skill_id);
ALTER TABLE user_skills ADD INDEX idx_skill_type (skill_type);

ALTER TABLE sessions ADD INDEX idx_mentor_id (mentor_id);
ALTER TABLE sessions ADD INDEX idx_student_id (student_id);
ALTER TABLE sessions ADD INDEX idx_scheduled_at (scheduled_at);
ALTER TABLE sessions ADD INDEX idx_status (status);
ALTER TABLE sessions ADD INDEX idx_mentor_scheduled (mentor_id, scheduled_at);

ALTER TABLE messages ADD INDEX idx_sender_recipient (sender_id, recipient_id);
ALTER TABLE messages ADD INDEX idx_created_at (created_at);
ALTER TABLE messages ADD INDEX idx_is_read (is_read);

ALTER TABLE reviews ADD INDEX idx_reviewer_id (reviewer_id);
ALTER TABLE reviews ADD INDEX idx_reviewee_id (reviewee_id);
ALTER TABLE reviews ADD INDEX idx_session_id (session_id);
ALTER TABLE reviews ADD INDEX idx_rating (rating);

ALTER TABLE activities ADD INDEX idx_user_id (user_id);
ALTER TABLE activities ADD INDEX idx_activity_type (activity_type);
ALTER TABLE activities ADD INDEX idx_created_at (created_at);

ALTER TABLE notifications ADD INDEX idx_user_read (user_id, is_read);
ALTER TABLE notifications ADD INDEX idx_type (type);
ALTER TABLE notifications ADD INDEX idx_created_at (created_at);

ALTER TABLE user_sessions ADD INDEX idx_user_id (user_id);
ALTER TABLE user_sessions ADD INDEX idx_last_activity (last_activity);

-- Optimize table structures
OPTIMIZE TABLE users;
OPTIMIZE TABLE sessions;
OPTIMIZE TABLE messages;
OPTIMIZE TABLE activities;
OPTIMIZE TABLE notifications;

-- Add composite indexes for common query patterns
ALTER TABLE sessions ADD INDEX idx_mentor_status_date (mentor_id, status, scheduled_at);
ALTER TABLE messages ADD INDEX idx_recipient_read_date (recipient_id, is_read, created_at);
ALTER TABLE notifications ADD INDEX idx_user_read_date (user_id, is_read, created_at);

-- Add full-text search indexes where appropriate
ALTER TABLE users ADD FULLTEXT(first_name, last_name, bio);
ALTER TABLE sessions ADD FULLTEXT(title, description);
ALTER TABLE skills ADD FULLTEXT(name, description);