<?php
session_start();
require_once 'config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        // Use the global PDO connection from config
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables correctly
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role']; // Make sure this matches what dashboard expects
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();

            echo "Login successful! Welcome, " . $user['username'];
            
            // Redirect to appropriate dashboard
            $redirectUrl = $user['role'] === 'mentor' ? 'dashboard/mentor.php' : 'dashboard/student.php';
            header("Location: " . $redirectUrl);
            exit();
        } else {
            echo "Invalid email or password.";
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo "An error occurred. Please try again.";
    }
}
?>
