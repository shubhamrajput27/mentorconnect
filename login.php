<?php
/**
 * DEPRECATED: Use auth/login.php instead
 * This file is maintained for backward compatibility only
 */

// Redirect to the new login page
header('Location: auth/login.php');
exit();

// Legacy code below (secured but deprecated)
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Rate limiting
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!checkRateLimit($clientIP, 'login')) {
        $error = 'Too many login attempts. Please try again later.';
    } elseif (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Use secure database functions
                $user = fetchOne(
                    "SELECT id, username, password_hash, role, status FROM users WHERE email = ?",
                    [$email]
                );

                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your account is not active. Please contact support.';
                    } else {
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Set session variables correctly
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();

                        // Log successful login
                        logActivity($user['id'], 'login', 'User logged in via legacy form');
                        
                        $success = "Login successful! Welcome, " . htmlspecialchars($user['username']);
                        
                        // Redirect to appropriate dashboard
                        $redirectUrl = $user['role'] === 'mentor' ? 'dashboard/mentor.php' : 'dashboard/student.php';
                        header("Location: " . $redirectUrl);
                        exit();
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "An error occurred. Please try again.";
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MentorConnect</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; 
        }
        button { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #0056b3; }
        .redirect-notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="redirect-notice">
        <strong>Notice:</strong> This login form is deprecated. 
        <a href="auth/login.php">Please use our new login page</a> for the best experience.
    </div>
    
    <h2>Login to MentorConnect</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        Don't have an account? <a href="auth/signup.php">Sign up here</a>
    </p>
</body>
</html>
