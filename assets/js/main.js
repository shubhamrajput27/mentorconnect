/**
 * Main application JavaScript for MentorConnect
 */

class MentorConnectApp {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.sidebarOpen = window.innerWidth > 768;
        this.cache = new Map();
        this.debounceTimers = new Map();
        this.observers = new Map();
        
        this.init();
    }

    /**
     * Initialize the application
     */
    init() {
        // Use requestAnimationFrame for better performance
        requestAnimationFrame(() => {
            this.initializeTheme();
            this.initializeSidebar();
            this.initializeSearch();
            this.initializeNotifications();
            this.bindEvents();
            this.initializeIntersectionObserver();
            this.initializePerformanceMonitoring();
        });
    }

    /**
     * Initialize theme functionality
     */
    initializeTheme() {
        const themeToggle = document.querySelector('.theme-toggle');
        if (!themeToggle) return;

        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(savedTheme);

        // Only add event listener if not on landing page (landing has its own)
        if (!document.querySelector('.landing-page')) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    /**
     * Toggle theme between light and dark
     */
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add transition class
        document.documentElement.classList.add('theme-transitioning');
        
        // Update theme
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        this.updateThemeIcon(newTheme);

        // Animate button
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.style.transform = 'scale(0.9)';
            setTimeout(() => {
                themeToggle.style.transform = '';
            }, 150);
        }

        // Remove transition class
        setTimeout(() => {
            document.documentElement.classList.remove('theme-transitioning');
        }, 300);
    }

    /**
     * Update theme icon based on current theme
     */
    updateThemeIcon(theme) {
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
    }

    /**
     * Initialize sidebar functionality
     */
    initializeSidebar() {
        const sidebarToggle = document.querySelector('.sidebar-toggle, .menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        if (overlay) {
            overlay.addEventListener('click', () => this.closeSidebar());
        }

        // Close sidebar on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.sidebarOpen) {
                this.closeSidebar();
            }
        });

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && this.sidebarOpen) {
                const sidebar = document.querySelector('.sidebar');
                const menuToggle = document.querySelector('.menu-toggle, .sidebar-toggle');
                
                if (!sidebar?.contains(e.target) && !menuToggle?.contains(e.target)) {
                    this.closeSidebar();
                }
            }
        });

        this.updateSidebarState();
    }

    /**
     * Toggle sidebar open/closed
     */
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
        this.updateSidebarState();
    }

    /**
     * Close sidebar
     */
    closeSidebar() {
        this.sidebarOpen = false;
        this.updateSidebarState();
    }

    /**
     * Update sidebar visual state
     */
    updateSidebarState() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const overlay = document.querySelector('.sidebar-overlay');
        const body = document.body;

        if (!sidebar) return;

        if (this.sidebarOpen) {
            sidebar.classList.add('open');
            sidebar.classList.remove('collapsed');
            body.classList.add('sidebar-open');
            mainContent?.classList.remove('expanded');
            overlay?.classList.add('active');
        } else {
            sidebar.classList.remove('open');
            sidebar.classList.add('collapsed');
            body.classList.remove('sidebar-open');
            mainContent?.classList.add('expanded');
            overlay?.classList.remove('active');
        }
    }

    /**
     * Initialize search functionality
     */
    initializeSearch() {
        const searchInput = document.querySelector('.search-input, .search-bar input');
        if (!searchInput) return;

        searchInput.addEventListener('input', this.debounce((e) => {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                this.performSearch(query);
            } else {
                this.clearSearchResults();
            }
        }, 300));
    }

    /**
     * Perform search query
     */
    async performSearch(query) {
        const cacheKey = `search_${query}`;
        
        // Check cache first
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

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            
            // Cache results for 5 minutes
            this.cache.set(cacheKey, data);
            setTimeout(() => this.cache.delete(cacheKey), 300000);
            
            this.displaySearchResults(data);

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search error:', error);
                this.showToast('Search failed. Please try again.', 'error');
            }
        }
    }

    /**
     * Display search results
     */
    displaySearchResults(results) {
        const searchResults = document.querySelector('.search-results');
        if (!searchResults) return;

        if (!results || results.length === 0) {
            searchResults.innerHTML = '<div class="no-results">No results found</div>';
            searchResults.style.display = 'block';
            return;
        }

        const html = results.map(result => `
            <div class="search-result" data-id="${result.id}">
                <h4>${this.escapeHtml(result.title || '')}</h4>
                <p>${this.escapeHtml(result.description || '')}</p>
            </div>
        `).join('');

        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
    }

    /**
     * Clear search results
     */
    clearSearchResults() {
        const searchResults = document.querySelector('.search-results');
        if (searchResults) {
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
        }
    }

    /**
     * Initialize notifications
     */
    initializeNotifications() {
        this.loadNotifications();
        
        // Poll for new notifications every 30 seconds
        this.startNotificationPolling(30000);
    }

    /**
     * Start notification polling with exponential backoff on errors
     */
    startNotificationPolling(interval = 30000) {
        const poll = async () => {
            try {
                await this.loadNotifications();
                setTimeout(poll, interval);
            } catch (error) {
                const backoffInterval = Math.min(interval * 2, 300000);
                console.warn(`Notification polling failed, retrying in ${backoffInterval/1000}s`);
                setTimeout(poll, backoffInterval);
            }
        };

        setTimeout(poll, interval);
    }

    /**
     * Load notifications from API
     */
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

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

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

    /**
     * Update notification badge
     */
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

    /**
     * Bind global event listeners
     */
    bindEvents() {
        // Handle async buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-async')) {
                e.preventDefault();
                this.handleAsyncAction(e.target);
            }
        });

        // Handle AJAX forms
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                this.submitFormAjax(e.target);
            }
        });

        // Handle window resize
        window.addEventListener('resize', this.debounce(() => {
            this.handleResize();
        }, 250));

        // Enhance forms
        this.enhanceForms();
    }

    /**
     * Handle async button actions
     */
    async handleAsyncAction(button) {
        const action = button.dataset.action;
        const originalText = button.textContent;
        
        button.disabled = true;
        button.textContent = 'Loading...';

        try {
            const response = await fetch(`/api/${action}.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: button.dataset.id
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Action completed successfully', 'success');
            } else {
                this.showToast(result.message || 'Action failed', 'error');
            }

        } catch (error) {
            this.showToast('Network error occurred', 'error');
            console.error('Async action error:', error);
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    /**
     * Submit forms via AJAX
     */
    async submitFormAjax(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Form submitted successfully', 'success');
                
                if (result.redirect) {
                    setTimeout(() => window.location.href = result.redirect, 1000);
                } else if (result.reload) {
                    setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                this.showToast(result.message || 'Submission failed', 'error');
            }

        } catch (error) {
            this.showToast('Network error occurred', 'error');
            console.error('Form submission error:', error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        // Adjust sidebar based on screen size
        if (window.innerWidth > 768 && !this.sidebarOpen) {
            this.sidebarOpen = true;
            this.updateSidebarState();
        } else if (window.innerWidth <= 768 && this.sidebarOpen) {
            this.sidebarOpen = false;
            this.updateSidebarState();
        }
    }

    /**
     * Enhance form elements
     */
    enhanceForms() {
        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            const resizeTextarea = this.throttle(() => {
                textarea.style.height = 'auto';
                textarea.style.height = textarea.scrollHeight + 'px';
            }, 100);

            textarea.addEventListener('input', resizeTextarea);
        });

        // File input previews
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
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
                            img.style.cssText = 'max-width:200px;border-radius:8px;object-fit:cover;';
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

    /**
     * Initialize intersection observer for lazy loading
     */
    initializeIntersectionObserver() {
        if (!('IntersectionObserver' in window)) return;

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }

                    img.classList.add('loaded');
                    img.classList.remove('lazy-placeholder');
                    
                    imageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px'
        });

        // Observe all lazy images
        document.querySelectorAll('img[data-src], [data-animate-on-scroll]').forEach(img => {
            imageObserver.observe(img);
        });

        this.observers.set('images', imageObserver);
    }

    /**
     * Initialize performance monitoring
     */
    initializePerformanceMonitoring() {
        if (!('PerformanceObserver' in window)) return;

        const observer = new PerformanceObserver((list) => {
            list.getEntries().forEach((entry) => {
                if (entry.entryType === 'largest-contentful-paint') {
                    console.log('LCP:', entry.startTime);
                }
                if (entry.entryType === 'first-input') {
                    console.log('FID:', entry.processingStart - entry.startTime);
                }
                if (entry.entryType === 'layout-shift') {
                    console.log('CLS:', entry.value);
                }
            });
        });

        observer.observe({
            entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift']
        });
    }

    /**
     * Debounce function calls
     */
    debounce(func, wait) {
        return (...args) => {
            const key = func.toString();
            clearTimeout(this.debounceTimers.get(key));
            this.debounceTimers.set(key, setTimeout(() => func.apply(this, args), wait));
        };
    }

    /**
     * Throttle function calls
     */
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

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${this.escapeHtml(message)}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(toast);

        // Show animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Auto-hide after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentElement) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }

    /**
     * Get icon for toast type
     */
    getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Format timestamp for display
     */
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'just now';
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        }
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        }
        if (diff < 604800000) {
            const days = Math.floor(diff / 86400000);
            return `${days} day${days > 1 ? 's' : ''} ago`;
        }

        return date.toLocaleDateString();
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }

    /**
     * Clean up resources
     */
    destroy() {
        // Clear all timers
        this.debounceTimers.forEach(timer => clearTimeout(timer));
        this.debounceTimers.clear();

        // Disconnect all observers
        this.observers.forEach(observer => observer.disconnect());
        this.observers.clear();

        // Clear cache
        this.cache.clear();
    }
}

/**
 * Initialize the application when DOM is ready
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}

function initializeApp() {
    try {
        window.mentorConnectApp = new MentorConnectApp();
        document.body.classList.add('loaded');
        
        // Preload critical resources
        preloadCriticalResources();
    } catch (error) {
        console.error('Failed to initialize app:', error);
        initializeFallback();
    }
}

function preloadCriticalResources() {
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
    // Basic theme toggle functionality as fallback
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const icon = themeToggle.querySelector('i');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    }
}

/**
 * Global reset function for theme debugging
 */
window.resetThemeToLight = function() {
    if (window.mentorConnectApp) {
        window.mentorConnectApp.theme = 'light';
        localStorage.setItem('theme', 'light');
        document.documentElement.setAttribute('data-theme', 'light');
        window.mentorConnectApp.updateThemeIcon('light');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
        
        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) {
            themeIcon.className = 'fas fa-moon';
        }
    }
    console.log('Theme reset to light mode');
};
