<?php
require_once '../config/config.php';

// Require login
requireLogin();

$user = getCurrentUser();
$pageTitle = ucfirst($user['role']) . ' Dashboard';

// Cache key for dashboard data
$cacheKey = 'dashboard_' . $user['id'] . '_' . $user['role'];

// Try to get dashboard data from cache
$dashboardData = cache_remember($cacheKey, function() use ($user) {
    $data = [];
    
    if ($user['role'] === 'mentor') {
        // Mentor dashboard data
        $data['upcoming_sessions'] = fetchAll(
            "SELECT s.*, u.first_name, u.last_name, u.email 
             FROM sessions s 
             JOIN users u ON s.student_id = u.id 
             WHERE s.mentor_id = ? AND s.scheduled_at > NOW() AND s.status = 'scheduled'
             ORDER BY s.scheduled_at ASC LIMIT 5",
            [$user['id']]
        );
        
        $data['stats'] = [
            'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$user['id']])['count'],
            'completed_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND status = 'completed'", [$user['id']])['count'],
            'avg_rating' => fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']])['avg'] ?? 0,
            'unread_messages' => fetchOne("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = FALSE", [$user['id']])['count']
        ];
        
        $data['recent_reviews'] = fetchAll(
            "SELECT r.*, u.first_name, u.last_name 
             FROM reviews r 
             JOIN users u ON r.reviewer_id = u.id 
             WHERE r.reviewee_id = ? 
             ORDER BY r.created_at DESC LIMIT 3",
            [$user['id']]
        );
        
    } else {
        // Student dashboard data
        $data['upcoming_sessions'] = fetchAll(
            "SELECT s.*, u.first_name, u.last_name, u.email 
             FROM sessions s 
             JOIN users u ON s.mentor_id = u.id 
             WHERE s.student_id = ? AND s.scheduled_at > NOW() AND s.status = 'scheduled'
             ORDER BY s.scheduled_at ASC LIMIT 5",
            [$user['id']]
        );
        
        $data['stats'] = [
            'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ?", [$user['id']])['count'],
            'completed_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ? AND status = 'completed'", [$user['id']])['count'],
            'mentors_worked_with' => fetchOne("SELECT COUNT(DISTINCT mentor_id) as count FROM sessions WHERE student_id = ?", [$user['id']])['count'],
            'unread_messages' => fetchOne("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = FALSE", [$user['id']])['count']
        ];
        
        $data['recommended_mentors'] = fetchAll(
            "SELECT u.*, AVG(r.rating) as avg_rating 
             FROM users u 
             LEFT JOIN reviews r ON u.id = r.reviewee_id 
             WHERE u.role = 'mentor' AND u.status = 'active' 
             GROUP BY u.id 
             ORDER BY avg_rating DESC LIMIT 4"
        );
    }
    
    // Get recent notifications for all users
    $data['notifications'] = fetchAll(
        "SELECT * FROM notifications 
         WHERE user_id = ? 
         ORDER BY created_at DESC LIMIT 5",
        [$user['id']]
    );
    
    return $data;
}, 300); // Cache for 5 minutes

// Generate CSRF token for forms
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $user['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - MentorConnect</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="../assets/optimized.css" as="style">
    <link rel="preload" href="../assets/optimized.js" as="script">
    
    <!-- Critical CSS inline for faster rendering -->
    <style>
        .dashboard-loading { display: flex; justify-content: center; align-items: center; height: 100vh; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #6366f1; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
    
    <!-- Optimized CSS -->
    <link rel="stylesheet" href="../assets/optimized.css?v=<?php echo filemtime('../assets/optimized.css'); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
</head>
<body>
    <!-- Loading indicator -->
    <div id="loading" class="dashboard-loading">
        <div class="spinner"></div>
    </div>
    
    <!-- Main Dashboard -->
    <div id="dashboard" class="app-layout" style="display: none;">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header p-6">
                <a href="../index.php" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>MentorConnect</span>
                </a>
            </div>
            
            <nav class="sidebar-nav p-4">
                <ul class="nav">
                    <li><a href="<?php echo $user['role']; ?>.php" class="nav-link active">
                        <i class="fas fa-home"></i> Dashboard
                    </a></li>
                    
                    <?php if ($user['role'] === 'mentor'): ?>
                        <li><a href="../sessions/manage.php" class="nav-link">
                            <i class="fas fa-calendar"></i> Sessions
                        </a></li>
                        <li><a href="../profile/mentor.php" class="nav-link">
                            <i class="fas fa-user"></i> Profile
                        </a></li>
                    <?php else: ?>
                        <li><a href="../mentors/browse.php" class="nav-link">
                            <i class="fas fa-search"></i> Find Mentors
                        </a></li>
                        <li><a href="../sessions/upcoming.php" class="nav-link">
                            <i class="fas fa-calendar"></i> My Sessions
                        </a></li>
                    <?php endif; ?>
                    
                    <li><a href="../messages/index.php" class="nav-link">
                        <i class="fas fa-envelope"></i> Messages
                        <?php if ($dashboardData['stats']['unread_messages'] > 0): ?>
                            <span class="badge bg-error"><?php echo $dashboardData['stats']['unread_messages']; ?></span>
                        <?php endif; ?>
                    </a></li>
                    
                    <li><a href="../profile/edit.php" class="nav-link">
                        <i class="fas fa-cog"></i> Settings
                    </a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="flex items-center gap-4">
                    <button class="menu-toggle btn btn-outline" style="display: none;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-2xl font-bold">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <div class="notification-dropdown">
                        <button class="btn btn-outline">
                            <i class="fas fa-bell"></i>
                            <?php if (count($dashboardData['notifications']) > 0): ?>
                                <span class="notification-badge"><?php echo count($dashboardData['notifications']); ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="user-menu">
                        <a href="../profile/edit.php" class="btn btn-outline">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <a href="../auth/logout.php" class="btn btn-primary">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <div class="content">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 mb-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-secondary mb-1">Total Sessions</p>
                                    <h3 class="text-2xl font-bold"><?php echo $dashboardData['stats']['total_sessions']; ?></h3>
                                </div>
                                <i class="fas fa-calendar-alt text-primary text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-secondary mb-1">Completed</p>
                                    <h3 class="text-2xl font-bold"><?php echo $dashboardData['stats']['completed_sessions']; ?></h3>
                                </div>
                                <i class="fas fa-check-circle text-success text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($user['role'] === 'mentor'): ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-secondary mb-1">Avg Rating</p>
                                        <h3 class="text-2xl font-bold"><?php echo number_format($dashboardData['stats']['avg_rating'], 1); ?></h3>
                                    </div>
                                    <i class="fas fa-star text-warning text-3xl"></i>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-secondary mb-1">Mentors</p>
                                        <h3 class="text-2xl font-bold"><?php echo $dashboardData['stats']['mentors_worked_with']; ?></h3>
                                    </div>
                                    <i class="fas fa-users text-info text-3xl"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-secondary mb-1">Messages</p>
                                    <h3 class="text-2xl font-bold"><?php echo $dashboardData['stats']['unread_messages']; ?></h3>
                                </div>
                                <i class="fas fa-envelope text-primary text-3xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Upcoming Sessions -->
                    <div class="lg:col-span-2">
                        <div class="card">
                            <div class="card-body">
                                <div class="flex items-center justify-between mb-6">
                                    <h2 class="text-xl font-bold">Upcoming Sessions</h2>
                                    <a href="../sessions/" class="btn btn-outline btn-sm">View All</a>
                                </div>
                                
                                <?php if (empty($dashboardData['upcoming_sessions'])): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-calendar-times text-6xl text-muted mb-4"></i>
                                        <p class="text-secondary">No upcoming sessions scheduled</p>
                                        <?php if ($user['role'] === 'student'): ?>
                                            <a href="../mentors/browse.php" class="btn btn-primary mt-4">Find a Mentor</a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($dashboardData['upcoming_sessions'] as $session): ?>
                                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                                <div>
                                                    <h4 class="font-semibold"><?php echo htmlspecialchars($session['title']); ?></h4>
                                                    <p class="text-secondary">
                                                        with <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                                    </p>
                                                    <p class="text-sm text-muted">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo date('M j, Y \a\t g:i A', strtotime($session['scheduled_at'])); ?>
                                                    </p>
                                                </div>
                                                <div class="flex gap-2">
                                                    <?php if ($session['meeting_link']): ?>
                                                        <a href="<?php echo htmlspecialchars($session['meeting_link']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fas fa-video"></i> Join
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="../sessions/view.php?id=<?php echo $session['id']; ?>" class="btn btn-sm btn-outline">
                                                        View
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar Content -->
                    <div>
                        <!-- Notifications -->
                        <div class="card mb-6">
                            <div class="card-body">
                                <h3 class="text-lg font-bold mb-4">Recent Notifications</h3>
                                
                                <?php if (empty($dashboardData['notifications'])): ?>
                                    <p class="text-secondary text-center py-4">No new notifications</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($dashboardData['notifications'] as $notification): ?>
                                            <div class="flex items-start gap-3 p-3 border-l-4 border-primary bg-gray-50 rounded">
                                                <i class="fas fa-bell text-primary mt-1"></i>
                                                <div class="flex-1">
                                                    <h5 class="font-semibold text-sm"><?php echo htmlspecialchars($notification['title']); ?></h5>
                                                    <?php if ($notification['message']): ?>
                                                        <p class="text-xs text-secondary mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <?php endif; ?>
                                                    <p class="text-xs text-muted mt-1">
                                                        <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Role-specific content -->
                        <?php if ($user['role'] === 'student' && !empty($dashboardData['recommended_mentors'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-lg font-bold mb-4">Recommended Mentors</h3>
                                    <div class="space-y-3">
                                        <?php foreach ($dashboardData['recommended_mentors'] as $mentor): ?>
                                            <div class="flex items-center gap-3 p-3 border rounded-lg">
                                                <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center">
                                                    <?php echo strtoupper($mentor['first_name'][0] . $mentor['last_name'][0]); ?>
                                                </div>
                                                <div class="flex-1">
                                                    <h5 class="font-semibold"><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h5>
                                                    <div class="flex items-center gap-1">
                                                        <div class="flex text-yellow-400">
                                                            <?php 
                                                            $rating = $mentor['avg_rating'] ?? 0;
                                                            for ($i = 1; $i <= 5; $i++): 
                                                            ?>
                                                                <i class="fas fa-star<?php echo $i <= $rating ? '' : '-o'; ?>"></i>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <span class="text-xs text-secondary">(<?php echo number_format($rating, 1); ?>)</span>
                                                    </div>
                                                </div>
                                                <a href="../mentors/profile.php?id=<?php echo $mentor['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($user['role'] === 'mentor' && !empty($dashboardData['recent_reviews'])): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-lg font-bold mb-4">Recent Reviews</h3>
                                    <div class="space-y-4">
                                        <?php foreach ($dashboardData['recent_reviews'] as $review): ?>
                                            <div class="p-3 border rounded-lg">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <div class="flex text-yellow-400">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="text-sm font-semibold"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                                                </div>
                                                <?php if ($review['comment']): ?>
                                                    <p class="text-sm text-secondary">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                                                <?php endif; ?>
                                                <p class="text-xs text-muted mt-2">
                                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Theme Toggle -->
    <button class="theme-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>
    
    <!-- Optimized JavaScript -->
    <script src="../assets/optimized.js?v=<?php echo filemtime('../assets/optimized.js'); ?>" defer></script>
    
    <!-- Dashboard specific JavaScript -->
    <script>
        // Hide loading and show dashboard when ready
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('dashboard').style.display = 'flex';
                document.body.classList.add('loaded');
            }, 100);
        });
        
        // Auto-refresh dashboard data every 5 minutes
        setInterval(function() {
            // Check for new notifications
            fetch('../api/notifications.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.unread_count > 0) {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                            badge.style.display = 'block';
                        }
                    }
                })
                .catch(error => console.log('Failed to check notifications:', error));
        }, 300000); // 5 minutes
        
        // Add loading states to links
        document.querySelectorAll('a[href*=".php"]').forEach(link => {
            link.addEventListener('click', function() {
                if (!this.getAttribute('target')) {
                    document.getElementById('loading').style.display = 'flex';
                }
            });
        });
    </script>
    
    <?php
    // Output performance information in debug mode
    if (DEBUG_MODE && isset($performanceMonitor)) {
        $performanceMonitor->endTimer('page_load');
        $report = perf_report();
        $cacheStats = cache_stats();
        
        echo "<!-- Performance Debug Info\n";
        echo "Page Load Time: {$report['execution_time']}s\n";
        echo "Memory Usage: {$report['memory_usage']['peak']}\n";
        echo "Database Queries: {$report['database_queries']}\n";
        echo "Cache Hit Ratio: {$cacheStats['hit_ratio']}%\n";
        echo "Performance Grade: {$report['performance_grade']}/100\n";
        echo "-->\n";
    }
    ?>
</body>
</html>