<?php
require_once '../config/optimized-config.php';
requireRole('student');

$user = getCurrentUser();

// Get search parameters
$search = sanitizeInput($_GET['search'] ?? '');
$skill = intval($_GET['skill'] ?? 0);
$minRating = floatval($_GET['min_rating'] ?? 0);
$maxRate = floatval($_GET['max_rate'] ?? 0);
$experience = intval($_GET['experience'] ?? 0);
$availability = sanitizeInput($_GET['availability'] ?? '');
$sortBy = sanitizeInput($_GET['sort'] ?? 'rating');

// Build search query
$whereConditions = ["u.role = 'mentor'", "u.status = 'active'", "mp.is_verified = TRUE"];
$params = [];

if ($search) {
    $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR mp.title LIKE ? OR mp.company LIKE ? OR u.bio LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($skill > 0) {
    $whereConditions[] = "EXISTS (SELECT 1 FROM user_skills us WHERE us.user_id = u.id AND us.skill_id = ?)";
    $params[] = $skill;
}

if ($minRating > 0) {
    $whereConditions[] = "mp.rating >= ?";
    $params[] = $minRating;
}

if ($maxRate > 0) {
    $whereConditions[] = "mp.hourly_rate <= ?";
    $params[] = $maxRate;
}

if ($experience > 0) {
    $whereConditions[] = "mp.experience_years >= ?";
    $params[] = $experience;
}

// Sort options
$sortOptions = [
    'rating' => 'mp.rating DESC',
    'rate_low' => 'mp.hourly_rate ASC',
    'rate_high' => 'mp.hourly_rate DESC',
    'experience' => 'mp.experience_years DESC',
    'sessions' => 'mp.total_sessions DESC'
];

$orderBy = $sortOptions[$sortBy] ?? 'mp.rating DESC';

$whereClause = implode(' AND ', $whereConditions);

// Get mentors with optimized query and pagination
$limit = min(intval($_GET['limit'] ?? 20), 50);
$offset = max(0, intval($_GET['page'] ?? 0)) * $limit;

$mentors = fetchAll(
    "SELECT u.id, u.first_name, u.last_name, u.profile_photo, u.bio,
            mp.title, mp.company, mp.rating, mp.hourly_rate, mp.experience_years, 
            mp.total_sessions, mp.is_available
     FROM users u 
     INNER JOIN mentor_profiles mp ON u.id = mp.user_id 
     WHERE $whereClause AND mp.is_available = 1
     ORDER BY $orderBy
     LIMIT $limit OFFSET $offset",
    $params
);

// Get all skills for filter with caching
$cacheKey = 'skills_list_' . md5('all_skills');
$skills = null;

if (function_exists('apcu_fetch')) {
    $skills = apcu_fetch($cacheKey);
}

if ($skills === false || $skills === null) {
    $skills = fetchAll(
        "SELECT id, name, category 
         FROM skills 
         WHERE id IN (SELECT DISTINCT skill_id FROM user_skills WHERE skill_type IN ('teaching', 'both'))
         ORDER BY category, name"
    );
    
    if (function_exists('apcu_store')) {
        apcu_store($cacheKey, $skills, 300); // Cache for 5 minutes
    }
}

// Optimize mentor skills with caching and single query
$mentorSkills = [];
if (!empty($mentors)) {
    $mentorIds = array_column($mentors, 'id');
    if (!empty($mentorIds)) {
        $placeholders = str_repeat('?,', count($mentorIds) - 1) . '?';
        $allMentorSkills = fetchAll(
            "SELECT us.user_id, s.name, s.category, us.proficiency_level,
                    us.skill_type
             FROM user_skills us 
             INNER JOIN skills s ON us.skill_id = s.id 
             WHERE us.user_id IN ($placeholders) 
               AND (us.skill_type = 'teaching' OR us.skill_type = 'both')
             ORDER BY us.proficiency_level DESC, s.name
             LIMIT " . (count($mentorIds) * 6),
            $mentorIds
        );
        
        // Group skills by mentor with limit
        foreach ($allMentorSkills as $skill) {
            if (!isset($mentorSkills[$skill['user_id']]) || count($mentorSkills[$skill['user_id']]) < 5) {
                $mentorSkills[$skill['user_id']][] = $skill;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Mentors - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/connections-optimized.css">
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
                    <a href="<?php echo BASE_URL; ?>/dashboard/student.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/mentors/browse.php" class="nav-link active">
                        <i class="fas fa-search"></i>
                        <span>Find Mentors</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/sessions/index.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>My Sessions</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/messages/index.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/profile/edit.php" class="nav-link">
                        <i class="fas fa-user-edit"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="nav-link">
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
                    <h2>Browse Mentors</h2>
                </div>
                
                <div class="header-right">
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <div class="user-menu">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-profile.svg'; ?>" 
                             alt="Profile" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Browse Content -->
            <div class="content">
                <!-- Search and Filters -->
                <div class="search-filters-container">
                    <form method="GET" class="search-form" id="searchForm">
                        <div class="search-row">
                            <div class="search-input-group">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search mentors by name, title, company..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Search
                            </button>
                        </div>
                        
                        <div class="filters-row">
                            <div class="filter-group">
                                <label>Skill</label>
                                <select name="skill">
                                    <option value="">All Skills</option>
                                    <?php 
                                    $currentCategory = '';
                                    foreach ($skills as $skillOption): 
                                        if ($skillOption['category'] !== $currentCategory):
                                            if ($currentCategory !== '') echo '</optgroup>';
                                            $currentCategory = $skillOption['category'];
                                            echo '<optgroup label="' . htmlspecialchars($currentCategory) . '">';
                                        endif;
                                    ?>
                                        <option value="<?php echo $skillOption['id']; ?>" 
                                                <?php echo $skill == $skillOption['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($skillOption['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Min Rating</label>
                                <select name="min_rating">
                                    <option value="">Any Rating</option>
                                    <option value="4.5" <?php echo $minRating == 4.5 ? 'selected' : ''; ?>>4.5+ Stars</option>
                                    <option value="4.0" <?php echo $minRating == 4.0 ? 'selected' : ''; ?>>4.0+ Stars</option>
                                    <option value="3.5" <?php echo $minRating == 3.5 ? 'selected' : ''; ?>>3.5+ Stars</option>
                                    <option value="3.0" <?php echo $minRating == 3.0 ? 'selected' : ''; ?>>3.0+ Stars</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Max Rate ($/hr)</label>
                                <select name="max_rate">
                                    <option value="">Any Rate</option>
                                    <option value="50" <?php echo $maxRate == 50 ? 'selected' : ''; ?>>Under $50</option>
                                    <option value="75" <?php echo $maxRate == 75 ? 'selected' : ''; ?>>Under $75</option>
                                    <option value="100" <?php echo $maxRate == 100 ? 'selected' : ''; ?>>Under $100</option>
                                    <option value="150" <?php echo $maxRate == 150 ? 'selected' : ''; ?>>Under $150</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Experience</label>
                                <select name="experience">
                                    <option value="">Any Experience</option>
                                    <option value="1" <?php echo $experience == 1 ? 'selected' : ''; ?>>1+ Years</option>
                                    <option value="3" <?php echo $experience == 3 ? 'selected' : ''; ?>>3+ Years</option>
                                    <option value="5" <?php echo $experience == 5 ? 'selected' : ''; ?>>5+ Years</option>
                                    <option value="10" <?php echo $experience == 10 ? 'selected' : ''; ?>>10+ Years</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Sort By</label>
                                <select name="sort">
                                    <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                                    <option value="rate_low" <?php echo $sortBy === 'rate_low' ? 'selected' : ''; ?>>Lowest Rate</option>
                                    <option value="rate_high" <?php echo $sortBy === 'rate_high' ? 'selected' : ''; ?>>Highest Rate</option>
                                    <option value="experience" <?php echo $sortBy === 'experience' ? 'selected' : ''; ?>>Most Experienced</option>
                                    <option value="sessions" <?php echo $sortBy === 'sessions' ? 'selected' : ''; ?>>Most Sessions</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <a href="/mentors/browse.php" class="btn btn-ghost">Clear Filters</a>
                        </div>
                    </form>
                </div>

                <!-- Results -->
                <div class="results-container">
                    <div class="results-header">
                        <h3><?php echo count($mentors); ?> Mentors Found</h3>
                    </div>
                    
                    <div class="mentors-grid">
                        <?php if (empty($mentors)): ?>
                            <div class="no-results">
                                <i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h3>No mentors found</h3>
                                <p>Try adjusting your search criteria or browse all mentors.</p>
                                <a href="/mentors/browse.php" class="btn btn-primary">Browse All Mentors</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($mentors as $mentor): ?>
                                <div class="mentor-card">
                                    <div class="mentor-header">
                                        <img src="<?php echo $mentor['profile_photo'] ? '../uploads/' . $mentor['profile_photo'] : '../assets/images/default-profile.svg'; ?>" 
                                             alt="<?php echo htmlspecialchars($mentor['first_name']); ?>" class="mentor-avatar">
                                        <div class="mentor-basic-info">
                                            <h4><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h4>
                                            <p class="mentor-title"><?php echo htmlspecialchars($mentor['title'] ?? 'Mentor'); ?></p>
                                            <?php if ($mentor['company']): ?>
                                                <p class="mentor-company"><?php echo htmlspecialchars($mentor['company']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mentor-rating">
                                            <div class="rating-stars">
                                                <?php 
                                                $rating = floatval($mentor['rating']);
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
                                            <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="mentor-bio">
                                        <p><?php echo htmlspecialchars(substr($mentor['bio'] ?? '', 0, 150)); ?>
                                           <?php if (strlen($mentor['bio'] ?? '') > 150): ?>...<?php endif; ?></p>
                                    </div>
                                    
                                    <div class="mentor-skills">
                                        <?php if (isset($mentorSkills[$mentor['id']])): ?>
                                            <?php foreach (array_slice($mentorSkills[$mentor['id']], 0, 4) as $skill): ?>
                                                <span class="skill-tag"><?php echo htmlspecialchars($skill['name']); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($mentorSkills[$mentor['id']]) > 4): ?>
                                                <span class="skill-tag more">+<?php echo count($mentorSkills[$mentor['id']]) - 4; ?> more</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mentor-stats">
                                        <div class="stat">
                                            <i class="fas fa-briefcase"></i>
                                            <span><?php echo $mentor['experience_years']; ?> years</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-calendar-check"></i>
                                            <span><?php echo $mentor['total_sessions']; ?> sessions</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span>$<?php echo number_format($mentor['hourly_rate'], 0); ?>/hr</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mentor-actions">
                                        <button class="btn btn-primary" onclick="connectWithMentor(<?php echo $mentor['id']; ?>)">
                                            <i class="fas fa-handshake"></i>
                                            Connect
                                        </button>
                                        <button class="btn btn-outline" onclick="bookSession(<?php echo $mentor['id']; ?>)">
                                            <i class="fas fa-calendar-plus"></i>
                                            Book Session
                                        </button>
                                        <button class="btn btn-ghost" onclick="sendMessage(<?php echo $mentor['id']; ?>)">
                                            <i class="fas fa-envelope"></i>
                                            Message
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .search-filters-container {
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-xl);
        padding: var(--spacing-lg);
        margin-bottom: var(--spacing-lg);
        box-shadow: var(--shadow-sm);
    }

    .search-row {
        display: flex;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
        align-items: center;
    }

    .search-input-group {
        position: relative;
        flex: 1;
    }

    .search-input-group i {
        position: absolute;
        left: var(--spacing-md);
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }

    .search-input-group input {
        padding-left: 2.5rem;
        height: 48px;
        font-size: 1rem;
    }

    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-md);
    }

    .filter-group label {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--spacing-sm);
    }

    .filter-actions {
        display: flex;
        justify-content: flex-end;
    }

    .results-container {
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-xl);
        overflow: hidden;
    }

    .results-header {
        padding: var(--spacing-lg);
        border-bottom: 1px solid var(--border-color);
        background: var(--surface-color);
    }

    .results-header h3 {
        margin: 0;
        color: var(--text-primary);
    }

    .mentors-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: var(--spacing-lg);
        padding: var(--spacing-lg);
    }

    .mentor-card {
        background: var(--background-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        transition: all var(--transition-fast);
    }

    .mentor-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }

    .mentor-header {
        display: flex;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-md);
        align-items: flex-start;
    }

    .mentor-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }

    .mentor-basic-info {
        flex: 1;
    }

    .mentor-basic-info h4 {
        margin: 0 0 var(--spacing-xs) 0;
        color: var(--text-primary);
    }

    .mentor-title {
        color: var(--primary-color);
        font-weight: 500;
        margin: 0 0 var(--spacing-xs) 0;
    }

    .mentor-company {
        color: var(--text-muted);
        font-size: 0.875rem;
        margin: 0;
    }

    .mentor-rating {
        text-align: right;
    }

    .rating-stars {
        color: var(--warning-color);
        margin-bottom: var(--spacing-xs);
    }

    .rating-value {
        font-weight: 600;
        color: var(--text-primary);
    }

    .mentor-bio {
        margin-bottom: var(--spacing-md);
    }

    .mentor-bio p {
        color: var(--text-secondary);
        line-height: 1.5;
        margin: 0;
    }

    .mentor-skills {
        display: flex;
        flex-wrap: wrap;
        gap: var(--spacing-xs);
        margin-bottom: var(--spacing-md);
    }

    .skill-tag {
        background: var(--surface-color);
        color: var(--text-secondary);
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid var(--border-color);
    }

    .skill-tag.more {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    .mentor-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: var(--spacing-md);
        padding: var(--spacing-sm) 0;
        border-top: 1px solid var(--divider-color);
        border-bottom: 1px solid var(--divider-color);
    }

    .stat {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .mentor-actions {
        display: flex;
        gap: var(--spacing-sm);
    }

    .mentor-actions .btn {
        flex: 1;
        font-size: 0.875rem;
        padding: var(--spacing-sm);
    }

    .no-results {
        grid-column: 1 / -1;
        text-align: center;
        padding: var(--spacing-2xl);
        color: var(--text-muted);
    }

    @media (max-width: 768px) {
        .search-row {
            flex-direction: column;
        }
        
        .filters-row {
            grid-template-columns: 1fr;
        }
        
        .mentors-grid {
            grid-template-columns: 1fr;
        }
        
        .mentor-actions {
            flex-direction: column;
        }
    }
    </style>



    <script>
    // Auto-submit form when filters change
    document.querySelectorAll('#searchForm select').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('searchForm').submit();
        });
    });

    function connectWithMentor(mentorId) {
        window.location.href = `connect.php?mentor_id=${mentorId}`;
    }



    function bookSession(mentorId) {
        window.location.href = `/sessions/book.php?mentor_id=${mentorId}`;
    }

    function sendMessage(mentorId) {
        window.location.href = `/messages/compose.php?recipient_id=${mentorId}`;
    }

    function viewProfile(mentorId) {
        window.location.href = `/mentors/profile.php?id=${mentorId}`;
    }


    </script>

    <script src="../assets/js/app.js"></script>
</body>
</html>
