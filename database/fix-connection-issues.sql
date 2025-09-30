-- Fix for connection request issues
USE mentorconnect;

-- Drop the problematic unique constraint if it exists
DROP INDEX IF EXISTS unique_active_connection ON mentor_mentee_connections;

-- Make sure all required tables exist
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
);

-- Ensure mentor_profiles table has proper structure
ALTER TABLE mentor_profiles 
    ADD COLUMN IF NOT EXISTS is_available BOOLEAN DEFAULT TRUE,
    ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00;

-- Add proper indexes for performance
ALTER TABLE mentor_mentee_connections 
    ADD INDEX IF NOT EXISTS idx_mentor_mentee (mentor_id, mentee_id),
    ADD INDEX IF NOT EXISTS idx_status_created (status, created_at);

-- Insert sample notifications table structure if needed
SELECT 'Database tables fixed and ready' as status;