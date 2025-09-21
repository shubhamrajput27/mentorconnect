<?php
/**
 * Create Missing Tables Script
 */
require_once 'config/config.php';

echo "<h2>Creating Missing Tables</h2>";

try {
    // Create sessions table
    $sessionsSql = "
    CREATE TABLE IF NOT EXISTS sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        student_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        scheduled_at DATETIME NOT NULL,
        duration_minutes INT DEFAULT 60,
        status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
        meeting_link VARCHAR(500),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    executeQuery($sessionsSql);
    echo "<p style='color: green;'>✓ Sessions table created</p>";
    
    // Create reviews table
    $reviewsSql = "
    CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        session_id INT,
        rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL
    )";
    
    executeQuery($reviewsSql);
    echo "<p style='color: green;'>✓ Reviews table created</p>";
    
    // Create skills table
    $skillsSql = "
    CREATE TABLE IF NOT EXISTS skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    executeQuery($skillsSql);
    echo "<p style='color: green;'>✓ Skills table created</p>";
    
    // Create user_skills table
    $userSkillsSql = "
    CREATE TABLE IF NOT EXISTS user_skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        skill_id INT NOT NULL,
        proficiency_level TINYINT DEFAULT 1,
        skill_type ENUM('learning', 'teaching', 'both') DEFAULT 'both',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_skill (user_id, skill_id)
    )";
    
    executeQuery($userSkillsSql);
    echo "<p style='color: green;'>✓ User skills table created</p>";
    
    // Insert some sample skills
    $sampleSkills = [
        ['JavaScript', 'Programming', 'Modern JavaScript programming language'],
        ['Python', 'Programming', 'Python programming language'],
        ['React', 'Frontend', 'React JavaScript library'],
        ['Node.js', 'Backend', 'Node.js runtime environment'],
        ['MySQL', 'Database', 'MySQL database management'],
        ['Project Management', 'Business', 'Project management skills'],
        ['UI/UX Design', 'Design', 'User interface and experience design'],
        ['Data Science', 'Analytics', 'Data analysis and machine learning'],
        ['Digital Marketing', 'Marketing', 'Online marketing strategies'],
        ['Leadership', 'Soft Skills', 'Team leadership and management']
    ];
    
    foreach ($sampleSkills as $skill) {
        try {
            executeQuery(
                "INSERT IGNORE INTO skills (name, category, description) VALUES (?, ?, ?)",
                $skill
            );
        } catch (Exception $e) {
            // Ignore duplicates
        }
    }
    echo "<p style='color: green;'>✓ Sample skills added</p>";
    
    // Create some sample sessions for testing
    $users = fetchAll("SELECT id, role FROM users WHERE role IN ('mentor', 'student')");
    $mentors = array_filter($users, function($u) { return $u['role'] === 'mentor'; });
    $students = array_filter($users, function($u) { return $u['role'] === 'student'; });
    
    if (!empty($mentors) && !empty($students)) {
        $mentor = reset($mentors);
        $student = reset($students);
        
        // Create sample sessions
        $sampleSessions = [
            [
                'mentor_id' => $mentor['id'],
                'student_id' => $student['id'],
                'title' => 'Introduction to JavaScript',
                'description' => 'Basic JavaScript concepts and syntax',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'status' => 'scheduled'
            ],
            [
                'mentor_id' => $mentor['id'],
                'student_id' => $student['id'],
                'title' => 'React Components Deep Dive',
                'description' => 'Advanced React component patterns',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'completed'
            ]
        ];
        
        foreach ($sampleSessions as $session) {
            executeQuery(
                "INSERT INTO sessions (mentor_id, student_id, title, description, scheduled_at, status) VALUES (?, ?, ?, ?, ?, ?)",
                [$session['mentor_id'], $session['student_id'], $session['title'], $session['description'], $session['scheduled_at'], $session['status']]
            );
        }
        echo "<p style='color: green;'>✓ Sample sessions created</p>";
        
        // Create sample review
        executeQuery(
            "INSERT INTO reviews (reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?)",
            [$student['id'], $mentor['id'], 5, 'Excellent mentor! Very helpful and patient.']
        );
        echo "<p style='color: green;'>✓ Sample review created</p>";
    }
    
    // Show final table list
    $tables = fetchAll("SHOW TABLES");
    echo "<h3>All Tables Now:</h3><ul>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li>" . htmlspecialchars($tableName) . "</li>";
    }
    echo "</ul>";
    
    echo "<br><strong>Missing tables created successfully!</strong><br>";
    echo "<a href='dashboard/mentor.php'>Try Mentor Dashboard</a> | ";
    echo "<a href='dashboard/debug-mentor.php'>Debug Dashboard</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>