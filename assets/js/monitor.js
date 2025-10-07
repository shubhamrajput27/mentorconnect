/* Additional Performance Optimizations for MentorConnect */

/* Preload Critical Resources */
const link1 = document.createElement('link');
link1.rel = 'preload';
link1.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap';
link1.as = 'style';
link1.crossOrigin = 'anonymous';
document.head.appendChild(link1);

const link2 = document.createElement('link');
link2.rel = 'preload';
link2.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
link2.as = 'style';
link2.crossOrigin = 'anonymous';
document.head.appendChild(link2);

/* Service Worker for Caching */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/mentorconnect/sw.js')
            .then(registration => {
                console.log('Service Worker registered successfully');
            })
            .catch(error => {
                console.log('Service Worker registration failed');
            });
    });
}

/* Lazy Loading Enhancements */
function initAdvancedLazyLoading() {
    if ('IntersectionObserver' in window) {
        const lazyImageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    lazyImageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            lazyImageObserver.observe(img);
        });
    }
}

/* Critical CSS Inlining */
function inlineCriticalCSS() {
    const criticalCSS = `
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .landing-nav { position: fixed; top: 0; width: 100%; z-index: 1000; }
        .hero { padding: 120px 0 80px; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 25px; }
    `;
    
    const style = document.createElement('style');
    style.innerHTML = criticalCSS;
    document.head.insertBefore(style, document.head.firstChild);
}

/* Performance Monitoring */
function monitorPerformance() {
    if ('PerformanceObserver' in window) {
        // Monitor Largest Contentful Paint (LCP)
        const lcpObserver = new PerformanceObserver((entryList) => {
            const lcpEntries = entryList.getEntries();
            const lastLcpEntry = lcpEntries[lcpEntries.length - 1];
            console.log('LCP:', lastLcpEntry.startTime);
        });
        lcpObserver.observe({entryTypes: ['largest-contentful-paint']});

        // Monitor First Input Delay (FID)
        const fidObserver = new PerformanceObserver((entryList) => {
            const fidEntries = entryList.getEntries();
            fidEntries.forEach(entry => {
                console.log('FID:', entry.processingStart - entry.startTime);
            });
        });
        fidObserver.observe({entryTypes: ['first-input']});

        // Monitor Cumulative Layout Shift (CLS)
        let clsScore = 0;
        const clsObserver = new PerformanceObserver((entryList) => {
            for (const entry of entryList.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsScore += entry.value;
                    console.log('CLS Score:', clsScore);
                }
            }
        });
        clsObserver.observe({entryTypes: ['layout-shift']});
    }
}

/* Resource Hints */
function addResourceHints() {
    const hints = [
        { rel: 'dns-prefetch', href: '//fonts.googleapis.com' },
        { rel: 'dns-prefetch', href: '//cdnjs.cloudflare.com' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com', crossorigin: true },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: true }
    ];

    hints.forEach(hint => {
        const link = document.createElement('link');
        Object.keys(hint).forEach(key => {
            if (key === 'crossorigin' && hint[key]) {
                link.crossOrigin = 'anonymous';
            } else {
                link[key] = hint[key];
            }
        });
        document.head.appendChild(link);
    });
}

/* Image Optimization */
function optimizeImages() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        // Add loading="lazy" for better performance
        if (!img.hasAttribute('loading')) {
            img.loading = 'lazy';
        }
        
        // Add proper alt attributes if missing
        if (!img.hasAttribute('alt')) {
            img.alt = 'MentorConnect Image';
        }
        
        // Add error handling
        img.onerror = function() {
            this.style.display = 'none';
        };
    });
}

/* Enhanced Form Validation */
function enhanceFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const isValid = validateForm(this);
            if (!isValid) {
                e.preventDefault();
                return false;
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
        
        // Email validation
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
        }
        
        // Password validation
        if (field.type === 'password' && field.value) {
            if (field.value.length < 8) {
                showFieldError(field, 'Password must be at least 8 characters long');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    errorDiv.style.cssText = `
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    `;
    
    field.style.borderColor = '#ef4444';
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

/* Accessibility Enhancements */
function enhanceAccessibility() {
    // Add keyboard navigation for buttons
    document.querySelectorAll('button, .btn').forEach(btn => {
        btn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Add aria-labels for better screen reader support
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle && !themeToggle.hasAttribute('aria-label')) {
        themeToggle.setAttribute('aria-label', 'Toggle dark mode');
    }
    
    // Add skip links for keyboard navigation
    if (!document.querySelector('.skip-link')) {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'skip-link';
        skipLink.textContent = 'Skip to main content';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            z-index: 10001;
            border-radius: 4px;
        `;
        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '6px';
        });
        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });
        document.body.insertBefore(skipLink, document.body.firstChild);
    }
}

/* Initialize all optimizations */
function initializeOptimizations() {
    initAdvancedLazyLoading();
    monitorPerformance();
    addResourceHints();
    optimizeImages();
    enhanceFormValidation();
    enhanceAccessibility();
    
    console.log('✅ All optimizations loaded successfully');
}

/* Run optimizations when DOM is ready */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeOptimizations);
} else {
    initializeOptimizations();
}

/* Add critical performance CSS */
const performanceCSS = `
    /* Loading states */
    .loading {
        opacity: 0.7;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    
    /* Smooth transitions for better UX */
    * {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    /* Optimized animations */
    .fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Skip link styles */
    .skip-link:focus {
        top: 6px !important;
    }
    
    /* Field error styles */
    .field-error {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from { opacity: 0; height: 0; }
        to { opacity: 1; height: auto; }
    }
    
    /* Performance optimizations */
    img {
        content-visibility: auto;
    }
    
    .hero-svg {
        contain: layout style paint;
    }
`;

const perfStyle = document.createElement('style');
perfStyle.innerHTML = performanceCSS;
document.head.appendChild(perfStyle);