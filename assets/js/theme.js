/**
 * Theme Management for MentorConnect
 * Handles light/dark theme switching across all pages
 */

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.init();
    }

    init() {
        // Set initial theme
        this.applyTheme(this.currentTheme);
        
        // Bind event listeners
        this.bindThemeToggle();
        
        // Update theme icon
        this.updateThemeIcon(this.currentTheme);
    }

    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        this.setStoredTheme(theme);
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        
        // Add transition class for smooth animation
        document.documentElement.classList.add('theme-transitioning');
        
        // Apply new theme
        this.applyTheme(newTheme);
        
        // Update icon
        this.updateThemeIcon(newTheme);
        
        // Add button animation
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.style.transform = 'scale(0.9)';
            setTimeout(() => {
                themeToggle.style.transform = '';
            }, 150);
        }
        
        // Remove transition class after animation
        setTimeout(() => {
            document.documentElement.classList.remove('theme-transitioning');
        }, 300);

        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { theme: newTheme } 
        }));
    }

    updateThemeIcon(theme) {
        const themeIcon = document.getElementById('theme-icon');
        const themeIcons = document.querySelectorAll('.theme-toggle i');
        
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        // Update all theme toggle icons
        themeIcons.forEach(icon => {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    }

    bindThemeToggle() {
        const themeToggles = document.querySelectorAll('.theme-toggle');
        
        themeToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleTheme();
            });
        });
    }

    // Method to get current theme
    getCurrentTheme() {
        return this.currentTheme;
    }

    // Method to set specific theme
    setTheme(theme) {
        if (theme === 'light' || theme === 'dark') {
            this.applyTheme(theme);
            this.updateThemeIcon(theme);
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme manager
    window.themeManager = new ThemeManager();
    
    // Add global function for backward compatibility
    window.toggleTheme = function() {
        window.themeManager.toggleTheme();
    };
    
    // Add reset to light mode function
    window.resetThemeToLight = function() {
        window.themeManager.setTheme('light');
        console.log('Theme reset to light mode');
    };
});

// Also initialize immediately if DOM is already loaded
if (document.readyState !== 'loading') {
    window.themeManager = new ThemeManager();
    
    window.toggleTheme = function() {
        window.themeManager.toggleTheme();
    };
    
    window.resetThemeToLight = function() {
        window.themeManager.setTheme('light');
        console.log('Theme reset to light mode');
    };
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
