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
    
    <!-- Performance Hints -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#6366f1">
    <meta name="msapplication-TileColor" content="#6366f1">
    
    <!-- Primary Meta Tags -->
    <title><?php echo $pageTitle; ?></title>
    <meta name="title" content="<?php echo $pageTitle; ?>">
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <meta name="keywords" content="<?php echo $pageKeywords; ?>">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="<?php echo $currentUrl; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $currentUrl; ?>">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="<?php echo APP_NAME; ?>">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $currentUrl; ?>">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDescription; ?>">
    <meta name="twitter:image" content="<?php echo BASE_URL; ?>/assets/images/twitter-card.jpg">
    <meta name="twitter:creator" content="@mentorconnect">
    <meta name="twitter:site" content="@mentorconnect">
    
    <!-- Favicons and App Icons -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/favicon-16x16.png">
    <link rel="mask-icon" href="<?php echo BASE_URL; ?>/safari-pinned-tab.svg" color="#6366f1">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    
    <!-- DNS Prefetch for External Resources -->
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    
    <!-- Preconnect for Critical Third-Party Resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="assets/css/critical.css?v=<?php echo filemtime(__DIR__ . '/assets/css/critical.css'); ?>" as="style">
    <link rel="preload" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>" as="style">
    <link rel="preload" href="assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>" as="style">
    <link rel="preload" href="assets/js/landing.js?v=<?php echo filemtime(__DIR__ . '/assets/js/landing.js'); ?>" as="script">
    <link rel="preload" href="assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/assets/js/app.js'); ?>" as="script">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style" crossorigin>
    
    <!-- CSS Loading Strategy - Critical CSS Inline -->
    <style><?php include __DIR__ . '/assets/css/critical.css'; ?></style>
    
    <!-- Performance & Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="camera=(), microphone=(), geolocation=()">
    
    <!-- Performance Monitoring Script -->
    <script>
        // Mark start of critical resource loading
        performance.mark('critical-css-start');
        
        // Performance budget monitoring
        const PERFORMANCE_BUDGET = {
            LCP: 2500, // 2.5s
            FID: 100,  // 100ms
            CLS: 0.1   // 0.1
        };
        
        // Early error tracking
        window.addEventListener('error', (e) => {
            console.error('JS Error:', e.error);
        });
        
        window.addEventListener('unhandledrejection', (e) => {
            console.error('Promise Rejection:', e.reason);
        });
        
        // Theme persistence and FOUC prevention
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <!-- Non-Critical CSS with Media Query -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>" media="print" onload="this.media='all'">
    
    <!-- External CSS with Async Loading -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" media="print" onload="this.media='all'" crossorigin>
    
    <!-- Fallback for disabled JavaScript -->
    <noscript>
        <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
        <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" crossorigin>
    </noscript>
    
    <!-- Enhanced Button Styles for Better Visibility and Accessibility -->
    <style>
    /* Enhanced Button Styles for Better Visibility and Accessibility */
    .nav-links .btn {
        font-size: 0.9rem !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 120px !important;
        position: relative !important;
        overflow: hidden !important;
    }

    /* Primary button - Get Started */
    .nav-links .btn-primary {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
        color: #ffffff !important;
        border: 2px solid transparent !important;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3), 
                    0 2px 4px rgba(0, 0, 0, 0.1) !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
    }

    .nav-links .btn-primary:hover {
        background: linear-gradient(135deg, #4338ca 0%, #7c3aed 100%) !important;
        color: #ffffff !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4), 
                    0 4px 8px rgba(0, 0, 0, 0.15) !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }

    /* Outline button - Sign In */
    .nav-links .btn-outline {
        background: rgba(255, 255, 255, 0.95) !important;
        color: #6366f1 !important;
        border: 2px solid #6366f1 !important;
        -webkit-backdrop-filter: blur(10px) !important;
        backdrop-filter: blur(10px) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        font-weight: 600 !important;
    }

    .nav-links .btn-outline:hover {
        background: #6366f1 !important;
        color: #ffffff !important;
        border-color: #6366f1 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3), 
                    0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .nav-links .btn-primary {
        background: linear-gradient(135deg, #818cf8 0%, #a5b4fc 100%) !important;
        color: #0f172a !important;
        text-shadow: none !important;
        box-shadow: 0 4px 12px rgba(129, 140, 248, 0.3), 
                    0 2px 4px rgba(0, 0, 0, 0.2) !important;
    }

    [data-theme="dark"] .nav-links .btn-primary:hover {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
        color: #ffffff !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
    }

    [data-theme="dark"] .nav-links .btn-outline {
        background: rgba(30, 41, 59, 0.95) !important;
        color: #818cf8 !important;
        border-color: #818cf8 !important;
    }

    [data-theme="dark"] .nav-links .btn-outline:hover {
        background: #818cf8 !important;
        color: #0f172a !important;
    }

    /* Focus states for accessibility */
    .nav-links .btn:focus {
        outline: 2px solid #06b6d4 !important;
        outline-offset: 2px !important;
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .nav-links .btn {
            min-width: 100px !important;
            padding: 0.625rem 1.25rem !important;
            font-size: 0.875rem !important;
        }
    }
    </style>
    
    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo APP_NAME; ?>",
        "url": "<?php echo BASE_URL; ?>",
        "logo": "<?php echo BASE_URL; ?>/assets/images/logo.png",
        "description": "<?php echo $pageDescription; ?>",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+1-555-MENTOR",
            "contactType": "customer service",
            "availableLanguage": ["en"]
        },
        "sameAs": [
            "https://facebook.com/mentorconnect",
            "https://twitter.com/mentorconnect",
            "https://linkedin.com/company/mentorconnect"
        ]
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php echo APP_NAME; ?>",
        "url": "<?php echo BASE_URL; ?>",
        "description": "<?php echo $pageDescription; ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo BASE_URL; ?>/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Professional Mentorship Platform",
        "description": "Connect with expert mentors for career coaching and professional development",
        "provider": {
            "@type": "Organization",
            "name": "<?php echo APP_NAME; ?>"
        },
        "serviceType": "Career Mentoring",
        "areaServed": "Worldwide",
        "hasOfferCatalog": {
            "@type": "OfferCatalog",
            "name": "Mentorship Services",
            "itemListElement": [
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "One-on-One Mentorship"
                    }
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Career Coaching"
                    }
                },
                {
                    "@type": "Offer",
                    "itemOffered": {
                        "@type": "Service",
                        "name": "Skill Development"
                    }
                }
            ]
        }
    }
    </script>
    
    <!-- Performance and Security -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- Analytics and Monitoring -->
    <script>
        // Performance monitoring
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'largest-contentful-paint') {
                        console.log('LCP:', entry.startTime);
                    }
                    if (entry.entryType === 'first-input') {
                        console.log('FID:', entry.processingStart - entry.startTime);
                    }
                });
            });
            observer.observe({entryTypes: ['largest-contentful-paint', 'first-input']});
        }
        
        // Register service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('SW registered'))
                    .catch(error => console.log('SW registration failed'));
            });
        }
    </script>
    
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

    <!-- JavaScript - Optimized Loading Strategy -->
    <!-- Critical JavaScript for above-the-fold functionality -->
    <script>
        // Critical theme initialization to prevent FOUC
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Preload critical scripts
            const scripts = [
                'assets/js/landing.js?v=<?php echo filemtime(__DIR__ . '/assets/js/landing.js'); ?>',
                'assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/assets/js/app.js'); ?>'
            ];
            
            scripts.forEach(src => {
                const link = document.createElement('link');
                link.rel = 'preload';
                link.as = 'script';
                link.href = src;
                document.head.appendChild(link);
            });
        })();
    </script>
    
    <!-- Main Application Scripts -->
    <script src="assets/js/landing.js?v=<?php echo filemtime(__DIR__ . '/assets/js/landing.js'); ?>" defer></script>
    <script src="assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/assets/js/app.js'); ?>" defer></script>
    
    <!-- Load non-critical CSS asynchronously -->
    <script>
        // Load CSS files asynchronously after page load
        function loadCSS(href, before, media, attributes) {
            const doc = window.document;
            const ss = doc.createElement('link');
            const ref = before || doc.getElementsByTagName('script')[0];
            const sheets = doc.styleSheets;
            
            ss.rel = 'stylesheet';
            ss.href = href;
            ss.media = media || 'all';
            
            if (attributes) {
                Object.keys(attributes).forEach(attr => {
                    ss.setAttribute(attr, attributes[attr]);
                });
            }
            
            ref.parentNode.insertBefore(ss, ref);
            return ss;
        }
        
        // Load non-critical CSS after page load
        if (window.requestIdleCallback) {
            requestIdleCallback(() => {
                // Load if not already loaded via print media trick
                if (!document.querySelector('link[href*="font-awesome"][media="all"]')) {
                    loadCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', null, 'all', {crossorigin: 'anonymous'});
                }
                if (!document.querySelector('link[href*="fonts.googleapis"][media="all"]')) {
                    loadCSS('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap', null, 'all', {crossorigin: 'anonymous'});
                }
                if (!document.querySelector('link[href*="style.css"][media="all"]')) {
                    loadCSS('assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>');
                }
                if (!document.querySelector('link[href*="landing.css"][media="all"]')) {
                    loadCSS('assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>');
                }
            });
        }
    </script>
    
    <!-- Web Vitals Monitoring -->
    <script>
        function sendToAnalytics(metric) {
            console.log('Web Vital:', metric);
            // Send to your analytics service
        }
        
        // Core Web Vitals measurement
        function getCLS(onPerfEntry) {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    let cls = 0;
                    list.getEntries().forEach((entry) => {
                        if (!entry.hadRecentInput) {
                            cls += entry.value;
                        }
                    });
                    if (cls > 0) {
                        onPerfEntry({name: 'CLS', value: cls});
                    }
                });
                observer.observe({entryTypes: ['layout-shift']});
            }
        }
        
        function getFID(onPerfEntry) {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        onPerfEntry({name: 'FID', value: entry.processingStart - entry.startTime});
                    });
                });
                observer.observe({entryTypes: ['first-input']});
            }
        }
        
        function getLCP(onPerfEntry) {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        onPerfEntry({name: 'LCP', value: entry.startTime});
                    });
                });
                observer.observe({entryTypes: ['largest-contentful-paint']});
            }
        }
        
        // Initialize Web Vitals monitoring
        getCLS(sendToAnalytics);
        getFID(sendToAnalytics);
        getLCP(sendToAnalytics);
        
        // Lazy loading implementation
        function initLazyLoading() {
            const lazyImages = document.querySelectorAll('.lazy-image, img[data-src], picture source[data-srcset]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            
                            // Handle different lazy loading patterns
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                            
                            if (img.dataset.srcset) {
                                img.srcset = img.dataset.srcset;
                                img.removeAttribute('data-srcset');
                            }
                            
                            // Add loaded class for CSS transitions
                            img.classList.add('loaded');
                            img.classList.remove('lazy-placeholder');
                            
                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px 0px'
                });
                
                lazyImages.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for browsers without IntersectionObserver
                lazyImages.forEach(img => {
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                        img.removeAttribute('data-srcset');
                    }
                    img.classList.add('loaded');
                });
            }
        }
        
        // Initialize after DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLazyLoading);
        } else {
            initLazyLoading();
        }
    </script>
</body>
</html>
