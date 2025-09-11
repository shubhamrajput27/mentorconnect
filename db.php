<?php

$host = "localhost";
$user = "root"; // change if different
$pass = "";     // your MySQL password
$db   = "mentorconnect";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
