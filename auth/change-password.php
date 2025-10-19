<?php
require_once '../config/optimized-config.php';

$error = '';
$success = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } else {
        try {
            // Verify current password
            $user = fetchOne(
                "SELECT password_hash FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                executeQuery(
                    "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                    [$hashedPassword, $_SESSION['user_id']]
                );
                
                // Log activity
                logActivity($_SESSION['user_id'], 'password_change', 'Password changed successfully');
                
                $success = 'Password changed successfully!';
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $error = 'An error occurred. Please try again.';
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
    <title>Change Password - MentorConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/password-strength.css" rel="stylesheet">
    <style>
        /* CSS Variables for Theme Support */
        :root {
            --primary-color: #f97316;
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
        }

        [data-theme="dark"] {
            --primary-color: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary-color: #06b6d4;
            --background-gradient: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
            --text-inverse: #111827;
            --card-bg: rgba(30, 41, 59, 0.95);
            --input-bg: #374151;
            --input-border: #4b5563;
            --input-focus: #8b5cf6;
            --border-color: #4b5563;
            --shadow-light: rgba(0, 0, 0, 0.3);
            --shadow-medium: rgba(0, 0, 0, 0.4);
        }

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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .change-password-container {
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px var(--shadow-light), 0 10px 10px -5px var(--shadow-medium);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

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
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }

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
            padding: 0.875rem 1rem 0.875rem 3rem;
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

        [data-theme="dark"] .input-wrapper input:focus {
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.2);
        }

        .input-wrapper input::placeholder {
            color: var(--text-muted);
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
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
        }

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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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

        .btn-secondary {
            background: var(--input-bg);
            color: var(--text-primary);
            border: 2px solid var(--input-border);
        }

        .btn-secondary:hover {
            background: var(--input-border);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .password-input-container.has-strength-indicator {
            position: relative;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .change-password-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }

        /* Theme Toggle Button */
        

        .theme-toggle:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 20px 25px -5px var(--shadow-light), 0 10px 10px -5px var(--shadow-medium);
            background: var(--primary-color);
        }

        .theme-toggle:hover i {
            color: var(--text-inverse);
            transform: rotate(360deg);
        }

        .theme-toggle i {
            font-size: 1.3rem;
            color: var(--text-primary);
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="form-header">
            <h1><i class="fas fa-key"></i> Change Password</h1>
            <p>Update your account password for better security</p>
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
        
        <form method="POST" id="changePasswordForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="input-wrapper">
                    <input type="password" id="current_password" name="current_password" required 
                           placeholder="Enter your current password">
                    <i class="fas fa-lock"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group password-input-container has-strength-indicator">
                <label for="new_password">New Password</label>
                <div class="input-wrapper">
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="Create a strong new password" class="form-input">
                    <i class="fas fa-key"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="password-strength-container"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your new password" class="form-input">
                    <i class="fas fa-lock"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="password-match-indicator" class="password-match-feedback" style="display: none;">
                    <div class="match-status">
                        <i class="fas fa-check-circle" style="color: #16a34a; display: none;"></i>
                        <i class="fas fa-times-circle" style="color: #ef4444; display: none;"></i>
                        <span class="match-text"></span>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" style="flex: 2;">
                    <i class="fas fa-save"></i> Update Password
                </button>
                <a href="../dashboard/" 
                   class="btn btn-secondary" style="flex: 1;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
    
    <!-- Theme Toggle Button -->
    
    
    <!-- Password Strength JavaScript -->
    <script src="../assets/js/password-strength.js"></script>
    
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
        
        // Initialize Password Strength Validator
        let passwordValidator;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize password strength validator
            passwordValidator = new PasswordStrengthValidator({
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            });
            
            // Create strength indicator
            const strengthContainer = document.getElementById('password-strength-container');
            if (strengthContainer) {
                passwordValidator.createStrengthIndicator(strengthContainer, 'new_password');
            }
            
            // Setup password confirmation matching
            setupPasswordMatching();
        });
        
        // Password matching functionality
        function setupPasswordMatching() {
            const passwordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const matchIndicator = document.getElementById('password-match-indicator');
            const matchText = matchIndicator.querySelector('.match-text');
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length === 0) {
                    matchIndicator.style.display = 'none';
                    return;
                }
                
                matchIndicator.style.display = 'block';
                
                if (password === confirmPassword) {
                    matchIndicator.querySelector('.fa-check-circle').style.display = 'inline';
                    matchIndicator.querySelector('.fa-times-circle').style.display = 'none';
                    matchText.textContent = 'Passwords match';
                    matchText.style.color = '#16a34a';
                } else {
                    matchIndicator.querySelector('.fa-check-circle').style.display = 'none';
                    matchIndicator.querySelector('.fa-times-circle').style.display = 'inline';
                    matchText.textContent = 'Passwords do not match';
                    matchText.style.color = '#ef4444';
                }
            }
            
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            passwordInput.addEventListener('input', checkPasswordMatch);
        }
        
        // Enhanced form validation
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check password strength
            if (passwordValidator) {
                const strengthAnalysis = passwordValidator.calculateStrength(newPassword);
                if (!strengthAnalysis.isAcceptable) {
                    e.preventDefault();
                    alert('Please create a stronger password. Check the requirements below the password field.');
                    return;
                }
            }
            
            // Check password match
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return;
            }
            
            // Basic length check as fallback
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('New password must be at least 8 characters long!');
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
                const savedTheme = localStorage.getItem('theme') || 'light';
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
                , 150);
                }
            }
            
            bindEvents() {
                
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


