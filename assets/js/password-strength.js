/**
 * Simple Password Strength Validator
 * Shows colored line indicator: Red (Weak) -> Yellow (Medium) -> Green (Strong)
 */
class PasswordStrengthValidator {
    constructor() {
        this.strengthLevels = {
            weak: { label: 'Weak', color: '#ef4444', class: 'weak' },
            medium: { label: 'Medium', color: '#eab308', class: 'medium' },
            strong: { label: 'Strong', color: '#22c55e', class: 'strong' }
        };
    }

    /**
     * Calculate simple password strength
     * @param {string} password - The password to evaluate
     * @returns {Object} - Strength analysis object
     */
    calculateStrength(password) {
        if (!password) {
            return {
                level: 'weak',
                strength: this.strengthLevels.weak,
                isAcceptable: false
            };
        }

        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChars = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password);
        const hasMinLength = password.length >= 8;

        // Count requirements met
        let requirementsMet = 0;
        if (hasUppercase) requirementsMet++;
        if (hasLowercase) requirementsMet++;
        if (hasNumbers) requirementsMet++;
        if (hasSpecialChars) requirementsMet++;
        if (hasMinLength) requirementsMet++;

        // Determine strength level
        let level = 'weak';
        let isAcceptable = false;

        if (requirementsMet >= 5 && hasUppercase && hasLowercase && hasNumbers && hasSpecialChars) {
            level = 'strong';
            isAcceptable = true;
        } else if (requirementsMet >= 3 && hasMinLength) {
            level = 'medium';
            isAcceptable = false;
        } else {
            level = 'weak';
            isAcceptable = false;
        }

        return {
            level,
            strength: this.strengthLevels[level],
            isAcceptable,
            requirements: {
                hasUppercase,
                hasLowercase,
                hasNumbers,
                hasSpecialChars,
                hasMinLength
            }
        };
    }

    /**
     * Create simple strength indicator (colored line)
     * @param {HTMLElement} container - Container element
     * @param {string} inputId - Password input ID
     */
    createStrengthIndicator(container, inputId) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength-line';
        indicator.innerHTML = `
            <div class="strength-line"></div>
            <span class="strength-text"></span>
        `;
        
        container.appendChild(indicator);
        
        // Bind to password input
        const passwordInput = document.getElementById(inputId);
        if (passwordInput) {
            passwordInput.addEventListener('input', (e) => {
                this.updateIndicator(indicator, e.target.value);
            });
        }
        
        return indicator;
    }

    /**
     * Update simple strength indicator
     * @param {HTMLElement} indicator 
     * @param {string} password 
     */
    updateIndicator(indicator, password) {
        const analysis = this.calculateStrength(password);
        const strengthLine = indicator.querySelector('.strength-line');
        const strengthText = indicator.querySelector('.strength-text');

        // Update line color and text
        strengthLine.style.backgroundColor = analysis.strength.color;
        strengthText.textContent = analysis.strength.label;
        strengthText.style.color = analysis.strength.color;

        // Update indicator class
        indicator.className = `password-strength-line ${analysis.strength.class}`;

        // Store analysis for form validation
        indicator.dataset.strength = analysis.level;
        indicator.dataset.acceptable = analysis.isAcceptable;
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PasswordStrengthValidator;
} else {
    window.PasswordStrengthValidator = PasswordStrengthValidator;
}
