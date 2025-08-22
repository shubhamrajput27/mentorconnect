<?php
// Fix login credentials in database
require_once 'config/config.php';

try {
    // Generate proper password hash for 'password123'
    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    
    echo "<h2>Fixing Login Credentials</h2>";
    
    // Update or insert the correct test users
    $users = [
        [
            'username' => 'john_doe',
            'email' => 'john.doe@email.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'mentor'
        ],
        [
            'username' => 'jane_smith',
            'email' => 'jane.smith@email.com', 
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'role' => 'student'
        ]
    ];
    
    foreach ($users as $user) {
        // Check if user exists
        $existingUser = fetchOne("SELECT id FROM users WHERE email = ?", [$user['email']]);
        
        if ($existingUser) {
            // Update password hash
            executeQuery(
                "UPDATE users SET password_hash = ? WHERE email = ?",
                [$passwordHash, $user['email']]
            );
            echo "✅ Updated password for: " . $user['email'] . "<br>";
        } else {
            // Insert new user
            executeQuery(
                "INSERT INTO users (username, email, password_hash, first_name, last_name, role, email_verified, bio) VALUES (?, ?, ?, ?, ?, ?, 1, ?)",
                [
                    $user['username'],
                    $user['email'],
                    $passwordHash,
                    $user['first_name'],
                    $user['last_name'],
                    $user['role'],
                    $user['role'] === 'mentor' ? 'Experienced mentor ready to help students' : 'Eager to learn and grow'
                ]
            );
            
            $userId = fetchOne("SELECT LAST_INSERT_ID() as id")['id'];
            
            // Create mentor profile if mentor
            if ($user['role'] === 'mentor') {
                executeQuery(
                    "INSERT INTO mentor_profiles (user_id, title, company, experience_years, hourly_rate, rating, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$userId, 'Senior Developer', 'Tech Company', 5, 50.00, 4.5, 1]
                );
            }
            
            echo "✅ Created new user: " . $user['email'] . "<br>";
        }
    }
    
    echo "<br><h3>Test Login Credentials:</h3>";
    echo "<strong>Mentor Account:</strong><br>";
    echo "Email: john.doe@email.com<br>";
    echo "Password: password123<br><br>";
    
    echo "<strong>Student Account:</strong><br>";
    echo "Email: jane.smith@email.com<br>";
    echo "Password: password123<br><br>";
    
    // Test password verification
    $testUser = fetchOne("SELECT * FROM users WHERE email = ?", ['john.doe@email.com']);
    if ($testUser) {
        $isValid = password_verify('password123', $testUser['password_hash']);
        echo "<h3>Password Verification Test:</h3>";
        echo "User found: ✅<br>";
        echo "Password verification: " . ($isValid ? "✅ Valid" : "❌ Invalid") . "<br>";
    }
    
    echo "<br><div style='background: #d4edda; padding: 10px; border-radius: 5px; color: #155724;'>";
    echo "<strong>✅ Login fix completed!</strong><br>";
    echo "You can now login with the credentials above.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
