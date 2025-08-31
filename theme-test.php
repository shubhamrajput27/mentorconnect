<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="landing-page">
        <!-- Theme Toggle -->
        <button class="theme-toggle" aria-label="Toggle theme">
            <i class="fas fa-moon" id="theme-icon"></i>
        </button>
        
        <div style="padding: 2rem; text-align: center;">
            <h1>Theme Test Page</h1>
            <p>Current theme: <span id="current-theme">light</span></p>
            <p>Click the moon/sun icon to toggle between light and dark themes.</p>
            
            <div style="margin-top: 2rem;">
                <button onclick="checkTheme()">Check Current Theme</button>
                <button onclick="manualToggle()">Manual Toggle</button>
            </div>
        </div>
    </div>

    <script src="assets/js/landing.js"></script>
    <script>
        function checkTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            document.getElementById('current-theme').textContent = currentTheme;
            console.log('Current theme:', currentTheme);
        }
        
        function manualToggle() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
            
            document.getElementById('current-theme').textContent = newTheme;
            console.log('Theme manually set to:', newTheme);
        }
        
        // Check theme on page load
        window.addEventListener('load', checkTheme);
    </script>
</body>
</html>
