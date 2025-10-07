<?php
require_once '../config/database.php';
requireLogin();

$user = getCurrentUser();
$mentorId = intval($_GET['id'] ?? 0);

if (!$mentorId) {
    header('Location: ../mentors/browse.php');
    exit();
}

// Get mentor profile
$mentor = fetchOne(
    "SELECT u.*, mp.title, mp.company, mp.hourly_rate, mp.experience_years, mp.total_sessions, 
            mp.bio, mp.rating, mp.languages, mp.availability, mp.education, mp.certifications,
            mp.linkedin_url, mp.github_url, mp.portfolio_url
     FROM users u 
     LEFT JOIN mentor_profiles mp ON u.id = mp.user_id 
     WHERE u.id = ? AND u.role = 'mentor' AND u.status = 'active'",
    [$mentorId]
);

if (!$mentor) {
    header('Location: ../mentors/browse.php');
    exit();
}

// Get mentor skills
$mentorSkills = fetchAll(
    "SELECT s.*, us.proficiency_level, us.years_experience
     FROM user_skills us 
     JOIN skills s ON us.skill_id = s.id 
     WHERE us.user_id = ?
     ORDER BY s.category, s.name",
    [$mentorId]
);

// Get recent reviews
$reviews = fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.profile_photo
     FROM reviews r
     JOIN users u ON r.student_id = u.id
     WHERE r.mentor_id = ? AND r.status = 'published'
     ORDER BY r.created_at DESC
     LIMIT 10",
    [$mentorId]
);

// Get mentor's recent sessions count
$recentSessionsCount = fetchOne(
    "SELECT COUNT(*) as count 
     FROM sessions 
     WHERE mentor_id = ? AND status = 'completed' AND scheduled_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)",
    [$mentorId]
)['count'];

// Calculate response rate (simplified)
$responseRate = 95; // Would be calculated from actual data

// Get availability status
$isAvailable = true; // Would be calculated from mentor's schedule
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-light: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
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
            max-width: 1200px;
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

        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .profile-header {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            object-fit: cover;
            border: 3px solid var(--border);
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .profile-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-company {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .rating-stars {
            color: var(--warning-color);
            font-size: 1.2rem;
        }

        .rating-text {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .availability-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .availability-status.available {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .availability-status.busy {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bio-text {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 1.1rem;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .skill-category {
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .skill-category-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            text-transform: capitalize;
        }

        .skill-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .skill-item:last-child {
            margin-bottom: 0;
        }

        .skill-name {
            color: var(--text-secondary);
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

        .review-item {
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .review-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .review-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .review-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
        }

        .review-info {
            flex: 1;
        }

        .review-author {
            font-weight: 500;
            color: var(--text-primary);
        }

        .review-date {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .review-rating {
            color: var(--warning-color);
        }

        .review-text {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .booking-card {
            position: sticky;
            top: 2rem;
        }

        .rate-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1rem;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
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
            transform: translateY(-1px);
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-secondary);
        }

        .contact-item i {
            width: 20px;
            color: var(--primary-color);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .quick-stat {
            text-align: center;
            padding: 1rem;
            background: var(--bg-primary);
            border-radius: 12px;
        }

        .quick-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .quick-stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        @media (max-width: 1024px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                order: -1;
            }

            .booking-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .skills-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="../mentors/browse.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Browse Mentors
        </a>

        <div class="profile-layout">
            <div class="profile-main">
                <!-- Profile Header -->
                <div class="card">
                    <div class="profile-header">
                        <img src="<?php echo htmlspecialchars($mentor['profile_photo'] ?: 'https://via.placeholder.com/120x120/667eea/ffffff?text=' . strtoupper(substr($mentor['first_name'], 0, 1))); ?>" 
                             alt="<?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>" 
                             class="profile-avatar">
                        
                        <div class="profile-info">
                            <h1 class="profile-name">
                                <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                            </h1>
                            
                            <?php if ($mentor['title']): ?>
                            <div class="profile-title"><?php echo htmlspecialchars($mentor['title']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($mentor['company']): ?>
                            <div class="profile-company">at <?php echo htmlspecialchars($mentor['company']); ?></div>
                            <?php endif; ?>
                            
                            <div class="rating-display">
                                <div class="rating-stars">
                                    <?php 
                                    $rating = floatval($mentor['rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++): 
                                        if ($i <= $rating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif;
                                    endfor; ?>
                                </div>
                                <span class="rating-text">
                                    <?php echo number_format($rating, 1); ?> 
                                    (<?php echo intval($mentor['total_sessions'] ?? 0); ?> sessions)
                                </span>
                            </div>
                            
                            <div class="availability-status <?php echo $isAvailable ? 'available' : 'busy'; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo $isAvailable ? 'Available for sessions' : 'Currently busy'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo intval($mentor['experience_years'] ?? 0); ?></div>
                            <div class="stat-label">Years Experience</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo intval($mentor['total_sessions'] ?? 0); ?></div>
                            <div class="stat-label">Total Sessions</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $recentSessionsCount; ?></div>
                            <div class="stat-label">Recent Sessions</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo $responseRate; ?>%</div>
                            <div class="stat-label">Response Rate</div>
                        </div>
                    </div>
                </div>

                <!-- About Section -->
                <?php if ($mentor['bio']): ?>
                <div class="card">
                    <h2 class="section-title">
                        <i class="fas fa-user"></i>
                        About Me
                    </h2>
                    <div class="bio-text">
                        <?php echo nl2br(htmlspecialchars($mentor['bio'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Skills Section -->
                <?php if (!empty($mentorSkills)): ?>
                <div class="card">
                    <h2 class="section-title">
                        <i class="fas fa-code"></i>
                        Skills & Expertise
                    </h2>
                    <div class="skills-grid">
                        <?php 
                        $skillsByCategory = [];
                        foreach ($mentorSkills as $skill) {
                            $skillsByCategory[$skill['category']][] = $skill;
                        }
                        
                        foreach ($skillsByCategory as $category => $skills):
                        ?>
                        <div class="skill-category">
                            <div class="skill-category-title"><?php echo htmlspecialchars($category); ?></div>
                            <?php foreach ($skills as $skill): ?>
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
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reviews Section -->
                <?php if (!empty($reviews)): ?>
                <div class="card">
                    <h2 class="section-title">
                        <i class="fas fa-star"></i>
                        Student Reviews
                    </h2>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <img src="<?php echo htmlspecialchars($review['profile_photo'] ?: 'https://via.placeholder.com/40x40/667eea/ffffff?text=' . strtoupper(substr($review['first_name'], 0, 1))); ?>" 
                                 alt="Student" class="review-avatar">
                            <div class="review-info">
                                <div class="review-author">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?>
                                </div>
                                <div class="review-date">
                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'far'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="profile-sidebar">
                <!-- Booking Card -->
                <div class="card booking-card">
                    <div class="rate-display">
                        $<?php echo number_format($mentor['hourly_rate'] ?? 0, 0); ?><span style="font-size: 1rem; color: var(--text-muted);">/hour</span>
                    </div>
                    
                    <a href="../sessions/book.php?mentor_id=<?php echo $mentor['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Book Session
                    </a>
                    
                    <a href="../messages/compose.php?recipient_id=<?php echo $mentor['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-envelope"></i>
                        Send Message
                    </a>
                    
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <div class="quick-stat-value">24h</div>
                            <div class="quick-stat-label">Avg Response</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-value">98%</div>
                            <div class="quick-stat-label">Success Rate</div>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="card">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Information
                    </h3>
                    
                    <div class="contact-info">
                        <?php if ($mentor['languages']): ?>
                        <div class="contact-item">
                            <i class="fas fa-language"></i>
                            <span>Languages: <?php echo htmlspecialchars($mentor['languages']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mentor['availability']): ?>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars($mentor['availability']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="contact-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Member since <?php echo date('M Y', strtotime($mentor['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($mentor['linkedin_url'] || $mentor['github_url'] || $mentor['portfolio_url']): ?>
                    <div class="social-links">
                        <?php if ($mentor['linkedin_url']): ?>
                        <a href="<?php echo htmlspecialchars($mentor['linkedin_url']); ?>" target="_blank" class="social-link">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($mentor['github_url']): ?>
                        <a href="<?php echo htmlspecialchars($mentor['github_url']); ?>" target="_blank" class="social-link">
                            <i class="fab fa-github"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($mentor['portfolio_url']): ?>
                        <a href="<?php echo htmlspecialchars($mentor['portfolio_url']); ?>" target="_blank" class="social-link">
                            <i class="fas fa-globe"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
