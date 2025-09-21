<?php
/**
 * Example implementation showing how to use the optimized template
 * This demonstrates the proper way to use all optimization features
 */

// Include the main configuration (this includes all optimizations)
require_once 'config/config.php';

// Initialize SEO for this page
$pageTitle = 'Dashboard - MentorConnect';
$pageDescription = 'Access your personalized dashboard with mentorship tools and resources.';
$pageKeywords = 'dashboard, mentorship, learning, student, mentor';
$pageType = 'website';

// Set SEO meta tags using the optimizer
if (isset($seoOptimizer)) {
    $seoOptimizer->setPageMeta([
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => $pageKeywords,
        'type' => $pageType,
        'url' => BASE_URL . $_SERVER['REQUEST_URI']
    ]);
}

// Check authentication
requireLogin();

// Get current user data with caching
$user = getCurrentUser();
if (!$user) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}

// Performance optimization: Cache dashboard data for 5 minutes
$cacheKey = 'dashboard_' . $user['id'];
$dashboardData = cache_get($cacheKey);

if ($dashboardData === false) {
    // Fetch dashboard data from database
    $dashboardData = [
        'recent_messages' => fetchAll(
            "SELECT m.*, u.username as sender_name 
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             WHERE m.receiver_id = ? 
             ORDER BY m.created_at DESC 
             LIMIT 5",
            [$user['id']]
        ),
        'upcoming_sessions' => fetchAll(
            "SELECT s.*, u.username as partner_name 
             FROM sessions s 
             JOIN users u ON s.mentor_id = u.id OR s.student_id = u.id 
             WHERE (s.mentor_id = ? OR s.student_id = ?) 
             AND s.scheduled_at > NOW() 
             ORDER BY s.scheduled_at ASC 
             LIMIT 5",
            [$user['id'], $user['id']]
        ),
        'notifications_count' => fetchOne(
            "SELECT COUNT(*) as count FROM notifications 
             WHERE user_id = ? AND read_at IS NULL",
            [$user['id']]
        )['count'] ?? 0
    ];
    
    // Cache for 5 minutes
    cache_set($cacheKey, $dashboardData, 300);
}

// Log user activity
logActivity($user['id'], 'page_view', 'Dashboard accessed');

// Set template variables
$bodyClass = 'dashboard-page';
$pageCSS = 'dashboard.css'; // Will be loaded asynchronously
$pageJS = 'dashboard.js';   // Will be loaded with defer

// Define the main content
ob_start();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Welcome back, <?= htmlspecialchars($user['username']) ?>!</h1>
        <div class="user-stats">
            <div class="stat-item">
                <span class="stat-number"><?= $dashboardData['notifications_count'] ?></span>
                <span class="stat-label">New Notifications</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= count($dashboardData['recent_messages']) ?></span>
                <span class="stat-label">Recent Messages</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= count($dashboardData['upcoming_sessions']) ?></span>
                <span class="stat-label">Upcoming Sessions</span>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Quick Actions -->
        <div class="dashboard-card quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <?php if ($user['role'] === 'student'): ?>
                    <a href="<?= BASE_URL ?>/mentors/browse.php" class="btn btn-primary">Find Mentors</a>
                    <a href="<?= BASE_URL ?>/sessions/book.php" class="btn btn-secondary">Book Session</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/students/browse.php" class="btn btn-primary">View Students</a>
                    <a href="<?= BASE_URL ?>/sessions/schedule.php" class="btn btn-secondary">Schedule Session</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/profile/edit.php" class="btn btn-outline">Edit Profile</a>
            </div>
        </div>
        
        <!-- Recent Messages -->
        <div class="dashboard-card recent-messages">
            <h2>Recent Messages</h2>
            <?php if (empty($dashboardData['recent_messages'])): ?>
                <p class="empty-state">No recent messages</p>
                <a href="<?= BASE_URL ?>/messages/" class="btn btn-outline">View All Messages</a>
            <?php else: ?>
                <div class="message-list">
                    <?php foreach ($dashboardData['recent_messages'] as $message): ?>
                        <div class="message-item">
                            <div class="message-sender"><?= htmlspecialchars($message['sender_name']) ?></div>
                            <div class="message-preview"><?= htmlspecialchars(substr($message['content'], 0, 100)) ?>...</div>
                            <div class="message-time"><?= date('M j, g:i A', strtotime($message['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= BASE_URL ?>/messages/" class="btn btn-outline">View All Messages</a>
            <?php endif; ?>
        </div>
        
        <!-- Upcoming Sessions -->
        <div class="dashboard-card upcoming-sessions">
            <h2>Upcoming Sessions</h2>
            <?php if (empty($dashboardData['upcoming_sessions'])): ?>
                <p class="empty-state">No upcoming sessions</p>
                <a href="<?= BASE_URL ?>/sessions/" class="btn btn-outline">Schedule a Session</a>
            <?php else: ?>
                <div class="session-list">
                    <?php foreach ($dashboardData['upcoming_sessions'] as $session): ?>
                        <div class="session-item">
                            <div class="session-partner"><?= htmlspecialchars($session['partner_name']) ?></div>
                            <div class="session-topic"><?= htmlspecialchars($session['topic'] ?? 'General Session') ?></div>
                            <div class="session-time"><?= date('M j, Y g:i A', strtotime($session['scheduled_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= BASE_URL ?>/sessions/" class="btn btn-outline">View All Sessions</a>
            <?php endif; ?>
        </div>
        
        <!-- Performance Insights (if available) -->
        <?php if ($user['role'] === 'mentor'): ?>
        <div class="dashboard-card performance-insights">
            <h2>Your Impact</h2>
            <div class="insight-stats">
                <div class="insight-item">
                    <span class="insight-number">12</span>
                    <span class="insight-label">Students Mentored</span>
                </div>
                <div class="insight-item">
                    <span class="insight-number">45</span>
                    <span class="insight-label">Sessions Completed</span>
                </div>
                <div class="insight-item">
                    <span class="insight-number">4.8</span>
                    <span class="insight-label">Average Rating</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

// Additional page-specific head content
$pageHead = '
<meta name="robots" content="noindex, nofollow">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "' . htmlspecialchars($pageTitle) . '",
  "description": "' . htmlspecialchars($pageDescription) . '",
  "url": "' . BASE_URL . $_SERVER['REQUEST_URI'] . '"
}
</script>';

// Page-specific scripts
$pageScripts = '
<script>
// Dashboard-specific JavaScript
document.addEventListener("DOMContentLoaded", function() {
    // Initialize dashboard features
    if (window.MentorConnectApp) {
        window.MentorConnectApp.initDashboard();
    }
    
    // Auto-refresh notifications every 30 seconds
    setInterval(function() {
        if (window.MentorConnectApp && window.MentorConnectApp.notifications) {
            window.MentorConnectApp.notifications.refresh();
        }
    }, 30000);
});
</script>';

// Include the optimized template
include 'includes/optimized-template.php';

// End performance monitoring
if (isset($performanceMonitor)) {
    $performanceMonitor->endTimer('page_load');
    
    // Log performance metrics in debug mode
    if (DEBUG_MODE) {
        $metrics = $performanceMonitor->getMetrics();
        error_log("Dashboard load time: " . $metrics['page_load'] . "ms");
    }
}
?>