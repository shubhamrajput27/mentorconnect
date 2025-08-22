<?php
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Rate limiting check
    if (!checkRateLimit($clientIP, 'login')) {
        $error = 'Too many login attempts. Please try again later.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } elseif (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Check for login attempts lockout
                $lockoutKey = 'login_attempts_' . $email;
                $lockoutFile = sys_get_temp_dir() . '/' . md5($lockoutKey);
                
                if (file_exists($lockoutFile)) {
                    $lockoutData = json_decode(file_get_contents($lockoutFile), true);
                    if ($lockoutData && $lockoutData['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                        if (time() - $lockoutData['timestamp'] < LOGIN_LOCKOUT_TIME) {
                            $error = 'Account temporarily locked due to multiple failed attempts. Try again later.';
                        } else {
                            // Reset attempts after lockout period
                            unlink($lockoutFile);
                        }
                    }
                }
                
                if (empty($error)) {
                    $user = fetchOne(
                        "SELECT id, username, email, password_hash, role, status, email_verified FROM users WHERE email = ?",
                        [$email]
                    );
                    
                    if ($user && password_verify($password, $user['password_hash'])) {
                        if ($user['status'] !== 'active') {
                            $error = 'Your account is not active. Please contact support.';
                        } elseif (!$user['email_verified']) {
                            $error = 'Please verify your email address before logging in.';
                        } else {
                            // Reset failed attempts on successful login
                            if (file_exists($lockoutFile)) {
                                unlink($lockoutFile);
                            }
                            
                            // Regenerate session ID for security
                            session_regenerate_id(true);
                            
                            // Set session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['login_time'] = time();
                            $_SESSION['last_activity'] = time();
                            
                            // Store session in database
                            executeQuery(
                                "INSERT INTO user_sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP",
                                [session_id(), $user['id'], $clientIP, $_SERVER['HTTP_USER_AGENT'] ?? '']
                            );
                            
                            // Log activity
                            logActivity($user['id'], 'login', 'User logged in successfully');
                            
                            // Set remember me cookie if requested
                            if ($remember) {
                                $token = bin2hex(random_bytes(32));
                                setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', false, true);
                                // Store token in database for validation
                                executeQuery(
                                    "UPDATE users SET remember_token = ? WHERE id = ?",
                                    [hash('sha256', $token), $user['id']]
                                );
                            }
                            
                            // Redirect to dashboard
                            $redirectUrl = $user['role'] === 'mentor' ? '/dashboard/mentor.php' : '/dashboard/student.php';
                            header('Location: ' . BASE_URL . $redirectUrl);
                            exit();
                        }
                    } else {
                        // Track failed login attempts
                        $attempts = 1;
                        if (file_exists($lockoutFile)) {
                            $lockoutData = json_decode(file_get_contents($lockoutFile), true);
                            if ($lockoutData && time() - $lockoutData['timestamp'] < LOGIN_LOCKOUT_TIME) {
                                $attempts = $lockoutData['attempts'] + 1;
                            }
                        }
                        
                        file_put_contents($lockoutFile, json_encode([
                            'attempts' => $attempts,
                            'timestamp' => time()
                        ]));
                        
                        $error = 'Invalid email or password.';
                        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                            $error .= ' Account temporarily locked due to multiple failed attempts.';
                        }
                    }
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                $error = 'An error occurred. Please try again.';
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
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="auth-page">
    <!-- Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </a>
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const body = document.body;
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            const themeIcon = document.getElementById('theme-icon');
            
            console.log('Toggling from', currentTheme, 'to', newTheme);
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Force immediate style update
            if (newTheme === 'dark') {
                body.style.backgroundColor = '#0f172a';
                body.style.color = '#f1f5f9';
            } else {
                body.style.backgroundColor = '#ffffff';
                body.style.color = '#111827';
            }
            
            if (themeIcon) {
                themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        // Initialize theme immediately
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const body = document.body;
            
            console.log('Initializing theme:', savedTheme);
            
            html.setAttribute('data-theme', savedTheme);
            
            if (savedTheme === 'dark') {
                body.style.backgroundColor = '#0f172a';
                body.style.color = '#f1f5f9';
            }
        })();
        
        // Initialize theme icon when DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const themeIcon = document.getElementById('theme-icon');
            
            if (themeIcon) {
                themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    </script>
    <script src="../assets/js/auth.js"></script>
</body>
</html>
