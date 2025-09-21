<?php
// Basic mentor dashboard test
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Basic Dashboard Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .info { background: #e7f3ff; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .error { background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336; }
        .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-left: 4px solid #4caf50; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Dashboard Access Test</h1>
    
    <div class="info">
        <h3>Session Information:</h3>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="success">
            <p><strong>✓ User is logged in</strong></p>
            <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
            <p>User Role: <?php echo $_SESSION['user_role'] ?? 'Not set'; ?></p>
        </div>
        
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'mentor'): ?>
            <div class="success">
                <p><strong>✓ User has mentor role</strong></p>
                <p><a href="mentor.php">Try to access full mentor dashboard</a></p>
                <p><a href="simple-mentor.php">Try simple dashboard</a></p>
                <p><a href="debug-mentor-detailed.php">Try detailed debug</a></p>
            </div>
        <?php else: ?>
            <div class="error">
                <p><strong>❌ User does not have mentor role</strong></p>
                <p>Current role: <?php echo $_SESSION['user_role'] ?? 'Not set'; ?></p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="error">
            <p><strong>❌ User is not logged in</strong></p>
            <p><a href="../auth/login.php">Go to login</a></p>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>File System Test:</h3>
        <p>Current directory: <?php echo __DIR__; ?></p>
        <p>Config file exists: <?php echo file_exists('../config/config.php') ? '✓ Yes' : '❌ No'; ?></p>
        <p>CSS file exists: <?php echo file_exists('../assets/css/style.css') ? '✓ Yes' : '❌ No'; ?></p>
    </div>
</body>
</html>