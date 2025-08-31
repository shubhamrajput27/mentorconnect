<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear Browser Cache - MentorConnect</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
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
        
        .instructions {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: left;
        }
        
        .status {
            margin: 1rem 0;
            padding: 0.5rem;
            border-radius: 5px;
        }
        
        .success { background: #d4edda; color: #155724; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔄 Clear Browser Cache</h2>
        <p>If you're still seeing the old tooltip text, please clear your browser cache.</p>
        
        <div class="instructions">
            <h4>Manual Cache Clear Instructions:</h4>
            <ul>
                <li><strong>Chrome/Edge:</strong> Press <code>Ctrl + Shift + R</code> or <code>F12</code> → Right-click refresh → "Empty Cache and Hard Reload"</li>
                <li><strong>Firefox:</strong> Press <code>Ctrl + Shift + R</code> or <code>Ctrl + F5</code></li>
                <li><strong>Safari:</strong> Press <code>Cmd + Shift + R</code></li>
            </ul>
        </div>
        
        <div class="status info">
            <strong>What was fixed:</strong><br>
            ✅ Removed data-tooltip attribute from theme button<br>
            ✅ Removed CSS tooltip styles<br>
            ✅ Updated cache-busting parameters<br>
        </div>
        
        <button onclick="clearCacheAndReload()" class="btn">Clear Cache & Reload</button>
        <a href="index.php" class="btn">Go to Home Page</a>
        
        <div id="status" class="status" style="display: none;"></div>
    </div>

    <script>
        function clearCacheAndReload() {
            // Clear localStorage
            localStorage.clear();
            
            // Clear sessionStorage
            sessionStorage.clear();
            
            // Set theme to light
            localStorage.setItem('theme', 'light');
            
            // Show status
            const status = document.getElementById('status');
            status.className = 'status success';
            status.style.display = 'block';
            status.innerHTML = '✅ Cache cleared! Reloading page...';
            
            // Force hard reload with cache bypass
            setTimeout(() => {
                window.location.href = window.location.href + '?t=' + new Date().getTime();
            }, 1000);
        }
        
        // Auto-clear and redirect after 10 seconds
        setTimeout(() => {
            const container = document.querySelector('.container');
            container.innerHTML += '<div style="margin-top: 1rem; color: #6c757d;">Auto-redirecting to clear cache in 3 seconds...</div>';
            
            setTimeout(() => {
                clearCacheAndReload();
            }, 3000);
        }, 7000);
    </script>
</body>
</html>
