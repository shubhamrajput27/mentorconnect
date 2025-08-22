<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

// Get user skills
$userSkills = fetchAll(
    "SELECT s.id, s.name, us.proficiency_level 
     FROM user_skills us 
     JOIN skills s ON us.skill_id = s.id 
     WHERE us.user_id = ?",
    [$user['id']]
);

// Get all available skills
$allSkills = fetchAll("SELECT * FROM skills ORDER BY category, name");

// Get mentor profile if user is a mentor
$mentorProfile = null;
if ($user['role'] === 'mentor') {
    $mentorProfile = fetchOne(
        "SELECT * FROM mentor_profiles WHERE user_id = ?",
        [$user['id']]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $timezone = sanitizeInput($_POST['timezone'] ?? 'UTC');
        
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Check if email is already taken by another user
                $existingUser = fetchOne(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$email, $user['id']]
                );
                
                if ($existingUser) {
                    $error = 'Email address is already in use.';
                } else {
                    // Handle profile photo upload
                    $profilePhoto = $user['profile_photo'];
                    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $uploadResult = uploadFile($_FILES['profile_photo'], ['jpg', 'jpeg', 'png']);
                            $profilePhoto = $uploadResult['stored_name'];
                            
                            // Delete old profile photo if exists
                            if ($user['profile_photo'] && file_exists(UPLOAD_PATH . $user['profile_photo'])) {
                                unlink(UPLOAD_PATH . $user['profile_photo']);
                            }
                        } catch (Exception $e) {
                            $error = 'Failed to upload profile photo: ' . $e->getMessage();
                        }
                    }
                    
                    if (!$error) {
                        // Update user profile
                        executeQuery(
                            "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, location = ?, bio = ?, timezone = ?, profile_photo = ?, updated_at = NOW() WHERE id = ?",
                            [$firstName, $lastName, $email, $phone, $location, $bio, $timezone, $profilePhoto, $user['id']]
                        );
                        
                        // Update mentor profile if user is a mentor
                        if ($user['role'] === 'mentor') {
                            $title = sanitizeInput($_POST['title'] ?? '');
                            $company = sanitizeInput($_POST['company'] ?? '');
                            $experienceYears = intval($_POST['experience_years'] ?? 0);
                            $hourlyRate = floatval($_POST['hourly_rate'] ?? 0);
                            $availability = sanitizeInput($_POST['availability'] ?? '');
                            $languages = sanitizeInput($_POST['languages'] ?? '');
                            
                            executeQuery(
                                "UPDATE mentor_profiles SET title = ?, company = ?, experience_years = ?, hourly_rate = ?, availability = ?, languages = ? WHERE user_id = ?",
                                [$title, $company, $experienceYears, $hourlyRate, $availability, $languages, $user['id']]
                            );
                        }
                        
                        // Update skills
                        if (isset($_POST['skills']) && is_array($_POST['skills'])) {
                            // Remove existing skills
                            executeQuery("DELETE FROM user_skills WHERE user_id = ?", [$user['id']]);
                            
                            // Add new skills
                            foreach ($_POST['skills'] as $skillId => $proficiency) {
                                if (!empty($proficiency)) {
                                    executeQuery(
                                        "INSERT INTO user_skills (user_id, skill_id, proficiency_level) VALUES (?, ?, ?)",
                                        [$user['id'], $skillId, $proficiency]
                                    );
                                }
                            }
                        }
                        
                        // Log activity
                        logActivity($user['id'], 'profile_update', 'Profile updated successfully');
                        
                        $success = 'Profile updated successfully!';
                        
                        // Refresh user data
                        $user = getCurrentUser();
                        if ($user['role'] === 'mentor') {
                            $mentorProfile = fetchOne("SELECT * FROM mentor_profiles WHERE user_id = ?", [$user['id']]);
                        }
                        $userSkills = fetchAll(
                            "SELECT s.id, s.name, us.proficiency_level FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ?",
                            [$user['id']]
                        );
                    }
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                $error = 'An error occurred while updating your profile.';
            }
        }
    }
}

$csrfToken = generateCSRFToken();
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
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </a>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item">
                    <a href="/dashboard/<?php echo $user['role']; ?>.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <?php if ($user['role'] === 'mentor'): ?>
                    <div class="nav-item">
                        <a href="/sessions/index.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Sessions</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="/students/index.php" class="nav-link">
                            <i class="fas fa-user-graduate"></i>
                            <span>My Students</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="nav-item">
                        <a href="/mentors/browse.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span>Find Mentors</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="/sessions/index.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>My Sessions</span>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="nav-item">
                    <a href="/messages/index.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/profile/edit.php" class="nav-link active">
                        <i class="fas fa-user-edit"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Edit Profile</h2>
                </div>
                
                <div class="header-right">
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <div class="user-menu">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Profile Content -->
            <div class="content">
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

                <form method="POST" enctype="multipart/form-data" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="grid grid-cols-1 gap-lg">
                        <!-- Basic Information -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Basic Information</h3>
                            </div>
                            <div class="card-body">
                                <!-- Profile Photo -->
                                <div class="form-group">
                                    <label>Profile Photo</label>
                                    <div class="profile-photo-upload">
                                        <div class="current-photo">
                                            <img id="profilePreview" 
                                                 src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                                 alt="Profile Photo">
                                        </div>
                                        <div class="upload-controls">
                                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display: none;">
                                            <button type="button" class="btn btn-outline" onclick="document.getElementById('profile_photo').click();">
                                                <i class="fas fa-camera"></i>
                                                Change Photo
                                            </button>
                                            <small class="text-muted">JPG, PNG up to 5MB</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name">First Name *</label>
                                        <input type="text" id="first_name" name="first_name" required 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="last_name">Last Name *</label>
                                        <input type="text" id="last_name" name="last_name" required 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" required 
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="location">Location</label>
                                        <input type="text" id="location" name="location" 
                                               value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                                               placeholder="City, Country">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="timezone">Timezone</label>
                                    <select id="timezone" name="timezone">
                                        <option value="UTC" <?php echo ($user['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        <option value="America/New_York" <?php echo ($user['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                        <option value="America/Chicago" <?php echo ($user['timezone'] ?? '') === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                        <option value="America/Denver" <?php echo ($user['timezone'] ?? '') === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                        <option value="America/Los_Angeles" <?php echo ($user['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                        <option value="Europe/London" <?php echo ($user['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                        <option value="Europe/Paris" <?php echo ($user['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                        <option value="Asia/Tokyo" <?php echo ($user['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="bio">Bio</label>
                                    <textarea id="bio" name="bio" rows="4" 
                                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <?php if ($user['role'] === 'mentor'): ?>
                        <!-- Mentor Information -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Professional Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="title">Job Title</label>
                                        <input type="text" id="title" name="title" 
                                               value="<?php echo htmlspecialchars($mentorProfile['title'] ?? ''); ?>"
                                               placeholder="e.g. Senior Software Engineer">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="company">Company</label>
                                        <input type="text" id="company" name="company" 
                                               value="<?php echo htmlspecialchars($mentorProfile['company'] ?? ''); ?>"
                                               placeholder="e.g. Google, Microsoft">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="experience_years">Years of Experience</label>
                                        <input type="number" id="experience_years" name="experience_years" min="0" max="50"
                                               value="<?php echo $mentorProfile['experience_years'] ?? 0; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="hourly_rate">Hourly Rate ($)</label>
                                        <input type="number" id="hourly_rate" name="hourly_rate" min="0" step="0.01"
                                               value="<?php echo $mentorProfile['hourly_rate'] ?? 0; ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="availability">Availability</label>
                                    <textarea id="availability" name="availability" rows="3" 
                                              placeholder="e.g. Mon-Fri 9AM-5PM PST, Weekends by appointment"><?php echo htmlspecialchars($mentorProfile['availability'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="languages">Languages</label>
                                    <input type="text" id="languages" name="languages" 
                                           value="<?php echo htmlspecialchars($mentorProfile['languages'] ?? ''); ?>"
                                           placeholder="e.g. English, Spanish, French">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Skills -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Skills & Expertise</h3>
                            </div>
                            <div class="card-body">
                                <div class="skills-grid">
                                    <?php 
                                    $currentCategory = '';
                                    foreach ($allSkills as $skill): 
                                        if ($skill['category'] !== $currentCategory):
                                            if ($currentCategory !== '') echo '</div>';
                                            $currentCategory = $skill['category'];
                                            echo '<div class="skill-category">';
                                            echo '<h5>' . htmlspecialchars($currentCategory) . '</h5>';
                                        endif;
                                        
                                        $userSkill = array_filter($userSkills, function($us) use ($skill) {
                                            return $us['id'] == $skill['id'];
                                        });
                                        $currentProficiency = !empty($userSkill) ? reset($userSkill)['proficiency_level'] : '';
                                    ?>
                                        <div class="skill-item">
                                            <label><?php echo htmlspecialchars($skill['name']); ?></label>
                                            <select name="skills[<?php echo $skill['id']; ?>]">
                                                <option value="">Not selected</option>
                                                <option value="beginner" <?php echo $currentProficiency === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                                <option value="intermediate" <?php echo $currentProficiency === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                                <option value="advanced" <?php echo $currentProficiency === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                                <option value="expert" <?php echo $currentProficiency === 'expert' ? 'selected' : ''; ?>>Expert</option>
                                            </select>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                            <a href="/dashboard/<?php echo $user['role']; ?>.php" class="btn btn-outline btn-lg">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <style>
    .profile-form {
        max-width: 800px;
        margin: 0 auto;
    }

    .profile-photo-upload {
        display: flex;
        align-items: center;
        gap: var(--spacing-lg);
    }

    .current-photo img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--border-color);
    }

    .upload-controls {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-sm);
    }

    .skills-grid {
        display: grid;
        gap: var(--spacing-lg);
    }

    .skill-category h5 {
        color: var(--primary-color);
        margin-bottom: var(--spacing-md);
        padding-bottom: var(--spacing-sm);
        border-bottom: 2px solid var(--primary-color);
    }

    .skill-item {
        display: grid;
        grid-template-columns: 1fr 150px;
        gap: var(--spacing-md);
        align-items: center;
        padding: var(--spacing-sm) 0;
        border-bottom: 1px solid var(--divider-color);
    }

    .skill-item:last-child {
        border-bottom: none;
    }

    .skill-item label {
        margin-bottom: 0;
        font-weight: 500;
    }

    .form-actions {
        display: flex;
        gap: var(--spacing-md);
        justify-content: center;
        padding: var(--spacing-lg) 0;
    }

    @media (max-width: 768px) {
        .profile-photo-upload {
            flex-direction: column;
            text-align: center;
        }
        
        .skill-item {
            grid-template-columns: 1fr;
            gap: var(--spacing-sm);
        }
        
        .form-actions {
            flex-direction: column;
        }
    }
    </style>

    <script>
    // Profile photo preview
    document.getElementById('profile_photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    </script>

    <script src="../assets/js/app.js"></script>
</body>
</html>
