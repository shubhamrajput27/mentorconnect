// Optimized Main Application JavaScript

class MentorConnectApp {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.sidebarOpen = window.innerWidth > 768;
        this.cache = new Map();
        this.debounceTimers = new Map();
        this.observers = new Map();
        this.init();
    }

    init() {
        // Use requestAnimationFrame for smooth initialization
        requestAnimationFrame(() => {
            this.initializeTheme();
            this.initializeSidebar();
            this.initializeSearch();
            this.initializeNotifications();
            this.initializeAnimations();
            this.bindEvents();
            this.initializeIntersectionObserver();
            this.initializePerformanceMonitoring();
        });
    }

    initializeTheme() {
        const themeToggle = document.querySelector('.theme-toggle');
        if (!themeToggle) return;

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Add smooth transition
            document.documentElement.style.transition = 'all 0.3s ease';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            this.updateThemeIcon(newTheme);
            
            // Remove transition after animation
            setTimeout(() => {
                document.documentElement.style.transition = '';
            }, 300);
        });
    }

    updateThemeIcon(theme) {
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    toggleTheme() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.theme);
        document.documentElement.setAttribute('data-theme', this.theme);
        
        this.updateThemeIcon(this.theme);

        // Save theme preference to server
        this.saveThemePreference();
    }

    async saveThemePreference() {
        try {
            await fetch('/api/preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    theme: this.theme
                })
            });
        } catch (error) {
            console.error('Failed to save theme preference:', error);
        }
    }

    initializeSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth <= 768) {
            sidebar?.classList.add('collapsed');
            mainContent?.classList.add('expanded');
            this.sidebarOpen = false;
        }
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (this.sidebarOpen) {
            sidebar?.classList.add('collapsed');
            mainContent?.classList.add('expanded');
        } else {
            sidebar?.classList.remove('collapsed');
            mainContent?.classList.remove('expanded');
        }
        
        this.sidebarOpen = !this.sidebarOpen;
    }

    initializeSearch() {
        const searchInput = document.querySelector('.search-bar input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.performSearch(e.target.value);
            }, 300));
        }
    }

    async performSearch(query) {
        if (query.length < 2) {
            this.clearSearchResults();
            return;
        }
        
        // Check cache first
        const cacheKey = `search_${query}`;
        if (this.cache.has(cacheKey)) {
            this.displaySearchResults(this.cache.get(cacheKey));
            return;
        }
        
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);
            
            const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`, {
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const results = await response.json();
            
            // Cache results for 5 minutes
            this.cache.set(cacheKey, results);
            setTimeout(() => this.cache.delete(cacheKey), 300000);
            
            this.displaySearchResults(results);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search failed:', error);
                this.showToast('Search failed. Please try again.', 'error');
            }
        }
    }

    displaySearchResults(results) {
        // Implementation for search results display
        console.log('Search results:', results);
    }

    initializeNotifications() {
        this.loadNotifications();
        // Use exponential backoff for polling
        this.startNotificationPolling();
    }
    
    startNotificationPolling(interval = 30000) {
        const poll = async () => {
            try {
                await this.loadNotifications();
                // Reset to normal interval on success
                setTimeout(poll, interval);
            } catch (error) {
                // Exponential backoff on error
                const backoffInterval = Math.min(interval * 2, 300000); // Max 5 minutes
                console.warn(`Notification polling failed, retrying in ${backoffInterval/1000}s`);
                setTimeout(poll, backoffInterval);
            }
        };
        
        // Start polling
        setTimeout(poll, interval);
    }

    async loadNotifications() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);
            
            const response = await fetch('/api/notifications.php?action=count', {
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            if (data.success) {
                this.updateNotificationBadge(data.unread_count);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Failed to load notifications:', error);
                throw error;
            }
        }
    }

    updateNotificationBadge(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    bindEvents() {
        // Theme toggle
        document.addEventListener('click', (e) => {
            if (e.target.closest('.theme-toggle')) {
                this.toggleTheme();
            }
        });

        // Sidebar toggle
        document.addEventListener('click', (e) => {
            if (e.target.closest('.menu-toggle')) {
                this.toggleSidebar();
            }
        });

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }

        // Add smooth page transitions
        this.initializePageTransitions();
        
        // Add hover animations to interactive elements
        this.initializeHoverEffects();

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const menuToggle = document.querySelector('.menu-toggle');
                
                if (!sidebar?.contains(e.target) && !menuToggle?.contains(e.target)) {
                    if (this.sidebarOpen) {
                        this.toggleSidebar();
                    }
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && !this.sidebarOpen) {
                this.toggleSidebar();
            } else if (window.innerWidth <= 768 && this.sidebarOpen) {
                this.toggleSidebar();
            }
        });

        // Form enhancements
        this.enhanceForms();
    }

    initializePageTransitions() {
        // Add smooth page transitions
    }

    initializeHoverEffects() {
        // Add hover animations to interactive elements
    }

    enhanceForms() {
        // Auto-resize textareas with throttling
        document.querySelectorAll('textarea').forEach(textarea => {
            const resizeTextarea = this.throttle(() => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }, 100);
            
            textarea.addEventListener('input', resizeTextarea);
        });

        // Optimized file upload preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    // Validate file size (max 10MB)
                    if (file.size > 10 * 1024 * 1024) {
                        this.showToast('File size must be less than 10MB', 'error');
                        input.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const preview = document.querySelector('.file-preview');
                        if (preview) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.alt = 'Preview';
                            img.style.cssText = 'max-width: 200px; border-radius: 8px; object-fit: cover;';
                            img.loading = 'lazy';
                            preview.innerHTML = '';
                            preview.appendChild(img);
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    }

    // Utility methods
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
            toast.style.animation = 'slideInRight 0.3s ease-out';
        }, 100);
        
        // Remove after delay
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease-in reverse';
            setTimeout(() => {
                if (toast.parentElement) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }

    getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    async makeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, mergedOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('Request failed:', error);
            this.showToast('Request failed. Please try again.', 'error');
            throw error;
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        // Less than a minute
        if (diff < 60000) {
            return 'just now';
        }
        
        // Less than an hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        }
        
        // Less than a day
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        }
        
        // Less than a week
        if (diff < 604800000) {
            const days = Math.floor(diff / 86400000);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }
        
        // Format as date
        return date.toLocaleDateString();
    }

    validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        return isValid;
    }

    showFieldError(field, message) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        formGroup.classList.add('error');
        
        let errorElement = formGroup.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            field.parentNode.insertAdjacentElement('afterend', errorElement);
        }
        
        errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }

    clearFieldError(field) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        formGroup.classList.remove('error');
        const errorElement = formGroup.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    // Utility methods for performance
    debounce(func, wait) {
        return (...args) => {
            const key = func.toString();
            clearTimeout(this.debounceTimers.get(key));
            this.debounceTimers.set(key, setTimeout(() => func.apply(this, args), wait));
        };
    }
    
    throttle(func, limit) {
        let inThrottle;
        return (...args) => {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    initializeIntersectionObserver() {
        // Lazy load images and animations
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                }
            });
        }, { rootMargin: '50px' });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
        
        this.observers.set('images', imageObserver);
    }
    
    initializePerformanceMonitoring() {
        // Monitor performance metrics
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'navigation') {
                        console.log('Page Load Time:', entry.loadEventEnd - entry.loadEventStart);
                    }
                });
            });
            observer.observe({ entryTypes: ['navigation'] });
        }
    }
    
    clearSearchResults() {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '';
        }
    }
    
    // Cleanup method
    destroy() {
        // Clear all timers
        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();
        
        // Disconnect observers
        this.observers.forEach(observer => observer.disconnect());
        this.observers.clear();
        
        // Clear cache
        this.cache.clear();
    }
}

// Optimized app initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}

function initializeApp() {
    // Initialize app with error handling
    try {
        window.mentorConnectApp = new MentorConnectApp();
        
        // Add loading animation
        document.body.classList.add('loaded');
        
        // Preload critical resources
        preloadCriticalResources();
        
    } catch (error) {
        console.error('Failed to initialize app:', error);
        // Fallback for basic functionality
        initializeFallback();
    }
}

function preloadCriticalResources() {
    // Preload important API endpoints
    const criticalEndpoints = [
        '/api/notifications.php?action=count',
        '/api/user-preferences.php'
    ];
    
    criticalEndpoints.forEach(url => {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    });
}

function initializeFallback() {
    // Basic theme toggle fallback
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
}

// Add optimized CSS for new animations
if (!document.querySelector('#app-styles')) {
    const style = document.createElement('style');
    style.id = 'app-styles';
    style.textContent = `
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .animate-slide-in {
            animation: slideInLeft 0.4s ease-out forwards;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        .theme-transitioning * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
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
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .toast-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
        }
        
        .toast-close:hover {
            background: var(--surface-color);
            color: var(--text-primary);
        }
        
        .toast-success { border-left: 4px solid var(--success-color); }
        .toast-error { border-left: 4px solid var(--error-color); }
        .toast-warning { border-left: 4px solid var(--warning-color); }
        .toast-info { border-left: 4px solid var(--info-color); }
    `;
    document.head.appendChild(style);
}
