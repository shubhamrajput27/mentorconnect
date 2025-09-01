<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MentorConnect - Advanced Frontend Demo</title>
    
    <!-- Existing CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    
    <!-- New Advanced CSS -->
    <link rel="stylesheet" href="assets/css/advanced.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .demo-section {
            padding: var(--space-fluid-lg);
            margin: var(--space-fluid-md) 0;
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-fluid-md);
            margin: var(--space-fluid-md) 0;
        }
        
        .demo-item {
            padding: var(--space-fluid-sm);
            border-radius: var(--radius-xl);
            background: var(--card-color);
        }
        
        .feature-showcase {
            text-align: center;
            padding: var(--space-fluid-md);
        }
    </style>
</head>
<body class="landing-page">
    <!-- Scroll Progress Indicator -->
    <div class="scroll-indicator">
        <div class="scroll-progress" id="scrollProgress"></div>
    </div>

    <!-- Theme Toggle -->
    <div class="theme-toggle" title="Toggle Theme (Alt+T)">
        <i class="fas fa-moon" id="theme-icon"></i>
    </div>

    <!-- Header -->
    <header class="demo-section">
        <div class="container">
            <h1 class="text-gradient text-center">🚀 Advanced Frontend Features Demo</h1>
            <p class="text-center" style="font-size: var(--fluid-lg); margin-top: var(--space-fluid-sm);">
                Cutting-edge web technologies and user experience enhancements
            </p>
        </div>
    </header>

    <!-- Advanced Buttons Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Advanced Button Styles</h2>
            <div class="demo-grid">
                <div class="demo-item">
                    <h3>Gradient Buttons</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                        <button class="btn btn-gradient">Gradient Button</button>
                        <button class="btn btn-gradient btn-lg">Large Gradient</button>
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3>Neon Effect Buttons</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                        <button class="btn btn-neon">Neon Effect</button>
                        <button class="btn btn-neon btn-sm">Small Neon</button>
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3>Spring Animation Buttons</h3>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                        <button class="btn btn-primary spring-btn">Spring Button</button>
                        <button class="btn btn-secondary spring-btn">Spring Secondary</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Advanced Cards Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Advanced Card Designs</h2>
            <div class="demo-grid">
                <div class="card-morphic demo-item">
                    <h3>Morphic Card</h3>
                    <p>This card uses advanced neumorphism design with soft shadows and lighting effects.</p>
                    <button class="btn btn-primary btn-sm">Learn More</button>
                </div>
                
                <div class="card-holographic demo-item">
                    <h3>Holographic Card</h3>
                    <p>A futuristic card with holographic effects and animated gradients.</p>
                    <button class="btn btn-outline btn-sm">Explore</button>
                </div>
                
                <div class="glass-advanced demo-item">
                    <h3>Advanced Glass</h3>
                    <p>Enhanced glassmorphism with improved backdrop filters and border effects.</p>
                    <button class="btn btn-ghost btn-sm">Discover</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Advanced Animations Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Advanced Animations & Effects</h2>
            <div class="demo-grid">
                <div class="demo-item hover-lift">
                    <h3>Hover Lift Effect</h3>
                    <p>Hover over this card to see the smooth lift animation with glow effects.</p>
                </div>
                
                <div class="demo-item hover-tilt">
                    <h3>3D Tilt Effect</h3>
                    <p>This card tilts in 3D space when you hover over it using CSS transforms.</p>
                </div>
                
                <div class="demo-item hover-zoom">
                    <h3>Zoom Effect</h3>
                    <div style="width: 100%; height: 200px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: var(--radius-lg); margin-top: 1rem;"></div>
                    <p>Hover to see the zoom effect in action.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Typography Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Advanced Typography</h2>
            <div class="demo-grid">
                <div class="demo-item">
                    <h3 class="text-gradient">Gradient Text</h3>
                    <p>Beautiful gradient text effects using CSS background-clip.</p>
                </div>
                
                <div class="demo-item">
                    <h3 class="text-glow">Glowing Text</h3>
                    <p>Text with subtle glow effects for dark themes.</p>
                </div>
                
                <div class="demo-item">
                    <h3 class="text-outline">Outlined Text</h3>
                    <p>Outlined text style using CSS text-stroke.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Floating Forms Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Advanced Form Elements</h2>
            <div class="demo-grid">
                <div class="demo-item">
                    <h3>Floating Label Input</h3>
                    <div class="form-floating" style="margin-top: 1rem;">
                        <input type="text" id="floatingInput" placeholder=" ">
                        <label for="floatingInput">Enter your name</label>
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3>Floating Label Textarea</h3>
                    <div class="form-floating" style="margin-top: 1rem;">
                        <textarea id="floatingTextarea" placeholder=" " rows="4"></textarea>
                        <label for="floatingTextarea">Your message</label>
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3>Focus Ring Demo</h3>
                    <button class="btn btn-primary focus-ring" style="margin-top: 1rem;">Focus me with Tab</button>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;">
                        Try using Tab to focus this button to see the enhanced focus ring.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Loading States Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Loading States & Skeletons</h2>
            <div class="demo-grid">
                <div class="demo-item">
                    <h3>Skeleton Loading</h3>
                    <div class="skeleton" style="height: 20px; border-radius: 4px; margin: 8px 0;"></div>
                    <div class="skeleton" style="height: 20px; border-radius: 4px; margin: 8px 0; width: 80%;"></div>
                    <div class="skeleton" style="height: 60px; border-radius: 4px; margin: 8px 0;"></div>
                </div>
                
                <div class="demo-item">
                    <h3>Pulse Dots Loader</h3>
                    <div class="pulse-dots" style="margin: 2rem 0; justify-content: center;">
                        Loading
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3>Morphing Shape</h3>
                    <div class="morph-shape" style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); margin: 2rem auto;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Features Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Interactive Features</h2>
            <div class="demo-grid">
                <div class="demo-item">
                    <h3>Share Button</h3>
                    <button class="btn btn-primary share-btn" 
                            data-title="MentorConnect Advanced Demo"
                            data-text="Check out these amazing frontend features!"
                            data-url="https://mentorconnect.demo">
                        <i class="fas fa-share"></i> Share This Page
                    </button>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;">
                        Uses Web Share API when available
                    </p>
                </div>
                
                <div class="demo-item">
                    <h3>Touch Gestures</h3>
                    <p>On touch devices, try swiping left/right to interact with the sidebar.</p>
                    <div style="padding: 1rem; background: var(--surface-color); border-radius: var(--radius-lg); margin-top: 1rem; text-align: center;">
                        <i class="fas fa-hand-paper" style="font-size: 2rem; color: var(--primary-color);"></i>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">Swipe Zone</p>
                    </div>
                </div>
                
                <div class="demo-item">
                    <h3>Keyboard Shortcuts</h3>
                    <div style="font-family: monospace; font-size: 0.875rem; background: var(--surface-color); padding: 1rem; border-radius: var(--radius-lg); margin-top: 1rem;">
                        <p><kbd>Alt + T</kbd> - Toggle Theme</p>
                        <p><kbd>Alt + S</kbd> - Focus Search</p>
                        <p><kbd>Esc</kbd> - Close Modals</p>
                        <p><kbd>Tab</kbd> - Navigate Elements</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Performance Analytics Section -->
    <section class="demo-section">
        <div class="container">
            <h2 class="text-center mb-lg">Performance Analytics</h2>
            <div class="feature-showcase">
                <div class="glass-advanced" style="padding: var(--space-fluid-lg); margin: var(--space-fluid-md) auto; max-width: 600px;">
                    <h3>Real-time Web Vitals Tracking</h3>
                    <p>This demo tracks Core Web Vitals (CLS, FID, LCP) and user interactions in real-time.</p>
                    <button class="btn btn-primary" onclick="showAnalytics()">
                        <i class="fas fa-chart-line"></i> View Analytics
                    </button>
                    <div id="analyticsDisplay" style="margin-top: 1rem; display: none;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="demo-section" style="background: var(--surface-color); margin-top: var(--space-fluid-xl);">
        <div class="container text-center">
            <h3 class="text-gradient">Advanced Frontend Complete! 🎉</h3>
            <p style="margin: var(--space-fluid-sm) 0;">
                These cutting-edge features enhance user experience with modern web technologies.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: var(--space-fluid-md);">
                <a href="index.php" class="btn btn-primary">Back to App</a>
                <a href="performance-test.php" class="btn btn-secondary">Performance Test</a>
                <button class="btn btn-outline" onclick="resetDemo()">Reset Demo</button>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/advanced-features.js"></script>
    
    <script>
        // Scroll Progress Indicator
        window.addEventListener('scroll', () => {
            const scrollProgress = document.getElementById('scrollProgress');
            const scrolled = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            scrollProgress.style.width = scrolled + '%';
        });

        // Show Analytics Function
        function showAnalytics() {
            const display = document.getElementById('analyticsDisplay');
            if (window.advancedFeatures) {
                const analytics = window.advancedFeatures.getAnalytics();
                display.innerHTML = `
                    <div style="background: var(--background-color); padding: 1rem; border-radius: var(--radius-lg); text-align: left;">
                        <h4>Current Analytics:</h4>
                        <pre style="font-size: 0.875rem; margin: 0; white-space: pre-wrap;">${JSON.stringify(analytics, null, 2)}</pre>
                    </div>
                `;
                display.style.display = 'block';
            } else {
                display.innerHTML = '<p>Advanced features not loaded yet. Please wait...</p>';
                display.style.display = 'block';
            }
        }

        // Reset Demo Function
        function resetDemo() {
            localStorage.clear();
            location.reload();
        }

        // Initialize demo-specific features
        document.addEventListener('DOMContentLoaded', () => {
            // Add demo-specific animations
            const demoItems = document.querySelectorAll('.demo-item');
            demoItems.forEach(item => {
                item.classList.add('animate-fade-in');
            });

            // Show welcome message
            setTimeout(() => {
                if (window.advancedFeatures) {
                    window.advancedFeatures.showToast('🎉 Welcome to the Advanced Features Demo!', 'success');
                }
            }, 1000);
        });
    </script>
</body>
</html>
