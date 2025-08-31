// Landing Page JavaScript - Optimized
class LandingPage {
    constructor() {
        this.init();
    }

    init() {
        this.initializeTheme();
        this.initializeNavigation();
        this.initializeSmoothScrolling();
        this.initializeAnimations();
        this.bindEvents();
    }

    initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const html = document.documentElement;
        
        html.setAttribute('data-theme', savedTheme);
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.updateThemeIcon(savedTheme);
            });
        } else {
            this.updateThemeIcon(savedTheme);
        }
    }

    updateThemeIcon(theme) {
        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add smooth transition
        html.style.transition = 'all 0.3s ease';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        this.updateThemeIcon(newTheme);
        
        // Remove transition after animation
        setTimeout(() => {
            html.style.transition = '';
        }, 300);
    }

    initializeNavigation() {
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const navLinks = document.querySelector('.nav-links');
        
        if (mobileToggle && navLinks) {
            mobileToggle.addEventListener('click', () => {
                navLinks.classList.toggle('show');
                
                // Update icon
                const icon = mobileToggle.querySelector('i');
                if (icon) {
                    icon.className = navLinks.classList.contains('show') 
                        ? 'fas fa-times' 
                        : 'fas fa-bars';
                }
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileToggle.contains(e.target) && !navLinks.contains(e.target)) {
                    navLinks.classList.remove('show');
                    const icon = mobileToggle.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-bars';
                    }
                }
            });

            // Close menu when clicking on links
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('show');
                    const icon = mobileToggle.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-bars';
                    }
                });
            });
        }
    }

    initializeSmoothScrolling() {
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(anchor.getAttribute('href'));
                
                if (target) {
                    const offsetTop = target.offsetTop - 80; // Account for fixed nav
                    
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    initializeAnimations() {
        // Intersection Observer for animations
        const animateOnScroll = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    animateOnScroll.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observe elements for animation
        const elementsToAnimate = document.querySelectorAll(
            '.feature-card, .step, .hero-content, .section-header'
        );
        
        elementsToAnimate.forEach((el) => {
            animateOnScroll.observe(el);
        });

        // Add stagger animation to feature cards
        const featureCards = document.querySelectorAll('.feature-card');
        featureCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });

        // Add stagger animation to steps
        const steps = document.querySelectorAll('.step');
        steps.forEach((step, index) => {
            step.style.animationDelay = `${index * 0.2}s`;
        });
    }

    bindEvents() {
        // Theme toggle
        document.addEventListener('click', (e) => {
            if (e.target.closest('.theme-toggle')) {
                this.toggleTheme();
            }
        });

        // Add scroll effect to navigation
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            const nav = document.querySelector('.landing-nav');
            
            if (nav) {
                if (currentScroll > lastScroll && currentScroll > 100) {
                    // Scrolling down
                    nav.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up
                    nav.style.transform = 'translateY(0)';
                }
                
                // Add background blur when scrolled
                if (currentScroll > 50) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            }
            
            lastScroll = currentScroll;
        });

        // Add parallax effect to hero background
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            
            if (hero) {
                const rate = scrolled * -0.5;
                hero.style.transform = `translateY(${rate}px)`;
            }
        });

        // Add hover effects with performance optimization
        this.addHoverEffects();
    }

    addHoverEffects() {
        // Use event delegation for better performance
        document.addEventListener('mouseenter', (e) => {
            if (e.target.closest('.feature-card')) {
                this.addRippleEffect(e.target.closest('.feature-card'), e);
            }
        }, true);

        // Button ripple effects
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn')) {
                this.addButtonRipple(e.target.closest('.btn'), e);
            }
        });
    }

    addRippleEffect(element, event) {
        const ripple = document.createElement('div');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple-effect');

        element.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    addButtonRipple(button, event) {
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;

        button.style.position = 'relative';
        button.style.overflow = 'hidden';
        button.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    // Performance optimization methods
    debounce(func, wait) {
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

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.landingPage = new LandingPage();
    });
} else {
    window.landingPage = new LandingPage();
}

// Add CSS for new animations
if (!document.querySelector('#landing-animations')) {
    const style = document.createElement('style');
    style.id = 'landing-animations';
    style.textContent = `
        .animate-fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease-out forwards;
        }

        @keyframes fadeInUp {
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

        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        .landing-nav {
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        .landing-nav.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-md);
        }

        [data-theme="dark"] .landing-nav.scrolled {
            background: rgba(15, 23, 42, 0.98);
        }

        /* Reduce motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            .animate-fade-in {
                animation: none;
                opacity: 1;
                transform: none;
            }
            
            .hero {
                transform: none !important;
            }
        }
    `;
    document.head.appendChild(style);
}

// Global function for backward compatibility
window.toggleTheme = function() {
    if (window.landingPage) {
        window.landingPage.toggleTheme();
    }
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LandingPage;
}
