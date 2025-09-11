<?php
// Debug script to test database connection and user authentication

require_once 'config/config.php';

echo "<h2>MentorConnect Login Debug</h2>";

// Test database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo "✅ Database connection successful<br>";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Check if users table exists and has data
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        echo "<strong>Users table columns:</strong><br>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        
        // Check if users exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "<br>✅ Total users in database: " . $count . "<br>";
        
        if ($count > 0) {
            // Show sample users
            $stmt = $pdo->query("SELECT id, username, email, role, status FROM users LIMIT 5");
            $users = $stmt->fetchAll();
            echo "<br><strong>Sample users:</strong><br>";
            foreach ($users as $user) {
                echo "- ID: {$user['id']}, Email: {$user['email']}, Role: {$user['role']}, Status: {$user['status']}<br>";
            }
        }
        
    } else {
        echo "❌ Users table does not exist<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error checking users table: " . $e->getMessage() . "<br>";
}

// Test specific user login
echo "<br><h3>Test User Authentication</h3>";
$testEmail = 'admin@mentorconnect.com';
$testPassword = 'admin123';

try {
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, status FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found: " . $user['email'] . "<br>";
        echo "- Role: " . $user['role'] . "<br>";
        echo "- Status: " . $user['status'] . "<br>";
        echo "- Password hash: " . substr($user['password_hash'], 0, 20) . "...<br>";
        
        // Test password verification
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "✅ Password verification successful<br>";
        } else {
            echo "❌ Password verification failed<br>";
            
            // Try creating a new hash for comparison
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "New hash would be: " . substr($newHash, 0, 20) . "...<br>";
        }
    } else {
        echo "❌ User not found with email: " . $testEmail . "<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error testing authentication: " . $e->getMessage() . "<br>";
}

// Test session functionality
echo "<br><h3>Test Session</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session started successfully<br>";
    echo "Session ID: " . session_id() . "<br>";
} else {
    echo "❌ Session failed to start<br>";
}

echo "<br><h3>Database Import Status</h3>";
echo "Make sure you imported database.sql successfully in phpMyAdmin<br>";
echo "If users table is empty, the import may have failed<br>";
?>
