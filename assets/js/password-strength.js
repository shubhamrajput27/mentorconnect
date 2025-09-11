/**
 * Password Strength Validator
 * Comprehensive password strength checking with visual feedback
 */
class PasswordStrengthValidator {
    constructor(options = {}) {
        this.options = {
            minLength: 8,
            maxLength: 128,
            requireUppercase: true,
            requireLowercase: true,
            requireNumbers: true,
            requireSpecialChars: true,
            commonPasswords: [
                'password', '123456', '123456789', 'qwerty', 'abc123', 
                'password123', 'admin', 'letmein', 'welcome', 'monkey',
                'dragon', 'master', 'shadow', 'qwertyuiop', 'asdfghjkl'
            ],
            ...options
        };
        
        this.strengthLevels = {
            0: { label: 'Very Weak', color: '#ef4444', class: 'very-weak' },
            1: { label: 'Weak', color: '#f97316', class: 'weak' },
            2: { label: 'Fair', color: '#eab308', class: 'fair' },
            3: { label: 'Good', color: '#22c55e', class: 'good' },
            4: { label: 'Strong', color: '#16a34a', class: 'strong' },
            5: { label: 'Very Strong', color: '#15803d', class: 'very-strong' }
        };
    }

    /**
     * Calculate password strength score
     * @param {string} password - The password to evaluate
     * @returns {Object} - Strength analysis object
     */
    calculateStrength(password) {
        if (!password) {
            return {
                score: 0,
                level: 0,
                feedback: ['Enter a password to see strength analysis'],
                passed: [],
                failed: ['Password is required']
            };
        }

        let score = 0;
        const feedback = [];
        const passed = [];
        const failed = [];
        const checks = this.performChecks(password);

        // Length check (0-20 points)
        if (password.length >= this.options.minLength) {
            const lengthScore = Math.min(20, (password.length - this.options.minLength) * 2 + 10);
            score += lengthScore;
            passed.push(`Length: ${password.length} characters`);
        } else {
            failed.push(`Minimum ${this.options.minLength} characters required`);
        }

        // Character variety checks (10 points each)
        if (checks.hasUppercase) {
            score += 10;
            passed.push('Contains uppercase letters');
        } else if (this.options.requireUppercase) {
            failed.push('Add uppercase letters (A-Z)');
        }

        if (checks.hasLowercase) {
            score += 10;
            passed.push('Contains lowercase letters');
        } else if (this.options.requireLowercase) {
            failed.push('Add lowercase letters (a-z)');
        }

        if (checks.hasNumbers) {
            score += 10;
            passed.push('Contains numbers');
        } else if (this.options.requireNumbers) {
            failed.push('Add numbers (0-9)');
        }

        if (checks.hasSpecialChars) {
            score += 10;
            passed.push('Contains special characters');
        } else if (this.options.requireSpecialChars) {
            failed.push('Add special characters (!@#$%^&*)');
        }

        // Pattern complexity (0-15 points)
        const patternScore = this.calculatePatternComplexity(password);
        score += patternScore;
        if (patternScore >= 10) {
            passed.push('Good character variety');
        } else {
            feedback.push('Mix different character types for better security');
        }

        // Common password check (-20 points)
        if (checks.isCommon) {
            score -= 20;
            failed.push('Avoid common passwords');
        } else {
            passed.push('Not a common password');
        }

        // Sequential/repeated character penalties
        if (checks.hasSequential) {
            score -= 10;
            failed.push('Avoid sequential characters (abc, 123)');
        }

        if (checks.hasRepeated) {
            score -= 5;
            feedback.push('Minimize repeated characters');
        }

        // Dictionary word check
        if (checks.hasDictionaryWords) {
            score -= 10;
            feedback.push('Avoid common dictionary words');
        }

        // Bonus for length
        if (password.length >= 12) {
            score += 5;
            passed.push('Good length for security');
        }
        if (password.length >= 16) {
            score += 5;
            passed.push('Excellent length');
        }

        // Calculate final level (0-5)
        const level = this.scoreToLevel(Math.max(0, score));
        
        // Add general feedback based on level
        if (level <= 1) {
            feedback.unshift('Password is too weak - consider using a passphrase');
        } else if (level === 2) {
            feedback.unshift('Password strength is fair - add more complexity');
        } else if (level === 3) {
            feedback.unshift('Good password strength');
        } else if (level >= 4) {
            feedback.unshift('Excellent password strength!');
        }

        return {
            score: Math.max(0, score),
            level,
            feedback: feedback.filter(f => f),
            passed: passed.filter(p => p),
            failed: failed.filter(f => f),
            strength: this.strengthLevels[level],
            isAcceptable: level >= 2 && failed.length === 0
        };
    }

    /**
     * Perform various password checks
     * @param {string} password 
     * @returns {Object} Check results
     */
    performChecks(password) {
        return {
            hasUppercase: /[A-Z]/.test(password),
            hasLowercase: /[a-z]/.test(password),
            hasNumbers: /\d/.test(password),
            hasSpecialChars: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(password),
            isCommon: this.options.commonPasswords.some(common => 
                password.toLowerCase().includes(common.toLowerCase())
            ),
            hasSequential: this.hasSequentialChars(password),
            hasRepeated: this.hasRepeatedChars(password),
            hasDictionaryWords: this.hasDictionaryWords(password)
        };
    }

    /**
     * Calculate pattern complexity score
     * @param {string} password 
     * @returns {number} Complexity score (0-15)
     */
    calculatePatternComplexity(password) {
        const uniqueChars = new Set(password.toLowerCase()).size;
        const totalChars = password.length;
        const ratio = uniqueChars / totalChars;
        
        let score = 0;
        
        // Character uniqueness (0-10 points)
        if (ratio >= 0.8) score += 10;
        else if (ratio >= 0.6) score += 7;
        else if (ratio >= 0.4) score += 4;
        else if (ratio >= 0.2) score += 2;
        
        // Character type mixing (0-5 points)
        const types = [
            /[a-z]/.test(password),
            /[A-Z]/.test(password),
            /\d/.test(password),
            /[^a-zA-Z\d]/.test(password)
        ].filter(Boolean).length;
        
        score += Math.min(5, types * 1.25);
        
        return Math.round(score);
    }

    /**
     * Check for sequential characters
     * @param {string} password 
     * @returns {boolean}
     */
    hasSequentialChars(password) {
        const sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'qwertyuiopasdfghjklzxcvbnm',
            '0123456789'
        ];
        
        const lower = password.toLowerCase();
        
        for (const seq of sequences) {
            for (let i = 0; i <= seq.length - 3; i++) {
                const subseq = seq.substring(i, i + 3);
                if (lower.includes(subseq) || lower.includes(subseq.split('').reverse().join(''))) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check for repeated characters
     * @param {string} password 
     * @returns {boolean}
     */
    hasRepeatedChars(password) {
        return /(.)\1{2,}/.test(password);
    }

    /**
     * Check for common dictionary words
     * @param {string} password 
     * @returns {boolean}
     */
    hasDictionaryWords(password) {
        const commonWords = [
            'love', 'hate', 'good', 'bad', 'best', 'test', 'user', 'admin',
            'login', 'pass', 'word', 'secret', 'private', 'public', 'system'
        ];
        
        const lower = password.toLowerCase();
        return commonWords.some(word => lower.includes(word));
    }

    /**
     * Convert score to strength level
     * @param {number} score 
     * @returns {number} Level (0-5)
     */
    scoreToLevel(score) {
        if (score >= 80) return 5;
        if (score >= 65) return 4;
        if (score >= 50) return 3;
        if (score >= 35) return 2;
        if (score >= 20) return 1;
        return 0;
    }

    /**
     * Generate password suggestions
     * @returns {Array} Array of password suggestions
     */
    generateSuggestions() {
        return [
            'Use a passphrase with 4+ random words',
            'Mix uppercase, lowercase, numbers, and symbols',
            'Avoid personal information (names, dates)',
            'Use at least 12 characters for better security',
            'Consider using a password manager',
            'Avoid common patterns and dictionary words'
        ];
    }

    /**
     * Create visual strength indicator
     * @param {HTMLElement} container - Container element
     * @param {string} inputId - Password input ID
     */
    createStrengthIndicator(container, inputId) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength-indicator';
        indicator.innerHTML = `
            <div class="strength-bar-container">
                <div class="strength-bar">
                    <div class="strength-fill"></div>
                </div>
                <span class="strength-label">Enter password</span>
            </div>
            <div class="strength-feedback">
                <div class="feedback-section passed-checks" style="display: none;">
                    <h4><i class="fas fa-check-circle"></i> Requirements Met:</h4>
                    <ul class="passed-list"></ul>
                </div>
                <div class="feedback-section failed-checks" style="display: none;">
                    <h4><i class="fas fa-exclamation-triangle"></i> Requirements Missing:</h4>
                    <ul class="failed-list"></ul>
                </div>
                <div class="feedback-section general-feedback" style="display: none;">
                    <h4><i class="fas fa-info-circle"></i> Suggestions:</h4>
                    <ul class="feedback-list"></ul>
                </div>
            </div>
        `;
        
        container.appendChild(indicator);
        
        // Bind to password input
        const passwordInput = document.getElementById(inputId);
        if (passwordInput) {
            passwordInput.addEventListener('input', (e) => {
                this.updateIndicator(indicator, e.target.value);
            });
            
            passwordInput.addEventListener('focus', () => {
                indicator.classList.add('active');
            });
        }
        
        return indicator;
    }

    /**
     * Update strength indicator display
     * @param {HTMLElement} indicator 
     * @param {string} password 
     */
    updateIndicator(indicator, password) {
        const analysis = this.calculateStrength(password);
        const strengthFill = indicator.querySelector('.strength-fill');
        const strengthLabel = indicator.querySelector('.strength-label');
        const passedList = indicator.querySelector('.passed-list');
        const failedList = indicator.querySelector('.failed-list');
        const feedbackList = indicator.querySelector('.feedback-list');
        const passedSection = indicator.querySelector('.passed-checks');
        const failedSection = indicator.querySelector('.failed-checks');
        const feedbackSection = indicator.querySelector('.general-feedback');

        // Update strength bar
        const percentage = (analysis.level / 5) * 100;
        strengthFill.style.width = `${percentage}%`;
        strengthFill.style.backgroundColor = analysis.strength.color;
        strengthLabel.textContent = analysis.strength.label;
        strengthLabel.style.color = analysis.strength.color;

        // Update indicator class
        indicator.className = `password-strength-indicator ${analysis.strength.class}`;

        // Update passed requirements
        if (analysis.passed.length > 0) {
            passedList.innerHTML = analysis.passed.map(item => `<li>${item}</li>`).join('');
            passedSection.style.display = 'block';
        } else {
            passedSection.style.display = 'none';
        }

        // Update failed requirements
        if (analysis.failed.length > 0) {
            failedList.innerHTML = analysis.failed.map(item => `<li>${item}</li>`).join('');
            failedSection.style.display = 'block';
        } else {
            failedSection.style.display = 'none';
        }

        // Update general feedback
        if (analysis.feedback.length > 0) {
            feedbackList.innerHTML = analysis.feedback.map(item => `<li>${item}</li>`).join('');
            feedbackSection.style.display = 'block';
        } else {
            feedbackSection.style.display = 'none';
        }

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
