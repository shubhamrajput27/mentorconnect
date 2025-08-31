<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark Theme Debug</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .debug-panel {
            position: fixed;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            min-width: 200px;
        }
        [data-theme="dark"] .debug-panel {
            background: rgba(30, 41, 59, 0.9);
            color: white;
        }
    </style>
</head>
<body>
    <div class="landing-page">
        <!-- Theme Toggle -->
        <button class="theme-toggle" aria-label="Toggle dark mode">
            <i class="fas fa-moon" id="theme-icon"></i>
        </button>

        <!-- Debug Panel -->
        <div class="debug-panel">
            <h4>Theme Debug</h4>
            <p>Current theme: <span id="current-theme">light</span></p>
            <p>HTML data-theme: <span id="html-theme">-</span></p>
            <p>Background color: <span id="bg-color">-</span></p>
            <button onclick="forceLight()">Force Light</button>
            <button onclick="forceDark()">Force Dark</button>
            <button onclick="checkVars()">Check CSS Vars</button>
        </div>

        <!-- Sample Content -->
        <section class="hero" style="padding: 2rem;">
            <div class="hero-container">
                <div class="hero-content">
                    <h1>Dark Theme Test</h1>
                    <p>This is a test page to debug the dark theme functionality.</p>
                    <p>The background should change when you toggle the theme.</p>
                </div>
            </div>
        </section>
    </div>

    <script src="assets/js/landing.js"></script>
    <script>
        function updateDebugInfo() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const computedStyle = getComputedStyle(document.body);
            const backgroundColor = computedStyle.backgroundColor;
            
            document.getElementById('current-theme').textContent = currentTheme;
            document.getElementById('html-theme').textContent = html.getAttribute('data-theme') || 'none';
            document.getElementById('bg-color').textContent = backgroundColor;
        }

        function forceLight() {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            document.getElementById('theme-icon').className = 'fas fa-moon';
            updateDebugInfo();
        }

        function forceDark() {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            document.getElementById('theme-icon').className = 'fas fa-sun';
            updateDebugInfo();
        }

        function checkVars() {
            const style = getComputedStyle(document.documentElement);
            const bgColor = style.getPropertyValue('--background-color');
            const textColor = style.getPropertyValue('--text-primary');
            alert(`Background: ${bgColor}\nText: ${textColor}`);
        }

        // Update debug info every 500ms
        setInterval(updateDebugInfo, 500);
        
        // Initial update
        updateDebugInfo();
    </script>
</body>
</html>
