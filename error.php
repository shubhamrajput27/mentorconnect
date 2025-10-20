<?php
// Error handling page
$error_code = $_GET['code'] ?? '404';
$error_messages = [
    '400' => ['title' => 'Bad Request', 'message' => 'The request could not be understood by the server.'],
    '401' => ['title' => 'Unauthorized', 'message' => 'You must be logged in to access this page.'],
    '403' => ['title' => 'Forbidden', 'message' => 'You don\'t have permission to access this resource.'],
    '404' => ['title' => 'Page Not Found', 'message' => 'The page you\'re looking for doesn\'t exist.'],
    '500' => ['title' => 'Server Error', 'message' => 'Something went wrong on our end. We\'re working on it!'],
    '503' => ['title' => 'Service Unavailable', 'message' => 'The service is temporarily unavailable. Please try again later.']
];

$error = $error_messages[$error_code] ?? $error_messages['404'];
http_response_code((int)$error_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error['title']; ?> - MentorConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 2rem;
        }
        
        .error-container {
            text-align: center;
            max-width: 600px;
        }
        
        .error-icon {
            font-size: 8rem;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .error-code {
            font-size: 1.5rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }
        
        p {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            opacity: 0.95;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .btn-home {
            display: inline-block;
            padding: 1rem 2rem;
            background: white;
            color: #ea580c;
            text-decoration: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .btn-home i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1><?php echo htmlspecialchars($error['title']); ?></h1>
        <div class="error-code">Error <?php echo htmlspecialchars($error_code); ?></div>
        <p><?php echo htmlspecialchars($error['message']); ?></p>
        <a href="/mentorconnect/" class="btn-home">
            <i class="fas fa-home"></i> Go Back Home
        </a>
    </div>
</body>
</html>
