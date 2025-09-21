<?php
require_once '../config/config.php';
requireRole('mentor');

$user = getCurrentUser();

// Get mentor statistics with error handling
$stats = ['total_sessions' => 0, 'upcoming_sessions' => 0, 'total_students' => 0, 'avg_rating' => 0];

try {
    $stats['total_sessions'] = fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$user['id']])['count'] ?? 0;
} catch (Exception $e) { /* Table might not exist */ }

try {
    $stats['upcoming_sessions'] = fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND scheduled_at > NOW() AND status = 'scheduled'", [$user['id']])['count'] ?? 0;
} catch (Exception $e) { /* Table might not exist */ }

try {
    $stats['total_students'] = fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM sessions WHERE mentor_id = ?", [$user['id']])['count'] ?? 0;
} catch (Exception $e) { /* Table might not exist */ }

try {
    $stats['avg_rating'] = fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']])['avg'] ?? 0;
} catch (Exception $e) { /* Table might not exist */ }

// Get recent sessions with error handling
$recentSessions = [];
try {
    $recentSessions = fetchAll(
        "SELECT s.*, u.first_name, u.last_name, u.profile_photo 
         FROM sessions s 
         JOIN users u ON s.student_id = u.id 
         WHERE s.mentor_id = ? 
         ORDER BY s.scheduled_at DESC 
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) { /* Table might not exist */ }

// Get upcoming sessions with error handling
$upcomingSessions = [];
try {
    $upcomingSessions = fetchAll(
        "SELECT s.*, u.first_name, u.last_name, u.profile_photo 
         FROM sessions s 
         JOIN users u ON s.student_id = u.id 
         WHERE s.mentor_id = ? AND s.scheduled_at > NOW() AND s.status = 'scheduled'
         ORDER BY s.scheduled_at ASC 
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) { /* Table might not exist */ }

// Get recent messages with error handling
$recentMessages = [];
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
} catch (Exception $e) { /* Table might not exist */ }

// Get notifications with error handling
$notifications = [];
try {
    $notifications = fetchAll(
        "SELECT * FROM notifications 
         WHERE user_id = ? AND is_read = FALSE 
         ORDER BY created_at DESC 
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) { /* Table might not exist */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #334155; }
        
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; color: #3b82f6; }
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { display: flex; align-items: center; gap: 0.5rem; }
        .logout-btn { background: #ef4444; color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; text-decoration: none; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .welcome { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 2rem; border-radius: 1rem; margin-bottom: 2rem; }
        .welcome h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #64748b; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1e293b; }
        .stat-card .icon { float: right; font-size: 2rem; color: #3b82f6; opacity: 0.6; }
        
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-header { padding: 1.5rem 1.5rem 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 1rem; }
        .card-header h3 { font-size: 1.25rem; font-weight: 600; }
        .card-body { padding: 0 1.5rem 1.5rem; }
        
        .session-item, .message-item { padding: 1rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .session-item:last-child, .message-item:last-child { border-bottom: none; }
        .session-title { font-weight: 600; margin-bottom: 0.25rem; }
        .session-meta { color: #64748b; font-size: 0.875rem; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-scheduled { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #166534; }
        
        .empty-state { text-align: center; padding: 3rem; color: #64748b; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
        
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .header { padding: 1rem; }
            .container { padding: 0 0.5rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <?php echo APP_NAME; ?>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
            </div>
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome">
            <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
            <p>Here's what's happening with your mentoring sessions today.</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-calendar-alt icon"></i>
                <h3>Total Sessions</h3>
                <div class="value"><?php echo number_format($stats['total_sessions']); ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock icon"></i>
                <h3>Upcoming Sessions</h3>
                <div class="value"><?php echo number_format($stats['upcoming_sessions']); ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-graduate icon"></i>
                <h3>Total Students</h3>
                <div class="value"><?php echo number_format($stats['total_students']); ?></div>
            </div>
            <div class="stat-card">
                <i class="fas fa-star icon"></i>
                <h3>Average Rating</h3>
                <div class="value"><?php echo number_format($stats['avg_rating'], 1); ?></div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Sessions -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Sessions</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentSessions)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            <p>No sessions yet</p>
                            <small>Your sessions will appear here once you start mentoring</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentSessions as $session): ?>
                            <div class="session-item">
                                <div>
                                    <div class="session-title"><?php echo htmlspecialchars($session['title']); ?></div>
                                    <div class="session-meta">
                                        with <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?> •
                                        <?php echo date('M j, Y g:i A', strtotime($session['scheduled_at'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?php echo $session['status']; ?>">
                                    <?php echo ucfirst($session['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions & Notifications -->
            <div>
                <!-- Quick Actions -->
                <div class="card" style="margin-bottom: 1rem;">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; gap: 0.5rem;">
                            <a href="#" style="display: block; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; text-decoration: none; color: #334155;">
                                <i class="fas fa-plus"></i> Schedule New Session
                            </a>
                            <a href="#" style="display: block; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; text-decoration: none; color: #334155;">
                                <i class="fas fa-users"></i> View My Students
                            </a>
                            <a href="#" style="display: block; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; text-decoration: none; color: #334155;">
                                <i class="fas fa-envelope"></i> Messages (<?php echo count($recentMessages); ?>)
                            </a>
                            <a href="../create-missing-tables.php" style="display: block; padding: 0.75rem; background: #fef3c7; border-radius: 0.5rem; text-decoration: none; color: #92400e;">
                                <i class="fas fa-database"></i> Setup Missing Tables
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card">
                    <div class="card-header">
                        <h3>Notifications</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-state" style="padding: 2rem;">
                                <i class="fas fa-bell"></i>
                                <p>No new notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="message-item">
                                    <div>
                                        <div class="session-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="session-meta"><?php echo htmlspecialchars($notification['message']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>