/**
 * Performance Enhancements for MentorConnect
 * Advanced lazy loading, caching, and optimization features
 */

class PerformanceEnhancer {
    constructor() {
        this.imageObserver = null;
        this.contentObserver = null;
        this.cache = new Map();
        this.prefetchQueue = [];
        this.init();
    }

    init() {
        this.setupIntersectionObservers();
        this.setupResourceHints();
        this.setupServiceWorker();
        this.optimizeImages();
        this.setupPrefetching();
        this.monitorPerformance();
    }

    setupIntersectionObservers() {
        // Enhanced image lazy loading
        if ('IntersectionObserver' in window) {
            this.imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.imageObserver.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            // Content lazy loading
            this.contentObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadContent(entry.target);
                        this.contentObserver.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '100px 0px',
                threshold: 0.1
            });

            this.observeElements();
        }
    }

    observeElements() {
        // Observe images
        document.querySelectorAll('img[data-src], picture source[data-srcset]').forEach(img => {
            this.imageObserver.observe(img);
        });

        // Observe lazy content sections
        document.querySelectorAll('[data-lazy-content]').forEach(section => {
            this.contentObserver.observe(section);
        });
    }

    loadImage(img) {
        // Create a new image to preload
        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            // Apply loaded image
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }
            
            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
                img.removeAttribute('data-srcset');
            }
            
            // Add fade-in animation
            img.classList.add('loaded');
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease-in-out';
            
            requestAnimationFrame(() => {
                img.style.opacity = '1';
            });
        };
        
        imageLoader.onerror = () => {
            img.classList.add('error');
            console.warn('Failed to load image:', img.dataset.src);
        };
        
        // Start loading
        imageLoader.src = img.dataset.src || img.dataset.srcset.split(' ')[0];
    }

    loadContent(element) {
        const contentUrl = element.dataset.lazyContent;
        if (!contentUrl) return;

        // Check cache first
        if (this.cache.has(contentUrl)) {
            element.innerHTML = this.cache.get(contentUrl);
            element.classList.add('loaded');
            return;
        }

        // Show loading state
        element.classList.add('loading');
        element.innerHTML = '<div class="loading-spinner">Loading...</div>';

        // Fetch content
        fetch(contentUrl)
            .then(response => response.text())
            .then(html => {
                this.cache.set(contentUrl, html);
                element.innerHTML = html;
                element.classList.remove('loading');
                element.classList.add('loaded');
                
                // Re-observe any new lazy elements
                this.observeElements();
            })
            .catch(error => {
                console.error('Failed to load content:', error);
                element.classList.remove('loading');
                element.classList.add('error');
                element.innerHTML = '<div class="error-message">Failed to load content</div>';
            });
    }

    setupResourceHints() {
        // Preload critical resources
        const criticalResources = [
            '/assets/optimized.css',
            '/assets/optimized.js',
            '/api/user-preferences.php'
        ];

        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = resource.endsWith('.css') ? 'style' : 
                     resource.endsWith('.js') ? 'script' : 'fetch';
            link.href = resource;
            document.head.appendChild(link);
        });
    }

    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registered:', registration);
                    
                    // Update available
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                this.showUpdateNotification();
                            }
                        });
                    });
                })
                .catch(error => console.log('Service Worker registration failed:', error));
        }
    }

    optimizeImages() {
        // Convert images to WebP if supported
        const supportsWebP = this.checkWebPSupport();
        
        if (supportsWebP) {
            document.querySelectorAll('img[data-src]').forEach(img => {
                const src = img.dataset.src;
                if (src && !src.includes('.webp')) {
                    // Try WebP version first
                    const webpSrc = src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
                    img.dataset.src = webpSrc;
                    img.dataset.fallback = src;
                }
            });
        }
    }

    checkWebPSupport() {
        return new Promise(resolve => {
            const webP = new Image();
            webP.onload = webP.onerror = () => resolve(webP.height === 2);
            webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
        });
    }

    setupPrefetching() {
        // Intelligent prefetching based on user behavior
        let prefetchTimer;
        
        document.addEventListener('mouseover', (e) => {
            const link = e.target.closest('a[href]');
            if (link && this.shouldPrefetch(link.href)) {
                clearTimeout(prefetchTimer);
                prefetchTimer = setTimeout(() => {
                    this.prefetchPage(link.href);
                }, 100);
            }
        });

        // Prefetch on scroll near bottom
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    const scrollPercent = (window.scrollY + window.innerHeight) / document.body.scrollHeight;
                    if (scrollPercent > 0.8) {
                        this.prefetchNextPage();
                    }
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    shouldPrefetch(url) {
        // Don't prefetch external links, files, or already cached
        return url.startsWith(window.location.origin) && 
               !url.includes('#') && 
               !this.cache.has(url) &&
               !url.match(/\.(pdf|zip|exe|dmg)$/i);
    }

    prefetchPage(url) {
        if (this.prefetchQueue.includes(url)) return;
        
        this.prefetchQueue.push(url);
        
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
        
        // Also fetch and cache the content
        fetch(url)
            .then(response => response.text())
            .then(html => {
                this.cache.set(url, html);
            })
            .catch(() => {
                // Silently fail for prefetch
            });
    }

    prefetchNextPage() {
        // Logic to determine next likely page
        const currentPath = window.location.pathname;
        let nextUrl = null;

        if (currentPath.includes('/dashboard/')) {
            nextUrl = '/messages/';
        } else if (currentPath.includes('/messages/')) {
            nextUrl = '/mentors/browse.php';
        }

        if (nextUrl && !this.cache.has(nextUrl)) {
            this.prefetchPage(nextUrl);
        }
    }

    monitorPerformance() {
        // Core Web Vitals monitoring
        if ('PerformanceObserver' in window) {
            // Largest Contentful Paint
            new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'largest-contentful-paint') {
                        this.reportMetric('LCP', entry.startTime);
                    }
                });
            }).observe({entryTypes: ['largest-contentful-paint']});

            // First Input Delay
            new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'first-input') {
                        this.reportMetric('FID', entry.processingStart - entry.startTime);
                    }
                });
            }).observe({entryTypes: ['first-input']});

            // Cumulative Layout Shift
            let clsValue = 0;
            new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                });
                this.reportMetric('CLS', clsValue);
            }).observe({entryTypes: ['layout-shift']});
        }

        // Memory usage monitoring
        if ('memory' in performance) {
            setInterval(() => {
                const memory = performance.memory;
                if (memory.usedJSHeapSize / memory.jsHeapSizeLimit > 0.9) {
                    console.warn('High memory usage detected');
                    this.cleanupCache();
                }
            }, 30000);
        }
    }

    reportMetric(name, value) {
        // Send to analytics
        if (window.gtag) {
            gtag('event', 'web_vital', {
                name: name,
                value: Math.round(value),
                event_category: 'Performance'
            });
        }
        
        console.log(`${name}: ${Math.round(value)}ms`);
    }

    cleanupCache() {
        // Remove oldest cache entries if cache is too large
        if (this.cache.size > 50) {
            const entries = Array.from(this.cache.entries());
            const toRemove = entries.slice(0, 10);
            toRemove.forEach(([key]) => this.cache.delete(key));
        }
    }

    showUpdateNotification() {
        // Show user-friendly update notification
        const notification = document.createElement('div');
        notification.className = 'update-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <p>A new version is available!</p>
                <button onclick="window.location.reload()">Update Now</button>
                <button onclick="this.parentElement.parentElement.remove()">Later</button>
            </div>
        `;
        document.body.appendChild(notification);
    }

    // Public API for manual optimization
    optimizeElement(element) {
        if (element.tagName === 'IMG' && element.dataset.src) {
            this.loadImage(element);
        } else if (element.dataset.lazyContent) {
            this.loadContent(element);
        }
    }

    preloadResource(url, type = 'fetch') {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = type;
        link.href = url;
        document.head.appendChild(link);
    }

    clearCache() {
        this.cache.clear();
        this.prefetchQueue.length = 0;
    }
}

// Initialize performance enhancer
const performanceEnhancer = new PerformanceEnhancer();

// Export for global access
window.PerformanceEnhancer = performanceEnhancer;

// CSS for loading states
const style = document.createElement('style');
style.textContent = `
    .loading-spinner {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
        color: var(--text-secondary);
    }
    
    .loading-spinner::after {
        content: '';
        width: 20px;
        height: 20px;
        border: 2px solid var(--border-color);
        border-top: 2px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: 0.5rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .error-message {
        text-align: center;
        padding: 1rem;
        color: var(--error-color);
        font-size: 0.875rem;
    }
    
    .update-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1rem;
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    }
    
    .update-notification button {
        margin: 0.5rem 0.25rem 0 0;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: var(--radius-md);
        cursor: pointer;
        font-size: 0.875rem;
    }
    
    .update-notification button:first-of-type {
        background: var(--primary-color);
        color: white;
    }
    
    .update-notification button:last-of-type {
        background: var(--surface-color);
        color: var(--text-secondary);
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
    
    img[data-src] {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    
    img.loaded {
        opacity: 1;
    }
    
    img.error {
        opacity: 0.5;
        filter: grayscale(100%);
    }
`;
document.head.appendChild(style);
