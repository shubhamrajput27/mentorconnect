// Advanced Performance Optimizer for MentorConnect
class PerformanceOptimizer {
    constructor() {
        this.preloadedResources = new Set();
        this.resourceHints = new Map();
        this.intersectionObserver = null;
        this.idleCallback = null;
        this.metrics = {
            preloads: 0,
            prefetches: 0,
            lazy_loads: 0,
            cache_hits: 0
        };
        this.init();
    }
    
    init() {
        // Initialize in the next frame to avoid blocking
        requestAnimationFrame(() => {
            this.setupIntersectionObserver();
            this.preloadCriticalResources();
            this.optimizeImages();
            this.setupPrefetching();
            this.initializeIdleOptimizations();
            this.setupPerformanceMonitoring();
        });
    }
    
    preloadCriticalResources() {
        const criticalResources = [
            { href: '/api/notifications.php?action=count', as: 'fetch', type: 'api' },
            { href: '/api/user-preferences.php', as: 'fetch', type: 'api' },
            { href: '/assets/css/critical.css', as: 'style', type: 'style' },
            { href: '/assets/js/modules/dashboard.js', as: 'script', type: 'script' }
        ];
        
        criticalResources.forEach(resource => {
            if (!this.preloadedResources.has(resource.href)) {
                this.preloadResource(resource);
                this.metrics.preloads++;
            }
        });
    }
    
    preloadResource(resource) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.href = resource.href;
        link.as = resource.as;
        
        // Add appropriate attributes based on resource type
        if (resource.as === 'fetch') {
            link.crossOrigin = 'anonymous';
        } else if (resource.as === 'script') {
            link.crossOrigin = 'anonymous';
        } else if (resource.as === 'style') {
            link.onload = () => {
                console.log(`Preloaded CSS: ${resource.href}`);
            };
        }
        
        // Add error handling
        link.onerror = () => {
            console.warn(`Failed to preload: ${resource.href}`);
        };
        
        document.head.appendChild(link);
        this.preloadedResources.add(resource.href);
    }
    
    setupPrefetching() {
        // Prefetch resources on hover with throttling
        let hoverTimeout;
        
        document.addEventListener('mouseover', (e) => {
            if (hoverTimeout) return;
            
            const link = e.target.closest('a[href]');
            if (link && this.shouldPrefetch(link.href)) {
                hoverTimeout = setTimeout(() => {
                    this.prefetchPage(link.href);
                    hoverTimeout = null;
                }, 100); // Small delay to avoid prefetching on quick mouse movements
            }
        }, { passive: true });
        
        // Prefetch on touchstart for mobile
        document.addEventListener('touchstart', (e) => {
            const link = e.target.closest('a[href]');
            if (link && this.shouldPrefetch(link.href)) {
                this.prefetchPage(link.href);
            }
        }, { passive: true });
        
        // Prefetch links in viewport
        if ('IntersectionObserver' in window) {
            const linkObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const link = entry.target;
                        if (this.shouldPrefetch(link.href)) {
                            this.prefetchPage(link.href);
                            linkObserver.unobserve(link);
                        }
                    }
                });
            }, { rootMargin: '100px' });
            
            // Observe important navigation links
            document.querySelectorAll('nav a[href], .nav-links a[href]').forEach(link => {
                linkObserver.observe(link);
            });
        }
    }
    
    shouldPrefetch(url) {
        // Don't prefetch external links, file downloads, or already prefetched
        return !this.preloadedResources.has(url) &&
               !url.includes('logout') &&
               !url.includes('download') &&
               !url.match(/\.(pdf|zip|exe|dmg)$/i) &&
               url.startsWith(window.location.origin);
    }
    
    prefetchPage(url) {
        if (this.preloadedResources.has(url)) return;
        
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.as = 'document';
        
        link.onload = () => {
            console.log(`Prefetched: ${url}`);
        };
        
        document.head.appendChild(link);
        this.preloadedResources.add(url);
        this.metrics.prefetches++;
    }
    
    optimizeImages() {
        // Enhanced image optimization
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            // Add loading attribute if not present
            if (!img.hasAttribute('loading')) {
                img.loading = 'lazy';
            }
            
            // Add decode hint
            img.decoding = 'async';
            
            // Add dimension attributes if missing (prevent layout shift)
            if (!img.width || !img.height) {
                img.onload = () => {
                    if (!img.width) img.width = img.naturalWidth;
                    if (!img.height) img.height = img.naturalHeight;
                };
            }
            
            // Optimize high-resolution images
            if (window.devicePixelRatio > 1 && img.src && !img.srcset) {
                const src = img.src;
                const extension = src.split('.').pop();
                const baseName = src.replace(`.${extension}`, '');
                
                // Check if high-res version exists
                const highResSrc = `${baseName}@2x.${extension}`;
                img.srcset = `${src} 1x, ${highResSrc} 2x`;
            }
        });
    }
    
    setupIntersectionObserver() {
        if (!('IntersectionObserver' in window)) return;
        
        // Enhanced lazy loading with fade-in effect
        this.intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    
                    if (target.tagName === 'IMG' && target.dataset.src) {
                        this.loadImage(target);
                    } else if (target.classList.contains('lazy-section')) {
                        this.loadSection(target);
                    }
                    
                    this.intersectionObserver.unobserve(target);
                    this.metrics.lazy_loads++;
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.1
        });
        
        // Observe lazy images and sections
        document.querySelectorAll('img[data-src], .lazy-section').forEach(element => {
            this.intersectionObserver.observe(element);
        });
    }
    
    loadImage(img) {
        const src = img.dataset.src;
        if (!src) return;
        
        // Create a new image to test loading
        const tempImg = new Image();
        tempImg.onload = () => {
            img.src = src;
            img.removeAttribute('data-src');
            img.classList.add('loaded');
            img.classList.remove('lazy-placeholder');
            
            // Add fade-in animation
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
            requestAnimationFrame(() => {
                img.style.opacity = '1';
            });
        };
        
        tempImg.onerror = () => {
            img.classList.add('error');
            console.warn(`Failed to load image: ${src}`);
        };
        
        tempImg.src = src;
    }
    
    loadSection(section) {
        section.classList.add('loaded');
        section.classList.remove('lazy-section');
        
        // Trigger any section-specific loading
        const event = new CustomEvent('sectionLoaded', { detail: { section } });
        section.dispatchEvent(event);
    }
    
    initializeIdleOptimizations() {
        if ('requestIdleCallback' in window) {
            this.idleCallback = requestIdleCallback(() => {
                this.optimizeForIdle();
            });
        } else {
            // Fallback for browsers without requestIdleCallback
            setTimeout(() => {
                this.optimizeForIdle();
            }, 1000);
        }
    }
    
    optimizeForIdle() {
        // Preload non-critical resources during idle time
        const nonCriticalResources = [
            '/assets/js/modules/analytics.js',
            '/assets/js/modules/chat.js',
            '/assets/css/animations.css'
        ];
        
        nonCriticalResources.forEach(href => {
            if (!this.preloadedResources.has(href)) {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = href;
                document.head.appendChild(link);
                this.preloadedResources.add(href);
            }
        });
        
        // Clean up old cache entries
        this.cleanupCache();
        
        // Warm up commonly used APIs
        this.warmupAPIs();
    }
    
    cleanupCache() {
        // Clean up preloaded resources that are no longer needed
        const unusedResources = [];
        this.preloadedResources.forEach(resource => {
            const links = document.querySelectorAll(`link[href="${resource}"]`);
            if (links.length === 0) {
                unusedResources.push(resource);
            }
        });
        
        unusedResources.forEach(resource => {
            this.preloadedResources.delete(resource);
        });
    }
    
    warmupAPIs() {
        // Pre-warm frequently used API endpoints
        const apiEndpoints = [
            '/api/notifications.php?action=count',
            '/api/search.php?q=' // Empty search to warm up
        ];
        
        apiEndpoints.forEach(endpoint => {
            fetch(endpoint, {
                method: 'GET',
                headers: {
                    'X-Prefetch': 'true'
                }
            }).catch(() => {
                // Silently ignore prefetch errors
            });
        });
    }
    
    setupPerformanceMonitoring() {
        // Monitor and report performance metrics
        if ('PerformanceObserver' in window) {
            // Monitor Largest Contentful Paint
            const lcpObserver = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    console.log('LCP:', entry.startTime);
                    // Send to analytics if needed
                });
            });
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            
            // Monitor First Input Delay
            const fidObserver = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    console.log('FID:', entry.processingStart - entry.startTime);
                });
            });
            fidObserver.observe({ entryTypes: ['first-input'] });
            
            // Monitor Cumulative Layout Shift
            const clsObserver = new PerformanceObserver((list) => {
                let clsValue = 0;
                list.getEntries().forEach((entry) => {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                });
                console.log('CLS:', clsValue);
            });
            clsObserver.observe({ entryTypes: ['layout-shift'] });
        }
    }
    
    getMetrics() {
        return {
            ...this.metrics,
            preloaded_resources: this.preloadedResources.size,
            observer_active: this.intersectionObserver !== null
        };
    }
    
    destroy() {
        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
        }
        
        if (this.idleCallback) {
            cancelIdleCallback(this.idleCallback);
        }
        
        this.preloadedResources.clear();
        this.resourceHints.clear();
    }
}

// Initialize performance optimizer when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.performanceOptimizer = new PerformanceOptimizer();
    });
} else {
    window.performanceOptimizer = new PerformanceOptimizer();
}

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (window.performanceOptimizer) {
        window.performanceOptimizer.destroy();
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PerformanceOptimizer;
}
