<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();

// Handle review submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $sessionId = intval($_POST['session_id']);
        $rating = intval($_POST['rating']);
        $comment = sanitizeInput($_POST['comment']);
        
        if ($sessionId <= 0 || $rating < 1 || $rating > 5) {
            $error = 'Please provide valid session and rating.';
        } else {
            // Check if session exists and user participated
            $session = fetchOne(
                "SELECT id, mentor_id, student_id, title, scheduled_at FROM sessions WHERE id = ? AND (mentor_id = ? OR student_id = ?) AND status = 'completed'",
                [$sessionId, $user['id'], $user['id']]
            );
            
            if (!$session) {
                $error = 'Session not found or not eligible for review.';
            } else {
                // Check if review already exists
                $existingReview = fetchOne(
                    "SELECT id FROM reviews WHERE session_id = ? AND reviewer_id = ?",
                    [$sessionId, $user['id']]
                );
                
                if ($existingReview) {
                    $error = 'You have already reviewed this session.';
                } else {
                    // Determine who is being reviewed
                    $revieweeId = ($session['mentor_id'] == $user['id']) ? $session['student_id'] : $session['mentor_id'];
                    
                    // Insert review
                    executeQuery(
                        "INSERT INTO reviews (session_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)",
                        [$sessionId, $user['id'], $revieweeId, $rating, $comment]
                    );
                    
                    // Update mentor's average rating if they were reviewed
                    if ($session['mentor_id'] == $revieweeId) {
                        $avgRating = fetchOne(
                            "SELECT AVG(rating) as avg_rating FROM reviews WHERE reviewee_id = ?",
                            [$revieweeId]
                        )['avg_rating'];
                        
                        executeQuery(
                            "UPDATE mentor_profiles SET rating = ? WHERE user_id = ?",
                            [round($avgRating, 2), $revieweeId]
                        );
                    }
                    
                    // Create notification for reviewee
                    createNotification(
                        $revieweeId,
                        'feedback',
                        'You received a new review',
                        "You received a {$rating}-star review for your session."
                    );
                    
                    // Log activity
                    logActivity($user['id'], 'review_submitted', "Submitted review for session ID: {$sessionId}");
                    
                    $success = 'Review submitted successfully!';
                }
            }
        }
    }
}

// Get sessions eligible for review
$eligibleSessions = fetchAll(
    "SELECT s.*, 
            CASE WHEN s.mentor_id = ? THEN CONCAT(u2.first_name, ' ', u2.last_name) 
                 ELSE CONCAT(u1.first_name, ' ', u1.last_name) END as other_participant,
            CASE WHEN s.mentor_id = ? THEN u2.profile_photo 
                 ELSE u1.profile_photo END as other_photo,
            CASE WHEN s.mentor_id = ? THEN 'student' ELSE 'mentor' END as other_role
     FROM sessions s
     JOIN users u1 ON s.mentor_id = u1.id
     JOIN users u2 ON s.student_id = u2.id
     WHERE (s.mentor_id = ? OR s.student_id = ?) 
       AND s.status = 'completed'
       AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.session_id = s.id AND r.reviewer_id = ?)
     ORDER BY s.scheduled_at DESC",
    [$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]
);

// Get reviews given by user
$givenReviews = fetchAll(
    "SELECT r.*, s.title as session_title, s.scheduled_at,
            CONCAT(u.first_name, ' ', u.last_name) as reviewee_name,
            u.profile_photo as reviewee_photo
     FROM reviews r
     JOIN sessions s ON r.session_id = s.id
     JOIN users u ON r.reviewee_id = u.id
     WHERE r.reviewer_id = ?
     ORDER BY r.created_at DESC",
    [$user['id']]
);

// Get reviews received by user
$receivedReviews = fetchAll(
    "SELECT r.*, s.title as session_title, s.scheduled_at,
            CONCAT(u.first_name, ' ', u.last_name) as reviewer_name,
            u.profile_photo as reviewer_photo
     FROM reviews r
     JOIN sessions s ON r.session_id = s.id
     JOIN users u ON r.reviewer_id = u.id
     WHERE r.reviewee_id = ?
     ORDER BY r.created_at DESC",
    [$user['id']]
);

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews & Feedback - <?php echo APP_NAME; ?></title>
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
                    <a href="/dashboard/<?php echo $user['role']; ?>.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/sessions/index.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Sessions</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/messages/index.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/files/index.php" class="nav-link">
                        <i class="fas fa-folder"></i>
                        <span>Files</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/reviews/index.php" class="nav-link active">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/profile/edit.php" class="nav-link">
                        <i class="fas fa-user-edit"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/auth/logout.php" class="nav-link">
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
                    <h2>Reviews & Feedback</h2>
                </div>
                
                <div class="header-right">
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <div class="user-menu">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Reviews Content -->
            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Review Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="showTab('pending')">
                        <i class="fas fa-clock"></i>
                        Pending Reviews (<?php echo count($eligibleSessions); ?>)
                    </button>
                    <button class="tab-btn" onclick="showTab('given')">
                        <i class="fas fa-star"></i>
                        Reviews Given (<?php echo count($givenReviews); ?>)
                    </button>
                    <button class="tab-btn" onclick="showTab('received')">
                        <i class="fas fa-heart"></i>
                        Reviews Received (<?php echo count($receivedReviews); ?>)
                    </button>
                </div>

                <!-- Pending Reviews Tab -->
                <div id="pending-tab" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h3>Sessions Awaiting Your Review</h3>
                            <p>Share your feedback to help improve the mentoring experience</p>
                        </div>
                        <div class="card-body">
                            <?php if (empty($eligibleSessions)): ?>
                                <div class="no-reviews">
                                    <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                                    <h3>All caught up!</h3>
                                    <p>You have no pending reviews. Complete more sessions to leave feedback.</p>
                                </div>
                            <?php else: ?>
                                <div class="sessions-list">
                                    <?php foreach ($eligibleSessions as $session): ?>
                                        <div class="session-card">
                                            <div class="session-info">
                                                <div class="participant-info">
                                                    <img src="<?php echo $session['other_photo'] ? '../uploads/' . $session['other_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                                         alt="Profile" class="participant-avatar">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($session['other_participant']); ?></h4>
                                                        <span class="role-badge role-<?php echo $session['other_role']; ?>">
                                                            <?php echo ucfirst($session['other_role']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="session-details">
                                                    <h5><?php echo htmlspecialchars($session['title']); ?></h5>
                                                    <p class="session-date">
                                                        <i class="fas fa-calendar"></i>
                                                        <?php echo date('M j, Y \a\t g:i A', strtotime($session['scheduled_at'])); ?>
                                                    </p>
                                                    <p class="session-duration">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo $session['duration']; ?> minutes
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <button class="btn btn-primary" onclick="showReviewModal(<?php echo $session['id']; ?>, '<?php echo htmlspecialchars($session['other_participant']); ?>')">
                                                <i class="fas fa-star"></i>
                                                Leave Review
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Given Reviews Tab -->
                <div id="given-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Reviews You've Given</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($givenReviews)): ?>
                                <div class="no-reviews">
                                    <i class="fas fa-star-half-alt" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                    <h3>No reviews given yet</h3>
                                    <p>Complete sessions and leave reviews to help others in the community.</p>
                                </div>
                            <?php else: ?>
                                <div class="reviews-list">
                                    <?php foreach ($givenReviews as $review): ?>
                                        <div class="review-card">
                                            <div class="review-header">
                                                <div class="reviewer-info">
                                                    <img src="<?php echo $review['reviewee_photo'] ? '../uploads/' . $review['reviewee_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                                         alt="Profile" class="reviewer-avatar">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($review['reviewee_name']); ?></h4>
                                                        <p class="session-title"><?php echo htmlspecialchars($review['session_title']); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="review-meta">
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="review-date"><?php echo formatTimeAgo($review['created_at']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <?php if ($review['comment']): ?>
                                                <div class="review-comment">
                                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Received Reviews Tab -->
                <div id="received-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Reviews You've Received</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($receivedReviews)): ?>
                                <div class="no-reviews">
                                    <i class="fas fa-heart" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                    <h3>No reviews received yet</h3>
                                    <p>Complete more sessions to receive feedback from the community.</p>
                                </div>
                            <?php else: ?>
                                <div class="reviews-list">
                                    <?php foreach ($receivedReviews as $review): ?>
                                        <div class="review-card">
                                            <div class="review-header">
                                                <div class="reviewer-info">
                                                    <img src="<?php echo $review['reviewer_photo'] ? '../uploads/' . $review['reviewer_photo'] : '../assets/images/default-avatar.png'; ?>" 
                                                         alt="Profile" class="reviewer-avatar">
                                                    <div>
                                                        <h4><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                                        <p class="session-title"><?php echo htmlspecialchars($review['session_title']); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="review-meta">
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="review-date"><?php echo formatTimeAgo($review['created_at']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <?php if ($review['comment']): ?>
                                                <div class="review-comment">
                                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Leave a Review</h3>
                <button class="modal-close" onclick="hideReviewModal()">&times;</button>
            </div>
            
            <form method="POST" class="review-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="session_id" id="reviewSessionId">
                
                <div class="modal-body">
                    <div class="review-for">
                        <h4>Review for: <span id="revieweeName"></span></h4>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating *</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="star5">
                            <label for="star5"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Comment (Optional)</label>
                        <textarea name="comment" id="comment" rows="4" 
                                  placeholder="Share your experience and feedback..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideReviewModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-star"></i>
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .tabs {
        display: flex;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: var(--spacing-lg);
    }

    .tab-btn {
        background: none;
        border: none;
        padding: var(--spacing-md) var(--spacing-lg);
        cursor: pointer;
        color: var(--text-muted);
        border-bottom: 2px solid transparent;
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
    }

    .tab-btn.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .sessions-list, .reviews-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-lg);
    }

    .session-card {
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all var(--transition-fast);
    }

    .session-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }

    .session-info {
        display: flex;
        gap: var(--spacing-lg);
        align-items: center;
        flex: 1;
    }

    .participant-info {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
    }

    .participant-avatar, .reviewer-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .role-badge {
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .role-mentor {
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary-color);
    }

    .role-student {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
    }

    .session-details h5 {
        margin: 0 0 var(--spacing-xs) 0;
        color: var(--text-primary);
    }

    .session-date, .session-duration {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .review-card {
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        background: var(--card-color);
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: var(--spacing-md);
    }

    .reviewer-info {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
    }

    .reviewer-info h4 {
        margin: 0;
        color: var(--text-primary);
    }

    .session-title {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .review-meta {
        text-align: right;
    }

    .rating {
        margin-bottom: var(--spacing-xs);
    }

    .rating i {
        color: var(--text-muted);
        margin-right: 2px;
    }

    .rating i.active {
        color: #fbbf24;
    }

    .review-date {
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .review-comment {
        background: var(--background-color);
        border-radius: var(--radius-md);
        padding: var(--spacing-md);
        margin-top: var(--spacing-md);
    }

    .review-comment p {
        margin: 0;
        color: var(--text-secondary);
    }

    .no-reviews {
        text-align: center;
        padding: var(--spacing-2xl);
        color: var(--text-muted);
    }

    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 4px;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        cursor: pointer;
        font-size: 1.5rem;
        color: var(--text-muted);
        transition: color var(--transition-fast);
    }

    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
        color: #fbbf24;
    }

    .review-for {
        margin-bottom: var(--spacing-lg);
        padding: var(--spacing-md);
        background: var(--background-color);
        border-radius: var(--radius-md);
        text-align: center;
    }

    .review-for h4 {
        margin: 0;
        color: var(--text-primary);
    }

    @media (max-width: 768px) {
        .session-card {
            flex-direction: column;
            gap: var(--spacing-md);
            align-items: stretch;
        }

        .session-info {
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .review-header {
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .review-meta {
            text-align: left;
        }

        .tabs {
            flex-direction: column;
        }

        .tab-btn {
            justify-content: center;
        }
    }
    </style>

    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
    }

    function showReviewModal(sessionId, participantName) {
        document.getElementById('reviewSessionId').value = sessionId;
        document.getElementById('revieweeName').textContent = participantName;
        document.getElementById('reviewModal').style.display = 'flex';
    }

    function hideReviewModal() {
        document.getElementById('reviewModal').style.display = 'none';
        // Reset form
        document.querySelector('.review-form').reset();
        document.querySelectorAll('.star-rating input').forEach(input => {
            input.checked = false;
        });
    }

    // Close modal when clicking outside
    document.getElementById('reviewModal').addEventListener('click', (e) => {
        if (e.target.id === 'reviewModal') {
            hideReviewModal();
        }
    });
    </script>

    <script src="../assets/js/app.js"></script>
</body>
</html>
