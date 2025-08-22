<?php
require_once '../config/config.php';
require_once '../config/database.php';

$error = '';
$success = '';

// Rate limiting for signup attempts
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!checkRateLimit($clientIP, 'signup', 10, 3600)) { // 10 signups per hour
    $error = 'Too many signup attempts. Please try again later.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['student', 'mentor']) ? $_POST['role'] : '';
    $terms = isset($_POST['terms']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['mentor', 'student'])) {
        $error = 'Please select a valid role.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    
    if (empty($error)) {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();
            
            // Check if email already exists (case-insensitive)
            $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'An account with this email already exists.';
            } else {
                // Hash password with stronger options
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]);
                
                // Generate secure verification token
                $verification_token = bin2hex(random_bytes(32));
                
                $conn->beginTransaction();
                
                // Insert user with additional security fields
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        username, email, password, first_name, last_name, role, 
                        verification_token, registration_ip, created_at, phone, location, bio
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
                ");
                
                $stmt->execute([
                    $username, 
                    strtolower($email), // Store email in lowercase
                    $hashed_password, 
                    $firstName, 
                    $lastName, 
                    $role, 
                    $verification_token,
                    $clientIP,
                    $phone,
                    $location,
                    $bio
                ]);
                
                $userId = $conn->lastInsertId();
                
                // Insert user preferences
                executeQuery(
                    "INSERT INTO user_preferences (user_id) VALUES (?)",
                    [$userId]
                );
                
                // Create profile based on user type
                if ($role === 'mentor') {
                    $stmt = $conn->prepare("
                        INSERT INTO mentor_profiles (user_id, created_at) 
                        VALUES (?, NOW())
                    ");
                    $stmt->execute([$userId]);
                }
                
                // Set default user preferences
                $defaultPrefs = [
                    'theme' => 'light',
                    'language' => 'en',
                    'timezone' => 'UTC',
                    'email_frequency' => 'daily'
                ];
                
                foreach ($defaultPrefs as $key => $value) {
                    $stmt = $conn->prepare("
                        INSERT INTO user_preferences (user_id, preference_key, preference_value, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$userId, $key, json_encode($value)]);
                }
                
                $conn->commit();
                
                // Log activity
                logActivity($userId, 'user_registered', 'User account created', [
                    'user_type' => $role,
                    'ip_address' => $clientIP
                ]);
                
                // Send welcome notification
                createNotification(
                    $userId,
                    'system',
                    'Welcome to ' . APP_NAME . '!',
                    'Your account has been created successfully. Complete your profile to get started.',
                    '/profile/edit.php'
                );
                
                $success = 'Account created successfully! You can now sign in.';
            }
        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollback();
            }
            
            error_log("Registration error: " . $e->getMessage() . " IP: " . $clientIP);
            $error = 'Registration failed. Please try again.';
        }
    }
}

// Generate CSRF token for form

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo APP_NAME; ?></title>
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
        <div class="auth-card signup-card">
            <div class="auth-header">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </a>
                <h2>Create Account</h2>
                <p>Join our mentoring community</p>
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
                    <a href="login.php" class="btn btn-sm btn-primary" style="margin-top: 10px;">Sign In Now</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="signupForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="first_name" name="first_name" required 
                                   value="<?php echo htmlspecialchars($firstName ?? ''); ?>"
                                   placeholder="First name">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="last_name" name="last_name" required 
                                   value="<?php echo htmlspecialchars($lastName ?? ''); ?>"
                                   placeholder="Last name">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <div class="input-group">
                        <i class="fas fa-at"></i>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($username ?? ''); ?>"
                               placeholder="Choose a username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-group">
                        <span class="country-code">+91</span>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                               placeholder="Enter 10-digit mobile number"
                               pattern="[0-9]{10}"
                               maxlength="10"
                               title="Please enter a valid 10-digit mobile number">
                    </div>
                    <small class="form-help">Enter your 10-digit mobile number without +91</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Create password" oninput="checkPasswordStrength()">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm password" oninput="checkPasswordMatch()">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <div class="form-group">
                    <label for="role">I am a *</label>
                    <div class="role-selection">
                        <label class="role-option">
                            <input type="radio" name="role" value="student" <?php echo ($role ?? '') === 'student' ? 'checked' : ''; ?>>
                            <div class="role-card">
                                <i class="fas fa-user-graduate"></i>
                                <h4>Student</h4>
                                <p>Looking for mentorship</p>
                            </div>
                        </label>
                        
                        <label class="role-option">
                            <input type="radio" name="role" value="mentor" <?php echo ($role ?? '') === 'mentor' ? 'checked' : ''; ?>>
                            <div class="role-card">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <h4>Mentor</h4>
                                <p>Ready to guide others</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="3" 
                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (!password) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let score = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 8) score++;
            else feedback.push('At least 8 characters');
            
            // Uppercase check
            if (/[A-Z]/.test(password)) score++;
            else feedback.push('One uppercase letter');
            
            // Lowercase check
            if (/[a-z]/.test(password)) score++;
            else feedback.push('One lowercase letter');
            
            // Number check
            if (/\d/.test(password)) score++;
            else feedback.push('One number');
            
            // Special character check
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
            else feedback.push('One special character');
            
            let strength = '';
            let className = '';
            
            if (score < 2) {
                strength = 'Weak';
                className = 'strength-weak';
            } else if (score < 4) {
                strength = 'Medium';
                className = 'strength-medium';
            } else {
                strength = 'Strong';
                className = 'strength-strong';
            }
            
            strengthDiv.innerHTML = `
                <div class="strength-bar ${className}">
                    <div class="strength-fill" style="width: ${(score/5)*100}%"></div>
                </div>
                <div class="strength-text">${strength}</div>
                ${feedback.length > 0 ? '<div class="strength-feedback">Need: ' + feedback.join(', ') + '</div>' : ''}
            `;
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (!confirmPassword) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<div class="match-success"><i class="fas fa-check"></i> Passwords match</div>';
            } else {
                matchDiv.innerHTML = '<div class="match-error"><i class="fas fa-times"></i> Passwords do not match</div>';
            }
        }
        
        // Password toggle function
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Theme toggle function
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
