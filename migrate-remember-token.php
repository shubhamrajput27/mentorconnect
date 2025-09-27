<?php
require_once 'config/config.php';

echo "<h2>Database Migration - Remember Token</h2>";

try {
    // Check if remember_token column exists
    $columnCheck = fetchOne("SHOW COLUMNS FROM users LIKE 'remember_token'");
    
    if (!$columnCheck) {
        echo "<p>❌ remember_token column not found. Adding it...</p>";
        
        // Add the column
        executeQuery("ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL");
        echo "<p>✅ Added remember_token column</p>";
        
        // Add index for performance
        executeQuery("ALTER TABLE users ADD INDEX idx_remember_token (remember_token)");
        echo "<p>✅ Added index for remember_token</p>";
        
        echo "<p><strong>Migration completed successfully!</strong></p>";
    } else {
        echo "<p>✅ remember_token column already exists</p>";
        
        // Show column info
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        echo "<tr>";
        echo "<td>" . $columnCheck['Field'] . "</td>";
        echo "<td>" . $columnCheck['Type'] . "</td>";
        echo "<td>" . $columnCheck['Null'] . "</td>";
        echo "<td>" . $columnCheck['Key'] . "</td>";
        echo "<td>" . ($columnCheck['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        echo "</table>";
    }
    
    // Test the column by updating a dummy value
    echo "<h3>Testing Column:</h3>";
    $testUser = fetchOne("SELECT id FROM users LIMIT 1");
    if ($testUser) {
        executeQuery("UPDATE users SET remember_token = ? WHERE id = ?", ['test_token_' . time(), $testUser['id']]);
        echo "<p>✅ Column is writable</p>";
        
        // Clean up test
        executeQuery("UPDATE users SET remember_token = NULL WHERE id = ?", [$testUser['id']]);
        echo "<p>✅ Test cleanup completed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<br><br><a href='auth/login.php'>Back to Login</a>";
echo "<br><a href='check-db.php'>Check Database Schema</a>";
?>