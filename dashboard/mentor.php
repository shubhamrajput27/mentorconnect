<?php
require_once '../config/config.php';
requireRole('mentor');

$user = getCurrentUser();

// Get mentor statistics
$stats = [
    'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$user['id']])['count'],
    'upcoming_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND scheduled_at > NOW() AND status = 'scheduled'", [$user['id']])['count'],
    'total_students' => fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM sessions WHERE mentor_id = ?", [$user['id']])['count'],
    'avg_rating' => fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']])['avg'] ?? 0
];

// Get recent sessions
$recentSessions = fetchAll(
    "SELECT s.*, u.first_name, u.last_name, u.profile_photo 
     FROM sessions s 
     JOIN users u ON s.student_id = u.id 
     WHERE s.mentor_id = ? 
     ORDER BY s.scheduled_at DESC 
     LIMIT 5",
    [$user['id']]
);

// Get upcoming sessions
$upcomingSessions = fetchAll(
    "SELECT s.*, u.first_name, u.last_name, u.profile_photo 
     FROM sessions s 
     JOIN users u ON s.student_id = u.id 
     WHERE s.mentor_id = ? AND s.scheduled_at > NOW() AND s.status = 'scheduled'
     ORDER BY s.scheduled_at ASC 
     LIMIT 5",
    [$user['id']]
);

// Get recent messages
$recentMessages = fetchAll(
    "SELECT m.*, u.first_name, u.last_name, u.profile_photo 
     FROM messages m 
     JOIN users u ON m.sender_id = u.id 
     WHERE m.receiver_id = ? 
     ORDER BY m.created_at DESC 
     LIMIT 5",
    [$user['id']]
);

// Get notifications
$notifications = fetchAll(
    "SELECT * FROM notifications 
     WHERE user_id = ? AND is_read = FALSE 
     ORDER BY created_at DESC 
     LIMIT 5",
    [$user['id']]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - <?php echo APP_NAME; ?></title>
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
                    <a href="<?php echo BASE_URL; ?>/dashboard/mentor.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/sessions/index.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Sessions</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/mentors/browse.php" class="nav-link">
                        <i class="fas fa-user-graduate"></i>
                        <span>My Students</span>
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
                    <a href="<?php echo BASE_URL; ?>/reviews/index.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reviews</span>
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
                        <input type="text" placeholder="Search students, sessions...">
                    </div>
                </div>
                
                <div class="header-right">
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <button class="notifications-btn">
                        <i class="fas fa-bell"></i>
                        <?php if (count($notifications) > 0): ?>
                            <span class="notification-badge"><?php echo count($notifications); ?></span>
                        <?php endif; ?>
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
                    <p>Here's what's happening with your mentoring activities.</p>
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
                                    <p class="text-muted mb-sm">Total Students</p>
                                    <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
                                </div>
                                <div style="background-color: var(--accent-color); padding: 12px; border-radius: 12px; color: white;">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-muted mb-sm">Average Rating</p>
                                    <h3 class="mb-0"><?php echo number_format($stats['avg_rating'], 1); ?></h3>
                                </div>
                                <div style="background-color: var(--success-color); padding: 12px; border-radius: 12px; color: white;">
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-lg">
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
                                    <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>No upcoming sessions scheduled.</p>
                                    <a href="<?php echo BASE_URL; ?>/sessions/index.php" class="btn btn-primary">Schedule Session</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcomingSessions as $session): ?>
                                    <div class="flex items-center gap-md mb-md">
                                        <img src="<?php echo $session['profile_photo'] ? '../uploads/' . $session['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                             alt="Student" style="width: 40px; height: 40px; border-radius: 50%;">
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
                                            <button class="btn btn-sm btn-outline">
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

                    <!-- Recent Messages -->
                    <div class="card">
                        <div class="card-header">
                            <div class="flex items-center justify-between">
                                <h3>Recent Messages</h3>
                                <a href="<?php echo BASE_URL; ?>/messages/index.php" class="btn btn-sm btn-outline">View All</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentMessages)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-envelope-open" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>No recent messages.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentMessages as $message): ?>
                                    <div class="flex items-start gap-md mb-md">
                                        <img src="<?php echo $message['profile_photo'] ? '../uploads/' . $message['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                             alt="Student" style="width: 32px; height: 32px; border-radius: 50%;">
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-xs">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?></h6>
                                                <small class="text-muted"><?php echo formatTimeAgo($message['created_at']); ?></small>
                                            </div>
                                            <p class="text-muted mb-0" style="font-size: 0.875rem;">
                                                <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>
                                                <?php if (strlen($message['message']) > 100): ?>...<?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card mt-lg">
                    <div class="card-header">
                        <h3>Recent Sessions</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentSessions)): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-history" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>No recent sessions.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Session</th>
                                            <th>Date</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentSessions as $session): ?>
                                            <tr>
                                                <td>
                                                    <div class="flex items-center gap-sm">
                                                        <img src="<?php echo $session['profile_photo'] ? '../uploads/' . $session['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                                             alt="Student" style="width: 32px; height: 32px; border-radius: 50%;">
                                                        <span><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($session['title']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($session['scheduled_at'])); ?></td>
                                                <td><?php echo $session['duration_minutes']; ?> min</td>
                                                <td>
                                                    <span class="badge badge-<?php echo $session['status']; ?>">
                                                        <?php echo ucfirst($session['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex gap-sm">
                                                        <button class="btn btn-sm btn-ghost">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($session['status'] === 'completed'): ?>
                                                            <button class="btn btn-sm btn-ghost">
                                                                <i class="fas fa-star"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
