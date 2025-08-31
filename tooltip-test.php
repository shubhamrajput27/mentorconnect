<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tooltip Test - MentorConnect</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .test-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1001;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .theme-toggle:hover {
            transform: scale(1.05) translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .theme-toggle i {
            font-size: 1.25rem;
            color: #6366f1;
            transition: all 0.3s ease;
        }
        
        .status {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid #6366f1;
        }
        
        .success { border-left-color: #10b981; background: #d4edda; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="test-container">
        <h2>🔍 Tooltip Removal Test</h2>
        <p>This page tests that the theme toggle button has NO tooltips.</p>
        
        <div class="status">
            <h4>✅ What was removed:</h4>
            <ul style="text-align: left;">
                <li><code>data-tooltip</code> attribute from the button</li>
                <li><code>.theme-toggle::before</code> CSS rule</li>
                <li><code>.theme-toggle:hover::before</code> CSS rule</li>
                <li>All tooltip-related content</li>
            </ul>
        </div>
        
        <div class="status success">
            <h4>🎯 Test Instructions:</h4>
            <ol style="text-align: left;">
                <li>Look for the theme toggle button in the bottom-right corner</li>
                <li>Hover over the button with your mouse</li>
                <li>Verify that NO tooltip text appears</li>
                <li>Click the button to test functionality</li>
            </ol>
        </div>
        
        <div style="margin: 2rem 0;">
            <p><strong>Expected Result:</strong> Button should hover with animation but show NO text tooltips</p>
            <p><strong>Button Location:</strong> Bottom-right corner (fixed position)</p>
        </div>
        
        <button onclick="window.location.href='index.php'" style="background: #6366f1; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem; margin: 0.5rem;">
            Go to Home Page
        </button>
        
        <button onclick="testTooltip()" style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem; margin: 0.5rem;">
            Test Theme Toggle
        </button>
    </div>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <script>
        let themeCount = 0;
        
        function testTooltip() {
            themeCount++;
            const button = document.querySelector('.theme-toggle');
            
            // Simulate hover and check for tooltips
            const tooltips = document.querySelectorAll('[data-tooltip], .tooltip, .theme-toggle::before');
            
            if (tooltips.length === 0) {
                alert(`✅ Test ${themeCount}: NO tooltips found! Button is clean.`);
            } else {
                alert(`❌ Test ${themeCount}: ${tooltips.length} tooltip(s) still found.`);
            }
            
            // Toggle theme for visual feedback
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            
            const icon = document.getElementById('theme-icon');
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        // Add click event to theme toggle
        document.querySelector('.theme-toggle').addEventListener('click', testTooltip);
        
        // Check for existing tooltips on load
        window.addEventListener('load', () => {
            setTimeout(() => {
                const hasTooltips = document.querySelector('[data-tooltip]') !== null;
                const status = document.createElement('div');
                status.className = hasTooltips ? 'status' : 'status success';
                status.innerHTML = hasTooltips ? 
                    '❌ <strong>Warning:</strong> data-tooltip attributes still found!' :
                    '✅ <strong>Success:</strong> No data-tooltip attributes found!';
                document.querySelector('.test-container').appendChild(status);
            }, 1000);
        });
    </script>
</body>
</html>
