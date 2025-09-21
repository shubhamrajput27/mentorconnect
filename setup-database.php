<?php
/**
 * Database Setup Script
 * Run this to initialize the database with proper tables
 */

$host = "localhost";
$user = "root";
$pass = "";
$db = "mentorconnect";

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to MySQL successfully<br>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$db}' created or already exists<br>";
    
    // Use the database
    $pdo->exec("USE {$db}");
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/database/database.sql';
    if (file_exists($sqlFile)) {
        echo "Reading SQL file from: {$sqlFile}<br>";
        $sql = file_get_contents($sqlFile);
        
        if (empty($sql)) {
            echo "SQL file is empty<br>";
        } else {
            // Remove comments and empty lines
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            // Split into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            echo "Found " . count($statements) . " SQL statements<br>";
            
            foreach ($statements as $index => $statement) {
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                        echo "Statement " . ($index + 1) . ": OK<br>";
                    } catch (PDOException $e) {
                        // Some statements might fail if tables already exist
                        if (strpos($e->getMessage(), 'already exists') !== false) {
                            echo "Statement " . ($index + 1) . ": Already exists (OK)<br>";
                        } else {
                            echo "Statement " . ($index + 1) . ": Warning - " . $e->getMessage() . "<br>";
                        }
                    }
                }
            }
        }
        
        echo "Database schema executed successfully<br>";
    } else {
        echo "SQL file not found at: {$sqlFile}<br>";
        echo "Current directory: " . __DIR__ . "<br>";
        echo "Available files in /database/: ";
        $dbDir = __DIR__ . '/database/';
        if (is_dir($dbDir)) {
            $files = scandir($dbDir);
            echo implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; }));
        } else {
            echo "Directory not found";
        }
        echo "<br>";
    }
    
    // Verify tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables created: " . implode(', ', $tables) . "<br>";
    
    // Check if we have any users
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "User count: {$userCount}<br>";
    
    if ($userCount == 0) {
        echo "Creating default users...<br>";
        
        // Create test users
        $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        $pdo->exec("INSERT INTO users (username, email, password_hash, first_name, last_name, role, status) VALUES 
            ('admin', 'admin@mentorconnect.com', '{$defaultPassword}', 'Admin', 'User', 'admin', 'active'),
            ('mentor1', 'mentor@mentorconnect.com', '{$defaultPassword}', 'John', 'Mentor', 'mentor', 'active'),
            ('student1', 'student@mentorconnect.com', '{$defaultPassword}', 'Jane', 'Student', 'student', 'active')");
        
        echo "Default users created (password: password123)<br>";
    }
    
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "<a href='auth/login.php'>Go to Login</a><br>";
    echo "<a href='index.php'>Go to Home</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>