<?php
/**
 * Database Setup and Verification Script
 * Run this to create the database and tables if they don't exist
 */

// Database configuration for setup
$host = 'localhost';
$username = 'root';
$password = ''; // Default WAMP password
$database = 'mentorconnect';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;} .result{background:white;padding:15px;margin:10px 0;border-radius:8px;} .success{border-left:4px solid #10b981;} .error{border-left:4px solid #ef4444;} .warning{border-left:4px solid #f59e0b;}</style>";
echo "</head><body>";

echo "<h1>🗄️ MentorConnect Database Setup</h1>";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='result success'>";
    echo "<h3>✅ MySQL Connection Successful</h3>";
    echo "Connected to MySQL server successfully<br>";
    echo "</div>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='result success'>";
    echo "<h3>✅ Database Created/Verified</h3>";
    echo "Database '$database' is ready<br>";
    echo "</div>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create basic tables if they don't exist
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('student', 'mentor') NOT NULL,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                email_verified BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_users_email (email),
                INDEX idx_users_role_status (role, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'user_preferences' => "
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                theme ENUM('light', 'dark') DEFAULT 'light',
                language VARCHAR(10) DEFAULT 'en',
                notifications_enabled BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_preferences (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'notifications' => "
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                action_url VARCHAR(500),
                is_read BOOLEAN DEFAULT FALSE,
                priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_notifications_user_read_created (user_id, is_read, created_at),
                INDEX idx_notifications_type (type),
                INDEX idx_notifications_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'sessions' => "
            CREATE TABLE IF NOT EXISTS sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mentor_id INT NOT NULL,
                student_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                scheduled_time DATETIME NOT NULL,
                duration_minutes INT DEFAULT 60,
                status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
                meeting_url VARCHAR(500),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_sessions_mentor_date_status (mentor_id, scheduled_time, status),
                INDEX idx_sessions_student_status (student_id, status),
                INDEX idx_sessions_scheduled_time (scheduled_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'messages' => "
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                recipient_id INT NOT NULL,
                subject VARCHAR(255),
                content TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_messages_recipient_read (recipient_id, is_read),
                INDEX idx_messages_conversation (sender_id, recipient_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'reviews' => "
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id INT NOT NULL,
                mentor_id INT NOT NULL,
                student_id INT NOT NULL,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                comment TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
                FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_reviews_mentor_rating (mentor_id, rating),
                INDEX idx_reviews_session (session_id),
                UNIQUE KEY unique_session_review (session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    echo "<div class='result'>";
    echo "<h3>🏗️ Creating Tables</h3>";
    
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Table '$tableName' created/verified<br>";
        } catch (PDOException $e) {
            echo "❌ Error creating table '$tableName': " . $e->getMessage() . "<br>";
        }
    }
    echo "</div>";
    
    // Insert sample data if tables are empty
    echo "<div class='result'>";
    echo "<h3>📊 Sample Data</h3>";
    
    // Check if we have any users
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    if ($userCount == 0) {
        echo "Creating sample data...<br>";
        
        // Create sample users
        $pdo->exec("
            INSERT INTO users (username, email, password_hash, role, email_verified) VALUES
            ('john_mentor', 'john@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'mentor', TRUE),
            ('jane_student', 'jane@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'student', TRUE)
        ");
        
        // Get user IDs
        $mentorId = $pdo->query("SELECT id FROM users WHERE role = 'mentor' LIMIT 1")->fetchColumn();
        $studentId = $pdo->query("SELECT id FROM users WHERE role = 'student' LIMIT 1")->fetchColumn();
        
        // Create sample preferences
        $pdo->exec("
            INSERT INTO user_preferences (user_id, theme, language) VALUES
            ($mentorId, 'light', 'en'),
            ($studentId, 'dark', 'en')
        ");
        
        // Create sample notifications
        $pdo->exec("
            INSERT INTO notifications (user_id, type, title, content) VALUES
            ($studentId, 'welcome', 'Welcome to MentorConnect!', 'Your account has been created successfully.'),
            ($mentorId, 'profile', 'Complete your profile', 'Please complete your mentor profile to start receiving students.')
        ");
        
        echo "✅ Sample data created successfully<br>";
        echo "Sample login credentials:<br>";
        echo "&nbsp;&nbsp;Mentor: john@example.com / password123<br>";
        echo "&nbsp;&nbsp;Student: jane@example.com / password123<br>";
        
    } else {
        echo "✅ Database contains $userCount users<br>";
    }
    echo "</div>";
    
    // Test the application connection
    echo "<div class='result'>";
    echo "<h3>🔗 Application Connection Test</h3>";
    
    try {
        require_once 'config/config.php';
        echo "✅ Application configuration loaded<br>";
        
        $testConnection = getConnection();
        if ($testConnection) {
            echo "✅ Application database connection working<br>";
            
            $stats = getDatabaseStats();
            if ($stats['healthy']) {
                echo "✅ Database health check passed<br>";
                echo "Database size: {$stats['database_size_mb']} MB<br>";
                echo "Tables: {$stats['table_count']}<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Application connection error: " . $e->getMessage() . "<br>";
    }
    echo "</div>";
    
    echo "<div class='result success'>";
    echo "<h3>🎉 Setup Complete!</h3>";
    echo "<p>Your database is ready! You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Open MentorConnect Application</a></li>";
    echo "<li><a href='test.php'>Run Application Test</a></li>";
    echo "<li><a href='performance-test.php'>Run Performance Test</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='result error'>";
    echo "<h3>❌ Database Setup Error</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>WAMP/XAMPP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>Database credentials are correct</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<div style='text-align:center;margin:40px;color:#666;'>";
echo "<small>Database setup completed at " . date('Y-m-d H:i:s') . "</small>";
echo "</div>";

echo "</body></html>";
?>
