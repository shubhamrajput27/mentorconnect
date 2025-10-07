<?php
/**
 * Database Setup and Initialization Script
 * This script creates the database and tables if they don't exist
 */

require_once 'config/database.php';

try {
    // First, connect to MySQL without specifying a database
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Read and execute the SQL file
        $sqlFile = file_get_contents(__DIR__ . '/database/database.sql');
        if ($sqlFile === false) {
            throw new Exception("Could not read database.sql file");
        }
        
        // Split the SQL file into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sqlFile)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Skip errors for statements that might already exist
                    if (!str_contains($e->getMessage(), 'already exists')) {
                        error_log("Database setup warning: " . $e->getMessage());
                    }
                }
            }
        }
        
        echo "Database setup completed successfully!<br>";
    } else {
        echo "Database already exists and is set up.<br>";
    }
    
    // Test the connection with the application database
    $appDsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $testPdo = new PDO($appDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Test query
    $stmt = $testPdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    
    echo "Total users in database: " . $result['count'] . "<br>";
    echo "MentorConnect is ready to use!<br>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    exit(1);
}
?>