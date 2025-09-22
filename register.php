<?php
/**
 * DEPRECATED: Use auth/signup.php instead
 * This file is maintained for backward compatibility only
 */

// Redirect to the new signup page
header('Location: auth/signup.php');
exit();

// Legacy code below (secured but deprecated)
require_once 'config/config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic CSRF protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username   = sanitizeInput($_POST['username'] ?? '');
        $email      = sanitizeInput($_POST['email'] ?? '');
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name  = sanitizeInput($_POST['last_name'] ?? '');
        $password   = $_POST['password'] ?? '';

        // Enhanced validation
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!validatePassword($password)) {
            $error = 'Password must be at least 12 characters long and include uppercase, lowercase, numbers, and special characters.';
        } else {
            try {
                // Use PDO from config instead of mysqli
                global $pdo;
                
                // Check if email already exists
                $check = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
                
                if ($check) {
                    $error = "Email already registered!";
                } else {
                    // Hash password with better options
                    $password_hash = password_hash($password, PASSWORD_ARGON2ID, [
                        'memory_cost' => 65536,
                        'time_cost' => 4,
                        'threads' => 3
                    ]);

                    executeQuery(
                        "INSERT INTO users (username, email, password_hash, first_name, last_name, email_verified) 
                         VALUES (?, ?, ?, ?, ?, FALSE)",
                        [$username, $email, $password_hash, $first_name, $last_name]
                    );

                    $success = "Registration successful! Please check your email to verify your account.";
                    
                    // Log the registration
                    $userId = getLastInsertId();
                    logActivity($userId, 'register', 'User registered via legacy form');
                }
            } catch (Exception $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = "Registration failed. Please try again.";
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
    <title>Register - MentorConnect</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; 
        }
        button { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #0056b3; }
        .redirect-notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="redirect-notice">
        <strong>Notice:</strong> This registration form is deprecated. 
        <a href="auth/signup.php">Please use our new signup page</a> for the best experience.
    </div>
    
    <h2>Register for MentorConnect</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required 
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required 
                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required 
                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required minlength="12">
            <small>Must be at least 12 characters with uppercase, lowercase, numbers, and special characters.</small>
        </div>
        
        <button type="submit">Register</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        Already have an account? <a href="auth/login.php">Sign in here</a>
    </p>
</body>
</html>
