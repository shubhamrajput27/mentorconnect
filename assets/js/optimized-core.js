/**
 * MentorConnect Optimized Core JavaScript
 * Minified and performance-optimized version
 */

class MentorConnectApp {
    constructor() {
        this.init();
        this.performanceMonitor = new PerformanceMonitor();
    }

    init() {
        // Use requestIdleCallback for non-critical initialization
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => this.initializeNonCritical());
        } else {
            setTimeout(() => this.initializeNonCritical(), 100);
        }
        
        this.initializeCritical();
    }

    initializeCritical() {
        // Critical functionality that must load immediately
        this.setupThemeManager();
        this.setupErrorHandling();
        this.preloadCriticalResources();
    }

    initializeNonCritical() {
        // Non-critical features loaded when browser is idle
        this.setupAnimations();
        this.setupAnalytics();
        this.setupServiceWorker();
    }

    setupThemeManager() {
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            // Use event delegation for better performance
            document.addEventListener('click', (e) => {
                if (e.target.closest('.theme-toggle')) {
                    this.toggleTheme();
                }
            }, { passive: true });
        }
        
        // Apply saved theme immediately to prevent FOUC
        this.applySavedTheme();
    }

    applySavedTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Update theme icon
        const themeIcons = document.querySelectorAll('.theme-toggle i');
        themeIcons.forEach(icon => {
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    }

    setupErrorHandling() {
        window.addEventListener('error', (e) => {
            console.error('JavaScript Error:', e.error);
            this.performanceMonitor.logError(e.error);
        });

        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled Promise Rejection:', e.reason);
            this.performanceMonitor.logError(e.reason);
        });
    }

    preloadCriticalResources() {
        // Preload critical images and fonts
        const criticalImages = [
            '/assets/images/default-avatar.png',
            '/assets/images/logo.png'
        ];

        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }

    setupAnimations() {
        // Use Intersection Observer for scroll animations
        if ('IntersectionObserver' in window) {
            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                animationObserver.observe(el);
            });
        }
    }

    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('SW registered:', registration);
                })
                .catch(error => {
                    console.log('SW registration failed:', error);
                });
        }
    }

    setupAnalytics() {
        // Lightweight analytics tracking
        this.trackPageView();
        this.setupPerformanceTracking();
    }

    trackPageView() {
        const pageData = {
            url: window.location.href,
            title: document.title,
            timestamp: Date.now(),
            userAgent: navigator.userAgent
        };
        
        // Send to analytics endpoint (non-blocking)
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/api/analytics', JSON.stringify(pageData));
        }
    }

    setupPerformanceTracking() {
        // Track Core Web Vitals
        this.performanceMonitor.trackCoreWebVitals();
    }
}

class PerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.errors = [];
    }

    trackCoreWebVitals() {
        // Largest Contentful Paint
        if ('LargestContentfulPaint' in window) {
            new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                this.metrics.lcp = lastEntry.startTime;
            }).observe({ entryTypes: ['largest-contentful-paint'] });
        }

        // First Input Delay
        if ('PerformanceEventTiming' in window) {
            new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach(entry => {
                    if (entry.name === 'first-input') {
                        this.metrics.fid = entry.processingStart - entry.startTime;
                    }
                });
            }).observe({ entryTypes: ['event'] });
        }

        // Cumulative Layout Shift
        if ('LayoutShift' in window) {
            let clsValue = 0;
            new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                }
                this.metrics.cls = clsValue;
            }).observe({ entryTypes: ['layout-shift'] });
        }
    }

    logError(error) {
        this.errors.push({
            message: error.message,
            stack: error.stack,
            timestamp: Date.now()
        });

        // Limit error log size
        if (this.errors.length > 50) {
            this.errors = this.errors.slice(-25);
        }
    }

    getMetrics() {
        return {
            ...this.metrics,
            errors: this.errors,
            memory: performance.memory ? {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize,
                limit: performance.memory.jsHeapSizeLimit
            } : null
        };
    }
}

// Optimized form handling
class FormOptimizer {
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static setupValidation() {
        // Use event delegation for all forms
        document.addEventListener('input', this.debounce((e) => {
            if (e.target.matches('input[required], textarea[required]')) {
                this.validateField(e.target);
            }
        }, 300), { passive: true });
    }

    static validateField(field) {
        const isValid = field.checkValidity();
        field.classList.toggle('is-invalid', !isValid);
        field.classList.toggle('is-valid', isValid);
    }
}

// Initialize app when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mentorConnectApp = new MentorConnectApp();
        FormOptimizer.setupValidation();
    });
} else {
    window.mentorConnectApp = new MentorConnectApp();
    FormOptimizer.setupValidation();
}

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { MentorConnectApp, PerformanceMonitor, FormOptimizer };
}