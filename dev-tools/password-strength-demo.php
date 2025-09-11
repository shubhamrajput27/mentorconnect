<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Strength Demo - MentorConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/password-strength.css" rel="stylesheet">
    <style>
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
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .demo-header {
            text-align: center;
            margin-bottom: 3rem;
            color: var(--text-inverse);
        }

        .demo-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .demo-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .demo-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px var(--shadow-light), 0 10px 10px -5px var(--shadow-medium);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .demo-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .test-passwords {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px var(--shadow-light), 0 10px 10px -5px var(--shadow-medium);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .test-passwords h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .password-examples {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .password-example {
            padding: 1rem;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .password-example:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .password-example .label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .password-example .password {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: var(--text-primary);
            word-break: break-all;
        }

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

        .theme-toggle i {
            font-size: 1.3rem;
            color: var(--text-primary);
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        }

        @media (max-width: 768px) {
            .demo-grid {
                grid-template-columns: 1fr;
            }
            
            .password-examples {
                grid-template-columns: 1fr;
            }
            
            .demo-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1><i class="fas fa-shield-alt"></i> Password Strength Demo</h1>
            <p>Test the password strength validator with different password examples</p>
        </div>
        
        <div class="demo-grid">
            <div class="demo-card">
                <h3><i class="fas fa-key"></i> Interactive Password Test</h3>
                <div class="form-group password-input-container has-strength-indicator">
                    <label for="test-password">Enter a password to test:</label>
                    <div class="input-wrapper">
                        <input type="password" id="test-password" placeholder="Type your password here..." class="form-input">
                        <i class="fas fa-lock"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('test-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="test-strength-container"></div>
                </div>
            </div>
            
            <div class="demo-card">
                <h3><i class="fas fa-clipboard-check"></i> Password Confirmation Test</h3>
                <div class="form-group">
                    <label for="confirm-test">Confirm password:</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm-test" placeholder="Retype the password..." class="form-input">
                        <i class="fas fa-lock"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm-test')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="confirm-match-indicator" class="password-match-feedback" style="display: none;">
                        <div class="match-status">
                            <i class="fas fa-check-circle" style="color: #16a34a; display: none;"></i>
                            <i class="fas fa-times-circle" style="color: #ef4444; display: none;"></i>
                            <span class="match-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="test-passwords">
            <h3><i class="fas fa-list"></i> Click to Test Different Password Examples</h3>
            <div class="password-examples">
                <div class="password-example" onclick="testPassword('123456')">
                    <div class="label">Very Weak</div>
                    <div class="password">123456</div>
                </div>
                <div class="password-example" onclick="testPassword('password')">
                    <div class="label">Very Weak</div>
                    <div class="password">password</div>
                </div>
                <div class="password-example" onclick="testPassword('Password1')">
                    <div class="label">Weak</div>
                    <div class="password">Password1</div>
                </div>
                <div class="password-example" onclick="testPassword('MyPassword123')">
                    <div class="label">Fair</div>
                    <div class="password">MyPassword123</div>
                </div>
                <div class="password-example" onclick="testPassword('MyP@ssw0rd123')">
                    <div class="label">Good</div>
                    <div class="password">MyP@ssw0rd123</div>
                </div>
                <div class="password-example" onclick="testPassword('Tr0ub4dor&3')">
                    <div class="label">Strong</div>
                    <div class="password">Tr0ub4dor&3</div>
                </div>
                <div class="password-example" onclick="testPassword('correct-horse-battery-staple-2024!')">
                    <div class="label">Very Strong</div>
                    <div class="password">correct-horse-battery-staple-2024!</div>
                </div>
                <div class="password-example" onclick="testPassword('Xy9#mK$pL@2vN&qR8!')">
                    <div class="label">Very Strong</div>
                    <div class="password">Xy9#mK$pL@2vN&qR8!</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>
    
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
        
        function testPassword(password) {
            const testInput = document.getElementById('test-password');
            const confirmInput = document.getElementById('confirm-test');
            
            testInput.value = password;
            confirmInput.value = '';
            
            // Trigger input events to update strength indicator
            testInput.dispatchEvent(new Event('input'));
            confirmInput.dispatchEvent(new Event('input'));
            
            // Show the password temporarily
            testInput.type = 'text';
            setTimeout(() => {
                testInput.type = 'password';
                testInput.parentElement.querySelector('.password-toggle i').className = 'fas fa-eye';
            }, 2000);
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
            const strengthContainer = document.getElementById('test-strength-container');
            if (strengthContainer) {
                passwordValidator.createStrengthIndicator(strengthContainer, 'test-password');
            }
            
            // Setup password confirmation matching
            setupPasswordMatching();
        });
        
        // Password matching functionality
        function setupPasswordMatching() {
            const passwordInput = document.getElementById('test-password');
            const confirmPasswordInput = document.getElementById('confirm-test');
            const matchIndicator = document.getElementById('confirm-match-indicator');
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
