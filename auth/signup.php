<?php
// Include required files first (before starting session)
require_once '../config/config.php';
// Database connection is already loaded via config.php

// Start session for CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

// Simple rate limiting check
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $terms = isset($_POST['terms']);
    
    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, ['student', 'mentor'])) {
        $error = 'Please select a valid role.';
    } elseif (!$terms) {
        $error = 'Please accept the Terms of Service and Privacy Policy.';
    }
    
    if (empty($error)) {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'An account with this email already exists.';
            } else {
                // Generate username from email
                $username = strtolower(explode('@', $email)[0]);
                $baseUsername = $username;
                $counter = 1;
                
                // Ensure unique username
                do {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                    $stmt->execute([$username]);
                    if ($stmt->rowCount() > 0) {
                        $username = $baseUsername . $counter;
                        $counter++;
                    } else {
                        break;
                    }
                } while ($counter < 100);
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $conn->beginTransaction();
                
                // Insert user
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        username, email, password_hash, first_name, last_name, user_type, 
                        phone, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $username, 
                    strtolower($email), 
                    $hashedPassword, 
                    $firstName, 
                    $lastName, 
                    $role, 
                    $phone
                ]);
                
                $userId = $conn->lastInsertId();
                
                $conn->commit();
                
                $success = 'Account created successfully! You can now sign in with your email and password.';
            }
        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollback();
            }
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed. Please try again. Error: ' . $e->getMessage();
        }
    }
}

// Generate a simple CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join MentorConnect - Create Your Account</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/password-strength.css" rel="stylesheet">
    <style>
        /* CSS Variables for Theme Support */
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --background-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --text-inverse: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.95);
            --input-bg: #ffffff;
            --input-border: #e5e7eb;
            --input-focus: #667eea;
            --border-color: #e5e7eb;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
        }

        [data-theme="dark"] {
            --primary-color: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary-color: #06b6d4;
            --background-gradient: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
            --text-inverse: #111827;
            --card-bg: rgba(30, 41, 59, 0.95);
            --input-bg: #374151;
            --input-border: #4b5563;
            --input-focus: #8b5cf6;
            --border-color: #4b5563;
            --shadow-light: rgba(0, 0, 0, 0.3);
            --shadow-medium: rgba(0, 0, 0, 0.4);
        }

        /* Modern Signup Page Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--background-gradient);
            min-height: 100vh;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }

        .signup-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Brand Panel */
        .brand-panel {
            flex: 1;
            background: var(--background-gradient);
            color: var(--text-inverse);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 1;
            max-width: 500px;
            margin: 0 auto;
            padding-top: 4rem;
        }

        .logo-section {
            margin-bottom: 2rem;
            margin-top: -1rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            width: fit-content;
        }
        
        .logo:hover {
            transform: translateY(-2px);
            filter: drop-shadow(0 4px 8px rgba(251, 191, 36, 0.3));
        }
        
        .logo:active {
            transform: translateY(0);
        }

        .logo i {
            font-size: 2rem;
            margin-right: 0.75rem;
            color: #fbbf24;
        }

        .logo-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #fbbf24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.8;
        }

        .features-list {
            margin-bottom: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .feature-item i {
            font-size: 1.25rem;
            margin-right: 1rem;
            margin-top: 0.25rem;
            color: #fbbf24;
            min-width: 24px;
        }

        .feature-item h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .feature-item p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Testimonial Section */
        .testimonial-section {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .testimonial-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }

        .author-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .author-info p {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Statistics Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fbbf24;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        /* Success Stories */
        .success-stories {
            margin-top: 2rem;
        }

        .success-stories h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #fbbf24;
        }

        .story-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .story-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .story-item i {
            color: #fbbf24;
            min-width: 16px;
        }

        /* Form Panel */
        .form-panel {
            flex: 1;
            background: var(--card-bg);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem;
            padding-top: 4rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .form-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            flex-direction: column;
            align-items: flex-start;
        }

        .success-link {
            background: #16a34a;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            margin-top: 0.75rem;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .success-link:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        /* Form Groups */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input,
        .input-wrapper textarea {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
            font-family: inherit;
        }

        .input-wrapper textarea {
            resize: vertical;
            min-height: 100px;
            padding-top: 1rem;
        }

        .input-wrapper input:focus,
        .input-wrapper textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
        }

        .input-wrapper textarea + i {
            top: 1.5rem;
            transform: none;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        /* Role Selection */
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .role-card {
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .role-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }

        .role-card input[type="radio"] {
            display: none;
        }

        .role-card input[type="radio"]:checked + .role-content {
            color: #667eea;
        }

        .role-card input[type="radio"]:checked + .role-content .role-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.1);
        }

        .role-content {
            position: relative;
            z-index: 1;
        }

        .role-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            background: #f3f4f6;
            color: #6b7280;
        }

        .role-content h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .role-content p {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        /* Checkbox */
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            cursor: pointer;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .checkbox-label input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            font-size: 0.75rem;
            font-weight: bold;
        }

        .checkbox-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .checkbox-text a:hover {
            text-decoration: underline;
        }

        /* Buttons */
        .btn {
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-full {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
        }

        /* Form Footer */
        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .form-footer p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .signup-container {
                flex-direction: column;
            }
            
            .brand-panel {
                padding: 2rem;
                min-height: 40vh;
                justify-content: center;
            }
            
            .brand-content {
                text-align: center;
                padding-top: 0;
            }
            
            .logo-section {
                margin-top: 0;
                margin-bottom: 1.5rem;
            }
            
            .logo-section h1 {
                font-size: 2rem;
            }
            
            .features-list {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 768px) {
            .form-panel {
                padding: 1rem;
                padding-top: 1rem;
                align-items: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .role-selection {
                grid-template-columns: 1fr;
            }
            
            .features-list {
                grid-template-columns: 1fr;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .testimonial-card {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .brand-panel {
                padding: 1.5rem;
            }
            
            .logo-section h1 {
                font-size: 1.75rem;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
            
            .role-card {
                padding: 1rem;
            }
            
            .role-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .testimonial-section {
                margin-top: 1rem;
                margin-bottom: 1rem;
            }
            
            .success-stories {
                margin-top: 1rem;
            }
            
            .stats-section {
                padding: 1rem;
                gap: 0.5rem;
            }
        }

        /* Theme Toggle Button */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            box-shadow: 0 10px 15px -3px var(--shadow-light), 0 4px 6px -2px var(--shadow-medium);
        }

        .theme-toggle:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 20px 25px -5px var(--shadow-light), 0 10px 10px -5px var(--shadow-medium);
            background: var(--primary-color);
        }

        .theme-toggle:hover i {
            color: var(--text-inverse);
            transform: rotate(360deg);
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }

        .theme-toggle i {
            font-size: 1.3rem;
            color: var(--text-primary);
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        }

        /* Enhanced Form Styles for Dark Theme */
        .form-header h2 {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .form-header p {
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .form-group label {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .form-input, .form-select {
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--input-focus);
            background: var(--input-bg);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        [data-theme="dark"] .form-input:focus, 
        [data-theme="dark"] .form-select:focus {
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .role-content h4 {
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        .role-content p {
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .form-footer p {
            color: var(--text-secondary);
        }

        .form-footer a {
            color: var(--primary-color);
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: var(--primary-dark);
        }

        /* Dark theme adjustments for alerts */
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--error-color);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="brand-content">
                <div class="logo-section">
                    <a href="../index.php" class="logo" title="Go to Home">
                        <i class="fas fa-graduation-cap"></i>
                        <span>MentorConnect</span>
                    </a>
                    <h1>Join Our Community</h1>
                    <p>Connect with mentors and mentees worldwide. Build meaningful relationships and grow your career.</p>
                </div>
                
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <h3>Expert Mentors</h3>
                            <p>Learn from industry professionals</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-rocket"></i>
                        <div>
                            <h3>Career Growth</h3>
                            <p>Accelerate your professional journey</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-network-wired"></i>
                        <div>
                            <h3>Global Network</h3>
                            <p>Connect with peers worldwide</p>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial Section -->
                <div class="testimonial-section">
                    <div class="testimonial-card">
                        <div class="testimonial-text">
                            "MentorConnect transformed my career! My mentor helped me land my dream job in just 3 months."
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">S</div>
                            <div class="author-info">
                                <h4>Sarah Chen</h4>
                                <p>Software Engineer</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="stats-section">
                    <div class="stat-item">
                        <span class="stat-number">2K+</span>
                        <span class="stat-label">Active Members</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Expert Mentors</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">Success Rate</span>
                    </div>
                </div>
                
                <!-- Success Stories -->
                <div class="success-stories">
                    <h3>Recent Success Stories</h3>
                    <div class="story-list">
                        <div class="story-item">
                            <i class="fas fa-star"></i>
                            <span>Alex got promoted to Senior Developer</span>
                        </div>
                        <div class="story-item">
                            <i class="fas fa-briefcase"></i>
                            <span>Maria launched her own startup</span>
                        </div>
                        <div class="story-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span>David switched to Data Science career</span>
                        </div>
                        <div class="story-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Emma increased her salary by 40%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Signup Form -->
        <div class="form-panel">
            <div class="form-container">
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Start your mentoring journey today</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                        <a href="login.php" class="success-link">Sign In Now</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="signup-form" id="signupForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <div class="input-wrapper">
                                <input type="text" id="first_name" name="first_name" required 
                                       value="<?php echo htmlspecialchars($firstName ?? ''); ?>"
                                       placeholder="Enter your first name">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <div class="input-wrapper">
                                <input type="text" id="last_name" name="last_name" required 
                                       value="<?php echo htmlspecialchars($lastName ?? ''); ?>"
                                       placeholder="Enter your last name">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                   placeholder="Enter your email address">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <div class="input-wrapper">
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                   placeholder="Enter your phone number">
                            <i class="fas fa-phone"></i>
                        </div>
                    </div>
                    
                    <div class="form-group password-input-container has-strength-indicator">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Create a strong password" class="form-input">
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="password-strength-container"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your password" class="form-input">
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="password-match-indicator" class="password-match-feedback" style="display: none;">
                            <div class="match-status">
                                <i class="fas fa-check-circle" style="color: #16a34a; display: none;"></i>
                                <i class="fas fa-times-circle" style="color: #ef4444; display: none;"></i>
                                <span class="match-text"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Choose Your Role</label>
                        <div class="role-selection">
                            <label class="role-card">
                                <input type="radio" name="role" value="student" required>
                                <div class="role-content">
                                    <div class="role-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <h4>I'm a Student</h4>
                                    <p>Looking for guidance and mentorship</p>
                                </div>
                            </label>
                            
                            <label class="role-card">
                                <input type="radio" name="role" value="mentor" required>
                                <div class="role-content">
                                    <div class="role-icon">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <h4>I'm a Mentor</h4>
                                    <p>Ready to share my experience</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="skills">Skills/Interests (Optional)</label>
                        <div class="input-wrapper">
                            <textarea id="skills" name="skills" placeholder="Tell us about your skills or interests..."></textarea>
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required>
                            <span class="checkmark"></span>
                            <span class="checkbox-text">
                                I agree to the <a href="#" target="_blank">Terms of Service</a> 
                                and <a href="#" target="_blank">Privacy Policy</a>
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.parentElement.querySelector('.password-toggle i');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                toggle.className = 'fas fa-eye';
            }
        }
        
        // Initialize Password Strength Validator
        let passwordValidator;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize password strength validator
            passwordValidator = new PasswordStrengthValidator();
            
            // Create strength indicator
            const strengthContainer = document.getElementById('password-strength-container');
            if (strengthContainer) {
                passwordValidator.createStrengthIndicator(strengthContainer, 'password');
            }
            
            // Setup password confirmation matching
            setupPasswordMatching();
        });
        
        // Password matching functionality
        function setupPasswordMatching() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const matchIndicator = document.getElementById('password-match-indicator');
            const matchIcon = matchIndicator.querySelector('.fas');
            const matchText = matchIndicator.querySelector('.match-text');
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length === 0) {
                    matchIndicator.style.display = 'none';
                    return;
                }
                
                matchIndicator.style.display = 'block';
                
                if (password === confirmPassword) {
                    matchIndicator.querySelector('.fa-check-circle').style.display = 'inline';
                    matchIndicator.querySelector('.fa-times-circle').style.display = 'none';
                    matchText.textContent = 'Passwords match';
                    matchText.style.color = '#16a34a';
                } else {
                    matchIndicator.querySelector('.fa-check-circle').style.display = 'none';
                    matchIndicator.querySelector('.fa-times-circle').style.display = 'inline';
                    matchText.textContent = 'Passwords do not match';
                    matchText.style.color = '#ef4444';
                }
            }
            
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            passwordInput.addEventListener('input', checkPasswordMatch);
        }
        
        // Enhanced form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check password strength
            if (passwordValidator) {
                const strengthAnalysis = passwordValidator.calculateStrength(password);
                if (strengthAnalysis.level === 'weak') {
                    e.preventDefault();
                    alert('Please create a stronger password. It must include uppercase, lowercase, numbers, and special characters.');
                    return;
                }
            }
            
            // Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            // Basic length check as fallback
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
        });
        
        // Role-based field updates
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const skillsField = document.getElementById('skills');
        const skillsLabel = document.querySelector('label[for="skills"]');
        
        roleInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'mentor') {
                    skillsLabel.textContent = 'Your Skills (Optional)';
                    skillsField.placeholder = 'e.g., JavaScript, Leadership, Project Management...';
                    skillsField.name = 'skills';
                } else {
                    skillsLabel.textContent = 'Your Interests (Optional)';
                    skillsField.placeholder = 'e.g., Web Development, Data Science, Career Growth...';
                    skillsField.name = 'interests';
                }
            });
        });
    </script>
    
    <!-- Password Strength JavaScript -->
    <script src="../assets/js/password-strength.js"></script>
    
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <script>
        // Theme Management
        class ThemeManager {
            constructor() {
                this.init();
            }
            
            init() {
                this.initializeTheme();
                this.bindEvents();
            }
            
            initializeTheme() {
                const savedTheme = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', savedTheme);
                this.updateThemeIcon(savedTheme);
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
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                this.updateThemeIcon(newTheme);
                
                // Add animation effect
                const themeToggle = document.querySelector('.theme-toggle');
                if (themeToggle) {
                    themeToggle.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        themeToggle.style.transform = '';
                    }, 150);
                }
            }
            
            bindEvents() {
                const themeToggle = document.querySelector('.theme-toggle');
                if (themeToggle) {
                    themeToggle.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.toggleTheme();
                    });
                }
            }
        }
        
        // Initialize theme manager
        document.addEventListener('DOMContentLoaded', () => {
            new ThemeManager();
        });
    </script>
</body>
</html>
