/* Advanced Performance Optimizer for MentorConnect
 * Implements intelligent resource loading, caching, and performance monitoring
 * Created: 2025-01-10
 */

class AdvancedPerformanceOptimizer {
    constructor() {
        this.resourceCache = new Map();
        this.performanceMetrics = {
            loadTimes: [],
            resourceSizes: new Map(),
            cacheHits: 0,
            cacheMisses: 0
        };
        this.observers = new Map();
        this.init();
    }

    init() {
        this.setupPerformanceObservers();
        this.optimizeImages();
        this.implementIntelligentPrefetching();
        this.setupServiceWorkerCaching();
        this.monitorNetworkConditions();
    }

    setupPerformanceObservers() {
        // Largest Contentful Paint observer
        if ('PerformanceObserver' in window) {
            const lcpObserver = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'largest-contentful-paint') {
                        this.performanceMetrics.lcp = entry.startTime;
                        this.optimizeBasedOnLCP(entry.startTime);
                    }
                });
            });
            lcpObserver.observe({entryTypes: ['largest-contentful-paint']});

            // First Input Delay observer
            const fidObserver = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'first-input') {
                        this.performanceMetrics.fid = entry.processingStart - entry.startTime;
                        this.optimizeBasedOnFID(entry.processingStart - entry.startTime);
                    }
                });
            });
            fidObserver.observe({entryTypes: ['first-input']});

            // Cumulative Layout Shift observer
            const clsObserver = new PerformanceObserver((list) => {
                let clsValue = 0;
                list.getEntries().forEach((entry) => {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                });
                this.performanceMetrics.cls = clsValue;
            });
            clsObserver.observe({entryTypes: ['layout-shift']});
        }
    }

    optimizeImages() {
        // Implement progressive image loading
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        this.loadImageOptimized(img);
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            images.forEach(img => this.loadImageOptimized(img));
        }
    }

    loadImageOptimized(img) {
        const src = img.dataset.src;
        if (!src) return;

        // Check if WebP is supported
        const supportsWebP = this.supportsWebP();
        const optimizedSrc = supportsWebP ? src.replace(/\.(jpg|jpeg|png)$/i, '.webp') : src;

        // Create a new image to test loading
        const testImg = new Image();
        testImg.onload = () => {
            img.src = optimizedSrc;
            img.classList.add('loaded');
            this.performanceMetrics.resourceSizes.set(optimizedSrc, testImg.naturalWidth * testImg.naturalHeight);
        };
        testImg.onerror = () => {
            // Fallback to original format
            img.src = src;
            img.classList.add('loaded');
        };
        testImg.src = optimizedSrc;
    }

    supportsWebP() {
        if (this.webpSupport !== undefined) return this.webpSupport;
        
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        this.webpSupport = canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        return this.webpSupport;
    }

    implementIntelligentPrefetching() {
        // Prefetch resources based on user behavior patterns
        const links = document.querySelectorAll('a[href]');
        const prefetchCandidates = new Set();

        links.forEach(link => {
            link.addEventListener('mouseenter', () => {
                const href = link.getAttribute('href');
                if (this.shouldPrefetch(href)) {
                    this.prefetchResource(href);
                }
            });
        });

        // Prefetch critical resources on idle
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => {
                this.prefetchCriticalResources();
            });
        } else {
            setTimeout(() => this.prefetchCriticalResources(), 2000);
        }
    }

    shouldPrefetch(href) {
        // Don't prefetch external links, files, or already cached resources
        if (!href || href.startsWith('http') || href.includes('.pdf') || 
            href.includes('.zip') || this.resourceCache.has(href)) {
            return false;
        }
        return true;
    }

    prefetchResource(href) {
        if (this.resourceCache.has(href)) {
            this.performanceMetrics.cacheHits++;
            return;
        }

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = href;
        link.onload = () => {
            this.resourceCache.set(href, Date.now());
            this.performanceMetrics.cacheMisses++;
        };
        document.head.appendChild(link);
    }

    prefetchCriticalResources() {
        const criticalResources = [
            '/api/notifications.php?action=count',
            '/api/user-preferences.php',
            '/assets/css/critical.css',
            '/assets/js/app.js'
        ];

        criticalResources.forEach(resource => {
            if (!this.resourceCache.has(resource)) {
                this.prefetchResource(resource);
            }
        });
    }

    setupServiceWorkerCaching() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('SW registered:', registration);
                    this.serviceWorkerRegistration = registration;
                })
                .catch(error => console.log('SW registration failed:', error));
        }
    }

    monitorNetworkConditions() {
        if ('connection' in navigator) {
            const connection = navigator.connection;
            
            const updateNetworkStrategy = () => {
                const effectiveType = connection.effectiveType;
                
                if (effectiveType === 'slow-2g' || effectiveType === '2g') {
                    this.enableDataSaverMode();
                } else if (effectiveType === '3g') {
                    this.enableReducedQualityMode();
                } else {
                    this.enableHighQualityMode();
                }
            };

            connection.addEventListener('change', updateNetworkStrategy);
            updateNetworkStrategy(); // Initial check
        }
    }

    enableDataSaverMode() {
        document.body.classList.add('data-saver-mode');
        // Disable non-critical animations
        document.documentElement.style.setProperty('--transition-normal', '0s');
        // Reduce image quality
        this.adjustImageQuality(0.6);
    }

    enableReducedQualityMode() {
        document.body.classList.add('reduced-quality-mode');
        this.adjustImageQuality(0.8);
    }

    enableHighQualityMode() {
        document.body.classList.remove('data-saver-mode', 'reduced-quality-mode');
        this.adjustImageQuality(1.0);
    }

    adjustImageQuality(quality) {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            if (img.dataset.originalSrc) {
                const src = img.dataset.originalSrc;
                // This would typically involve server-side image optimization
                img.src = `${src}?quality=${Math.round(quality * 100)}`;
            }
        });
    }

    optimizeBasedOnLCP(lcpTime) {
        if (lcpTime > 2500) { // Poor LCP
            // Implement aggressive optimizations
            this.enableDataSaverMode();
            this.preloadCriticalResources();
        } else if (lcpTime > 1500) { // Needs improvement
            this.preloadCriticalResources();
        }
    }

    optimizeBasedOnFID(fidTime) {
        if (fidTime > 100) { // Poor FID
            // Defer non-critical JavaScript
            this.deferNonCriticalScripts();
        }
    }

    preloadCriticalResources() {
        const criticalResources = [
            { href: '/assets/css/critical.css', as: 'style' },
            { href: '/assets/js/app.js', as: 'script' },
            { href: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', as: 'style' }
        ];

        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource.href;
            link.as = resource.as;
            if (resource.as === 'style') {
                link.onload = function() { this.rel = 'stylesheet'; };
            }
            document.head.appendChild(link);
        });
    }

    deferNonCriticalScripts() {
        const scripts = document.querySelectorAll('script:not([data-critical])');
        scripts.forEach(script => {
            if (!script.defer && !script.async) {
                script.defer = true;
            }
        });
    }

    // Memory management
    cleanupResources() {
        // Clear old cache entries
        const now = Date.now();
        const maxAge = 30 * 60 * 1000; // 30 minutes

        for (const [key, timestamp] of this.resourceCache.entries()) {
            if (now - timestamp > maxAge) {
                this.resourceCache.delete(key);
            }
        }

        // Cleanup observers
        this.observers.forEach(observer => observer.disconnect());
        this.observers.clear();
    }

    // Performance reporting
    getPerformanceReport() {
        return {
            metrics: this.performanceMetrics,
            cacheStats: {
                size: this.resourceCache.size,
                hitRate: this.performanceMetrics.cacheHits / 
                        (this.performanceMetrics.cacheHits + this.performanceMetrics.cacheMisses) * 100
            },
            recommendations: this.getPerformanceRecommendations()
        };
    }

    getPerformanceRecommendations() {
        const recommendations = [];
        
        if (this.performanceMetrics.lcp > 2500) {
            recommendations.push('Consider optimizing largest contentful paint element');
        }
        
        if (this.performanceMetrics.fid > 100) {
            recommendations.push('Reduce JavaScript execution time for better interactivity');
        }
        
        if (this.performanceMetrics.cls > 0.1) {
            recommendations.push('Minimize layout shifts by reserving space for dynamic content');
        }

        return recommendations;
    }
}

// Initialize performance optimizer when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.performanceOptimizer = new AdvancedPerformanceOptimizer();
    });
} else {
    window.performanceOptimizer = new AdvancedPerformanceOptimizer();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.performanceOptimizer) {
        window.performanceOptimizer.cleanupResources();
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdvancedPerformanceOptimizer;
}
