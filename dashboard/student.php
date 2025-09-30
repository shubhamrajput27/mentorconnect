<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/optimized-config.php';
requireRole('student');

$user = getCurrentUser();

// Get student statistics with error handling
try {
    $stats = [
        'total_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ?", [$user['id']])['count'] ?? 0,
        'upcoming_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ? AND scheduled_at > NOW() AND status = 'scheduled'", [$user['id']])['count'] ?? 0,
        'completed_sessions' => fetchOne("SELECT COUNT(*) as count FROM sessions WHERE student_id = ? AND status = 'completed'", [$user['id']])['count'] ?? 0,
        'mentors_count' => fetchOne("SELECT COUNT(DISTINCT mentor_id) as count FROM sessions WHERE student_id = ?", [$user['id']])['count'] ?? 0,
        'active_connections' => fetchOne("SELECT COUNT(*) as count FROM mentor_mentee_connections WHERE mentee_id = ? AND status = 'active'", [$user['id']])['count'] ?? 0,
        'pending_requests' => fetchOne("SELECT COUNT(*) as count FROM mentor_mentee_connections WHERE mentee_id = ? AND status = 'pending'", [$user['id']])['count'] ?? 0
    ];
} catch (Exception $e) {
    $stats = ['total_sessions' => 0, 'upcoming_sessions' => 0, 'completed_sessions' => 0, 'mentors_count' => 0, 'active_connections' => 0, 'pending_requests' => 0];
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
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - MentorConnect</title>
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
            background: var(--primary);
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
            background: var(--primary);
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
            background: var(--primary);
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
            background: var(--primary);
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
            background: var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow);
            border-color: rgba(102, 126, 234, 0.3);
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

        .stat-icon.primary { background: var(--primary); }
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
            background: var(--primary);
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
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
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

        .session-mentor {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .mentor-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
        }

        .mentor-info h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .mentor-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .session-time {
            color: var(--primary-solid);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Mentor Cards */
        .mentors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .mentor-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .mentor-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: rgba(102, 126, 234, 0.3);
        }

        .mentor-card .mentor-avatar {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            margin: 0 auto 1rem;
        }

        .mentor-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .rating {
            color: #fbbf24;
        }

        .price {
            color: var(--primary-solid);
            font-weight: 600;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="../index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
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
                <a href="../mentors/browse.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <span>Find Mentors</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../connections/index.php" class="nav-link">
                    <i class="fas fa-users"></i>
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
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>My Progress</span>
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
                    <input type="text" class="search-input" placeholder="Search mentors, skills...">
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
                <p class="welcome-subtitle">Continue your learning journey with expert mentors.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card animate-in animate-delay-1">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Total Sessions</div>
                        </div>
                        <div class="stat-icon primary">
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
                            <div class="stat-title">Completed Sessions</div>
                        </div>
                        <div class="stat-icon warning">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['completed_sessions']; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-trophy"></i>
                        <span>Successfully completed</span>
                    </div>
                </div>

                <div class="stat-card animate-in animate-delay-4">
                    <div class="stat-header">
                        <div>
                            <div class="stat-title">Active Connections</div>
                        </div>
                        <div class="stat-icon secondary">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['active_connections']; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-users"></i>
                        <span>Connected mentors</span>
                    </div>
                </div>
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
                        <div class="empty-title">No upcoming sessions scheduled</div>
                        <div class="empty-text">Ready to start learning? Find a mentor and book your first session.</div>
                        <a href="../mentors/browse.php" class="btn">
                            <i class="fas fa-search"></i>
                            Find a Mentor
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="card-content">
                        <?php foreach ($upcomingSessions as $session): ?>
                        <div class="session-item">
                            <div class="session-mentor">
                                <img src="<?php echo htmlspecialchars($session['profile_photo'] ?: 'https://via.placeholder.com/48x48/667eea/ffffff?text=' . strtoupper(substr($session['first_name'], 0, 1))); ?>" 
                                     alt="Mentor" class="mentor-avatar">
                                <div class="mentor-info">
                                    <h4><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($session['title'] ?: 'Mentor'); ?></p>
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

                <!-- Learning Progress -->
                <div class="content-card animate-in">
                    <div class="card-header">
                        <h2 class="card-title">Learning Progress</h2>
                        <a href="#" class="card-action">View Details</a>
                    </div>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="empty-title">Start learning to see your progress</div>
                        <div class="empty-text">Your learning progress and achievements will appear here.</div>
                        <a href="../mentors/browse.php" class="btn btn-outline">
                            <i class="fas fa-play"></i>
                            Start Learning
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recommended Mentors -->
            <?php if (!empty($recommendedMentors)): ?>
            <div class="content-card animate-in" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 class="card-title">Recommended Mentors</h2>
                    <a href="../mentors/browse.php" class="card-action">Browse All</a>
                </div>
                <div class="mentors-grid">
                    <?php foreach (array_slice($recommendedMentors, 0, 3) as $mentor): ?>
                    <div class="mentor-card">
                        <img src="<?php echo htmlspecialchars($mentor['profile_photo'] ?: 'https://via.placeholder.com/64x64/667eea/ffffff?text=' . strtoupper(substr($mentor['first_name'], 0, 1))); ?>" 
                             alt="<?php echo htmlspecialchars($mentor['first_name']); ?>" class="mentor-avatar">
                        <h4><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h4>
                        <p><?php echo htmlspecialchars($mentor['title'] ?: 'Professional Mentor'); ?></p>
                        <div class="mentor-rating">
                            <span class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= ($mentor['rating'] ?: 5) ? '' : 'far'; ?>"></i>
                                <?php endfor; ?>
                            </span>
                            <span class="price">$<?php echo $mentor['hourly_rate'] ?: '50'; ?>/hr</span>
                        </div>
                        <button class="btn btn-outline" style="margin-top: 1rem;">View Profile</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
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
                    window.location.href = `../mentors/browse.php?search=${encodeURIComponent(query)}`;
                }
            }
        });
    </script>
</body>
</html>
