/**
 * Image Optimization and WebP Support
 * Handles automatic WebP conversion and lazy loading
 */
class ImageOptimizer {
    constructor() {
        this.supportsWebP = false;
        this.lazyImages = [];
        this.imageObserver = null;
        this.init();
    }

    init() {
        this.checkWebPSupport().then(() => {
            this.setupLazyLoading();
            this.optimizeExistingImages();
            this.setupImageErrorHandling();
        });
    }

    // Check WebP support
    checkWebPSupport() {
        return new Promise((resolve) => {
            const webP = new Image();
            webP.onload = webP.onerror = () => {
                this.supportsWebP = (webP.height === 2);
                resolve();
            };
            webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
        });
    }

    // Setup intersection observer for lazy loading
    setupLazyLoading() {
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

            // Observe all lazy images
            document.querySelectorAll('img[data-src]').forEach(img => {
                this.imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            document.querySelectorAll('img[data-src]').forEach(img => {
                this.loadImage(img);
            });
        }
    }

    // Load individual image with WebP optimization
    loadImage(img) {
        const originalSrc = img.dataset.src;
        if (!originalSrc) return;

        // Try WebP version first if supported
        if (this.supportsWebP && !originalSrc.includes('.svg')) {
            const webpSrc = this.getWebPVersion(originalSrc);
            this.loadWithFallback(img, webpSrc, originalSrc);
        } else {
            img.src = originalSrc;
            img.removeAttribute('data-src');
        }

        // Add fade-in animation
        img.addEventListener('load', () => {
            img.classList.add('loaded');
        });
    }

    // Generate WebP version path
    getWebPVersion(src) {
        const extension = src.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png'].includes(extension)) {
            return src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
        }
        return src;
    }

    // Load with fallback mechanism
    loadWithFallback(img, webpSrc, fallbackSrc) {
        const webpImg = new Image();
        
        webpImg.onload = () => {
            img.src = webpSrc;
            img.removeAttribute('data-src');
        };
        
        webpImg.onerror = () => {
            img.src = fallbackSrc;
            img.removeAttribute('data-src');
        };
        
        webpImg.src = webpSrc;
    }

    // Optimize existing images
    optimizeExistingImages() {
        document.querySelectorAll('img:not([data-src])').forEach(img => {
            if (img.complete && img.naturalHeight !== 0) {
                this.addImageOptimizations(img);
            } else {
                img.addEventListener('load', () => {
                    this.addImageOptimizations(img);
                });
            }
        });
    }

    // Add optimization attributes to images
    addImageOptimizations(img) {
        // Add loading="lazy" for native lazy loading support
        if ('loading' in HTMLImageElement.prototype) {
            img.loading = 'lazy';
        }

        // Add decoding="async" for better performance
        img.decoding = 'async';

        // Add proper alt text if missing
        if (!img.alt && img.dataset.alt) {
            img.alt = img.dataset.alt;
        }
    }

    // Setup error handling for broken images
    setupImageErrorHandling() {
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                this.handleImageError(e.target);
            }
        }, true);
    }

    // Handle image loading errors
    handleImageError(img) {
        // Try fallback image if available
        if (img.dataset.fallback) {
            img.src = img.dataset.fallback;
            img.removeAttribute('data-fallback');
            return;
        }

        // Add error class for styling
        img.classList.add('image-error');
        
        // Replace with placeholder if no fallback
        if (!img.classList.contains('no-placeholder')) {
            this.addPlaceholder(img);
        }
    }

    // Add placeholder for broken images
    addPlaceholder(img) {
        const placeholder = document.createElement('div');
        placeholder.className = 'image-placeholder';
        placeholder.innerHTML = `
            <div class="placeholder-content">
                <i class="fas fa-image"></i>
                <span>Image not available</span>
            </div>
        `;
        
        // Copy dimensions if available
        if (img.width) placeholder.style.width = img.width + 'px';
        if (img.height) placeholder.style.height = img.height + 'px';
        
        img.parentNode.replaceChild(placeholder, img);
    }

    // Preload critical images
    preloadCriticalImages() {
        const criticalImages = document.querySelectorAll('img[data-critical]');
        criticalImages.forEach(img => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = img.src || img.dataset.src;
            document.head.appendChild(link);
        });
    }

    // Convert images to WebP on upload (client-side)
    convertToWebP(file, quality = 0.8) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = () => {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                canvas.toBlob(resolve, 'image/webp', quality);
            };

            img.src = URL.createObjectURL(file);
        });
    }

    // Responsive image loading based on viewport
    loadResponsiveImage(img) {
        const sources = {
            small: img.dataset.srcSmall,
            medium: img.dataset.srcMedium,
            large: img.dataset.srcLarge
        };

        let selectedSrc;
        if (window.innerWidth <= 768 && sources.small) {
            selectedSrc = sources.small;
        } else if (window.innerWidth <= 1200 && sources.medium) {
            selectedSrc = sources.medium;
        } else if (sources.large) {
            selectedSrc = sources.large;
        } else {
            selectedSrc = img.dataset.src;
        }

        if (this.supportsWebP) {
            selectedSrc = this.getWebPVersion(selectedSrc);
        }

        this.loadWithFallback(img, selectedSrc, img.dataset.src);
    }
}

// CSS for image optimization
const imageOptimizerCSS = `
    img[data-src] {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    
    img.loaded {
        opacity: 1;
    }
    
    .image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        min-height: 200px;
        color: #6b7280;
    }
    
    .placeholder-content {
        text-align: center;
    }
    
    .placeholder-content i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .image-error {
        filter: grayscale(100%);
        opacity: 0.5;
    }
    
    /* Progressive enhancement for slow connections */
    @media (prefers-reduced-data: reduce) {
        img[data-src] {
            display: none;
        }
        
        .image-placeholder {
            display: flex;
        }
    }
`;

// Inject CSS
const style = document.createElement('style');
style.textContent = imageOptimizerCSS;
document.head.appendChild(style);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.imageOptimizer = new ImageOptimizer();
    });
} else {
    window.imageOptimizer = new ImageOptimizer();
}
