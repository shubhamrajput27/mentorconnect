<?php
require_once '../config/database.php';
requireLogin();

$user = getCurrentUser();

// Calculate progress statistics
$progressStats = [];

try {
    if ($user['role'] === 'student') {
        // Student progress
        $stats = fetchOne(
            "SELECT 
                COUNT(CASE WHEN s.status = 'completed' THEN 1 END) as completed_sessions,
                COUNT(CASE WHEN s.status = 'scheduled' AND s.scheduled_at > NOW() THEN 1 END) as upcoming_sessions,
                COUNT(DISTINCT s.mentor_id) as mentors_worked_with,
                SUM(CASE WHEN s.status = 'completed' THEN COALESCE(s.duration_minutes, 60) ELSE 0 END) as total_learning_minutes,
                AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating END) as avg_rating_given
             FROM sessions s
             LEFT JOIN reviews r ON s.id = r.session_id AND r.reviewer_id = ?
             WHERE s.student_id = ?",
            [$user['id'], $user['id']]
        );
        
        // Get skill progress
        $skillProgress = fetchAll(
            "SELECT s.name, s.category, us.proficiency_level, us.created_at, us.updated_at
             FROM user_skills us
             JOIN skills s ON us.skill_id = s.id
             WHERE us.user_id = ?
             ORDER BY s.category, s.name",
            [$user['id']]
        );
        
        // Get learning goals
        $learningGoals = fetchAll(
            "SELECT * FROM user_goals 
             WHERE user_id = ? AND status IN ('active', 'completed')
             ORDER BY created_at DESC",
            [$user['id']]
        );
        
        // Recent completed sessions with feedback
        $recentSessions = fetchAll(
            "SELECT s.*, u.first_name, u.last_name, u.profile_photo, mp.title,
                    r.rating, r.comment as review_comment
             FROM sessions s
             JOIN users u ON s.mentor_id = u.id
             LEFT JOIN mentor_profiles mp ON u.id = mp.user_id
             LEFT JOIN reviews r ON s.id = r.session_id AND r.reviewer_id = ?
             WHERE s.student_id = ? AND s.status = 'completed'
             ORDER BY s.scheduled_at DESC
             LIMIT 5",
            [$user['id'], $user['id']]
        );
        
    } else {
        // Mentor progress
        $stats = fetchOne(
            "SELECT 
                COUNT(CASE WHEN s.status = 'completed' THEN 1 END) as completed_sessions,
                COUNT(CASE WHEN s.status = 'scheduled' AND s.scheduled_at > NOW() THEN 1 END) as upcoming_sessions,
                COUNT(DISTINCT s.student_id) as students_mentored,
                SUM(CASE WHEN s.status = 'completed' THEN COALESCE(s.duration_minutes, 60) ELSE 0 END) as total_mentoring_minutes,
                AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating END) as avg_rating_received
             FROM sessions s
             LEFT JOIN reviews r ON s.id = r.session_id AND r.reviewer_id != ?
             WHERE s.mentor_id = ?",
            [$user['id'], $user['id']]
        );
        
        // Get expertise areas
        $skillProgress = fetchAll(
            "SELECT s.name, s.category, us.proficiency_level, us.created_at, us.updated_at
             FROM user_skills us
             JOIN skills s ON us.skill_id = s.id
             WHERE us.user_id = ?
             ORDER BY s.category, s.name",
            [$user['id']]
        );
        
        // Recent students and their feedback
        $recentSessions = fetchAll(
            "SELECT s.*, u.first_name, u.last_name, u.profile_photo,
                    r.rating, r.comment as review_comment
             FROM sessions s
             JOIN users u ON s.student_id = u.id
             LEFT JOIN reviews r ON s.id = r.session_id AND r.reviewer_id = u.id
             WHERE s.mentor_id = ? AND s.status = 'completed'
             ORDER BY s.scheduled_at DESC
             LIMIT 5",
            [$user['id']]
        );
    }
    
    $progressStats = $stats ?: [];
    
} catch (Exception $e) {
    error_log("Progress page error: " . $e->getMessage());
    $progressStats = [];
    $skillProgress = [];
    $recentSessions = [];
    $learningGoals = [];
}

// Calculate derived metrics
$totalHours = round(($progressStats['total_learning_minutes'] ?? $progressStats['total_mentoring_minutes'] ?? 0) / 60, 1);
$avgSessionRating = round($progressStats['avg_rating_given'] ?? $progressStats['avg_rating_received'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --primary-light: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --bg-primary: #f9fafb;
            --surface: #ffffff;
            --border: #e5e7eb;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .app-layout {
            display: flex;
            min-height: 100vh;
            background: var(--bg-primary);
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.25rem;
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
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.primary { background: var(--primary-color); }
        .stat-icon.success { background: var(--success-color); }
        .stat-icon.warning { background: var(--warning-color); }
        .stat-icon.info { background: var(--info-color); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .progress-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .progress-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .skills-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .skill-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: 8px;
        }

        .skill-info h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .skill-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .proficiency-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .proficiency-beginner { background: #fee2e2; color: #991b1b; }
        .proficiency-intermediate { background: #fef3c7; color: #92400e; }
        .proficiency-advanced { background: #d1fae5; color: #065f46; }
        .proficiency-expert { background: #dbeafe; color: #1e40af; }

        .sessions-timeline {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-avatar {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            object-fit: cover;
        }

        .timeline-content h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .timeline-content p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .timeline-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .rating-stars {
            display: flex;
            gap: 2px;
        }

        .star {
            color: #fbbf24;
        }

        .star.empty {
            color: #e5e7eb;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        .goals-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .goal-item {
            padding: 1rem;
            background: var(--bg-primary);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .goal-item.completed {
            border-left-color: var(--success-color);
        }

        .goal-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .goal-title {
            color: var(--text-primary);
            font-weight: 600;
        }

        .goal-status {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-active { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }

        @media (max-width: 1024px) {
            .progress-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="app-layout">
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
                    <a href="../dashboard/<?php echo $user['role']; ?>.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <?php if ($user['role'] === 'student'): ?>
                <div class="nav-item">
                    <a href="../mentors/browse.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Find Mentors</span>
                    </a>
                </div>
                <?php endif; ?>
                <div class="nav-item">
                    <a href="../sessions/" class="nav-link">
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
                    <a href="index.php" class="nav-link active">
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">My Progress</h1>
                <p class="page-subtitle">
                    <?php if ($user['role'] === 'student'): ?>
                        Track your learning journey and skill development
                    <?php else: ?>
                        Monitor your mentoring impact and growth
                    <?php endif; ?>
                </p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $progressStats['completed_sessions'] ?? 0; ?></div>
                    <div class="stat-label">Completed Sessions</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalHours; ?>h</div>
                    <div class="stat-label">
                        <?php echo $user['role'] === 'student' ? 'Learning Time' : 'Mentoring Time'; ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value">
                        <?php echo $progressStats['mentors_worked_with'] ?? $progressStats['students_mentored'] ?? 0; ?>
                    </div>
                    <div class="stat-label">
                        <?php echo $user['role'] === 'student' ? 'Mentors Worked With' : 'Students Mentored'; ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value"><?php echo $avgSessionRating > 0 ? $avgSessionRating : 'N/A'; ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>

            <!-- Progress Details -->
            <div class="progress-grid">
                <!-- Skills Progress -->
                <div class="progress-card">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i>
                        <?php echo $user['role'] === 'student' ? 'Skills Learning' : 'Expertise Areas'; ?>
                    </h3>
                    
                    <?php if (empty($skillProgress)): ?>
                        <div class="empty-state">
                            <i class="fas fa-lightbulb"></i>
                            <h4>No skills added yet</h4>
                            <p>Add skills to your profile to track your progress.</p>
                        </div>
                    <?php else: ?>
                        <div class="skills-list">
                            <?php foreach ($skillProgress as $skill): ?>
                            <div class="skill-item">
                                <div class="skill-info">
                                    <h4><?php echo htmlspecialchars($skill['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($skill['category']); ?></p>
                                </div>
                                <span class="proficiency-badge proficiency-<?php echo $skill['proficiency_level']; ?>">
                                    <?php echo ucfirst($skill['proficiency_level']); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Goals (for students) or Performance Chart (for mentors) -->
                <div class="progress-card">
                    <?php if ($user['role'] === 'student'): ?>
                        <h3 class="card-title">
                            <i class="fas fa-target"></i>
                            Learning Goals
                        </h3>
                        
                        <?php if (empty($learningGoals)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bullseye"></i>
                                <h4>No goals set</h4>
                                <p>Set learning goals to track your progress.</p>
                            </div>
                        <?php else: ?>
                            <div class="goals-list">
                                <?php foreach ($learningGoals as $goal): ?>
                                <div class="goal-item <?php echo $goal['status']; ?>">
                                    <div class="goal-header">
                                        <span class="goal-title"><?php echo htmlspecialchars($goal['title']); ?></span>
                                        <span class="goal-status status-<?php echo $goal['status']; ?>">
                                            <?php echo ucfirst($goal['status']); ?>
                                        </span>
                                    </div>
                                    <?php if ($goal['description']): ?>
                                        <p><?php echo htmlspecialchars($goal['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Monthly Activity
                        </h3>
                        <div class="chart-container">
                            <canvas id="activityChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Sessions Timeline -->
            <div class="sessions-timeline">
                <h3 class="card-title">
                    <i class="fas fa-history"></i>
                    Recent Sessions
                </h3>
                
                <?php if (empty($recentSessions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h4>No recent sessions</h4>
                        <p>Complete some sessions to see your activity here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentSessions as $session): ?>
                    <div class="timeline-item">
                        <img src="<?php echo htmlspecialchars($session['profile_photo'] ?: 'https://via.placeholder.com/50x50/667eea/ffffff?text=' . strtoupper(substr($session['first_name'], 0, 1))); ?>" 
                             alt="Profile" class="timeline-avatar">
                        <div class="timeline-content">
                            <h4>
                                Session with <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                            </h4>
                            <p>
                                <?php if ($user['role'] === 'student' && $session['title']): ?>
                                    <?php echo htmlspecialchars($session['title']); ?>
                                <?php endif; ?>
                                <?php if ($session['review_comment']): ?>
                                    - "<?php echo htmlspecialchars(substr($session['review_comment'], 0, 100)); ?>..."
                                <?php endif; ?>
                            </p>
                            <div class="timeline-meta">
                                <span><?php echo date('M j, Y', strtotime($session['scheduled_at'])); ?></span>
                                <?php if ($session['rating']): ?>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $session['rating'] ? 'star' : 'star empty'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Activity Chart for Mentors
        <?php if ($user['role'] === 'mentor'): ?>
        const ctx = document.getElementById('activityChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sessions Completed',
                        data: [12, 19, 8, 15, 22, 18],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>
