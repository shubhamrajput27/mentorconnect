<?php
require_once 'config/config.php';

echo "<h2>Database Schema Check</h2>";

try {
    // Check if users table exists and has remember_token column
    $result = fetchAll("DESCRIBE users");
    
    echo "<h3>Users Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasRememberToken = false;
    foreach ($result as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'remember_token') {
            $hasRememberToken = true;
        }
    }
    echo "</table>";
    
    echo "<h3>Remember Token Column:</h3>";
    if ($hasRememberToken) {
        echo "<span style='color: green;'>✅ remember_token column EXISTS</span>";
    } else {
        echo "<span style='color: red;'>❌ remember_token column MISSING</span>";
        echo "<br><br><strong>Fix needed:</strong> Add remember_token column to users table.";
    }
    
    // Test a simple update query
    echo "<h3>Database Connection Test:</h3>";
    $testResult = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $testResult->fetch()['count'];
    echo "✅ Database connection working. Found {$count} users in database.";
    
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database Error: " . $e->getMessage() . "</span>";
}

echo "<br><br><a href='auth/login.php'>Back to Login</a>";
?>