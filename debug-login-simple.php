<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting login page debug...<br>";

try {
    echo "Including config...<br>";
    require_once '../config/config.php';
    echo "Config loaded successfully<br>";
    
    echo "Testing database connection...<br>";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, 
                   DB_USER, DB_PASS);
    echo "Database connected successfully<br>";
    
    echo "All tests passed!<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Debug</title>
</head>
<body>
    <h1>Debug completed - check above for any errors</h1>
</body>
</html>