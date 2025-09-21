<?php
require_once 'config/config.php';

echo "<h1>Create Test Student User</h1>";

try {
    // Check if test student already exists
    $existing = fetchOne("SELECT id, username FROM users WHERE email = 'student@test.com'");
    
    if ($existing) {
        echo "<p style='color: orange;'>✓ Test student already exists with ID: " . $existing['id'] . " (username: " . $existing['username'] . ")</p>";
    } else {
        // Create test student with unique username
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        // Generate unique username
        $baseUsername = 'jane_student';
        $username = $baseUsername;
        $counter = 1;
        
        // Check for username conflicts and generate unique one
        while (true) {
            $usernameCheck = fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            if (!$usernameCheck) {
                break; // Username is available
            }
            $username = $baseUsername . '_' . $counter;
            $counter++;
            if ($counter > 50) {
                throw new Exception("Could not generate unique username");
            }
        }
        
        executeQuery(
            "INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone, created_at, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')",
            [$username, 'student@test.com', $hashedPassword, 'Jane', 'Student', 'student', '555-0124']
        );
        
        echo "<p style='color: green;'>✓ Test student created successfully with username: " . htmlspecialchars($username) . "</p>";
    }
    
    echo "<h2>Test Users Available:</h2>";
    
    // Add cleanup option
    if (isset($_GET['cleanup'])) {
        echo "<div style='background: #fffbf0; border: 1px solid #f0ad4e; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>Cleanup Mode:</strong> Removing duplicate test users...";
        
        // Keep only the first user of each email
        $duplicateUsers = fetchAll(
            "SELECT email, COUNT(*) as count, GROUP_CONCAT(id ORDER BY created_at) as ids 
             FROM users 
             WHERE email IN ('john_mentor@test.com', 'student@test.com') 
             GROUP BY email 
             HAVING count > 1"
        );
        
        foreach ($duplicateUsers as $dup) {
            $ids = explode(',', $dup['ids']);
            $keepId = array_shift($ids); // Keep the first (oldest) user
            
            foreach ($ids as $deleteId) {
                executeQuery("DELETE FROM users WHERE id = ?", [$deleteId]);
                echo "<br>Removed duplicate user ID: " . $deleteId . " for " . $dup['email'];
            }
        }
        echo "<br><a href='test-connections.php'>Refresh page</a>";
        echo "</div>";
    }
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Email</th><th>Role</th><th>Name</th><th>Username</th><th>Created</th><th>Actions</th></tr>";
    
    $users = fetchAll("SELECT email, role, first_name, last_name, username, created_at FROM users ORDER BY role, first_name");
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td style='color: " . ($user['role'] === 'mentor' ? 'blue' : 'green') . ";'>" . ucfirst($user['role']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . date('M j, Y', strtotime($user['created_at'])) . "</td>";
        echo "<td><a href='auth/login.php' target='_blank'>Login Page</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show cleanup option if duplicates exist
    $duplicateCount = fetchOne(
        "SELECT COUNT(*) as count FROM (
            SELECT email FROM users 
            WHERE email IN ('john_mentor@test.com', 'student@test.com') 
            GROUP BY email 
            HAVING COUNT(*) > 1
        ) as duplicates"
    )['count'];
    
    if ($duplicateCount > 0) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>⚠️ Found " . $duplicateCount . " duplicate email(s)</strong><br>";
        echo "<a href='test-connections.php?cleanup=1' style='color: #721c24; font-weight: bold;'>🧹 Clean up duplicates</a>";
        echo "</div>";
    }
    
    echo "<h2>Test the Complete Flow:</h2>";
    echo "<ol>";
    echo "<li><a href='index.php' target='_blank'>Visit Home Page</a> - Should show landing page with login/signup links</li>";
    echo "<li><a href='auth/signup.php' target='_blank'>Test Signup</a> - Create new user and auto-redirect to dashboard</li>";
    echo "<li><a href='auth/login.php' target='_blank'>Test Login</a> - Use john_mentor@test.com or student@test.com (password: password123)</li>";
    echo "<li>Test Dashboard Access - Should redirect based on role</li>";
    echo "<li>Test Logout - Should return to home page</li>";
    echo "</ol>";
    
    echo "<h2>Login Credentials for Testing:</h2>";
    echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #2196F3;'>";
    echo "<strong>Available Test Accounts:</strong><br><br>";
    
    // Get actual mentor and student accounts
    $mentors = fetchAll("SELECT email, first_name, last_name FROM users WHERE role = 'mentor' ORDER BY created_at");
    $students = fetchAll("SELECT email, first_name, last_name FROM users WHERE role = 'student' ORDER BY created_at");
    
    echo "<strong>📚 Student Accounts:</strong><br>";
    foreach ($students as $student) {
        echo "• " . $student['email'] . " (" . $student['first_name'] . " " . $student['last_name'] . ") - password: password123<br>";
    }
    
    echo "<br><strong>🎓 Mentor Accounts:</strong><br>";
    foreach ($mentors as $mentor) {
        echo "• " . $mentor['email'] . " (" . $mentor['first_name'] . " " . $mentor['last_name'] . ") - password: password123<br>";
    }
    
    echo "<br><em>Use any of these accounts to test the login → dashboard flow!</em>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; }
th, td { padding: 10px; text-align: left; }
th { background: #f0f0f0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>