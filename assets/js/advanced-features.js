/**
 * Advanced Frontend Features for MentorConnect
 * Cutting-edge web technologies and user experience enhancements
 */

class AdvancedFrontendFeatures {
    constructor() {
        this.gestures = new Map();
        this.analytics = new Map();
        this.prefetch = new Set();
        this.observers = new Map();
        this.workers = new Map();
        
        this.init();
    }

    init() {
        this.initAdvancedPWA();
        this.initPerformanceOptimizations();
        this.initSmartPrefetching();
        this.initAdvancedAnimations();
        this.initGestureSupport();
        this.initWebVitalsTracking();
        this.initAdvancedA11y();
        this.initMicroInteractions();
        this.initAdvancedCaching();
        this.initBehavioralAnalytics();
    }

    // Advanced PWA Features
    initAdvancedPWA() {
        // Enhanced offline experience
        if ('serviceWorker' in navigator) {
            this.setupAdvancedOffline();
            this.setupBackgroundSync();
            this.setupPushNotifications();
        }

        // App shortcuts and file handling
        this.setupAppShortcuts();
        this.setupFileHandling();
        
        // Share API integration
        this.setupWebShare();
        
        // Install prompt optimization
        this.optimizeInstallPrompt();
    }

    setupAdvancedOffline() {
        // Intelligent offline fallbacks
        window.addEventListener('online', () => {
            this.showToast('🌐 Back online! Syncing data...', 'success');
            this.syncOfflineData();
        });

        window.addEventListener('offline', () => {
            this.showToast('📡 You\'re offline. App features still available!', 'info');
            this.enableOfflineMode();
        });
    }

    setupBackgroundSync() {
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            navigator.serviceWorker.ready.then(registration => {
                // Register background sync for form submissions
                registration.sync.register('background-sync');
            });
        }
    }

    setupWebShare() {
        if (navigator.share) {
            document.addEventListener('click', (e) => {
                if (e.target.matches('.share-btn')) {
                    e.preventDefault();
                    const url = e.target.dataset.url || window.location.href;
                    const title = e.target.dataset.title || document.title;
                    const text = e.target.dataset.text || 'Check out MentorConnect!';

                    navigator.share({ title, text, url }).catch(console.error);
                }
            });
        }
    }

    // Performance Optimizations
    initPerformanceOptimizations() {
        // Critical resource preloading
        this.preloadCriticalResources();
        
        // Adaptive loading based on connection
        this.adaptToConnection();
        
        // Image optimization with WebP/AVIF
        this.optimizeImages();
        
        // Memory leak prevention
        this.preventMemoryLeaks();
        
        // Bundle splitting simulation
        this.loadModulesOnDemand();
    }

    preloadCriticalResources() {
        const criticalResources = [
            '/api/user-preferences.php',
            '/api/notifications.php?action=count',
            'assets/css/critical.css',
            'assets/js/app.js'
        ];

        criticalResources.forEach(resource => {
            if (!this.prefetch.has(resource)) {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = resource;
                document.head.appendChild(link);
                this.prefetch.add(resource);
            }
        });
    }

    adaptToConnection() {
        if ('connection' in navigator) {
            const connection = navigator.connection;
            
            // Adjust quality based on connection
            if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
                this.enableLowDataMode();
            } else if (connection.effectiveType === '4g') {
                this.enableHighQualityMode();
            }

            connection.addEventListener('change', () => {
                this.adaptToConnection();
            });
        }
    }

    enableLowDataMode() {
        // Reduce image quality, disable animations
        document.documentElement.setAttribute('data-connection', 'slow');
        this.showToast('📶 Optimizing for slow connection', 'info');
    }

    enableHighQualityMode() {
        // Enable high-quality images, smooth animations
        document.documentElement.setAttribute('data-connection', 'fast');
    }

    // Smart Prefetching
    initSmartPrefetching() {
        // Predictive prefetching based on user behavior
        this.setupHoverPrefetch();
        this.setupViewportPrefetch();
        this.setupMLPrefetch();
    }

    setupHoverPrefetch() {
        let hoverTimer;
        
        document.addEventListener('mouseover', (e) => {
            const link = e.target.closest('a[href]');
            if (!link || link.hostname !== location.hostname) return;

            hoverTimer = setTimeout(() => {
                this.prefetchResource(link.href);
            }, 150); // Delay to avoid prefetching on accidental hovers
        });

        document.addEventListener('mouseout', () => {
            clearTimeout(hoverTimer);
        });
    }

    setupViewportPrefetch() {
        const prefetchObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const link = entry.target.querySelector('a[href]');
                    if (link) {
                        this.prefetchResource(link.href);
                        prefetchObserver.unobserve(entry.target);
                    }
                }
            });
        }, { rootMargin: '200px' });

        // Observe cards and navigation items
        document.querySelectorAll('.card, .nav-item').forEach(el => {
            prefetchObserver.observe(el);
        });
    }

    prefetchResource(url) {
        if (this.prefetch.has(url)) return;

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
        this.prefetch.add(url);
    }

    // Advanced Animations & Micro-interactions
    initAdvancedAnimations() {
        // Spring physics animations
        this.setupSpringAnimations();
        
        // Scroll-triggered animations
        this.setupScrollAnimations();
        
        // Parallax effects
        this.setupParallax();
        
        // Advanced hover effects
        this.setupAdvancedHovers();
    }

    setupSpringAnimations() {
        // Add spring-based animations for buttons and cards
        const style = document.createElement('style');
        style.textContent = `
            .spring-btn {
                transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            }
            .spring-btn:active {
                transform: scale(0.95);
            }
            .spring-card {
                transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .spring-card:hover {
                transform: translateY(-8px) scale(1.02);
            }
        `;
        document.head.appendChild(style);

        // Apply to existing elements
        document.querySelectorAll('.btn').forEach(btn => {
            btn.classList.add('spring-btn');
        });
        
        document.querySelectorAll('.card').forEach(card => {
            card.classList.add('spring-card');
        });
    }

    setupScrollAnimations() {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    
                    // Stagger child animations
                    const children = entry.target.querySelectorAll('.animate-child');
                    children.forEach((child, index) => {
                        setTimeout(() => {
                            child.classList.add('animate-in');
                        }, index * 100);
                    });
                }
            });
        }, { threshold: 0.2 });

        document.querySelectorAll('.feature-card, .step, .stat').forEach(el => {
            animationObserver.observe(el);
        });
    }

    // Gesture Support
    initGestureSupport() {
        if ('ontouchstart' in window) {
            this.setupTouchGestures();
            this.setupSwipeNavigation();
        }
    }

    setupTouchGestures() {
        let startX, startY, currentX, currentY;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });

        document.addEventListener('touchmove', (e) => {
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
        }, { passive: true });

        document.addEventListener('touchend', () => {
            if (!startX || !startY) return;

            const diffX = currentX - startX;
            const diffY = currentY - startY;

            // Detect swipe gestures
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    this.handleSwipeRight();
                } else {
                    this.handleSwipeLeft();
                }
            }

            startX = startY = currentX = currentY = null;
        });
    }

    handleSwipeLeft() {
        // Navigate forward or open sidebar
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && window.innerWidth <= 768) {
            if (window.mentorConnectApp) {
                window.mentorConnectApp.toggleSidebar();
            }
        }
    }

    handleSwipeRight() {
        // Navigate back or close sidebar
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && sidebar.classList.contains('open')) {
            if (window.mentorConnectApp) {
                window.mentorConnectApp.toggleSidebar();
            }
        }
    }

    // Web Vitals Tracking
    initWebVitalsTracking() {
        if ('PerformanceObserver' in window) {
            this.trackCLS();
            this.trackFID();
            this.trackLCP();
            this.trackFCP();
        }
    }

    trackCLS() {
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    this.analytics.set('CLS', entry.value);
                    if (entry.value > 0.1) {
                        console.warn('High CLS detected:', entry.value);
                    }
                }
            }
        });
        observer.observe({ entryTypes: ['layout-shift'] });
    }

    trackFID() {
        const observer = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                this.analytics.set('FID', entry.processingStart - entry.startTime);
                if (entry.processingStart - entry.startTime > 100) {
                    console.warn('High FID detected:', entry.processingStart - entry.startTime);
                }
            }
        });
        observer.observe({ entryTypes: ['first-input'] });
    }

    trackLCP() {
        const observer = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            this.analytics.set('LCP', lastEntry.startTime);
        });
        observer.observe({ entryTypes: ['largest-contentful-paint'] });
    }

    // Advanced Accessibility
    initAdvancedA11y() {
        this.setupKeyboardNavigation();
        this.setupScreenReaderEnhancements();
        this.setupFocusManagement();
        this.setupColorBlindnessSupport();
    }

    setupKeyboardNavigation() {
        // Enhanced keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Alt + T for theme toggle
            if (e.altKey && e.key === 't') {
                e.preventDefault();
                if (window.mentorConnectApp) {
                    window.mentorConnectApp.toggleTheme();
                }
            }
            
            // Alt + S for search
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                const searchInput = document.querySelector('.search-input');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape to close modals/sidebars
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.open');
                if (openModal) {
                    openModal.classList.remove('open');
                }
                
                const openSidebar = document.querySelector('.sidebar.open');
                if (openSidebar && window.innerWidth <= 768) {
                    if (window.mentorConnectApp) {
                        window.mentorConnectApp.toggleSidebar();
                    }
                }
            }
        });
    }

    setupFocusManagement() {
        // Enhanced focus visible for keyboard users
        let isUsingKeyboard = false;

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                isUsingKeyboard = true;
                document.body.classList.add('keyboard-navigation');
            }
        });

        document.addEventListener('mousedown', () => {
            isUsingKeyboard = false;
            document.body.classList.remove('keyboard-navigation');
        });
    }

    // Micro-interactions
    initMicroInteractions() {
        this.setupButtonFeedback();
        this.setupLoadingStates();
        this.setupProgressIndicators();
        this.setupSuccessAnimations();
    }

    setupButtonFeedback() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn, button, .clickable')) {
                this.createRippleEffect(e);
                this.addHapticFeedback();
            }
        });
    }

    createRippleEffect(e) {
        const button = e.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
        `;

        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    addHapticFeedback() {
        if ('vibrate' in navigator) {
            navigator.vibrate(10); // Subtle haptic feedback
        }
    }

    // Advanced Caching Strategy
    initAdvancedCaching() {
        if ('caches' in window) {
            this.setupIntelligentCaching();
            this.setupCacheOptimization();
        }
    }

    async setupIntelligentCaching() {
        // Cache frequently accessed resources
        const cache = await caches.open('mentorconnect-smart-cache-v1');
        
        // Monitor resource usage
        const resourceObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.transferSize > 0) {
                    this.analytics.set(`resource_${entry.name}`, {
                        size: entry.transferSize,
                        time: entry.responseEnd - entry.startTime,
                        frequency: (this.analytics.get(`resource_${entry.name}`)?.frequency || 0) + 1
                    });
                }
            }
        });
        resourceObserver.observe({ entryTypes: ['resource'] });
    }

    // Behavioral Analytics
    initBehavioralAnalytics() {
        this.trackUserInteractions();
        this.trackScrollBehavior();
        this.trackTimeOnPage();
    }

    trackUserInteractions() {
        let interactionCount = 0;
        
        document.addEventListener('click', () => {
            interactionCount++;
            this.analytics.set('interactions', interactionCount);
        });

        // Track most clicked elements
        const clickHeatmap = new Map();
        document.addEventListener('click', (e) => {
            const selector = this.getElementSelector(e.target);
            clickHeatmap.set(selector, (clickHeatmap.get(selector) || 0) + 1);
            this.analytics.set('clickHeatmap', clickHeatmap);
        });
    }

    getElementSelector(element) {
        if (element.id) return `#${element.id}`;
        if (element.className) return `.${element.className.split(' ')[0]}`;
        return element.tagName.toLowerCase();
    }

    // Utility Methods
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    getAnalytics() {
        return Object.fromEntries(this.analytics);
    }

    // Cleanup
    destroy() {
        this.observers.forEach(observer => observer.disconnect());
        this.workers.forEach(worker => worker.terminate());
        this.gestures.clear();
        this.analytics.clear();
        this.prefetch.clear();
    }
}

// CSS for advanced features
const advancedStyles = `
    .keyboard-navigation *:focus {
        outline: 2px solid var(--primary-color) !important;
        outline-offset: 2px !important;
    }

    .animate-in {
        animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .animate-child {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .animate-child.animate-in {
        opacity: 1;
        transform: translateY(0);
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        transform: translateX(100%);
        opacity: 0;
        transition: all 0.3s ease;
        max-width: 300px;
    }

    .toast.show {
        transform: translateX(0);
        opacity: 1;
    }

    .toast-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .toast-content button {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 18px;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    [data-connection="slow"] .hero-graphic {
        animation: none;
    }

    [data-connection="slow"] img {
        filter: blur(1px);
    }

    [data-connection="fast"] .hero-graphic {
        animation: float 6s ease-in-out infinite, pulse 4s ease-in-out infinite alternate;
    }

    @media (prefers-reduced-motion: reduce) {
        .spring-btn,
        .spring-card,
        .animate-in,
        .animate-child {
            animation: none !important;
            transition: none !important;
        }
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = advancedStyles;
document.head.appendChild(styleSheet);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.advancedFeatures = new AdvancedFrontendFeatures();
    });
} else {
    window.advancedFeatures = new AdvancedFrontendFeatures();
}

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdvancedFrontendFeatures;
}
