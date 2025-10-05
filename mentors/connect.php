<?php
require_once '../config/config.php';
requireRole('student');

$user = getCurrentUser();
$mentorId = intval($_GET['mentor_id'] ?? 0);

// Generate CSRF token for form security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!$mentorId) {
    header('Location: browse.php');
    exit;
}

// Optimized mentor and connection check with single query
$mentorData = fetchOne(
    "SELECT u.id, u.first_name, u.last_name, u.profile_photo, u.bio, u.status,
            mp.title, mp.company, mp.rating, mp.experience_years, mp.hourly_rate, mp.is_available,
            (SELECT COUNT(*) FROM mentor_mentee_connections 
             WHERE mentor_id = ? AND mentee_id = ? 
               AND status IN ('pending', 'active')) as existing_connection
     FROM users u
     LEFT JOIN mentor_profiles mp ON u.id = mp.user_id
     WHERE u.id = ? AND u.role = 'mentor' AND u.status = 'active' AND mp.is_available = 1",
    [$mentorId, $user['id'], $mentorId]
);

if (!$mentorData) {
    $_SESSION['error'] = 'Mentor not found or not available.';
    header('Location: browse.php');
    exit;
}

$mentor = $mentorData;
$existingConnection = $mentorData['existing_connection'] > 0;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token (if implemented)
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        // For now, just log the attempt
        error_log('CSRF token validation failed for connection request');
    }
    
    if ($existingConnection) {
        $error = 'You already have a pending or active connection with this mentor.';
    } else {
        // Enhanced validation
        $connectionType = sanitizeInput($_POST['connection_type'] ?? '');
        $requestMessage = sanitizeInput($_POST['message'] ?? '');
        $goals = sanitizeInput($_POST['goals'] ?? '');
        
        $validTypes = ['ongoing', 'one-time', 'project-based'];
        if (!in_array($connectionType, $validTypes)) {
            $error = 'Please select a valid connection type.';
        } elseif (strlen($requestMessage) > 1000) {
            $error = 'Message is too long (maximum 1000 characters).';
        } elseif (strlen($goals) > 500) {
            $error = 'Goals description is too long (maximum 500 characters).';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                $db->beginTransaction();
                
                // Insert connection request
                $sql = "INSERT INTO mentor_mentee_connections 
                        (mentor_id, mentee_id, status, connection_type, requested_by, request_message, goals) 
                        VALUES (?, ?, 'pending', ?, 'mentee', ?, ?)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$mentorId, $user['id'], $connectionType, $requestMessage, $goals]);
                $connectionId = $db->lastInsertId();
                
                // Create notification for mentor
                $notificationSql = "INSERT INTO notifications (user_id, type, title, message, data) 
                                   VALUES (?, 'connection_request', 'New Connection Request', ?, ?)";
                
                $notificationMsg = "You have a new connection request from {$user['first_name']} {$user['last_name']}";
                $notificationData = json_encode([
                    'connection_id' => $connectionId, 
                    'sender_id' => $user['id'],
                    'connection_type' => $connectionType
                ]);
                
                $notifStmt = $db->prepare($notificationSql);
                $notifStmt->execute([$mentorId, $notificationMsg, $notificationData]);
                
                $db->commit();
                
                $message = 'Connection request sent successfully! The mentor will be notified and can respond through their dashboard.';
                
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollback();
                }
                $error = 'Failed to send connection request. Please try again later.';
                error_log("Connection request error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect with <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?> - <?php echo APP_NAME; ?></title>
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
                    <a href="<?php echo BASE_URL; ?>/dashboard/student.php" class="nav-link">
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
                    <a href="<?php echo BASE_URL; ?>/connections/index.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>My Connections</span>
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
                    <h2>Connect with Mentor</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content" style="max-width: 800px; margin: 0 auto; padding: 2rem; background: #f8fafc; min-height: calc(100vh - 80px);">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <div style="margin-top: 1rem;">
                            <a href="../connections/index.php" class="btn btn-primary">View My Connections</a>
                            <a href="browse.php" class="btn btn-outline">Find More Mentors</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($existingConnection): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        You already have a <?php echo $existingConnection['status']; ?> connection with this mentor.
                        <div style="margin-top: 1rem;">
                            <a href="../connections/index.php" class="btn btn-primary">View Connection</a>
                            <a href="browse.php" class="btn btn-outline">Find Other Mentors</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Mentor Card -->
                    <div class="mentor-card" style="margin-bottom: 2rem;">
                        <div class="mentor-header">
                            <img src="<?php echo $mentor['profile_photo'] ? '../uploads/' . $mentor['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($mentor['first_name']); ?>" class="mentor-avatar">
                            <div class="mentor-info">
                                <h3><?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?></h3>
                                <p class="mentor-title"><?php echo htmlspecialchars($mentor['title'] ?? 'Mentor'); ?></p>
                                <?php if ($mentor['company']): ?>
                                    <p class="mentor-company"><?php echo htmlspecialchars($mentor['company']); ?></p>
                                <?php endif; ?>
                                <div class="mentor-rating">
                                    <div class="rating-stars">
                                        <?php 
                                        $rating = floatval($mentor['rating']);
                                        for ($i = 1; $i <= 5; $i++): 
                                            if ($i <= $rating): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $rating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif;
                                        endfor; ?>
                                    </div>
                                    <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Connection Form -->
                    <div class="form-container">
                        <h3>Send Connection Request</h3>
                        <p>Fill out the form below to send a connection request to this mentor.</p>
                        
                        <form method="POST" class="connection-form" id="connectionForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="form-group">
                                <label for="connection_type">Connection Type *</label>
                                <select id="connection_type" name="connection_type" required>
                                    <option value="">Select connection type...</option>
                                    <option value="ongoing">Ongoing Mentorship</option>
                                    <option value="one-time">One-time Session</option>
                                    <option value="project-based">Project-based</option>
                                </select>
                                <small>Choose the type of mentoring relationship you're looking for.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message to Mentor</label>
                                <textarea id="message" name="message" rows="5" 
                                        placeholder="Introduce yourself and explain why you'd like to connect with this mentor. What draws you to their expertise?"></textarea>
                                <small>A personalized message increases your chances of acceptance.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="goals">Your Learning Goals</label>
                                <textarea id="goals" name="goals" rows="4" 
                                        placeholder="What do you hope to achieve through this mentorship? What specific skills or knowledge are you looking to develop?"></textarea>
                                <small>Share your goals to help the mentor understand how they can help you.</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    Send Connection Request
                                </button>
                                <a href="browse.php" class="btn btn-outline">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
    :root {
        --primary-color: #3b82f6;
        --primary-dark: #2563eb;
        --success-color: #10b981;
        --error-color: #ef4444;
        --warning-color: #f59e0b;
        --text-primary: #1f2937;
        --text-secondary: #6b7280;
        --text-muted: #9ca3af;
        --background-color: #ffffff;
        --card-color: #ffffff;
        --border-color: #e5e7eb;
        --surface-color: #f9fafb;
        --hover-color: #f3f4f6;
        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --transition-fast: 150ms ease-in-out;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: var(--radius-lg);
        margin-bottom: 2rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        box-shadow: var(--shadow-sm);
    }

    .alert-success {
        background: #d1fae5;
        border: 1px solid #34d399;
        color: #065f46;
    }

    .alert-error {
        background: #fee2e2;
        border: 1px solid #f87171;
        color: #7f1d1d;
    }

    .alert-info {
        background: #dbeafe;
        border: 1px solid #60a5fa;
        color: #1e40af;
    }

    .mentor-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        margin-bottom: 2rem;
    }

    .mentor-header {
        display: flex;
        gap: 1.5rem;
        align-items: center;
    }

    .mentor-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #3b82f6;
        background: #f3f4f6;
    }

    .mentor-info h3 {
        margin: 0 0 0.5rem 0;
        color: #1f2937;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .mentor-title {
        color: #3b82f6;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        font-size: 1rem;
    }

    .mentor-company {
        color: #6b7280;
        margin: 0 0 1rem 0;
        font-size: 0.95rem;
    }

    .rating-stars {
        color: #f59e0b;
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }

    .rating-value {
        font-weight: 600;
        color: #1f2937;
        margin-left: 0.5rem;
        font-size: 0.95rem;
    }

    .form-container {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        margin-top: 1rem;
    }

    .form-container h3 {
        margin: 0 0 0.5rem 0;
        color: #1f2937;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .form-container > p {
        color: #6b7280;
        margin: 0 0 2rem 0;
        font-size: 1rem;
        line-height: 1.5;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
        font-size: 0.95rem;
    }

    .form-group input, 
    .form-group select, 
    .form-group textarea {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-size: 1rem;
        background: #ffffff;
        color: #1f2937;
        transition: all 0.2s ease;
        font-family: inherit;
        box-sizing: border-box;
    }

    .form-group input:focus, 
    .form-group select:focus, 
    .form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: #fefefe;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
    }

    .form-group select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'><path fill='%236b7280' d='m0 0 2 2 2-2z'/></svg>");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
        background-size: 0.65rem auto;
        padding-right: 2.5rem;
    }

    /* Main layout styling */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: #f8fafc;
        margin: 0;
        line-height: 1.6;
    }

    .content {
        background: #f8fafc !important;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .content {
            padding: 1rem !important;
        }
        
        .mentor-card, .form-container {
            padding: 1.5rem;
        }
        
        .mentor-header {
            flex-direction: column;
            text-align: center;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            justify-content: center;
        }
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .form-group small {
        display: block;
        margin-top: 0.5rem;
        color: #6b7280;
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.875rem 2rem;
        border: 2px solid transparent;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1rem;
        line-height: 1;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .btn-primary:hover {
        background: #2563eb;
        border-color: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-outline {
        background: transparent;
        color: #374151;
        border-color: #d1d5db;
    }

    .btn-outline:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        color: #3b82f6;
    }
    </style>

    <script src="../assets/js/app.js"></script>
</body>
</html>