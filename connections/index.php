<?php
require_once '../config/optimized-config.php';
requireLogin();

$user = getCurrentUser();
$pageTitle = 'My Connections';

// Optimized connections data retrieval
try {
    $userColumn = ($user['role'] === 'mentor') ? 'mentor_id' : 'mentee_id';
                                                };
                                            ?>
                                            <div class="user-info">
                                                <img src="<?php echo $otherUser['photo'] ? '../uploads/' . $otherUser['photo'] : '../assets/images/default-profile.svg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($otherUser['name']); ?>" class="user-avatar">// Single optimized query for connections
    $connections = fetchAll(
        "SELECT c.id, c.status, c.connection_type, c.request_message, c.goals, 
                c.created_at, c.start_date, c.requested_by, c.response_message,
                mentor.first_name as mentor_first_name, 
                mentor.last_name as mentor_last_name,
                mentor.profile_photo as mentor_photo,
                mentee.first_name as mentee_first_name, 
                mentee.last_name as mentee_last_name,
                mentee.profile_photo as mentee_photo,
                mp.title as mentor_title,
                mp.company as mentor_company,
                mp.rating as mentor_rating,
                c.mentor_id, c.mentee_id
         FROM mentor_mentee_connections c
         INNER JOIN users mentor ON c.mentor_id = mentor.id
         INNER JOIN users mentee ON c.mentee_id = mentee.id
         LEFT JOIN mentor_profiles mp ON mentor.id = mp.user_id
         WHERE c.{$userColumn} = ?
         ORDER BY 
             CASE WHEN c.status = 'pending' THEN 1 
                  WHEN c.status = 'active' THEN 2 
                  ELSE 3 END,
             c.created_at DESC
         LIMIT 100",
        [$user['id']]
    );
    
    // Single optimized query for stats
    $statsResult = fetchOne(
        "SELECT 
            COUNT(*) as total_connections,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_connections,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_connections
         FROM mentor_mentee_connections 
         WHERE {$userColumn} = ?",
        [$user['id']]
    );
    
    $stats = $statsResult ?: ['total_connections' => 0, 'pending_requests' => 0, 'active_connections' => 0, 'completed_connections' => 0];
    
} catch (Exception $e) {
    $connections = [];
    $stats = ['total_connections' => 0, 'pending_requests' => 0, 'active_connections' => 0, 'completed_connections' => 0];
    error_log("Connections page error: " . $e->getMessage());
}

// Filter connections by status
$pendingConnections = array_filter($connections, fn($c) => $c['status'] === 'pending');
$activeConnections = array_filter($connections, fn($c) => $c['status'] === 'active');
$completedConnections = array_filter($connections, fn($c) => $c['status'] === 'completed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
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
                    <a href="<?php echo BASE_URL; ?>/dashboard/<?php echo $user['role'] === 'mentor' ? 'mentor' : 'student'; ?>.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <?php if ($user['role'] === 'student'): ?>
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/mentors/browse.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Find Mentors</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/connections/index.php" class="nav-link active">
                        <i class="fas fa-users"></i>
                        <span>My Connections</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="<?php echo BASE_URL; ?>/sessions/index.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Sessions</span>
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
                    <h2><?php echo $pageTitle; ?></h2>
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

            <!-- Content -->
            <div class="content">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-handshake text-primary"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['active_connections'] ?? 0; ?></h3>
                            <p>Active Connections</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['pending_requests'] ?? 0; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['completed_connections'] ?? 0; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users text-info"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_connections'] ?? 0; ?></h3>
                            <p>Total Connections</p>
                        </div>
                    </div>
                </div>

                <!-- Connection Tabs -->
                <div class="tabs-container">
                    <div class="tabs-header">
                        <button class="tab-button active" data-tab="pending">
                            Pending Requests (<?php echo count($pendingConnections); ?>)
                        </button>
                        <button class="tab-button" data-tab="active">
                            Active Connections (<?php echo count($activeConnections); ?>)
                        </button>
                        <button class="tab-button" data-tab="completed">
                            Completed (<?php echo count($completedConnections); ?>)
                        </button>
                    </div>
                    
                    <!-- Pending Requests Tab -->
                    <div class="tab-content active" id="pending-tab">
                        <div class="connections-list">
                            <?php if (empty($pendingConnections)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                    <h3>No Pending Requests</h3>
                                    <p>You don't have any pending connection requests at the moment.</p>
                                    <?php if ($user['role'] === 'student'): ?>
                                        <a href="../mentors/browse.php" class="btn btn-primary">Find Mentors</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pendingConnections as $connection): ?>
                                    <div class="connection-card pending" data-connection-id="<?php echo $connection['id']; ?>">
                                        <div class="connection-header">
                                            <?php 
                                            // Determine who to show based on current user role
                                            if ($user['role'] === 'mentor') {
                                                $otherUser = [
                                                    'name' => $connection['mentee_first_name'] . ' ' . $connection['mentee_last_name'],
                                                    'photo' => $connection['mentee_photo'],
                                                    'role' => 'Student'
                                                ];
                                            } else {
                                                $otherUser = [
                                                    'name' => $connection['mentor_first_name'] . ' ' . $connection['mentor_last_name'],
                                                    'photo' => $connection['mentor_photo'],
                                                    'role' => 'Mentor',
                                                    'title' => $connection['mentor_title'],
                                                    'company' => $connection['mentor_company'],
                                                    'rating' => $connection['mentor_rating']
                                                ];
                                            }
                                            ?>
                                            <div class="user-info">
                                                <img src="<?php echo $otherUser['photo'] ? '../uploads/' . $otherUser['photo'] : '../assets/images/default-profile.svg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($otherUser['name']); ?>" class="user-avatar">
                                                <div class="user-details">
                                                    <h4><?php echo htmlspecialchars($otherUser['name']); ?></h4>
                                                    <p class="user-role"><?php echo $otherUser['role']; ?></p>
                                                    <?php if (isset($otherUser['title'])): ?>
                                                        <p class="user-title"><?php echo htmlspecialchars($otherUser['title']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="connection-meta">
                                                <span class="status-badge status-pending">
                                                    <i class="fas fa-clock"></i>
                                                    Pending
                                                </span>
                                                <span class="connection-date">
                                                    <?php echo date('M j, Y', strtotime($connection['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($connection['request_message']): ?>
                                            <div class="connection-message">
                                                <h5>Request Message:</h5>
                                                <p><?php echo htmlspecialchars($connection['request_message']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($connection['goals']): ?>
                                            <div class="connection-goals">
                                                <h5>Goals:</h5>
                                                <p><?php echo htmlspecialchars($connection['goals']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="connection-actions">
                                            <?php 
                                            // Show appropriate actions based on who sent the request
                                            $isRecipient = ($connection['requested_by'] === 'mentor' && $user['role'] === 'student') || 
                                                         ($connection['requested_by'] === 'mentee' && $user['role'] === 'mentor');
                                            ?>
                                            
                                            <?php if ($isRecipient): ?>
                                                <button class="btn btn-success" onclick="respondToRequest(<?php echo $connection['id']; ?>, 'accept')">
                                                    <i class="fas fa-check"></i>
                                                    Accept
                                                </button>
                                                <button class="btn btn-danger" onclick="respondToRequest(<?php echo $connection['id']; ?>, 'reject')">
                                                    <i class="fas fa-times"></i>
                                                    Decline
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    Waiting for response...
                                                </span>
                                                <button class="btn btn-outline" onclick="cancelRequest(<?php echo $connection['id']; ?>)">
                                                    Cancel Request
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Active Connections Tab -->
                    <div class="tab-content" id="active-tab">
                        <div class="connections-list">
                            <?php if (empty($activeConnections)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-users" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                    <h3>No Active Connections</h3>
                                    <p>You don't have any active connections yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activeConnections as $connection): ?>
                                    <div class="connection-card active" data-connection-id="<?php echo $connection['id']; ?>">
                                        <div class="connection-header">
                                            <?php 
                                            // Determine who to show
                                            if ($user['role'] === 'mentor') {
                                                $otherUser = [
                                                    'name' => $connection['mentee_first_name'] . ' ' . $connection['mentee_last_name'],
                                                    'photo' => $connection['mentee_photo'],
                                                    'role' => 'Mentee'
                                                ];
                                            } else {
                                                $otherUser = [
                                                    'name' => $connection['mentor_first_name'] . ' ' . $connection['mentor_last_name'],
                                                    'photo' => $connection['mentor_photo'],
                                                    'role' => 'Mentor',
                                                    'title' => $connection['mentor_title'],
                                                    'company' => $connection['mentor_company'],
                                                    'rating' => $connection['mentor_rating']
                                                ];
                                            }
                                            ?>
                                            <div class="user-info">
                                                <img src="<?php echo $otherUser['photo'] ? '../uploads/' . $otherUser['photo'] : '../assets/images/default-profile.svg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($otherUser['name']); ?>" class="user-avatar">
                                                <div class="user-details">
                                                    <h4><?php echo htmlspecialchars($otherUser['name']); ?></h4>
                                                    <p class="user-role"><?php echo $otherUser['role']; ?></p>
                                                    <?php if (isset($otherUser['title'])): ?>
                                                        <p class="user-title"><?php echo htmlspecialchars($otherUser['title']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="connection-meta">
                                                <span class="status-badge status-active">
                                                    <i class="fas fa-check-circle"></i>
                                                    Active
                                                </span>
                                                <span class="connection-date">
                                                    Connected <?php echo date('M j, Y', strtotime($connection['start_date'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="connection-actions">
                                            <button class="btn btn-primary" onclick="bookSession(<?php echo $user['role'] === 'mentor' ? $connection['mentee_id'] : $connection['mentor_id']; ?>)">
                                                <i class="fas fa-calendar-plus"></i>
                                                Book Session
                                            </button>
                                            <button class="btn btn-outline" onclick="sendMessage(<?php echo $user['role'] === 'mentor' ? $connection['mentee_id'] : $connection['mentor_id']; ?>)">
                                                <i class="fas fa-envelope"></i>
                                                Message
                                            </button>
                                            <button class="btn btn-ghost" onclick="manageConnection(<?php echo $connection['id']; ?>)">
                                                <i class="fas fa-cog"></i>
                                                Manage
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Completed Connections Tab -->
                    <div class="tab-content" id="completed-tab">
                        <div class="connections-list">
                            <?php if (empty($completedConnections)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-trophy" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                    <h3>No Completed Connections</h3>
                                    <p>Your completed mentorships will appear here.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($completedConnections as $connection): ?>
                                    <!-- Similar structure as active but with different actions -->
                                    <div class="connection-card completed" data-connection-id="<?php echo $connection['id']; ?>">
                                        <!-- Connection content here -->
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Respond to Connection Request</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="responseForm">
                    <input type="hidden" id="connectionId" name="connection_id">
                    <input type="hidden" id="responseAction" name="action">
                    
                    <div class="form-group">
                        <label for="responseMessage">Message (Optional)</label>
                        <textarea id="responseMessage" name="message" rows="4" placeholder="Add a message..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitResponse()">Send Response</button>
            </div>
        </div>
    </div>

    <style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-lg);
        margin-bottom: var(--spacing-xl);
    }

    .stat-card {
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
    }

    .stat-icon {
        font-size: 2rem;
    }

    .stat-content h3 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stat-content p {
        margin: 0;
        color: var(--text-muted);
    }

    .tabs-container {
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-xl);
        overflow: hidden;
    }

    .tabs-header {
        display: flex;
        background: var(--surface-color);
        border-bottom: 1px solid var(--border-color);
    }

    .tab-button {
        flex: 1;
        padding: var(--spacing-md) var(--spacing-lg);
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        color: var(--text-secondary);
        transition: all var(--transition-fast);
    }

    .tab-button:hover {
        background: var(--hover-color);
        color: var(--text-primary);
    }

    .tab-button.active {
        background: var(--primary-color);
        color: white;
    }

    .tab-content {
        display: none;
        padding: var(--spacing-lg);
    }

    .tab-content.active {
        display: block;
    }

    .connections-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-lg);
    }

    .connection-card {
        background: var(--background-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        transition: all var(--transition-fast);
    }

    .connection-card:hover {
        box-shadow: var(--shadow-sm);
    }

    .connection-card.pending {
        border-left: 4px solid var(--warning-color);
    }

    .connection-card.active {
        border-left: 4px solid var(--success-color);
    }

    .connection-card.completed {
        border-left: 4px solid var(--info-color);
    }

    .connection-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: var(--spacing-md);
    }

    .user-info {
        display: flex;
        gap: var(--spacing-md);
        align-items: center;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-details h4 {
        margin: 0 0 var(--spacing-xs) 0;
        color: var(--text-primary);
    }

    .user-role {
        margin: 0;
        color: var(--primary-color);
        font-weight: 500;
        font-size: 0.875rem;
    }

    .user-title {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .connection-meta {
        text-align: right;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-xs);
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 500;
        margin-bottom: var(--spacing-xs);
    }

    .status-pending {
        background: var(--warning-color-light);
        color: var(--warning-color);
    }

    .status-active {
        background: var(--success-color-light);
        color: var(--success-color);
    }

    .connection-date {
        display: block;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .connection-message, .connection-goals {
        margin-bottom: var(--spacing-md);
        padding: var(--spacing-md);
        background: var(--surface-color);
        border-radius: var(--radius-md);
    }

    .connection-message h5, .connection-goals h5 {
        margin: 0 0 var(--spacing-xs) 0;
        color: var(--text-primary);
        font-size: 0.875rem;
    }

    .connection-message p, .connection-goals p {
        margin: 0;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .connection-actions {
        display: flex;
        gap: var(--spacing-sm);
        align-items: center;
    }

    .connection-actions .btn {
        font-size: 0.875rem;
    }

    .empty-state {
        text-align: center;
        padding: var(--spacing-2xl);
        color: var(--text-muted);
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background: var(--card-color);
        margin: 15% auto;
        padding: 0;
        border-radius: var(--radius-lg);
        width: 90%;
        max-width: 500px;
        box-shadow: var(--shadow-lg);
    }

    .modal-header {
        padding: var(--spacing-lg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-muted);
    }

    .modal-body {
        padding: var(--spacing-lg);
    }

    .modal-footer {
        padding: var(--spacing-lg);
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: var(--spacing-sm);
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .connection-header {
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .connection-actions {
            flex-wrap: wrap;
        }
    }
    </style>

    <script>
    // Optimized tab functionality with event delegation
    const tabsHeader = document.querySelector('.tabs-header');
    const tabContents = document.querySelectorAll('.tab-content');
    const tabButtons = document.querySelectorAll('.tab-button');
    
    tabsHeader?.addEventListener('click', function(e) {
        const button = e.target.closest('.tab-button');
        if (!button) return;
        
        const targetTab = button.dataset.tab;
        
        // Use requestAnimationFrame for smooth transitions
        requestAnimationFrame(() => {
            // Update buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Update content
            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === targetTab + '-tab') {
                    content.classList.add('active');
                }
            });
        });
    });

    // Response functionality
    function respondToRequest(connectionId, action) {
        document.getElementById('connectionId').value = connectionId;
        document.getElementById('responseAction').value = action;
        document.getElementById('modalTitle').textContent = 
            action === 'accept' ? 'Accept Connection Request' : 'Decline Connection Request';
        
        document.getElementById('responseModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('responseModal').style.display = 'none';
        document.getElementById('responseForm').reset();
    }

    // Optimized response submission with better UX
    function submitResponse() {
        const form = document.getElementById('responseForm');
        const submitButton = document.querySelector('.modal-footer .btn-primary');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Disable button to prevent double submission
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        fetch('api-debug.php?action=respond', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(async response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('Response sent successfully!', 'success');
                closeModal();
                
                // Update the UI instead of full reload
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error: ' + error.message, 'error');
        })
        .finally(() => {
            // Re-enable button
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send Response';
        });
    }
    
    // Simple notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Other actions
    function bookSession(userId) {
        window.location.href = `../sessions/book.php?${userId}`;
    }

    function sendMessage(userId) {
        window.location.href = `../messages/compose.php?recipient_id=${userId}`;
    }

    function manageConnection(connectionId) {
        // Implement connection management functionality
        alert('Connection management coming soon!');
    }

    function cancelRequest(connectionId) {
        if (confirm('Are you sure you want to cancel this connection request?')) {
            // Implement cancel functionality
            alert('Cancel functionality coming soon!');
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('responseModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>

    <script src="../assets/js/app.js"></script>
</body>
</html>
