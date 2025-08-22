<?php
// Complete database setup and fix script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MentorConnect Database Setup & Fix</h1>";

try {
    // First, try to connect to MySQL without database
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'>✅ MySQL connection successful</div>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS mentorconnect");
    $pdo->exec("USE mentorconnect");
    echo "<div class='success'>✅ Database 'mentorconnect' created/selected</div>";
    
    // Read and execute schema
    $schemaPath = __DIR__ . '/database/schema.sql';
    if (file_exists($schemaPath)) {
        $schema = file_get_contents($schemaPath);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignore table already exists errors
                    if (!strpos($e->getMessage(), 'already exists')) {
                        throw $e;
                    }
                }
            }
        }
        echo "<div class='success'>✅ Database schema created</div>";
    }
    
    // Generate proper password hash
    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    
    // Clear existing users and insert test users
    $pdo->exec("DELETE FROM users WHERE email IN ('john.doe@email.com', 'jane.smith@email.com')");
    
    // Insert test users
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, first_name, last_name, role, bio, email_verified, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'active')
    ");
    
    $users = [
        ['john_doe', 'john.doe@email.com', $passwordHash, 'John', 'Doe', 'mentor', 'Experienced software developer and mentor'],
        ['jane_smith', 'jane.smith@email.com', $passwordHash, 'Jane', 'Smith', 'student', 'Aspiring developer eager to learn']
    ];
    
    foreach ($users as $user) {
        $stmt->execute($user);
    }
    
    echo "<div class='success'>✅ Test users created</div>";
    
    // Get user IDs
    $johnId = $pdo->query("SELECT id FROM users WHERE email = 'john.doe@email.com'")->fetchColumn();
    $janeId = $pdo->query("SELECT id FROM users WHERE email = 'jane.smith@email.com'")->fetchColumn();
    
    // Create mentor profile for John
    $pdo->prepare("
        INSERT INTO mentor_profiles (user_id, title, company, experience_years, hourly_rate, rating, total_sessions, is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
        title = VALUES(title), company = VALUES(company), experience_years = VALUES(experience_years)
    ")->execute([$johnId, 'Senior Full Stack Developer', 'TechCorp', 5, 75.00, 4.8, 25]);
    
    echo "<div class='success'>✅ Mentor profile created</div>";
    
    // Insert some basic skills
    $pdo->exec("
        INSERT IGNORE INTO skills (name, category) VALUES 
        ('JavaScript', 'Programming'),
        ('Python', 'Programming'),
        ('React', 'Frontend'),
        ('Node.js', 'Backend'),
        ('MySQL', 'Database'),
        ('HTML/CSS', 'Frontend'),
        ('PHP', 'Backend'),
        ('Git', 'Tools')
    ");
    
    // Add skills to users
    $skillIds = $pdo->query("SELECT id FROM skills LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($skillIds as $skillId) {
        $pdo->prepare("
            INSERT IGNORE INTO user_skills (user_id, skill_id, proficiency_level) 
            VALUES (?, ?, ?)
        ")->execute([$johnId, $skillId, 'expert']);
        
        $pdo->prepare("
            INSERT IGNORE INTO user_skills (user_id, skill_id, proficiency_level) 
            VALUES (?, ?, ?)
        ")->execute([$janeId, $skillId, 'beginner']);
    }
    
    echo "<div class='success'>✅ Skills added</div>";
    
    // Test password verification
    $testUser = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $testUser->execute(['john.doe@email.com']);
    $user = $testUser->fetch();
    
    if ($user && password_verify('password123', $user['password_hash'])) {
        echo "<div class='success'>✅ Password verification test passed</div>";
    } else {
        echo "<div class='error'>❌ Password verification failed</div>";
    }
    
    // Create uploads directory
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        echo "<div class='success'>✅ Uploads directory created</div>";
    }
    
    // Create default avatar image placeholder
    $defaultAvatar = $uploadsDir . '/default-avatar.png';
    if (!file_exists($defaultAvatar)) {
        // Create a simple 1x1 pixel transparent PNG
        $img = imagecreate(100, 100);
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagepng($img, $defaultAvatar);
        imagedestroy($img);
        echo "<div class='success'>✅ Default avatar created</div>";
    }
    
    echo "<div class='final-success'>";
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<h3>Test Login Credentials:</h3>";
    echo "<strong>Mentor Account:</strong><br>";
    echo "Email: john.doe@email.com<br>";
    echo "Password: password123<br><br>";
    echo "<strong>Student Account:</strong><br>";
    echo "Email: jane.smith@email.com<br>";
    echo "Password: password123<br><br>";
    echo "<a href='/mentorconnect/auth/login.php' style='background: #6366f1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Make sure WAMP64 is running and MySQL service is started.</div>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f5f5f5; 
}
.success { 
    background: #d4edda; 
    color: #155724; 
    padding: 10px; 
    margin: 5px 0; 
    border-radius: 5px; 
    border-left: 4px solid #28a745;
}
.error { 
    background: #f8d7da; 
    color: #721c24; 
    padding: 10px; 
    margin: 5px 0; 
    border-radius: 5px; 
    border-left: 4px solid #dc3545;
}
.final-success {
    background: #d1ecf1;
    color: #0c5460;
    padding: 20px;
    margin: 20px 0;
    border-radius: 10px;
    border: 2px solid #17a2b8;
    text-align: center;
}
h1 { color: #333; }
</style>
