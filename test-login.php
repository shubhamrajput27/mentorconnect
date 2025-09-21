<?php
/**
 * Login Test Page - Simple form for testing authentication
 */
require_once 'config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        try {
            // Test database connection first
            $testConnection = fetchOne("SELECT 1 as test");
            if (!$testConnection) {
                throw new Exception("Database connection failed");
            }
            
            // Debug: Show what we're searching for
            echo "<div class='info'>Searching for user with email: " . htmlspecialchars($email) . "</div>";
            
            $user = fetchOne(
                "SELECT id, username, email, password_hash, role, status FROM users WHERE email = ?",
                [$email]
            );
            
            // Debug: Show if user was found
            if ($user) {
                echo "<div class='info'>User found: " . htmlspecialchars($user['username']) . " (Status: " . htmlspecialchars($user['status']) . ")</div>";
                
                // Test password verification
                $passwordMatch = password_verify($password, $user['password_hash']);
                echo "<div class='info'>Password verification: " . ($passwordMatch ? 'PASS' : 'FAIL') . "</div>";
                
                if ($passwordMatch && $user['status'] === 'active') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['login_time'] = time();
                    
                    $success = "Login successful! Redirecting to dashboard...";
                    
                    // JavaScript redirect after 2 seconds
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'dashboard/" . ($user['role'] === 'mentor' ? 'mentor.php' : 'student.php') . "';
                        }, 2000);
                    </script>";
                } else {
                    if ($user['status'] !== 'active') {
                        $error = "Account is not active (Status: " . $user['status'] . ")";
                    } else {
                        $error = "Password verification failed.";
                    }
                }
            } else {
                $error = "No user found with email: " . htmlspecialchars($email);
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Connection refused") !== false) {
                $error = "Database not set up. Please run the database setup first.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Test - MentorConnect</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Login Test - MentorConnect</h1>
    
    <div class="info">
        <strong>Test Accounts (from database):</strong><br>
        Mentor: john@example.com / password123<br>
        Student: jane@example.com / password123
    </div>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <hr>
    <h3>Debug Information:</h3>
    
    <?php
    // Test database connection and show user data
    try {
        $testQuery = fetchOne("SELECT COUNT(*) as count FROM users");
        echo "<p><strong>Database Status:</strong> <span style='color: green;'>Connected ✓</span></p>";
        echo "<p><strong>Users in database:</strong> " . $testQuery['count'] . "</p>";
        
        // Show actual users in database for debugging
        $users = fetchAll("SELECT id, username, email, role, status FROM users LIMIT 10");
        if ($users) {
            echo "<p><strong>Actual Users in Database:</strong></p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                echo "<td>" . htmlspecialchars($user['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p><strong>Database Status:</strong> <span style='color: red;'>Error ✗</span></p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><a href='setup-database.php' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Setup Database First</a></p>";
    }
    ?>
    
    <p><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></p>
    <p><strong>Current Session:</strong></p>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <?php if (isLoggedIn()): ?>
        <p><strong>Current User:</strong></p>
        <pre><?php print_r(getCurrentUser()); ?></pre>
        <p><a href="dashboard/<?php echo $_SESSION['user_role']; ?>.php">Go to Dashboard</a></p>
    <?php endif; ?>
    
    <p><a href="setup-database.php">Setup Database</a></p>
    <p><a href="auth/login.php">Go to Styled Login Page</a></p>
    <p><a href="index.php">Go to Home</a></p>
</body>
</html>