<?php
require_once 'config/config.php';

// Redirect to appropriate dashboard if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectUrl = $user['role'] === 'mentor' ? '/dashboard/mentor.php' : '/dashboard/student.php';
    header('Location: ' . BASE_URL . $redirectUrl);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Connect, Learn, Grow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Landing Page -->
    <div class="landing-page">
        <!-- Theme Toggle -->
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
            <i class="fas fa-moon" id="theme-icon"></i>
        </button>
        
        <!-- Navigation -->
        <nav class="landing-nav">
            <div class="nav-container">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </a>
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="auth/login.php" class="btn btn-outline">Sign In</a>
                    <a href="auth/signup.php" class="btn btn-primary">Get Started</a>
                </div>
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-container">
                <div class="hero-content">
                    <h1>Connect with Expert Mentors</h1>
                    <p>Accelerate your career growth with personalized mentorship from industry professionals. Learn new skills, get guidance, and achieve your goals faster.</p>
                    <div class="hero-actions">
                        <a href="auth/signup.php?role=student" class="btn btn-primary btn-lg">Find a Mentor</a>
                        <a href="auth/signup.php?role=mentor" class="btn btn-outline btn-lg">Become a Mentor</a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat">
                            <h3>500+</h3>
                            <p>Expert Mentors</p>
                        </div>
                        <div class="stat">
                            <h3>10k+</h3>
                            <p>Successful Sessions</p>
                        </div>
                        <div class="stat">
                            <h3>50+</h3>
                            <p>Skills & Topics</p>
                        </div>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-graphic">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <div class="section-header">
                    <h2>Everything You Need to Succeed</h2>
                    <p>Comprehensive mentoring platform with all the tools for effective learning and teaching.</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Smart Matching</h3>
                        <p>Find the perfect mentor based on your goals, skills, and preferences with our intelligent matching system.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3>Video Sessions</h3>
                        <p>Conduct face-to-face mentoring sessions with integrated video calling and screen sharing capabilities.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Easy Scheduling</h3>
                        <p>Book sessions at your convenience with our flexible scheduling system and automated reminders.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Real-time Messaging</h3>
                        <p>Stay connected with your mentors and students through our built-in messaging system.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Progress Tracking</h3>
                        <p>Monitor your learning journey with detailed analytics and progress reports.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure Platform</h3>
                        <p>Your data is protected with enterprise-grade security and privacy measures.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="how-it-works">
            <div class="container">
                <div class="section-header">
                    <h2>How It Works</h2>
                    <p>Get started in just a few simple steps.</p>
                </div>
                <div class="steps-grid">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Create Your Profile</h3>
                        <p>Sign up and tell us about your goals, skills, and what you're looking to learn or teach.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Find Your Match</h3>
                        <p>Browse mentors or let our smart matching system recommend the perfect fit for your needs.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Book a Session</h3>
                        <p>Schedule your first mentoring session at a time that works for both of you.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h3>Start Learning</h3>
                        <p>Connect with your mentor and begin your personalized learning journey.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="container">
                <div class="cta-content">
                    <h2>Ready to Start Your Journey?</h2>
                    <p>Join thousands of learners and mentors who are already growing together.</p>
                    <div class="cta-actions">
                        <a href="auth/signup.php" class="btn btn-primary btn-lg">Get Started Today</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <div class="logo">
                            <i class="fas fa-graduation-cap"></i>
                            <h3><?php echo APP_NAME; ?></h3>
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
                    <p>&copy; 2025 <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <style>
    .landing-page {
        font-family: 'Inter', sans-serif;
    }

    .landing-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--border-color);
        z-index: 1000;
        padding: 1rem 0;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .nav-links a {
        text-decoration: none;
        color: var(--text-secondary);
        font-weight: 500;
        transition: color 0.3s;
    }

    .nav-links a:hover {
        color: var(--primary-color);
    }

    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .hero {
        padding: 8rem 0 4rem;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
    }

    .hero-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }

    .hero-content h1 {
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
    }

    .hero-content p {
        font-size: 1.25rem;
        color: var(--text-secondary);
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .hero-actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 3rem;
    }

    .hero-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }

    .stat {
        text-align: center;
    }

    .stat h3 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .stat p {
        color: var(--text-secondary);
        margin: 0;
    }

    .hero-graphic {
        width: 300px;
        height: 300px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
    }

    .hero-graphic i {
        font-size: 8rem;
        color: white;
    }

    .features {
        padding: 6rem 0;
        background: var(--background-color);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    .section-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .section-header h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .section-header p {
        font-size: 1.125rem;
        color: var(--text-secondary);
        max-width: 600px;
        margin: 0 auto;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .feature-card {
        background: var(--card-color);
        padding: 2rem;
        border-radius: 1rem;
        text-align: center;
        box-shadow: var(--shadow-sm);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .feature-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }

    .feature-icon i {
        font-size: 2rem;
        color: white;
    }

    .feature-card h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .how-it-works {
        padding: 6rem 0;
        background: var(--surface-color);
    }

    .steps-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
    }

    .step {
        text-align: center;
        padding: 2rem 1rem;
    }

    .step-number {
        width: 60px;
        height: 60px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 auto 1.5rem;
    }

    .step h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .cta {
        padding: 6rem 0;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        text-align: center;
    }

    .cta h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .cta p {
        font-size: 1.125rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }

    .footer {
        background: var(--text-primary);
        color: white;
        padding: 3rem 0 1rem;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .footer-section h3,
    .footer-section h4 {
        margin-bottom: 1rem;
    }

    .footer-section ul {
        list-style: none;
    }

    .footer-section ul li {
        margin-bottom: 0.5rem;
    }

    .footer-section ul li a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-section ul li a:hover {
        color: white;
    }

    .social-links {
        display: flex;
        gap: 1rem;
    }

    .social-links a {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: background 0.3s;
    }

    .social-links a:hover {
        background: var(--primary-color);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
    }

    @media (max-width: 768px) {
        .nav-links {
            display: none;
        }
        
        .mobile-menu-toggle {
            display: block;
        }
        
        .hero-container {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .hero-content h1 {
            font-size: 2.5rem;
        }
        
        .hero-actions {
            flex-direction: column;
            align-items: center;
        }
        
        .hero-stats {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Mobile menu toggle
    document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
        const navLinks = document.querySelector('.nav-links');
        navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
    });

    // Theme toggle functionality
    function toggleTheme() {
        const html = document.documentElement;
        const body = document.body;
        const currentTheme = html.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        const themeIcon = document.getElementById('theme-icon');
        
        console.log('Toggling from', currentTheme, 'to', newTheme);
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Force immediate style update for all sections
        const landingPage = document.querySelector('.landing-page');
        const heroSection = document.querySelector('.hero-section');
        const navContainer = document.querySelector('.nav-container');
        const allSections = document.querySelectorAll('.features-section, .how-it-works, .cta-section');
        
        if (newTheme === 'dark') {
            body.style.backgroundColor = '#0f172a';
            body.style.color = '#f1f5f9';
            if (landingPage) landingPage.style.backgroundColor = '#0f172a';
            if (heroSection) heroSection.style.background = 'linear-gradient(135deg, #1e293b 0%, #334155 100%)';
            if (navContainer) navContainer.style.backgroundColor = '#1e293b';
            allSections.forEach(section => {
                section.style.backgroundColor = '#0f172a';
                section.style.color = '#f1f5f9';
            });
        } else {
            body.style.backgroundColor = '#ffffff';
            body.style.color = '#111827';
            if (landingPage) landingPage.style.backgroundColor = '';
            if (heroSection) heroSection.style.background = '';
            if (navContainer) navContainer.style.backgroundColor = '';
            allSections.forEach(section => {
                section.style.backgroundColor = '';
                section.style.color = '';
            });
        }
        
        if (themeIcon) {
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    // Initialize theme immediately
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const html = document.documentElement;
        
        console.log('Initializing theme:', savedTheme);
        html.setAttribute('data-theme', savedTheme);
        
        // Apply theme on page load
        if (savedTheme === 'dark') {
            document.addEventListener('DOMContentLoaded', function() {
                const body = document.body;
                const landingPage = document.querySelector('.landing-page');
                const heroSection = document.querySelector('.hero-section');
                const navContainer = document.querySelector('.nav-container');
                const allSections = document.querySelectorAll('.features-section, .how-it-works, .cta-section');
                
                body.style.backgroundColor = '#0f172a';
                body.style.color = '#f1f5f9';
                if (landingPage) landingPage.style.backgroundColor = '#0f172a';
                if (heroSection) heroSection.style.background = 'linear-gradient(135deg, #1e293b 0%, #334155 100%)';
                if (navContainer) navContainer.style.backgroundColor = '#1e293b';
                allSections.forEach(section => {
                    section.style.backgroundColor = '#0f172a';
                    section.style.color = '#f1f5f9';
                });
            });
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
    <script src="assets/js/app.js"></script>
</body>
</html>
