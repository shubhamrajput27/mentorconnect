<?php
/**
 * Admin Dashboard Demo - MentorConnect
 * Direct access demo version (no authentication required)
 */

// Demo data
$stats = [
    'total_users' => 5247,
    'active_connections' => 1891,
    'sessions_completed' => 12456,
    'revenue' => 89432,
    'user_growth' => 12.5,
    'connection_growth' => 8.1,
    'session_growth' => 23.1,
    'revenue_growth' => 15.3
];

$recentActivity = [
    ['type' => 'new_mentor', 'user' => 'John Doe', 'time' => '2 minutes ago', 'avatar' => 'JD'],
    ['type' => 'session_completed', 'user' => 'Sarah Martinez', 'details' => 'completed a session with Mike', 'time' => '15 minutes ago', 'avatar' => 'SM'],
    ['type' => 'review', 'user' => 'Alex Johnson', 'details' => 'left a 5 star review', 'time' => '1 hour ago', 'avatar' => 'AL'],
    ['type' => 'new_sessions', 'user' => 'Maria Garcia', 'details' => 'scheduled 3 new sessions', 'time' => '2 hours ago', 'avatar' => 'MG']
];

$recentUsers = [
    ['name' => 'Sarah Martinez', 'email' => 'sarah@example.com', 'role' => 'MENTOR', 'status' => 'ACTIVE', 'sessions' => 127, 'rating' => 4.9, 'joined' => 'Jan 15, 2024'],
    ['name' => 'Mike Johnson', 'email' => 'mike@example.com', 'role' => 'MENTOR', 'status' => 'ACTIVE', 'sessions' => 89, 'rating' => 4.8, 'joined' => 'Feb 3, 2024'],
    ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'STUDENT', 'status' => 'ACTIVE', 'sessions' => 23, 'rating' => null, 'joined' => 'Mar 12, 2024'],
    ['name' => 'Anna Lee', 'email' => 'anna@example.com', 'role' => 'STUDENT', 'status' => 'PENDING', 'sessions' => 5, 'rating' => null, 'joined' => 'Apr 1, 2024']
];
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard Demo - MentorConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --purple: #8b5cf6;
            --pink: #ec4899;
            
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-active: #6366f1;
            
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
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
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: var(--sidebar-bg);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--purple) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-title {
            padding: 0 1.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }
        
        .nav-item {
            margin: 0.25rem 1rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-link.active {
            background: var(--sidebar-active);
            color: white;
        }
        
        .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 1rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .header-btn:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }
        
        .header-btn.secondary {
            background: var(--surface-hover);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        
        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-info h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .stat-change {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .stat-icon.users { background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); }
        .stat-icon.connections { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-icon.sessions { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.revenue { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-growth {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: var(--success);
        }
        
        .stat-growth.negative {
            color: var(--danger);
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        /* Chart Section */
        .chart-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .chart-placeholder {
            height: 300px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 1rem;
            border: 2px dashed var(--border);
        }
        
        /* Recent Activity */
        .activity-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .activity-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .activity-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .activity-list {
            padding: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
        }
        
        .activity-item:hover {
            background: var(--surface-hover);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-size: 0.875rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* Users Table */
        .users-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-top: 2rem;
            overflow: hidden;
        }
        
        .users-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        
        .users-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            text-align: left;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-secondary);
            background: var(--surface-hover);
            border-bottom: 1px solid var(--border);
        }
        
        .users-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }
        
        .users-table tr:hover {
            background: var(--surface-hover);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .user-details h4 {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.125rem;
        }
        
        .user-details p {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-badge.mentor {
            background: #ede9fe;
            color: #7c3aed;
        }
        
        .role-badge.student {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stars {
            color: #fbbf24;
        }
        
        /* Demo Banner */
        .demo-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--purple) 100%);
            color: white;
            padding: 0.75rem;
            text-align: center;
            font-weight: 500;
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
            
            .dashboard-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Demo Banner -->
    <div class="demo-banner">
        🎯 Admin Dashboard Demo - This is a sample interface showcasing the MentorConnect admin panel
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="logo-text">MentorConnect</span>
            </a>
        </div>
        
        <nav class="nav-menu">
            <div class="nav-section">
                <div class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Management</div>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-handshake"></i>
                        <span>Connections</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-calendar"></i>
                        <span>Sessions</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1 class="header-title">Dashboard Overview</h1>
                <div class="header-actions">
                    <a href="#" class="header-btn secondary">
                        <i class="fas fa-download"></i>
                        Export
                    </a>
                    <a href="#" class="header-btn">
                        <i class="fas fa-plus"></i>
                        Add User
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Total Users</h3>
                            <div class="stat-change">+12.5% from last month</div>
                        </div>
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-growth">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo $stats['user_growth']; ?>%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Active Connections</h3>
                            <div class="stat-change">+8.1% from last month</div>
                        </div>
                        <div class="stat-icon connections">
                            <i class="fas fa-video"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['active_connections']); ?></div>
                    <div class="stat-growth">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo $stats['connection_growth']; ?>%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Sessions Completed</h3>
                            <div class="stat-change">+23.1% from last month</div>
                        </div>
                        <div class="stat-icon sessions">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['sessions_completed']); ?></div>
                    <div class="stat-growth">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo $stats['session_growth']; ?>%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <h3>Revenue</h3>
                            <div class="stat-change">+15.3% from last month</div>
                        </div>
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">$<?php echo number_format($stats['revenue']); ?></div>
                    <div class="stat-growth">
                        <i class="fas fa-arrow-up"></i>
                        <span><?php echo $stats['revenue_growth']; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Chart Section -->
                <div class="chart-section">
                    <div class="chart-header">
                        <h2 class="chart-title">User Growth & Engagement</h2>
                        <select class="header-btn secondary" style="border: none; background: transparent;">
                            <option>This Month</option>
                            <option>Last 3 Months</option>
                            <option>This Year</option>
                        </select>
                    </div>
                    <div class="chart-placeholder">
                        <div style="text-align: center;">
                            <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            Interactive Chart Visualization<br>
                            <small>Monthly user registration and session trends</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <div class="activity-header">
                        <h2 class="activity-title">Recent Activity</h2>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-avatar"><?php echo $activity['avatar']; ?></div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong><?php echo $activity['user']; ?></strong>
                                    <?php if ($activity['type'] === 'new_mentor'): ?>
                                        joined as a new mentor
                                    <?php elseif (isset($activity['details'])): ?>
                                        <?php echo $activity['details']; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time"><?php echo $activity['time']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="users-section">
                <div class="users-header">
                    <h2 class="users-title">Recent Users</h2>
                </div>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>USER</th>
                            <th>ROLE</th>
                            <th>STATUS</th>
                            <th>SESSIONS</th>
                            <th>RATING</th>
                            <th>JOINED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 2)); ?></div>
                                    <div class="user-details">
                                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge <?php echo strtolower($user['role']); ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower($user['status']); ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $user['sessions']; ?></td>
                            <td>
                                <?php if ($user['rating']): ?>
                                <div class="rating-display">
                                    <span class="stars">★</span>
                                    <span><?php echo $user['rating']; ?></span>
                                </div>
                                <?php else: ?>
                                <span style="color: var(--text-muted);">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['joined']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>