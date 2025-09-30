<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // Generate SEO meta tags
    if (isset($seoOptimizer)) {
        echo $seoOptimizer->generateMetaTags();
    }
    ?>
    
    <!-- Critical CSS - loaded inline for fastest rendering -->
    <style>
        <?php
        // Inline critical CSS for instant loading
        $criticalCSS = file_get_contents(__DIR__ . '/assets/css/critical-optimized.css');
        echo $criticalCSS;
        ?>
    </style>
    
    <!-- Preconnect to external resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= BASE_URL ?>/assets/js/optimized-core.js" as="script">
    <link rel="preload" href="<?= BASE_URL ?>/assets/css/style.css" as="style">
    
    <!-- Non-critical CSS - loaded asynchronously -->
    <link rel="preload" href="<?= BASE_URL ?>/assets/css/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"></noscript>
    
    <!-- Custom page-specific CSS -->
    <?php if (isset($pageCSS) && $pageCSS): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $pageCSS ?>" media="print" onload="this.media='all'">
    <?php endif; ?>
    
    <!-- Favicon and app icons -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/apple-touch-icon.png">
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    
    <!-- Theme color for mobile browsers -->
    <meta name="theme-color" content="#2563eb">
    <meta name="msapplication-TileColor" content="#2563eb">
    
    <?php
    // Additional page-specific head content
    if (isset($pageHead) && $pageHead) {
        echo $pageHead;
    }
    ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Loading indicator -->
    <div id="loading-overlay" class="loading-overlay" aria-hidden="true">
        <div class="loading-spinner"></div>
        <span class="loading-text">Loading...</span>
    </div>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-left">
                <a href="<?= BASE_URL ?>" class="logo" aria-label="MentorConnect Home">
                    <h1>MentorConnect</h1>
                </a>
            </div>
            
            <nav class="nav" aria-label="Main navigation">
                <ul class="nav-list">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= BASE_URL ?>/dashboard/<?= $_SESSION['user_role'] ?>.php" class="nav-link">Dashboard</a></li>
                        <li><a href="<?= BASE_URL ?>/profile/edit.php" class="nav-link">Profile</a></li>
                        <li><a href="<?= BASE_URL ?>/messages/" class="nav-link">Messages</a></li>
                        <?php if ($_SESSION['user_role'] === 'student'): ?>
                            <li><a href="<?= BASE_URL ?>/mentors/browse.php" class="nav-link">Find Mentors</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>/mentors/browse.php" class="nav-link">Browse Mentors</a></li>
                        <li><a href="<?= BASE_URL ?>/auth/login.php" class="nav-link">Login</a></li>
                        <li><a href="<?= BASE_URL ?>/auth/signup.php" class="nav-link btn-primary">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="header-right">
                <!-- Theme toggle -->
                <button id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode" type="button">
                    <span class="theme-toggle-icon" aria-hidden="true">🌙</span>
                </button>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Notifications -->
                    <div class="notifications-wrapper">
                        <button id="notifications-toggle" class="notifications-toggle" aria-label="View notifications" type="button">
                            <span class="notification-icon" aria-hidden="true">🔔</span>
                            <span id="notification-count" class="notification-count" style="display: none;">0</span>
                        </button>
                        <div id="notifications-dropdown" class="notifications-dropdown" style="display: none;">
                            <div class="notifications-header">
                                <h3>Notifications</h3>
                                <button id="mark-all-read" class="btn-link">Mark all as read</button>
                            </div>
                            <div id="notifications-list" class="notifications-list">
                                <div class="notification-item loading">Loading notifications...</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User menu -->
                    <div class="user-menu">
                        <button id="user-menu-toggle" class="user-menu-toggle" aria-label="User menu" type="button">
                            <span class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></span>
                            <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                        </button>
                        <div id="user-menu-dropdown" class="user-menu-dropdown" style="display: none;">
                            <a href="<?= BASE_URL ?>/profile/edit.php" class="menu-item">Edit Profile</a>
                            <a href="<?= BASE_URL ?>/settings.php" class="menu-item">Settings</a>
                            <hr class="menu-divider">
                            <a href="<?= BASE_URL ?>/auth/logout.php" class="menu-item logout">Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Mobile menu toggle -->
                <button id="mobile-menu-toggle" class="mobile-menu-toggle" aria-label="Toggle mobile menu" type="button">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Main content -->
    <main id="main-content" class="main-content">
        <?php
        // Flash messages
        if (isset($_SESSION['flash_message'])):
        ?>
            <div class="flash-message flash-<?= $_SESSION['flash_type'] ?? 'info' ?>" role="alert">
                <span class="flash-text"><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
                <button class="flash-close" aria-label="Close message" type="button">&times;</button>
            </div>
            <?php
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        endif;
        ?>
        
        <!-- Page content -->
        <?= $content ?? '' ?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MentorConnect</h3>
                    <p>Connecting students with experienced mentors for personalized learning and career guidance.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/about.php">About Us</a></li>
                        <li><a href="<?= BASE_URL ?>/mentors/browse.php">Find Mentors</a></li>
                        <li><a href="<?= BASE_URL ?>/help.php">Help Center</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/privacy.php">Privacy Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/terms.php">Terms of Service</a></li>
                        <li><a href="<?= BASE_URL ?>/cookies.php">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <a href="#" aria-label="Twitter" class="social-link">Twitter</a>
                        <a href="#" aria-label="LinkedIn" class="social-link">LinkedIn</a>
                        <a href="#" aria-label="Facebook" class="social-link">Facebook</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> MentorConnect. All rights reserved.</p>
                <div class="footer-meta">
                    <span class="version">v<?= APP_VERSION ?></span>
                    <?php if (DEBUG_MODE): ?>
                        <span class="debug-info">
                            Page: <?= basename($_SERVER['PHP_SELF']) ?> | 
                            Load: <?= number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) ?>ms
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Optimized JavaScript - loaded at bottom for better performance -->
    <script src="<?= BASE_URL ?>/assets/js/optimized-core.js" defer></script>
    
    <?php if (isset($pageJS) && $pageJS): ?>
        <script src="<?= BASE_URL ?>/assets/js/<?= $pageJS ?>" defer></script>
    <?php endif; ?>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?= BASE_URL ?>/sw-optimized.js')
                    .then(function(registration) {
                        console.log('SW registered with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('SW registration failed: ', error);
                    });
            });
        }
    </script>
    
    <!-- Performance monitoring script -->
    <?php if (!DEBUG_MODE): ?>
    <script>
        // Send performance metrics to server
        window.addEventListener('load', function() {
            if ('performance' in window) {
                setTimeout(function() {
                    const perfData = {
                        loadTime: Math.round(performance.timing.loadEventEnd - performance.timing.navigationStart),
                        domReady: Math.round(performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart),
                        firstPaint: 0,
                        url: window.location.pathname
                    };
                    
                    // Get first paint if available
                    if ('PerformanceObserver' in window) {
                        const observer = new PerformanceObserver((list) => {
                            for (const entry of list.getEntries()) {
                                if (entry.name === 'first-paint') {
                                    perfData.firstPaint = Math.round(entry.startTime);
                                }
                            }
                        });
                        observer.observe({entryTypes: ['paint']});
                    }
                    
                    // Send data to analytics endpoint
                    fetch('<?= BASE_URL ?>/api/analytics.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(perfData)
                    }).catch(function(error) {
                        console.log('Analytics error:', error);
                    });
                }, 1000);
            }
        });
    </script>
    <?php endif; ?>
    
    <!-- Custom page scripts -->
    <?php if (isset($pageScripts) && $pageScripts): ?>
        <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>
