/**
 * Progressive Enhancement for Slower Connections
 * Adapts UI and functionality based on connection quality
 */
class ProgressiveEnhancement {
    constructor() {
        this.connectionType = 'unknown';
        this.isSlowConnection = false;
        this.dataUsageMode = false;
        this.init();
    }

    init() {
        this.detectConnectionType();
        this.setupConnectionMonitoring();
        this.applyEnhancements();
        this.setupDataSaver();
    }

    // Detect connection type and speed
    detectConnectionType() {
        if ('connection' in navigator) {
            const connection = navigator.connection;
            this.connectionType = connection.effectiveType || 'unknown';
            
            // Consider 2G and slow-3g as slow connections
            this.isSlowConnection = ['slow-2g', '2g', 'slow-3g'].includes(this.connectionType);
            
            // Monitor for changes
            connection.addEventListener('change', () => {
                this.connectionType = connection.effectiveType;
                this.isSlowConnection = ['slow-2g', '2g', 'slow-3g'].includes(this.connectionType);
                this.applyEnhancements();
            });
        } else {
            // Fallback: detect slow connection based on performance
            this.detectSlowConnectionFallback();
        }
    }

    // Fallback method to detect slow connections
    detectSlowConnectionFallback() {
        const startTime = performance.now();
        const testImage = new Image();
        
        testImage.onload = testImage.onerror = () => {
            const loadTime = performance.now() - startTime;
            this.isSlowConnection = loadTime > 1000; // Consider >1s as slow
            this.applyEnhancements();
        };
        
        // Small test image (1x1 pixel)
        testImage.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }

    // Setup connection monitoring
    setupConnectionMonitoring() {
        // Monitor page load performance
        window.addEventListener('load', () => {
            if ('performance' in window) {
                const navigation = performance.getEntriesByType('navigation')[0];
                if (navigation && navigation.loadEventEnd > 3000) {
                    this.isSlowConnection = true;
                    this.applyEnhancements();
                }
            }
        });

        // Monitor for data saver preference
        if ('connection' in navigator && 'saveData' in navigator.connection) {
            this.dataUsageMode = navigator.connection.saveData;
        }
    }

    // Apply progressive enhancements based on connection
    applyEnhancements() {
        if (this.isSlowConnection || this.dataUsageMode) {
            this.enableLowDataMode();
        } else {
            this.enableFullExperience();
        }
    }

    // Enable low data mode for slow connections
    enableLowDataMode() {
        document.body.classList.add('low-data-mode');
        
        // Reduce animations
        this.reduceAnimations();
        
        // Optimize images
        this.optimizeImages();
        
        // Defer non-critical resources
        this.deferNonCriticalResources();
        
        // Simplify UI
        this.simplifyUI();
        
        // Show data saver notice
        this.showDataSaverNotice();
    }

    // Enable full experience for fast connections
    enableFullExperience() {
        document.body.classList.remove('low-data-mode');
        document.body.classList.add('full-experience');
        
        // Enable all animations
        this.enableAnimations();
        
        // Load high-quality images
        this.loadHighQualityImages();
        
        // Load all resources
        this.loadAllResources();
    }

    // Reduce animations for slow connections
    reduceAnimations() {
        const style = document.createElement('style');
        style.id = 'reduced-animations';
        style.textContent = `
            .low-data-mode * {
                animation-duration: 0.1s !important;
                animation-delay: 0s !important;
                transition-duration: 0.1s !important;
                transition-delay: 0s !important;
            }
            
            .low-data-mode .hero-svg {
                display: none;
            }
            
            .low-data-mode .background-animation,
            .low-data-mode .floating-elements {
                display: none;
            }
        `;
        document.head.appendChild(style);
    }

    // Enable animations for fast connections
    enableAnimations() {
        const reducedStyle = document.getElementById('reduced-animations');
        if (reducedStyle) {
            reducedStyle.remove();
        }
    }

    // Optimize images for slow connections
    optimizeImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            // Use smaller versions if available
            if (img.dataset.srcSmall) {
                img.src = img.dataset.srcSmall;
            }
            
            // Disable lazy loading for critical images
            if (img.dataset.critical) {
                img.loading = 'eager';
            }
            
            // Add low quality placeholder
            if (!img.src && img.dataset.placeholder) {
                img.src = img.dataset.placeholder;
            }
        });
    }

    // Load high-quality images for fast connections
    loadHighQualityImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            if (img.dataset.srcLarge) {
                img.src = img.dataset.srcLarge;
            }
        });
    }

    // Defer non-critical resources
    deferNonCriticalResources() {
        // Defer non-critical CSS
        const nonCriticalCSS = document.querySelectorAll('link[rel="stylesheet"]:not([data-critical])');
        nonCriticalCSS.forEach(link => {
            link.media = 'print';
            link.onload = function() { this.media = 'all'; };
        });

        // Defer non-critical JavaScript
        const nonCriticalJS = document.querySelectorAll('script[data-defer]');
        nonCriticalJS.forEach(script => {
            script.defer = true;
        });
    }

    // Load all resources for fast connections
    loadAllResources() {
        // Load all CSS immediately
        const deferredCSS = document.querySelectorAll('link[rel="stylesheet"][media="print"]');
        deferredCSS.forEach(link => {
            link.media = 'all';
        });
    }

    // Simplify UI for slow connections
    simplifyUI() {
        // Hide decorative elements
        const decorativeElements = document.querySelectorAll('.decorative, .hero-background, .gradient-overlay');
        decorativeElements.forEach(el => {
            el.style.display = 'none';
        });

        // Simplify navigation
        const nav = document.querySelector('.landing-nav');
        if (nav) {
            nav.classList.add('simplified');
        }

        // Use system fonts as fallback
        document.body.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    }

    // Show data saver notice
    showDataSaverNotice() {
        if (document.querySelector('.data-saver-notice')) return;

        const notice = document.createElement('div');
        notice.className = 'data-saver-notice';
        notice.innerHTML = `
            <div class="notice-content">
                <i class="fas fa-wifi"></i>
                <span>Data Saver Mode Active</span>
                <button class="btn-small" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notice);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (notice.parentNode) {
                notice.remove();
            }
        }, 5000);
    }

    // Setup data saver toggle
    setupDataSaver() {
        const dataSaverToggle = document.querySelector('.data-saver-toggle');
        if (dataSaverToggle) {
            dataSaverToggle.addEventListener('click', () => {
                this.dataUsageMode = !this.dataUsageMode;
                localStorage.setItem('dataSaverMode', this.dataUsageMode);
                this.applyEnhancements();
            });
        }

        // Load saved preference
        const savedPreference = localStorage.getItem('dataSaverMode');
        if (savedPreference !== null) {
            this.dataUsageMode = savedPreference === 'true';
        }
    }

    // Preload critical resources only
    preloadCriticalOnly() {
        if (this.isSlowConnection || this.dataUsageMode) {
            // Remove non-critical preloads
            const preloads = document.querySelectorAll('link[rel="preload"]:not([data-critical])');
            preloads.forEach(link => link.remove());
        }
    }

    // Adaptive loading based on viewport
    setupAdaptiveLoading() {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        
                        // Load content based on connection speed
                        if (this.isSlowConnection) {
                            this.loadLowDataContent(element);
                        } else {
                            this.loadFullContent(element);
                        }
                        
                        observer.unobserve(element);
                    }
                });
            }, {
                rootMargin: this.isSlowConnection ? '20px' : '100px'
            });

            document.querySelectorAll('[data-adaptive-load]').forEach(el => {
                observer.observe(el);
            });
        }
    }

    // Load low data content
    loadLowDataContent(element) {
        const lowDataSrc = element.dataset.lowDataSrc;
        if (lowDataSrc) {
            if (element.tagName === 'IMG') {
                element.src = lowDataSrc;
            } else {
                element.innerHTML = lowDataSrc;
            }
        }
    }

    // Load full content
    loadFullContent(element) {
        const fullSrc = element.dataset.fullSrc || element.dataset.src;
        if (fullSrc) {
            if (element.tagName === 'IMG') {
                element.src = fullSrc;
            } else {
                fetch(fullSrc)
                    .then(response => response.text())
                    .then(html => element.innerHTML = html);
            }
        }
    }
}

// CSS for progressive enhancement
const progressiveCSS = `
    .data-saver-notice {
        position: fixed;
        top: 80px;
        right: 20px;
        background: #f59e0b;
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1001;
        animation: slideIn 0.3s ease-out;
    }
    
    .notice-content {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
    }
    
    .btn-small {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: background 0.2s;
    }
    
    .btn-small:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .low-data-mode .hero-svg,
    .low-data-mode .decorative,
    .low-data-mode .background-animation {
        display: none !important;
    }
    
    .low-data-mode .landing-nav.simplified {
        background: #fff;
        backdrop-filter: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @media (prefers-reduced-data: reduce) {
        .hero-svg,
        .decorative,
        .background-animation {
            display: none !important;
        }
    }
`;

// Inject CSS
const style = document.createElement('style');
style.textContent = progressiveCSS;
document.head.appendChild(style);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.progressiveEnhancement = new ProgressiveEnhancement();
    });
} else {
    window.progressiveEnhancement = new ProgressiveEnhancement();
}
