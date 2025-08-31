<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Reset - MentorConnect</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .reset-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .success {
            color: #28a745;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .info {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>🌟 Theme Reset Complete!</h2>
        <div class="success">
            ✅ Theme has been reset to Light Mode
        </div>
        <div class="info">
            Your theme preference has been cleared and set to light mode by default.
            <br><br>
            <strong>Theme Toggle Location:</strong><br>
            The theme toggle button is now located at the <strong>bottom-right corner</strong> of the screen.
            <br><br>
            <strong>How to use:</strong><br>
            • Single click: Toggle between light/dark<br>
            • Double click: Reset to light mode
        </div>
        
        <a href="index.php" class="btn">Go to Home Page</a>
        <button onclick="resetAgain()" class="btn btn-secondary">Reset Again</button>
    </div>

    <script>
        // Clear theme from localStorage and set to light
        localStorage.removeItem('theme');
        localStorage.setItem('theme', 'light');
        
        function resetAgain() {
            localStorage.removeItem('theme');
            localStorage.setItem('theme', 'light');
            alert('Theme reset to light mode again!');
        }
        
        // Auto redirect after 5 seconds
        setTimeout(() => {
            const container = document.querySelector('.reset-container');
            container.innerHTML += '<div style="margin-top: 1rem; color: #6c757d;">Redirecting to home page in 3 seconds...</div>';
            
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 3000);
        }, 2000);
    </script>
</body>
</html>
