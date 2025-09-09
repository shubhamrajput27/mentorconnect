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
    
    <!-- Landing Page CSS -->
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>">
    
    <!-- Optimized CSS Loading -->
    <link rel="stylesheet" href="assets/optimized.css?v=<?php echo filemtime(__DIR__ . '/assets/optimized.css'); ?>">
    
    <!-- Performance & Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="camera=(), microphone=(), geolocation=()">
    
    <!-- Theme persistence and FOUC prevention -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <!-- Resource Preloading for Performance -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style" crossorigin>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" crossorigin>
    
    <!-- Advanced CSS Features (if available) -->
    <?php if (file_exists(__DIR__ . '/assets/css/advanced.css')): ?>
    <link rel="stylesheet" href="assets/css/advanced.css?v=<?php echo filemtime(__DIR__ . '/assets/css/advanced.css'); ?>" media="print" onload="this.media='all'">
    <?php endif; ?>
    
    <!-- External CSS with Async Loading -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" media="print" onload="this.media='all'" crossorigin>
    
    <!-- Fallback for disabled JavaScript -->
    <noscript>
        <link rel="stylesheet" href="assets/css/critical.css?v=<?php echo filemtime(__DIR__ . '/assets/css/critical.css'); ?>">
        <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
        <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" crossorigin>
    </noscript>
    
    <!-- Critical CSS Inline -->
    <link rel="stylesheet" href="assets/css/critical.css?v=<?php echo filemtime(__DIR__ . '/assets/css/critical.css'); ?>">
    
    <!-- Main CSS with Async Loading -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo filemtime(__DIR__ . '/assets/css/landing.css'); ?>" media="print" onload="this.media='all'">
    
    <!-- Optimized Button Styles -->
    <style>
    .nav-links .btn{font-size:.9rem!important;padding:.75rem 1.5rem!important;font-weight:600!important;border-radius:25px!important;transition:all .3s cubic-bezier(.4,0,.2,1)!important;text-decoration:none!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:120px!important;position:relative!important;overflow:hidden!important}
    .nav-links .btn-primary{background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%)!important;color:#fff!important;border:2px solid transparent!important;box-shadow:0 4px 12px rgba(99,102,241,.3),0 2px 4px rgba(0,0,0,.1)!important;text-shadow:0 1px 2px rgba(0,0,0,.1)!important}
    .nav-links .btn-primary:hover{background:linear-gradient(135deg,#4338ca 0%,#7c3aed 100%)!important;color:#fff!important;transform:translateY(-2px)!important;box-shadow:0 8px 20px rgba(99,102,241,.4),0 4px 8px rgba(0,0,0,.15)!important;border-color:rgba(255,255,255,.2)!important}
    .nav-links .btn-outline{background:rgba(255,255,255,.95)!important;color:#6366f1!important;border:2px solid #6366f1!important;backdrop-filter:blur(10px)!important;box-shadow:0 2px 8px rgba(0,0,0,.1)!important;font-weight:600!important}
    .nav-links .btn-outline:hover{background:#6366f1!important;color:#fff!important;border-color:#6366f1!important;transform:translateY(-2px)!important;box-shadow:0 6px 16px rgba(99,102,241,.3),0 2px 4px rgba(0,0,0,.1)!important}
    [data-theme="dark"] .nav-links .btn-primary{background:linear-gradient(135deg,#818cf8 0%,#a5b4fc 100%)!important;color:#0f172a!important;text-shadow:none!important;box-shadow:0 4px 12px rgba(129,140,248,.3),0 2px 4px rgba(0,0,0,.2)!important}
    [data-theme="dark"] .nav-links .btn-primary:hover{background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%)!important;color:#fff!important;text-shadow:0 1px 2px rgba(0,0,0,.2)!important}
    [data-theme="dark"] .nav-links .btn-outline{background:rgba(30,41,59,.95)!important;color:#818cf8!important;border-color:#818cf8!important}
    [data-theme="dark"] .nav-links .btn-outline:hover{background:#818cf8!important;color:#0f172a!important}
    .nav-links .btn:focus{outline:2px solid #06b6d4!important;outline-offset:2px!important}
    @media (max-width:768px){.nav-links .btn{min-width:100px!important;padding:.625rem 1.25rem!important;font-size:.875rem!important}}

    /* Disable all cursor interactions on SVG characters */
    .mentor-figure, .student-figure {
        pointer-events: none !important;
    }
    
    .hero-svg {
        pointer-events: none !important;
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
                    <div class="hero-graphic-container">
                        <svg viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg" class="hero-svg">
                            <defs>
                                <!-- Simple gradients for characters -->
                                <linearGradient id="mentorGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#4F8EDB" />
                                    <stop offset="100%" style="stop-color:#3B7BC8" />
                                </linearGradient>
                                <linearGradient id="studentGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:#10B981" />
                                    <stop offset="100%" style="stop-color:#059669" />
                                </linearGradient>
                            </defs>
                            
                            <!-- Outer Light Purple Background Circle -->
                            <circle cx="300" cy="300" r="280" fill="#E8D5F2" opacity="0.4"/>
                            
                            <!-- Main Light Blue Circle -->
                            <circle cx="300" cy="300" r="220" fill="#B8E0E8" opacity="0.7"/>
                            
                            <!-- Light Beige Overlapping Circle -->
                            <circle cx="380" cy="380" r="160" fill="#E8D5C4" opacity="0.8"/>
                            
                            <!-- Small floating elements -->
                            <circle cx="150" cy="120" r="5" fill="#F97316" opacity="0.8">
                                <animate attributeName="cy" values="120;115;120" dur="3s" repeatCount="indefinite"/>
                            </circle>
                            
                            <!-- Chat Bubble -->
                            <g class="chat-bubble" transform="translate(350, 150)">
                                <ellipse cx="0" cy="0" rx="30" ry="18" fill="#10B981"/>
                                <polygon points="-8,12 2,12 -3,20" fill="#10B981"/>
                                <circle cx="-10" cy="-2" r="2" fill="#ffffff"/>
                                <circle cx="0" cy="-2" r="2" fill="#ffffff"/>
                                <circle cx="10" cy="-2" r="2" fill="#ffffff"/>
                                <animateTransform attributeName="transform" type="scale" values="1;1.05;1" dur="2s" repeatCount="indefinite"/>
                            </g>
                            
                            <!-- Lightbulb -->
                            <g class="lightbulb" transform="translate(300, 200)">
                                <circle cx="0" cy="0" r="6" fill="#FBD34D"/>
                                <rect x="-1.2" y="3.8" width="2.4" height="3.8" rx="1.2" fill="#92400E"/>
                                <rect x="-3" y="-0.5" width="6" height="1" fill="#FBD34D"/>
                                <animate attributeName="opacity" values="0.7;1;0.7" dur="2s" repeatCount="indefinite"/>
                            </g>
                            
                            <!-- Small Messaging Icon near Lightbulb -->
                            <g class="message-icon" transform="translate(320, 185)">
                                <rect x="-9" y="-6" width="18" height="12" rx="3" fill="#10B981"/>
                                <polygon points="-3,6 3,6 0,10" fill="#10B981"/>
                                <circle cx="-4.5" cy="-1.5" r="1.2" fill="#ffffff"/>
                                <circle cx="0" cy="-1.5" r="1.2" fill="#ffffff"/>
                                <circle cx="4.5" cy="-1.5" r="1.2" fill="#ffffff"/>
                                <animate attributeName="opacity" values="0.6;1;0.6" dur="2.5s" repeatCount="indefinite"/>
                            </g>
                            
                            <!-- Connection Line -->
                            <path d="M 220 380 Q 300 320 380 400" stroke="#10B981" stroke-width="3" fill="none" stroke-dasharray="6,3" opacity="0.8" class="connection-line">
                                <animate attributeName="stroke-dashoffset" values="0;-18;0" dur="3s" repeatCount="indefinite"/>
                            </path>
                            
                            <!-- Mentor Figure (Left) - Blue -->
                            <g class="mentor-figure" transform="translate(200, 360)">
                                <!-- Mentor Body (Capsule Shape) -->
                                <ellipse cx="0" cy="10" rx="30" ry="45" fill="url(#mentorGradient)"/>
                                <!-- Mentor Head -->
                                <circle cx="0" cy="-35" r="20" fill="#FBD34D"/>
                                <!-- Mentor Hair -->
                                <path d="M -18 -50 Q -15 -55 -8 -53 Q 0 -55 8 -53 Q 15 -55 18 -50 Q 12 -52 0 -52 Q -12 -52 -18 -50" fill="#8B4513"/>
                                <!-- Mentor Face - Happy expression -->
                                <circle cx="-6" cy="-38" r="2" fill="#1F2937"/>
                                <circle cx="6" cy="-38" r="2" fill="#1F2937"/>
                                <path d="M -8 -28 Q 0 -22 8 -28" stroke="#1F2937" stroke-width="1.5" fill="none"/>
                                <!-- Mentor Device/Laptop -->
                                <rect x="-12" y="0" width="24" height="16" rx="2" fill="#1E293B"/>
                                <rect x="-10" y="2" width="20" height="12" rx="1" fill="#3B82F6"/>
                                <!-- Mentor Arms -->
                                <circle cx="-35" cy="5" r="8" fill="#FBD34D"/>
                                <circle cx="35" cy="8" r="8" fill="#FBD34D"/>
                            </g>
                            
                            <!-- Student Figure (Right) - Green -->
                            <g class="student-figure" transform="translate(400, 400)">
                                <!-- Student Body (Capsule Shape) -->
                                <ellipse cx="0" cy="10" rx="30" ry="45" fill="url(#studentGradient)"/>
                                <!-- Student Head -->
                                <circle cx="0" cy="-35" r="20" fill="#FBD34D"/>
                                <!-- Student Hair -->
                                <path d="M -20 -48 Q -18 -53 -10 -52 Q -2 -54 2 -54 Q 10 -52 18 -53 Q 20 -48 15 -50 Q 8 -51 0 -51 Q -8 -51 -15 -50 Q -20 -48 -20 -48" fill="#654321"/>
                                <!-- Student Face - Happy expression -->
                                <circle cx="-6" cy="-38" r="2" fill="#1F2937"/>
                                <circle cx="6" cy="-38" r="2" fill="#1F2937"/>
                                <path d="M -8 -28 Q 0 -22 8 -28" stroke="#1F2937" stroke-width="1.5" fill="none"/>
                                <!-- Student Book -->
                                <rect x="-10" y="5" width="20" height="15" rx="2" fill="#EF4444"/>
                                <rect x="-8" y="7" width="16" height="1.5" fill="#ffffff"/>
                                <rect x="-8" y="10" width="12" height="1" fill="#ffffff"/>
                                <rect x="-8" y="13" width="14" height="1" fill="#ffffff"/>
                                <!-- Student Arms -->
                                <circle cx="-35" cy="8" r="8" fill="#FBD34D"/>
                                <circle cx="35" cy="12" r="8" fill="#FBD34D"/>
                            </g>
                        </svg>
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
    
    <!-- Optimized JavaScript -->
    <script src="assets/optimized.js?v=<?php echo filemtime(__DIR__ . '/assets/optimized.js'); ?>" defer></script>
    
    <!-- Performance Enhancements -->
    <script src="assets/js/performance-enhancements.js?v=<?php echo filemtime(__DIR__ . '/assets/js/performance-enhancements.js'); ?>" defer></script>
    <script src="assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/assets/js/app.js'); ?>" defer></script>
    
    <!-- Advanced Features (if available) -->
    <?php if (file_exists(__DIR__ . '/assets/js/advanced-features.js')): ?>
    <script src="assets/js/advanced-features.js?v=<?php echo filemtime(__DIR__ . '/assets/js/advanced-features.js'); ?>" defer></script>
    <?php endif; ?>
    
    <!-- Performance Optimizer (if available) -->
    <?php if (file_exists(__DIR__ . '/assets/js/performance-optimizer.js')): ?>
    <script src="assets/js/performance-optimizer.js?v=<?php echo filemtime(__DIR__ . '/assets/js/performance-optimizer.js'); ?>" defer></script>
    <?php endif; ?>
    
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
    
    <?php
    // End performance monitoring and output debug info
    if (isset($performanceMonitor)) {
        $performanceMonitor->endTimer('page_load');
        
        // Show performance info in debug mode
        if (DEBUG_MODE) {
            $report = perf_report();
            $cacheStats = cache_stats();
            
            echo "<!-- Performance Debug Info\n";
            echo "Page Load Time: {$report['execution_time']}s\n";
            echo "Memory Usage: {$report['memory_usage']['peak']}\n";
            echo "Database Queries: {$report['database_queries']}\n";
            echo "Cache Hit Ratio: {$cacheStats['hit_ratio']}%\n";
            echo "Performance Grade: {$report['performance_grade']}/100\n";
            
            if (!empty($report['operations'])) {
                echo "Operations:\n";
                foreach ($report['operations'] as $name => $op) {
                    echo "  {$name}: {$op['duration']}s ({$op['status']})\n";
                }
            }
            
            $suggestions = perf_suggestions();
            if (!empty($suggestions)) {
                echo "Optimization Suggestions:\n";
                foreach ($suggestions as $suggestion) {
                    echo "  - {$suggestion}\n";
                }
            }
            echo "-->\n";
        }
    }
    ?>
</body>
</html>
