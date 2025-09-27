<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            $firstName = sanitizeInput($_POST['first_name'] ?? '');
            $lastName = sanitizeInput($_POST['last_name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $bio = sanitizeInput($_POST['bio'] ?? '');
            $linkedin = sanitizeInput($_POST['linkedin_url'] ?? '');
            $github = sanitizeInput($_POST['github_url'] ?? '');
            $portfolio = sanitizeInput($_POST['portfolio_url'] ?? '');
            
            // Validate required fields
            if (empty($firstName) || empty($lastName) || empty($email)) {
                throw new Exception('First name, last name, and email are required.');
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }
            
            // Check if email is already taken by another user
            $existingUser = fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$email, $user['id']]
            );
            
            if ($existingUser) {
                throw new Exception('This email address is already in use.');
            }
            
            // Handle file upload for profile photo
            $profilePhoto = $user['profile_photo'];
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['profile_photo'], ['jpg', 'jpeg', 'png'], 2 * 1024 * 1024); // 2MB limit
                $profilePhoto = $uploadResult['stored_name'];
                
                // Delete old profile photo if it exists
                if ($user['profile_photo'] && file_exists('../uploads/' . $user['profile_photo'])) {
                    unlink('../uploads/' . $user['profile_photo']);
                }
            }
            
            // Update user profile
            executeQuery(
                "UPDATE users SET 
                    first_name = ?, last_name = ?, email = ?, phone = ?, 
                    bio = ?, profile_photo = ?, linkedin_url = ?, github_url = ?, 
                    portfolio_url = ?, updated_at = NOW()
                 WHERE id = ?",
                [
                    $firstName, $lastName, $email, $phone, $bio, 
                    $profilePhoto, $linkedin, $github, $portfolio, $user['id']
                ]
            );
            
            // Update mentor-specific fields if user is a mentor
            if ($user['role'] === 'mentor' && isset($_POST['mentor_data'])) {
                $title = sanitizeInput($_POST['title'] ?? '');
                $company = sanitizeInput($_POST['company'] ?? '');
                $hourlyRate = floatval($_POST['hourly_rate'] ?? 0);
                $experienceYears = intval($_POST['experience_years'] ?? 0);
                $languages = sanitizeInput($_POST['languages'] ?? '');
                $availability = sanitizeInput($_POST['availability'] ?? '');
                $expertise = sanitizeInput($_POST['expertise'] ?? '');
                
                // Check if mentor profile exists
                $mentorProfile = fetchOne(
                    "SELECT id FROM mentor_profiles WHERE user_id = ?",
                    [$user['id']]
                );
                
                if ($mentorProfile) {
                    // Update existing mentor profile
                    executeQuery(
                        "UPDATE mentor_profiles SET 
                            title = ?, company = ?, hourly_rate = ?, experience_years = ?,
                            languages = ?, availability = ?, expertise = ?, updated_at = NOW()
                         WHERE user_id = ?",
                        [$title, $company, $hourlyRate, $experienceYears, $languages, $availability, $expertise, $user['id']]
                    );
                } else {
                    // Create new mentor profile
                    executeQuery(
                        "INSERT INTO mentor_profiles 
                            (user_id, title, company, hourly_rate, experience_years, languages, availability, expertise, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                        [$user['id'], $title, $company, $hourlyRate, $experienceYears, $languages, $availability, $expertise]
                    );
                }
            }
            
            // Log activity
            logActivity($user['id'], 'profile_updated', 'Updated profile information');
            
            $success = 'Profile updated successfully!';
            
            // Refresh user data
            $user = getCurrentUser();
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get mentor profile data if user is a mentor
$mentorProfile = null;
if ($user['role'] === 'mentor') {
    $mentorProfile = fetchOne(
        "SELECT * FROM mentor_profiles WHERE user_id = ?",
        [$user['id']]
    );
}

// Get user's skills
$userSkills = fetchAll(
    "SELECT s.*, us.proficiency_level 
     FROM user_skills us 
     JOIN skills s ON us.skill_id = s.id 
     WHERE us.user_id = ?
     ORDER BY s.category, s.name",
    [$user['id']]
);

// Get all available skills for adding
$allSkills = fetchAll("SELECT * FROM skills ORDER BY category, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-light: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --bg-primary: #f9fafb;
            --surface: #ffffff;
            --border: #e5e7eb;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body {
            background: var(--bg-primary);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 2rem;
        }

        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .profile-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .profile-subtitle {
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .form-container {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .form-sections {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .form-section {
            border-bottom: 1px solid var(--border);
            padding-bottom: 2rem;
        }

        .form-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .photo-upload {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .current-photo {
            width: 100px;
            height: 100px;
            border-radius: 16px;
            object-fit: cover;
            border: 3px solid var(--border);
        }

        .photo-upload-area {
            flex: 1;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .file-input-button:hover {
            background: var(--primary-light);
        }

        .skills-section {
            margin-top: 2rem;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .skill-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .skill-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .skill-level {
            display: flex;
            gap: 2px;
        }

        .skill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--border);
        }

        .skill-dot.filled {
            background: var(--primary-color);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .photo-upload {
                flex-direction: column;
                text-align: center;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="../dashboard/<?php echo $user['role']; ?>.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="profile-header">
            <h1 class="profile-title">Edit Profile</h1>
            <p class="profile-subtitle">Update your personal information and preferences</p>
        </div>

        <div class="form-container">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-sections">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Basic Information
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group full-width">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-textarea" 
                                          placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Photo -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-camera"></i>
                            Profile Photo
                        </h3>
                        
                        <div class="photo-upload">
                            <img src="<?php echo htmlspecialchars($user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : 'https://via.placeholder.com/100x100/667eea/ffffff?text=' . strtoupper(substr($user['first_name'], 0, 1))); ?>" 
                                 alt="Current Profile Photo" class="current-photo">
                            
                            <div class="photo-upload-area">
                                <div class="file-input-wrapper">
                                    <input type="file" name="profile_photo" class="file-input" accept="image/*">
                                    <div class="file-input-button">
                                        <i class="fas fa-upload"></i>
                                        Choose New Photo
                                    </div>
                                </div>
                                <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.5rem;">
                                    JPG, PNG up to 2MB
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-link"></i>
                            Social Links
                        </h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">LinkedIn URL</label>
                                <input type="url" name="linkedin_url" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['linkedin_url'] ?? ''); ?>"
                                       placeholder="https://linkedin.com/in/yourprofile">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">GitHub URL</label>
                                <input type="url" name="github_url" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['github_url'] ?? ''); ?>"
                                       placeholder="https://github.com/yourusername">
                            </div>
                            
                            <div class="form-group full-width">
                                <label class="form-label">Portfolio URL</label>
                                <input type="url" name="portfolio_url" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['portfolio_url'] ?? ''); ?>"
                                       placeholder="https://yourportfolio.com">
                            </div>
                        </div>
                    </div>

                    <!-- Mentor-specific fields -->
                    <?php if ($user['role'] === 'mentor'): ?>
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-briefcase"></i>
                            Mentor Information
                        </h3>
                        
                        <input type="hidden" name="mentor_data" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Professional Title</label>
                                <input type="text" name="title" class="form-input" 
                                       value="<?php echo htmlspecialchars($mentorProfile['title'] ?? ''); ?>"
                                       placeholder="e.g., Senior Software Engineer">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Company</label>
                                <input type="text" name="company" class="form-input" 
                                       value="<?php echo htmlspecialchars($mentorProfile['company'] ?? ''); ?>"
                                       placeholder="e.g., Google, Microsoft">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Hourly Rate ($)</label>
                                <input type="number" name="hourly_rate" class="form-input" 
                                       value="<?php echo htmlspecialchars($mentorProfile['hourly_rate'] ?? ''); ?>"
                                       min="0" step="0.01" placeholder="50.00">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Years of Experience</label>
                                <input type="number" name="experience_years" class="form-input" 
                                       value="<?php echo htmlspecialchars($mentorProfile['experience_years'] ?? ''); ?>"
                                       min="0" placeholder="5">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Languages</label>
                                <input type="text" name="languages" class="form-input" 
                                       value="<?php echo htmlspecialchars($mentorProfile['languages'] ?? ''); ?>"
                                       placeholder="English, Spanish, French">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Availability</label>
                                <input type="text" name="availability" class="form-input" 
                                       value="<?php echo htmlspecialchars($mentorProfile['availability'] ?? ''); ?>"
                                       placeholder="Weekdays 9am-5pm EST">
                            </div>
                            
                            <div class="form-group full-width">
                                <label class="form-label">Areas of Expertise</label>
                                <textarea name="expertise" class="form-textarea" 
                                          placeholder="List your key skills and areas of expertise..."><?php echo htmlspecialchars($mentorProfile['expertise'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Skills Section -->
                    <?php if (!empty($userSkills)): ?>
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-code"></i>
                            Your Skills
                        </h3>
                        
                        <div class="skills-grid">
                            <?php foreach ($userSkills as $skill): ?>
                            <div class="skill-item">
                                <span class="skill-name"><?php echo htmlspecialchars($skill['name']); ?></span>
                                <div class="skill-level">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="skill-dot <?php echo $i <= $skill['proficiency_level'] ? 'filled' : ''; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="btn btn-outline btn-sm" onclick="manageSkills()">
                            <i class="fas fa-edit"></i>
                            Manage Skills
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <a href="../dashboard/<?php echo $user['role']; ?>.php" class="btn btn-outline">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function manageSkills() {
            // This would open a modal or redirect to skills management page
            alert('Skills management functionality would be implemented here');
        }

        // File input preview
        document.querySelector('input[name="profile_photo"]')?.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.current-photo').src = e.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            
            if (!firstName || !lastName || !email) {
                e.preventDefault();
                alert('Please fill in all required fields (First Name, Last Name, Email)');
                return false;
            }
            
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>