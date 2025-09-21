<?php
/**
 * Legacy Database Connection - DEPRECATED
 * Use config/config.php for new connections
 */

// Redirect to config.php for consistency
require_once __DIR__ . '/config/config.php';

// Legacy mysqli connection for backward compatibility
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$db = DB_NAME;

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset for legacy connection
$conn->set_charset(DB_CHARSET);
?>
