<?php
// Debug script to check database connection and user data
require_once 'config/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "✅ Database connection successful<br><br>";
    
    // Check if users table exists and has data
    $users = fetchAll("SELECT id, username, email, first_name, last_name, role FROM users LIMIT 10");
    
    echo "<h3>Users in Database:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Role</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['first_name'] . " " . $user['last_name'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification
    echo "<h3>Password Test:</h3>";
    $testUser = fetchOne("SELECT * FROM users WHERE email = ?", ['john@example.com']);
    if ($testUser) {
        $testPassword = 'password123';
        $isValid = password_verify($testPassword, $testUser['password_hash']);
        echo "Test user found: " . $testUser['email'] . "<br>";
        echo "Password verification result: " . ($isValid ? "✅ Valid" : "❌ Invalid") . "<br>";
        echo "Stored hash: " . substr($testUser['password_hash'], 0, 50) . "...<br>";
    } else {
        echo "❌ Test user not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Make sure WAMP is running and database 'mentorconnect' exists<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
