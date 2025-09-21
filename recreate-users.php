<?php
/**
 * Recreate Test Users Script
 */
require_once 'config/config.php';

echo "<h2>Recreating Test Users</h2>";

try {
    // Check what users exist
    $existingUsers = fetchAll("SELECT username, email, role FROM users");
    echo "<h3>Existing Users:</h3>";
    foreach ($existingUsers as $user) {
        echo "- " . $user['username'] . " (" . $user['email'] . ") - Role: " . $user['role'] . "<br>";
    }
    
    // Create new test users with proper password hashing
    $password = 'password123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<h3>Adding Expected Test Users:</h3>";
    
    // Check if mentor@mentorconnect.com exists
    $mentorExists = fetchOne("SELECT id FROM users WHERE email = 'mentor@mentorconnect.com'");
    if (!$mentorExists) {
        executeQuery(
            "INSERT INTO users (username, email, password_hash, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
            ['mentor1', 'mentor@mentorconnect.com', $hashedPassword, 'John', 'Mentor', 'mentor', 'active']
        );
        echo "✓ Created mentor@mentorconnect.com<br>";
    } else {
        echo "✓ mentor@mentorconnect.com already exists<br>";
    }
    
    // Check if student@mentorconnect.com exists
    $studentExists = fetchOne("SELECT id FROM users WHERE email = 'student@mentorconnect.com'");
    if (!$studentExists) {
        executeQuery(
            "INSERT INTO users (username, email, password_hash, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
            ['student1', 'student@mentorconnect.com', $hashedPassword, 'Jane', 'Student', 'student', 'active']
        );
        echo "✓ Created student@mentorconnect.com<br>";
    } else {
        echo "✓ student@mentorconnect.com already exists<br>";
    }
    
    // Also update existing users' passwords to ensure they work
    executeQuery("UPDATE users SET password_hash = ? WHERE email IN ('john@example.com', 'jane@example.com')", [$hashedPassword]);
    echo "✓ Updated passwords for existing users<br>";
    
    // Show final user list
    $allUsers = fetchAll("SELECT username, email, role, status FROM users ORDER BY email");
    echo "<h3>All Users Now:</h3>";
    foreach ($allUsers as $user) {
        echo "- " . $user['username'] . " (" . $user['email'] . ") - Role: " . $user['role'] . " - Status: " . $user['status'] . "<br>";
    }
    
    // Test password verification
    echo "<h3>Password Verification Test:</h3>";
    $testUser = fetchOne("SELECT password_hash FROM users WHERE email = 'mentor@mentorconnect.com'");
    if ($testUser) {
        $verified = password_verify('password123', $testUser['password_hash']);
        echo "Password verification for 'password123': " . ($verified ? "✓ SUCCESS" : "✗ FAILED") . "<br>";
    }
    
    echo "<br><strong>Users recreated successfully!</strong><br>";
    echo "<a href='test-login.php'>Test Login</a> | <a href='auth/login.php'>Styled Login</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>