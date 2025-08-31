<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Debug - MentorConnect</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            line-height: 1.6;
        }
        .debug-panel {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .btn {
            background: #007cba;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover {
            background: #005a87;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🛠️ Theme Debug Tool</h1>
    <p>This tool helps debug and fix theme issues on the MentorConnect landing page.</p>
    
    <div class="debug-panel">
        <h3>Current Status</h3>
        <div id="status"></div>
        
        <h3>Theme Actions</h3>
        <button class="btn" onclick="clearTheme()">Clear Theme Settings</button>
        <button class="btn" onclick="setLightTheme()">Force Light Theme</button>
        <button class="btn" onclick="setDarkTheme()">Force Dark Theme</button>
        <button class="btn" onclick="redirectToHome()">Go to Home Page</button>
        
        <h3>Debug Information</h3>
        <div id="debug-info"></div>
    </div>

    <script>
        function updateStatus() {
            const currentTheme = localStorage.getItem('theme');
            const htmlTheme = document.documentElement.getAttribute('data-theme');
            
            const statusHtml = `
                <div class="status info">
                    <strong>LocalStorage theme:</strong> ${currentTheme || 'Not set'}<br>
                    <strong>HTML data-theme:</strong> ${htmlTheme || 'Not set'}<br>
                    <strong>Browser support:</strong> ${typeof Storage !== "undefined" ? 'Yes' : 'No'}
                </div>
            `;
            
            document.getElementById('status').innerHTML = statusHtml;
            
            const debugInfo = `
                <div class="status info">
                    <strong>User Agent:</strong> ${navigator.userAgent}<br>
                    <strong>Page URL:</strong> ${window.location.href}<br>
                    <strong>Timestamp:</strong> ${new Date().toLocaleString()}
                </div>
            `;
            
            document.getElementById('debug-info').innerHTML = debugInfo;
        }
        
        function clearTheme() {
            localStorage.removeItem('theme');
            document.documentElement.removeAttribute('data-theme');
            updateStatus();
            showMessage('Theme settings cleared!', 'success');
        }
        
        function setLightTheme() {
            localStorage.setItem('theme', 'light');
            document.documentElement.setAttribute('data-theme', 'light');
            updateStatus();
            showMessage('Light theme set!', 'success');
        }
        
        function setDarkTheme() {
            localStorage.setItem('theme', 'dark');
            document.documentElement.setAttribute('data-theme', 'dark');
            updateStatus();
            showMessage('Dark theme set!', 'success');
        }
        
        function redirectToHome() {
            window.location.href = '/mentorconnect/';
        }
        
        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `status ${type}`;
            messageDiv.textContent = message;
            document.getElementById('status').appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }
        
        // Initialize
        updateStatus();
        
        // Auto-refresh status every 2 seconds
        setInterval(updateStatus, 2000);
    </script>
</body>
</html>
