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
                        "SELECT id, username, email, password_hash, role, status FROM users WHERE email = ?",
                        [$email]
                    );
                    
                    if ($user && password_verify($password, $user['password_hash'])) {
                        if ($user['status'] !== 'active') {
                            $error = 'Your account is not active. Please contact support.';
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
    <title>Sign In to MentorConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables for Theme Support */
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --background-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --text-inverse: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.95);
            --input-bg: #ffffff;
            --input-border: #e5e7eb;
            --input-focus: #667eea;
            --border-color: #e5e7eb;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --logo-accent: #fbbf24;
        }

        /* Force visible colors for debugging */
        body {
            background: white !important;
            color: black !important;
        }
        
        .login-container {
            background: white !important;
        }
        
        .form-panel {
            background: white !important;
            color: black !important;
        }

        [data-theme="dark"] {
            --primary-color: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary-color: #06b6d4;
            --background-gradient: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
            --text-inverse: #f9fafb;
            --card-bg: rgba(30, 41, 59, 0.95);
            --input-bg: #374151;
            --input-border: #4b5563;
            --input-focus: #8b5cf6;
            --border-color: #4b5563;
            --shadow-light: rgba(0, 0, 0, 0.3);
            --shadow-medium: rgba(0, 0, 0, 0.4);
            --logo-accent: #fbbf24;
        }

        /* Modern Login Page Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--background-gradient);
            min-height: 100vh;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }

        .login-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Brand Panel */
        .brand-panel {
            flex: 1;
            background: var(--background-gradient);
            color: var(--text-inverse);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 1;
            max-width: 500px;
            margin: 0 auto;
        }

        .logo-section {
            margin-bottom: 2rem;
            margin-top: -1rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            width: fit-content;
        }
        
        .logo:hover {
            transform: translateY(-2px);
            filter: drop-shadow(0 4px 8px rgba(251, 191, 36, 0.3));
        }
        
        .logo:active {
            transform: translateY(0);
        }

        .logo i {
            font-size: 2rem;
            margin-right: 0.75rem;
            color: var(--logo-accent);
        }

        .logo-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, var(--logo-accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        [data-theme="dark"] .logo-section h1 {
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--logo-accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.8;
        }

        .features-list {
            margin-bottom: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .feature-item i {
            font-size: 1.25rem;
            margin-right: 1rem;
            margin-top: 0.25rem;
            color: var(--logo-accent);
            min-width: 24px;
        }

        .feature-item h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .feature-item p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--logo-accent);
            display: block;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
            margin-top: 0.25rem;
            color: inherit;
        }

        /* Form Panel */
        .form-panel {
            flex: 1;
            background: var(--card-bg);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .form-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--error-color);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success-color);
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.875rem 2.5rem 0.875rem 3rem;
            border: 2px solid var(--input-border);
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--input-bg);
            color: var(--text-primary);
            font-family: inherit;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--input-focus);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.375rem;
            z-index: 10;
            transition: all 0.2s ease;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .checkbox-label input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            background: var(--input-bg);
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            font-size: 0.75rem;
            font-weight: bold;
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Buttons */
        .btn {
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-full {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
        }

        /* Form Footer */
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .form-footer p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .login-container {
                flex-direction: column;
            }
            
            .brand-panel {
                padding: 2rem;
                min-height: 40vh;
            }
            
            .brand-content {
                text-align: center;
            }
            
            .logo-section {
                margin-top: 0;
                margin-bottom: 1.5rem;
            }
            
            .logo-section h1 {
                font-size: 2rem;
            }
            
            .features-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .stats-section {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .form-panel {
                padding: 1rem;
            }
            
            .features-list {
                grid-template-columns: 1fr;
            }
            
            .form-options {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .brand-panel {
                padding: 1.5rem;
            }
            
            .logo-section h1 {
                font-size: 1.75rem;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        /* Theme Toggle Button */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            box-shadow: 0 10px 15px -3px var(--shadow-light), 0 4px 6px -2px var(--shadow-medium);
        }

        .theme-toggle:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 20px 25px -5px var(--shadow-light), 0 10px 10px -5px var(--shadow-medium);
            background: var(--primary-color);
        }

        .theme-toggle:hover i {
            color: var(--text-inverse);
            transform: rotate(360deg);
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }

        .theme-toggle i {
            font-size: 1.3rem;
            color: var(--text-primary);
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        }

        /* Enhanced Input Styles for Dark Theme */
        .form-input {
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--input-focus);
            background: var(--input-bg);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        [data-theme="dark"] .form-input:focus {
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        /* Dark theme adjustments for form footer */
        .form-footer p {
            color: var(--text-secondary);
        }

        .form-footer a {
            color: var(--primary-color);
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--primary-dark);
        }

        .forgot-link {
            color: var(--primary-color) !important;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-dark) !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="brand-content">
                <div class="logo-section">
                    <a href="../index.php" class="logo" title="Go to Home">
                        <i class="fas fa-graduation-cap"></i>
                        <span>MentorConnect</span>
                    </a>
                    <h1>Welcome Back!</h1>
                    <p>Sign in to continue your mentoring journey and connect with amazing people.</p>
                </div>
                
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <h3>Quick Access</h3>
                            <p>Jump right back into your conversations</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h3>Secure Login</h3>
                            <p>Your account is protected with advanced security</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h3>Stay Connected</h3>
                            <p>Never miss important updates and messages</p>
                        </div>
                    </div>
                </div>
                
                <div class="stats-section">
                    <div class="stat-item">
                        <span class="stat-number">1K+</span>
                        <span class="stat-label">Active Users</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Mentors</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">Success Rate</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Login Form -->
        <div class="form-panel">
            <div class="form-container">
                <div class="form-header">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to access your account</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="login-form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="Enter your email address">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter your password">
                            <i class="fas fa-lock"></i>
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
                
                <div class="form-footer">
                    <p>Don't have an account? <a href="signup.php">Create one here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-sun" id="theme-icon"></i>
    </button>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.parentElement.querySelector('.password-toggle i');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                toggle.className = 'fas fa-eye';
            }
        }
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all required fields!');
                return;
            }
        });
        
        // Theme Management
        class ThemeManager {
            constructor() {
                this.init();
            }
            
            init() {
                this.initializeTheme();
                this.bindEvents();
            }
            
            initializeTheme() {
                const savedTheme = localStorage.getItem('theme') || 'dark';
                document.documentElement.setAttribute('data-theme', savedTheme);
                this.updateThemeIcon(savedTheme);
            }
            
            updateThemeIcon(theme) {
                const themeIcon = document.getElementById('theme-icon');
                if (themeIcon) {
                    themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
                }
            }
            
            toggleTheme() {
                const html = document.documentElement;
                const currentTheme = html.getAttribute('data-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                this.updateThemeIcon(newTheme);
                
                // Add animation effect
                const themeToggle = document.querySelector('.theme-toggle');
                if (themeToggle) {
                    themeToggle.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        themeToggle.style.transform = '';
                    }, 150);
                }
            }
            
            bindEvents() {
                const themeToggle = document.querySelector('.theme-toggle');
                if (themeToggle) {
                    themeToggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.toggleTheme();
                    });
                }
            }
        }
        
        // Initialize theme manager
        document.addEventListener('DOMContentLoaded', () => {
            new ThemeManager();
        });
    </script>
</body>
</html>
