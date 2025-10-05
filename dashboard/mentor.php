<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';
requireRole('mentor');

$user = getCurrentUser();

// Get mentor statistics with error handling
try {
    $stats = [
        'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$user['id']])['count'] ?? 0,
        'upcoming_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND scheduled_at > NOW() AND status = 'scheduled'", [$user['id']])['count'] ?? 0,
        'total_students' => fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM sessions WHERE mentor_id = ?", [$user['id']])['count'] ?? 0,
        'avg_rating' => fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']])['avg'] ?? 0,
        'active_connections' => fetchOne("SELECT COUNT(*) as count FROM mentor_mentee_connections WHERE mentor_id = ? AND status = 'active'", [$user['id']])['count'] ?? 0,
        'pending_requests' => fetchOne("SELECT COUNT(*) as count FROM mentor_mentee_connections WHERE mentor_id = ? AND status = 'pending'", [$user['id']])['count'] ?? 0
    ];
} catch (Exception $e) {
    $stats = ['total_sessions' => 0, 'upcoming_sessions' => 0, 'total_students' => 0, 'avg_rating' => 0, 'active_connections' => 0, 'pending_requests' => 0];
    error_log("Mentor dashboard stats error: " . $e->getMessage());
}

// Get upcoming sessions with error handling
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
} catch (Exception $e) {
    $upcomingSessions = [];
    error_log("Mentor upcoming sessions error: " . $e->getMessage());
}

// Get recent reviews with error handling
try {
    $recentReviews = fetchAll(
        "SELECT r.*, u.first_name, u.last_name, u.profile_photo 
         FROM reviews r 
         JOIN users u ON r.reviewer_id = u.id 
         WHERE r.reviewee_id = ? 
         ORDER BY r.created_at DESC 
         LIMIT 5",
        [$user['id']]
    );
} catch (Exception $e) {
    $recentReviews = [];
    error_log("Mentor recent reviews error: " . $e->getMessage());
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
    error_log("Mentor recent messages error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - MentorConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/connections-optimized.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --primary-solid: #6366f1;
            --secondary: linear-gradient(135deg, #818cf8 0%, #a5b4fc 100%);
            --success: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            --warning: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            --mentor: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
            --background: #0a0a0f;
            --surface: #1a1a24;
            --surface-light: #2a2a3a;
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --border: rgba(255, 255, 255, 0.1);
            --glass: rgba(255, 255, 255, 0.05);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        [data-theme="light"] {
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-light: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border: rgba(15, 23, 42, 0.1);
            --glass: rgba(255, 255, 255, 0.7);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        body.loaded {
            opacity: 1;
        }

        /* Modern Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: var(--surface);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
        }

        .logo i {
            width: 40px;
            height: 40px;
            background: var(--mentor);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--mentor);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            padding: 1.5rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--mentor);
            transition: left 0.3s ease;
            z-index: -1;
            opacity: 0.1;
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            left: 0;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--text-primary);
            transform: translateX(8px);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .nav-badge {
            background: var(--primary);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            margin-left: auto;
            min-width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--background) 0%, #1a1a2e 100%);
        }

        .header {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-bar {
            position: relative;
            flex: 1;
            max-width: 500px;
            margin: 0 2rem;
        }

        .search-input {
            width: 100%;
            padding: 0.875rem 1.25rem 0.875rem 3.25rem;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 16px;
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-solid);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), var(--shadow);
            background: var(--surface);
        }

        .search-input::placeholder {
            color: var(--text-secondary);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .header-btn {
            width: 40px;
            height: 40px;
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            font-size: 1rem;
        }

        .header-btn:hover {
            background: var(--primary-solid);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.25);
            border-color: var(--primary-solid);
        }



        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }

        .welcome-section {
            margin-bottom: 3rem;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--mentor);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 400;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--mentor);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
            border-color: rgba(250, 112, 154, 0.3);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .stat-title {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
        }

        .stat-icon.mentor { background: var(--mentor); }
        .stat-icon.success { background: var(--success); }
        .stat-icon.warning { background: var(--warning); }
        .stat-icon.secondary { background: var(--secondary); }

        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-change {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #10b981;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .content-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-action {
            color: var(--primary-solid);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .card-content {
            padding: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .empty-text {
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--mentor);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--primary-solid);
            border-color: var(--primary-solid);
        }

        /* Session Cards */
        .session-item {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .session-item:hover {
            background: var(--surface-light);
            transform: translateX(8px);
        }

        .session-student {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .student-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
        }

        .student-info h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .student-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .session-time {
            color: var(--primary-solid);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Review Cards */
        .review-item {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .review-item:hover {
            background: var(--surface-light);
            transform: translateX(8px);
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

        .review-info h5 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .review-rating {
            color: #fbbf24;
            font-size: 0.9rem;
        }

        .review-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Message Cards */
        .message-item {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .message-item:hover {
            background: var(--surface-light);
            transform: translateX(8px);
        }

        .message-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
        }

        .message-info h6 {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .message-time {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .message-preview {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Rating Display */
        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stars {
            color: #fbbf24;
            font-size: 1.2rem;
        }

        .rating-value {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 1rem;
            }
            
            .search-bar {
                margin: 0 1rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: slideIn 0.6s ease-out forwards;
            opacity: 0;
        }

        .animate-delay-1 { animation-delay: 0.1s; }
        .animate-delay-2 { animation-delay: 0.2s; }
        .animate-delay-3 { animation-delay: 0.3s; }
        .animate-delay-4 { animation-delay: 0.4s; }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
        }

        .quick-action:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: rgba(250, 112, 154, 0.3);
        }

        .quick-action-icon {
            width: 60px;
            height: 60px;
            background: var(--mentor);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .quick-action-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .quick-action-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="../index.php" class="logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <h1>MentorConnect</h1>
            </a>
        </div>
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../connections/index.php" class="nav-link">
                    <i class="fas fa-handshake"></i>
                    <span>My Connections</span>
                    <?php if ($stats['pending_requests'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['pending_requests']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Sessions</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../messages/" class="nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../reviews/" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../profile/edit.php" class="nav-link">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../files/" class="nav-link">
                    <i class="fas fa-folder"></i>
                    <span>Files</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search students, sessions...">
                </div>
                <div class="header-actions">
                    <button class="header-btn" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="header-btn">
                        <i class="fas fa-bell"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section animate-in">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <p class="welcome-subtitle">Manage your mentoring sessions and help students achieve their goals.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card animate-in animate-delay-1">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Sessions</div>
                        </div>
                        <div class="stat-icon mentor">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_sessions']; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <span>All time sessions</span>
                    </div>
                </div>

                <div class="stat-card animate-in animate-delay-2">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Upcoming Sessions</div>
                        </div>
                        <div class="stat-icon success">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['upcoming_sessions']; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-calendar"></i>
                        <span>This week</span>
                    </div>
                </div>

                <div class="stat-card animate-in animate-delay-3">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Students</div>
                        </div>
                        <div class="stat-icon warning">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Students mentored</span>
                    </div>
                </div>

                <div class="stat-card animate-in animate-delay-4">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Average Rating</div>
                        </div>
                        <div class="stat-icon secondary">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo round($stats['avg_rating'], 1); ?></div>
                    <div class="stat-change">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Student feedback</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions animate-in">
                <a href="#" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="quick-action-title">Schedule Session</div>
                    <div class="quick-action-desc">Create a new mentoring session</div>
                </a>
                <a href="../messages/" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="quick-action-title">Send Message</div>
                    <div class="quick-action-desc">Connect with your students</div>
                </a>
                <a href="#" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-action-title">View Analytics</div>
                    <div class="quick-action-desc">Track your performance</div>
                </a>
                <a href="../profile/edit.php" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="quick-action-title">Update Profile</div>
                    <div class="quick-action-desc">Edit your mentor profile</div>
                </a>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Upcoming Sessions -->
                <div class="content-card animate-in">
                    <div class="card-header">
                        <h2 class="card-title">Upcoming Sessions</h2>
                        <a href="#" class="card-action">View All</a>
                    </div>
                    <?php if (empty($upcomingSessions)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="empty-title">No upcoming sessions</div>
                        <div class="empty-text">Your upcoming mentoring sessions will appear here.</div>
                        <a href="#" class="btn">
                            <i class="fas fa-plus"></i>
                            Schedule Session
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="card-content">
                        <?php foreach ($upcomingSessions as $session): ?>
                        <div class="session-item">
                            <div class="session-student">
                                <img src="<?php echo htmlspecialchars($session['profile_photo'] ?: 'https://via.placeholder.com/48x48/667eea/ffffff?text=' . strtoupper(substr($session['first_name'], 0, 1))); ?>" 
                                     alt="Student" class="student-avatar">
                                <div class="student-info">
                                    <h4><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></h4>
                                    <p>Mentoring Session</p>
                                </div>
                            </div>
                            <div class="session-time">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M j, Y g:i A', strtotime($session['scheduled_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Reviews -->
                <div class="content-card animate-in">
                    <div class="card-header">
                        <h2 class="card-title">Recent Reviews</h2>
                        <a href="../reviews/" class="card-action">View All</a>
                    </div>
                    <?php if (empty($recentReviews)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="empty-title">No reviews yet</div>
                        <div class="empty-text">Student reviews and feedback will appear here.</div>
                        <a href="#" class="btn btn-outline">
                            <i class="fas fa-graduation-cap"></i>
                            Start Mentoring
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="card-content">
                        <?php foreach ($recentReviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <img src="<?php echo htmlspecialchars($review['profile_photo'] ?: 'https://via.placeholder.com/40x40/667eea/ffffff?text=' . strtoupper(substr($review['first_name'], 0, 1))); ?>" 
                                     alt="Student" class="review-avatar">
                                <div class="review-info">
                                    <h5><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h5>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'far'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-text">
                                <?php echo htmlspecialchars(substr($review['comment'] ?? 'Great mentor!', 0, 100)) . '...'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="content-card animate-in" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Recent Messages</h2>
                    <a href="../messages/" class="card-action">View All</a>
                </div>
                <?php if (empty($recentMessages)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="empty-title">No recent messages</div>
                    <div class="empty-text">Messages from your students will appear here.</div>
                    <a href="../messages/" class="btn btn-outline">
                        <i class="fas fa-envelope"></i>
                        Send Message
                    </a>
                </div>
                <?php else: ?>
                <div class="card-content">
                    <?php foreach ($recentMessages as $message): ?>
                    <div class="message-item">
                        <div class="message-header">
                            <img src="<?php echo htmlspecialchars($message['profile_photo'] ?: 'https://via.placeholder.com/36x36/667eea/ffffff?text=' . strtoupper(substr($message['first_name'], 0, 1))); ?>" 
                                 alt="Student" class="message-avatar">
                            <div class="message-info">
                                <h6><?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?></h6>
                                <div class="message-time"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="message-preview">
                            <?php echo htmlspecialchars($message['content']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Load theme immediately before page renders
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        // Initialize page
        window.addEventListener('load', () => {
            document.body.classList.add('loaded');
        });

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        
        // Set initial icon based on theme
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        themeIcon.className = currentTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        
        themeToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            themeIcon.className = newTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        });

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query) {
                    // Redirect to sessions or students search
                    window.location.href = `#search=${encodeURIComponent(query)}`;
                }
            }
        });
    </script>
</body>
</html>