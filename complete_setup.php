<?php
// Complete setup and error fix script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>MentorConnect Setup</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #28a745; }
.error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #dc3545; }
.info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #17a2b8; }
.step { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 5px solid #ffc107; }
h1 { color: #333; text-align: center; margin-bottom: 30px; }
.btn { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
.btn:hover { background: #0056b3; }
.credentials { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
.final { text-align: center; background: #d4edda; padding: 30px; border-radius: 10px; margin: 20px 0; }
</style></head><body><div class='container'>";

echo "<h1>🚀 MentorConnect Complete Setup</h1>";

$steps = [];
$errors = [];

try {
    // Step 1: Test MySQL Connection
    echo "<div class='step'>Step 1: Testing MySQL Connection...</div>";
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<div class='success'>✅ MySQL connection successful</div>";
    $steps[] = "MySQL connection established";

    // Step 2: Create Database
    echo "<div class='step'>Step 2: Creating Database...</div>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS mentorconnect");
    $pdo->exec("USE mentorconnect");
    echo "<div class='success'>✅ Database 'mentorconnect' ready</div>";
    $steps[] = "Database created";

    // Step 3: Create Tables
    echo "<div class='step'>Step 3: Creating Database Tables...</div>";
    
    // Drop existing tables to avoid conflicts
    $tables = ['file_permissions', 'files', 'reviews', 'user_skills', 'mentor_profiles', 'activities', 'notifications', 'messages', 'sessions', 'user_preferences', 'skills', 'users'];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }

    // Create all tables
    $sql = "
    CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        role ENUM('mentor', 'student') NOT NULL,
        profile_photo VARCHAR(255) DEFAULT NULL,
        bio TEXT,
        phone VARCHAR(20),
        location VARCHAR(100),
        timezone VARCHAR(50) DEFAULT 'UTC',
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        email_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    CREATE TABLE skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE mentor_profiles (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(100),
        company VARCHAR(100),
        experience_years INT DEFAULT 0,
        hourly_rate DECIMAL(10,2) DEFAULT 0.00,
        availability TEXT,
        languages VARCHAR(255),
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_sessions INT DEFAULT 0,
        is_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE user_skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        skill_id INT NOT NULL,
        proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_skill (user_id, skill_id)
    );

    CREATE TABLE sessions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        mentor_id INT NOT NULL,
        student_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        scheduled_at DATETIME NOT NULL,
        duration_minutes INT DEFAULT 60,
        status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
        meeting_link VARCHAR(500),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        subject VARCHAR(200),
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        type ENUM('message', 'meeting', 'feedback', 'system') NOT NULL,
        title VARCHAR(200) NOT NULL,
        content TEXT,
        action_url VARCHAR(500),
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE files (
        id INT PRIMARY KEY AUTO_INCREMENT,
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

    CREATE TABLE file_permissions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        file_id INT NOT NULL,
        user_id INT NOT NULL,
        granted_by INT NOT NULL,
        permission_type ENUM('read', 'write', 'delete') DEFAULT 'read',
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        session_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE activities (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE user_preferences (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        theme ENUM('light', 'dark') DEFAULT 'light',
        language VARCHAR(10) DEFAULT 'en',
        timezone VARCHAR(50) DEFAULT 'UTC',
        email_notifications BOOLEAN DEFAULT TRUE,
        push_notifications BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE user_sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    echo "<div class='success'>✅ All database tables created</div>";
    $steps[] = "Database tables created";

    // Step 4: Insert Sample Data
    echo "<div class='step'>Step 4: Inserting Sample Data...</div>";
    
    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    
    // Insert skills
    $pdo->exec("
        INSERT INTO skills (name, category) VALUES 
        ('JavaScript', 'Programming'),
        ('Python', 'Programming'),
        ('React', 'Frontend'),
        ('Node.js', 'Backend'),
        ('MySQL', 'Database'),
        ('HTML/CSS', 'Frontend'),
        ('PHP', 'Backend'),
        ('Git', 'Tools')
    ");

    // Insert users
    $pdo->prepare("
        INSERT INTO users (username, email, password_hash, first_name, last_name, role, bio, email_verified, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'active')
    ")->execute(['john_doe', 'john.doe@email.com', $passwordHash, 'John', 'Doe', 'mentor', 'Experienced software developer and mentor']);

    $pdo->prepare("
        INSERT INTO users (username, email, password_hash, first_name, last_name, role, bio, email_verified, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'active')
    ")->execute(['jane_smith', 'jane.smith@email.com', $passwordHash, 'Jane', 'Smith', 'student', 'Aspiring developer eager to learn']);

    // Get user IDs
    $johnId = $pdo->query("SELECT id FROM users WHERE email = 'john.doe@email.com'")->fetchColumn();
    $janeId = $pdo->query("SELECT id FROM users WHERE email = 'jane.smith@email.com'")->fetchColumn();

    // Create mentor profile
    $pdo->prepare("
        INSERT INTO mentor_profiles (user_id, title, company, experience_years, hourly_rate, rating, total_sessions, is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ")->execute([$johnId, 'Senior Full Stack Developer', 'TechCorp', 5, 75.00, 4.8, 25]);

    // Add skills to users
    $skillIds = $pdo->query("SELECT id FROM skills LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($skillIds as $skillId) {
        $pdo->prepare("INSERT INTO user_skills (user_id, skill_id, proficiency_level) VALUES (?, ?, ?)")
            ->execute([$johnId, $skillId, 'expert']);
        $pdo->prepare("INSERT INTO user_skills (user_id, skill_id, proficiency_level) VALUES (?, ?, ?)")
            ->execute([$janeId, $skillId, 'beginner']);
    }

    echo "<div class='success'>✅ Sample data inserted</div>";
    $steps[] = "Sample data added";

    // Step 5: Create Directories
    echo "<div class='step'>Step 5: Creating Required Directories...</div>";
    
    $dirs = ['uploads', 'assets/images', 'uploads/profiles', 'uploads/files'];
    foreach ($dirs as $dir) {
        $fullPath = __DIR__ . '/' . $dir;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
            echo "<div class='success'>✅ Created directory: $dir</div>";
        }
    }
    
    // Create default avatar
    $defaultAvatar = __DIR__ . '/assets/images/default-avatar.png';
    if (!file_exists($defaultAvatar)) {
        $img = imagecreate(100, 100);
        $bg = imagecolorallocate($img, 200, 200, 200);
        $text = imagecolorallocate($img, 100, 100, 100);
        imagestring($img, 3, 35, 40, 'USER', $text);
        imagepng($img, $defaultAvatar);
        imagedestroy($img);
    }
    
    echo "<div class='success'>✅ Required directories and files created</div>";
    $steps[] = "Directories created";

    // Step 6: Test Login
    echo "<div class='step'>Step 6: Testing Login System...</div>";
    
    $testUser = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $testUser->execute(['john.doe@email.com']);
    $user = $testUser->fetch();
    
    if ($user && password_verify('password123', $user['password_hash'])) {
        echo "<div class='success'>✅ Login system working correctly</div>";
        $steps[] = "Login system verified";
    } else {
        throw new Exception("Login verification failed");
    }

    // Success Summary
    echo "<div class='final'>";
    echo "<h2>🎉 Setup Complete Successfully!</h2>";
    echo "<p>All errors have been fixed and the system is ready to use.</p>";
    
    echo "<div class='credentials'>";
    echo "<h3>🔑 Test Login Credentials</h3>";
    echo "<strong>Mentor Account:</strong><br>";
    echo "Email: <code>john.doe@email.com</code><br>";
    echo "Password: <code>password123</code><br><br>";
    echo "<strong>Student Account:</strong><br>";
    echo "Email: <code>jane.smith@email.com</code><br>";
    echo "Password: <code>password123</code><br>";
    echo "</div>";
    
    echo "<a href='auth/login.php' class='btn'>🚀 Go to Login Page</a>";
    echo "<a href='index.php' class='btn'>🏠 Go to Home Page</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Setup Failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>";
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Make sure WAMP64 is running (green icon in system tray)</li>";
    echo "<li>Ensure Apache and MySQL services are started</li>";
    echo "<li>Check if port 80 and 3306 are available</li>";
    echo "<li>Try accessing phpMyAdmin at http://localhost/phpmyadmin/</li>";
    echo "</ol>";
    echo "</div>";
}

echo "</div></body></html>";
?>
