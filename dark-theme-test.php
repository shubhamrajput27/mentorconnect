<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark Theme Test - MentorConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--card-color);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }
        
        .theme-controls {
            background: var(--surface-color);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin: 1rem 0;
            border-left: 4px solid var(--primary-color);
            text-align: center;
        }
        
        .btn {
            background: var(--primary-color);
            color: var(--text-inverse);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            cursor: pointer;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .test-section {
            margin: 2rem 0;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
        }
        
        .color-sample {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
        
        .primary-text { color: var(--text-primary); }
        .secondary-text { color: var(--text-secondary); }
        .muted-text { color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🌙 Dark Theme Text Readability Test</h1>
        <p>This page tests text readability in both light and dark themes.</p>
        
        <div class="theme-controls">
            <h3>Theme Controls</h3>
            <button class="btn" onclick="setLightTheme()">Light Theme</button>
            <button class="btn" onclick="setDarkTheme()">Dark Theme</button>
            <button class="btn" onclick="toggleTheme()">Toggle Theme</button>
        </div>
        
        <div class="test-section">
            <h3>Text Color Tests</h3>
            <div class="color-sample primary-text">Primary Text (--text-primary)</div>
            <div class="color-sample secondary-text">Secondary Text (--text-secondary)</div>
            <div class="color-sample muted-text">Muted Text (--text-muted)</div>
        </div>
        
        <div class="test-section">
            <h3>Background Tests</h3>
            <p><strong>Card Background:</strong> Should be readable on var(--card-color)</p>
            <p><strong>Surface Background:</strong> Should be readable on var(--surface-color)</p>
            <p><strong>Body Background:</strong> Should be readable on gradient background</p>
        </div>
    </div>

    <!-- Test Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>MentorConnect</h3>
                    </div>
                    <p>Connecting learners with expert mentors to accelerate career growth and skill development.</p>
                </div>
                <div class="footer-section">
                    <h4>Platform</h4>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="auth/signup.php">Sign Up</a></li>
                        <li><a href="auth/login.php">Sign In</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 MentorConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <script>
        function setLightTheme() {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            document.getElementById('theme-icon').className = 'fas fa-moon';
            updateStatus();
        }
        
        function setDarkTheme() {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
            updateStatus();
        }
        
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            if (newTheme === 'dark') {
                setDarkTheme();
            } else {
                setLightTheme();
            }
        }
        
        function updateStatus() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            console.log('Current theme:', currentTheme);
        }
        
        // Initialize theme
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.getElementById('theme-icon').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            updateStatus();
        }
        
        // Theme toggle functionality
        document.querySelector('.theme-toggle').addEventListener('click', toggleTheme);
        
        // Initialize on load
        initTheme();
        
        // Test readability by cycling themes every 3 seconds (for demo)
        let autoToggle = false;
        window.startAutoToggle = function() {
            autoToggle = true;
            const interval = setInterval(() => {
                if (!autoToggle) {
                    clearInterval(interval);
                    return;
                }
                toggleTheme();
            }, 3000);
        };
        
        window.stopAutoToggle = function() {
            autoToggle = false;
        };
        
        // Global functions for console testing
        window.setLightTheme = setLightTheme;
        window.setDarkTheme = setDarkTheme;
        window.toggleTheme = toggleTheme;
    </script>
</body>
</html>
