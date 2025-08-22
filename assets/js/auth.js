// Authentication JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {
    initializeAuthForm();
    initializePasswordValidation();
    initializeFormValidation();
});

function initializeAuthForm() {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }
    
    if (signupForm) {
        signupForm.addEventListener('submit', handleSignupSubmit);
    }
}

function handleLoginSubmit(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Add loading state
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Form will submit normally, but we show loading state
    setTimeout(() => {
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    }, 3000);
}

function handleSignupSubmit(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Validate passwords match
    const password = form.querySelector('#password').value;
    const confirmPassword = form.querySelector('#confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        showFieldError('confirm_password', 'Passwords do not match');
        return;
    }
    
    // Add loading state
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Form will submit normally, but we show loading state
    setTimeout(() => {
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    }, 3000);
}

function initializePasswordValidation() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            validatePasswordStrength(this.value);
        });
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }
}

function validatePasswordStrength(password) {
    const strengthIndicator = document.getElementById('passwordStrength');
    if (!strengthIndicator) return;
    
    let strength = 0;
    let feedback = [];
    
    // Length check
    if (password.length >= 8) strength++;
    else feedback.push('At least 8 characters');
    
    // Uppercase check
    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('One uppercase letter');
    
    // Lowercase check
    if (/[a-z]/.test(password)) strength++;
    else feedback.push('One lowercase letter');
    
    // Number check
    if (/\d/.test(password)) strength++;
    else feedback.push('One number');
    
    // Special character check
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    else feedback.push('One special character');
    
    // Update strength indicator
    strengthIndicator.className = 'password-strength';
    if (password.length === 0) {
        strengthIndicator.style.display = 'none';
    } else {
        strengthIndicator.style.display = 'block';
        if (strength < 3) {
            strengthIndicator.classList.add('weak');
            strengthIndicator.innerHTML = '<small style="color: var(--error-color);">Weak - Missing: ' + feedback.join(', ') + '</small>';
        } else if (strength < 5) {
            strengthIndicator.classList.add('medium');
            strengthIndicator.innerHTML = '<small style="color: var(--warning-color);">Medium - Missing: ' + feedback.join(', ') + '</small>';
        } else {
            strengthIndicator.classList.add('strong');
            strengthIndicator.innerHTML = '<small style="color: var(--success-color);">Strong password!</small>';
        }
    }
}

function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const confirmGroup = document.getElementById('confirm_password').closest('.form-group');
    
    if (confirmPassword.length === 0) {
        clearFieldError('confirm_password');
        return;
    }
    
    if (password !== confirmPassword) {
        showFieldError('confirm_password', 'Passwords do not match');
    } else {
        showFieldSuccess('confirm_password', 'Passwords match');
    }
}

function initializeFormValidation() {
    // Real-time email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            validateEmail(this.value, 'email');
        });
    }
    
    // Username validation
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('blur', function() {
            validateUsername(this.value);
        });
    }
    
    // Phone validation
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    }
}

function validateEmail(email, fieldId) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email.length === 0) {
        clearFieldError(fieldId);
        return true;
    }
    
    if (!emailRegex.test(email)) {
        showFieldError(fieldId, 'Please enter a valid email address');
        return false;
    } else {
        showFieldSuccess(fieldId, 'Valid email address');
        return true;
    }
}

function validateUsername(username) {
    if (username.length === 0) {
        clearFieldError('username');
        return true;
    }
    
    if (username.length < 3) {
        showFieldError('username', 'Username must be at least 3 characters');
        return false;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showFieldError('username', 'Username can only contain letters, numbers, and underscores');
        return false;
    }
    
    showFieldSuccess('username', 'Valid username');
    return true;
}

function formatPhoneNumber(input) {
    // Remove all non-digit characters
    let value = input.value.replace(/\D/g, '');
    
    // Format as (XXX) XXX-XXXX
    if (value.length >= 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{0,4})/, '($1) $2-$3');
    } else if (value.length >= 3) {
        value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
    }
    
    input.value = value;
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const formGroup = field.closest('.form-group');
    
    // Remove existing states
    formGroup.classList.remove('success');
    formGroup.classList.add('error');
    
    // Remove existing messages
    const existingMessage = formGroup.querySelector('.error-message, .success-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    field.parentNode.insertAdjacentElement('afterend', errorDiv);
}

function showFieldSuccess(fieldId, message) {
    const field = document.getElementById(fieldId);
    const formGroup = field.closest('.form-group');
    
    // Remove existing states
    formGroup.classList.remove('error');
    formGroup.classList.add('success');
    
    // Remove existing messages
    const existingMessage = formGroup.querySelector('.error-message, .success-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Add success message
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    field.parentNode.insertAdjacentElement('afterend', successDiv);
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const formGroup = field.closest('.form-group');
    
    formGroup.classList.remove('error', 'success');
    
    const existingMessage = formGroup.querySelector('.error-message, .success-message');
    if (existingMessage) {
        existingMessage.remove();
    }
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = field.parentNode.querySelector('.password-toggle i');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Add smooth transitions to form elements
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.style.transform = 'translateY(-1px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.style.transform = 'translateY(0)';
        });
    });
});
