<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
requireRole('mentor');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Mentor Dashboard</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px; 
            margin: 20px 0;
        }
        .stat-card { 
            background: #007bff; 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center;
        }
        .stat-number { 
            font-size: 2em; 
            font-weight: bold; 
            margin-bottom: 5px;
        }
        .stat-label { 
            font-size: 0.9em; 
            opacity: 0.9;
        }
        h1 { color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        <p>This is your simplified mentor dashboard.</p>
        
        <?php
        // Test each query individually
        echo "<h2>Database Tests:</h2>";
        
        try {
            $totalSessions = fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ?", [$user['id']]);
            echo "<div class='success'>✓ Total Sessions: " . $totalSessions['count'] . "</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Total Sessions Error: " . $e->getMessage() . "</div>";
        }
        
        try {
            $upcomingSessions = fetchOne("SELECT COUNT(*) as count FROM sessions WHERE mentor_id = ? AND scheduled_at > NOW() AND status = 'scheduled'", [$user['id']]);
            echo "<div class='success'>✓ Upcoming Sessions: " . $upcomingSessions['count'] . "</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Upcoming Sessions Error: " . $e->getMessage() . "</div>";
        }
        
        try {
            $totalStudents = fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM sessions WHERE mentor_id = ?", [$user['id']]);
            echo "<div class='success'>✓ Total Students: " . $totalStudents['count'] . "</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Total Students Error: " . $e->getMessage() . "</div>";
        }
        
        try {
            $avgRating = fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']]);
            echo "<div class='success'>✓ Average Rating: " . number_format($avgRating['avg'] ?? 0, 1) . "</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Average Rating Error: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalSessions['count'] ?? 0; ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $upcomingSessions['count'] ?? 0; ?></div>
                <div class="stat-label">Upcoming Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalStudents['count'] ?? 0; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($avgRating['avg'] ?? 0, 1); ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>
        
        <h3>Actions:</h3>
        <ul>
            <li><a href="../sessions/index.php">Manage Sessions</a></li>
            <li><a href="../messages/index.php">View Messages</a></li>
            <li><a href="../profile/edit.php">Edit Profile</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </div>
</body>
</html>