<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';
requireRole('student');

$user = getCurrentUser();

// Get student statistics with error handling
try {
    $stats = [
        'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ?", [$user['id']])['count'] ?? 0,
        'upcoming_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ? AND scheduled_at > NOW() AND status = 'scheduled'", [$user['id']])['count'] ?? 0,
        'completed_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ? AND status = 'completed'", [$user['id']])['count'] ?? 0,
        'mentors_count' => fetchOne("SELECT COUNT(DISTINCT mentor_id) as count FROM sessions WHERE student_id = ?", [$user['id']])['count'] ?? 0
    ];
} catch (Exception $e) {
    $stats = ['total_sessions' => 0, 'upcoming_sessions' => 0, 'completed_sessions' => 0, 'mentors_count' => 0];
    error_log("Student dashboard stats error: " . $e->getMessage());
}

// Get upcoming sessions with error handling
try {
    $upcomingSessions = fetchAll(
        "SELECT s.*, u.first_name, u.last_name, u.profile_photo, mp.title, mp.company 
         FROM sessions s 
         JOIN users u ON s.mentor_id = u.id 
         LEFT JOIN mentor_profiles mp ON u.id = mp.user_id
         WHERE s.student_id = ? AND s.scheduled_at > NOW() AND s.status = 'scheduled'
         ORDER BY s.scheduled_at ASC 
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) {
    $upcomingSessions = [];
    error_log("Student upcoming sessions error: " . $e->getMessage());
}

// Get recommended mentors with error handling
try {
    $recommendedMentors = fetchAll(
        "SELECT u.*, mp.title, mp.company, mp.rating, mp.hourly_rate, mp.experience_years
         FROM users u 
         JOIN mentor_profiles mp ON u.id = mp.user_id 
         WHERE u.role = 'mentor' AND u.status = 'active' AND mp.is_verified = TRUE
         ORDER BY mp.rating DESC, mp.total_sessions DESC 
         LIMIT 6"
    );
} catch (Exception $e) {
    $recommendedMentors = [];
    error_log("Student recommended mentors error: " . $e->getMessage());
}

// Get recent messages with error handling
try {
    $recentMessages = fetchAll(
        "SELECT m.*, u.first_name, u.last_name, u.profile_photo 
         FROM messages m 
         JOIN users u ON m.sender_id = u.id 
         WHERE m.recipient_id = ? 
         ORDER BY m.created_at DESC 
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) {
    $recentMessages = [];
    error_log("Student recent messages error: " . $e->getMessage());
}

// Get learning progress (completed sessions by skill) with error handling
try {
    $learningProgress = fetchAll(
        "SELECT sk.name as skill_name, COUNT(s.id) as session_count
         FROM sessions s
         JOIN users mentor ON s.mentor_id = mentor.id
         JOIN user_skills us ON mentor.id = us.user_id
         JOIN skills sk ON us.skill_id = sk.id
         WHERE s.student_id = ? AND s.status = 'completed'
         GROUP BY sk.id, sk.name
         ORDER BY session_count DESC
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) {
    $learningProgress = [];
    error_log("Student learning progress error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
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
                    <a href="<?php echo BASE_URL; ?>/dashboard/student.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/mentors/browse.php" class="nav-link">
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
                    <a href="<?php echo BASE_URL; ?>/reviews/index.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>My Progress</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/profile/edit.php" class="nav-link">
                        <i class="fas fa-user-edit"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/files/index.php" class="nav-link">
                        <i class="fas fa-folder"></i>
                        <span>Files</span>
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
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search mentors, skills...">
                    </div>
                </div>
                
                <div class="header-right">
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <button class="notifications-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" style="display: none;">0</span>
                    </button>
                    
                    <div class="user-menu">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="content">
                <div class="page-header">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                    <p>Continue your learning journey with expert mentors.</p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-4 mb-lg">
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-muted mb-sm">Total Sessions</p>
                                    <h3 class="mb-0"><?php echo $stats['total_sessions']; ?></h3>
                                </div>
                                <div class="bg-primary" style="padding: 12px; border-radius: 12px; color: white;">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-muted mb-sm">Upcoming Sessions</p>
                                    <h3 class="mb-0"><?php echo $stats['upcoming_sessions']; ?></h3>
                                </div>
                                <div class="bg-secondary" style="padding: 12px; border-radius: 12px; color: white;">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-muted mb-sm">Completed Sessions</p>
                                    <h3 class="mb-0"><?php echo $stats['completed_sessions']; ?></h3>
                                </div>
                                <div style="background-color: var(--success-color); padding: 12px; border-radius: 12px; color: white;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-muted mb-sm">Mentors</p>
                                    <h3 class="mb-0"><?php echo $stats['mentors_count']; ?></h3>
                                </div>
                                <div style="background-color: var(--accent-color); padding: 12px; border-radius: 12px; color: white;">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-lg mb-lg">
                    <!-- Upcoming Sessions -->
                    <div class="card">
                        <div class="card-header">
                            <div class="flex items-center justify-between">
                                <h3>Upcoming Sessions</h3>
                                <a href="<?php echo BASE_URL; ?>/sessions/index.php" class="btn btn-sm btn-outline">View All</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcomingSessions)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-calendar-plus" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>No upcoming sessions scheduled.</p>
                                    <a href="<?php echo BASE_URL; ?>/mentors/browse.php" class="btn btn-primary">Find a Mentor</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcomingSessions as $session): ?>
                                    <div class="flex items-center gap-md mb-md">
                                        <img src="<?php echo $session['profile_photo'] ? '../uploads/' . $session['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                             alt="Mentor" style="width: 40px; height: 40px; border-radius: 50%;">
                                        <div class="flex-1">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($session['title']); ?></h5>
                                            <p class="text-muted mb-0">
                                                with <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($session['scheduled_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="flex gap-sm">
                                            <button class="btn btn-sm btn-primary">
                                                <i class="fas fa-video"></i>
                                            </button>
                                            <button class="btn btn-sm btn-ghost">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Learning Progress -->
                    <div class="card">
                        <div class="card-header">
                            <div class="flex items-center justify-between">
                                <h3>Learning Progress</h3>
                                <a href="<?php echo BASE_URL; ?>/reviews/index.php" class="btn btn-sm btn-outline">View Details</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($learningProgress)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>Start learning to see your progress.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($learningProgress as $progress): ?>
                                    <div class="flex items-center justify-between mb-md">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($progress['skill_name']); ?></h6>
                                            <small class="text-muted"><?php echo $progress['session_count']; ?> sessions completed</small>
                                        </div>
                                        <div class="progress-circle">
                                            <span><?php echo min(100, $progress['session_count'] * 10); ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recommended Mentors -->
                <div class="card">
                    <div class="card-header">
                        <div class="flex items-center justify-between">
                            <h3>Recommended Mentors</h3>
                            <a href="<?php echo BASE_URL; ?>/mentors/browse.php" class="btn btn-sm btn-outline">Browse All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-3 gap-md">
                            <?php foreach ($recommendedMentors as $mentor): ?>
                                <div class="mentor-card">
                                    <div class="mentor-avatar">
                                        <img src="<?php echo $mentor['profile_photo'] ? '../uploads/' . $mentor['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($mentor['first_name']); ?>">
                                    </div>
                                    <div class="mentor-info">
                                        <h5><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h5>
                                        <p class="text-muted"><?php echo htmlspecialchars($mentor['title'] ?? 'Mentor'); ?></p>
                                        <?php if ($mentor['company']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($mentor['company']); ?></small>
                                        <?php endif; ?>
                                        <div class="mentor-stats">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <span><?php echo number_format($mentor['rating'], 1); ?></span>
                                            </div>
                                            <div class="rate">
                                                $<?php echo number_format($mentor['hourly_rate'], 0); ?>/hr
                                            </div>
                                        </div>
                                        <div class="mentor-actions">
                                            <button class="btn btn-sm btn-primary">Book Session</button>
                                            <button class="btn btn-sm btn-outline">Message</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    .mentor-card {
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        text-align: center;
        transition: all var(--transition-fast);
    }

    .mentor-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .mentor-avatar img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-bottom: var(--spacing-sm);
    }

    .mentor-info h5 {
        margin-bottom: var(--spacing-xs);
    }

    .mentor-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: var(--spacing-sm) 0;
        padding: var(--spacing-sm) 0;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
    }

    .rating {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        color: var(--warning-color);
    }

    .mentor-actions {
        display: flex;
        gap: var(--spacing-sm);
        margin-top: var(--spacing-md);
    }

    .mentor-actions .btn {
        flex: 1;
    }

    .progress-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: conic-gradient(var(--primary-color) 0deg, var(--surface-color) 0deg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: var(--spacing-sm) var(--spacing-md);
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .table th {
        font-weight: 600;
        color: var(--text-primary);
        background-color: var(--surface-color);
    }

    .badge {
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .badge-scheduled {
        background-color: rgba(59, 130, 246, 0.1);
        color: var(--info-color);
    }

    .badge-completed {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
    }

    .badge-cancelled {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--error-color);
    }
    </style>

    <script src="../assets/js/app.js"></script>
</body>
</html>
