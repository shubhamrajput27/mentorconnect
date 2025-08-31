<?php
require_once 'config/config.php';

// Redirect to appropriate dashboard if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectUrl = $user['role'] === 'mentor' ? '/dashboard/mentor.php' : '/dashboard/student.php';
    header('Location: ' . BASE_URL . $redirectUrl);
    exit();
}

// SEO and Meta data
$pageTitle = APP_NAME . ' - Connect, Learn, Grow with Expert Mentors';
$pageDescription = 'Join ' . APP_NAME . ' to connect with expert mentors and accelerate your career growth. Find personalized mentorship, book sessions, and achieve your goals faster.';
$pageKeywords = 'mentorship, career coaching, professional development, skill learning, expert mentors';
$currentUrl = BASE_URL . '/index.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title><?php echo $pageTitle; ?></title>
    <meta name="title" content="<?php echo $pageTitle; ?>">
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <meta name="keywords" content="<?php echo $pageKeywords; ?>">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $currentUrl; ?>">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/images/og-image.jpg">
    <meta property="og:site_name" content="<?php echo APP_NAME; ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $currentUrl; ?>">
    <meta property="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta property="twitter:description" content="<?php echo $pageDescription; ?>">
    <meta property="twitter:image" content="<?php echo BASE_URL; ?>/assets/images/twitter-card.jpg">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>/apple-touch-icon.png">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/style.css" as="style">
    <link rel="preload" href="assets/css/landing.css" as="style">
    <link rel="preload" href="assets/js/landing.js" as="script">
    
    <!-- Critical CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Font Awesome - Load asynchronously -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></noscript>
    
    <!-- Google Fonts - Load asynchronously -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"></noscript>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo APP_NAME; ?>",
        "url": "<?php echo BASE_URL; ?>",
        "logo": "<?php echo BASE_URL; ?>/assets/images/logo.png",
        "description": "<?php echo $pageDescription; ?>",
        "sameAs": [
            "https://twitter.com/mentorconnect",
            "https://linkedin.com/company/mentorconnect",
            "https://facebook.com/mentorconnect"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "customer service",
            "url": "<?php echo BASE_URL; ?>/contact"
        }
    }
    </script>
</head>
<body>
    <!-- Landing Page -->
    <div class="landing-page">
        <!-- Theme Toggle -->
        <button class="theme-toggle" aria-label="Toggle dark mode">
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

    <!-- JavaScript - Load at bottom for performance -->
    <script src="assets/js/landing.js?v=<?php echo filemtime(__DIR__ . '/assets/js/landing.js'); ?>" defer></script>
    <script src="assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/assets/js/app.js'); ?>" defer></script>
</body>
</html>
