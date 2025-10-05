<?php
// Simple theme test page to check light/dark theme colors
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Test - MentorConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/connections-optimized.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .theme-test-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .theme-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        
        .color-swatch {
            display: inline-block;
            width: 100px;
            height: 40px;
            margin: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-align: center;
            line-height: 40px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="theme-switch" onclick="toggleTheme()">
        <i class="fas fa-palette"></i> Switch Theme
    </div>

    <div class="theme-test-container">
        <h1>MentorConnect Theme Test</h1>
        
        <div class="test-section">
            <h2>Background Colors Test</h2>
            <p>This section should have proper background colors for both light and dark themes.</p>
            <div style="background: var(--background-color); padding: 1rem; margin: 0.5rem 0; border-radius: 8px;">
                Main Background Color
            </div>
            <div style="background: var(--surface-color); padding: 1rem; margin: 0.5rem 0; border-radius: 8px;">
                Surface Color
            </div>
            <div style="background: var(--card-color); padding: 1rem; margin: 0.5rem 0; border-radius: 8px;">
                Card Color
            </div>
        </div>

        <div class="test-section">
            <h2>Connection Components Test</h2>
            <div class="connections-container">
                <div class="tabs-header">
                    <button class="tab-button active">Active</button>
                    <button class="tab-button">Pending</button>
                </div>
                <div class="tab-content">
                    <div class="connection-card">
                        <h3>Sample Connection</h3>
                        <p>This should display properly in both themes.</p>
                        <div class="status-badge status-active">Active</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>Modal Test</h2>
            <button onclick="showModal()" class="btn btn-primary">Show Modal</button>
            <div id="testModal" class="modal" style="display: none;">
                <div class="modal-backdrop" onclick="hideModal()"></div>
                <div class="modal-content">
                    <h3>Test Modal</h3>
                    <p>Modal should have proper colors in both themes.</p>
                    <button onclick="hideModal()" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>Current Theme: <span id="currentTheme">Light</span></h2>
            <p>Check if any unwanted black backgrounds appear in light mode.</p>
            <div class="notification notification-info">
                <i class="fas fa-info-circle"></i>
                This is an info notification - should be properly themed.
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            document.getElementById('currentTheme').textContent = 
                newTheme.charAt(0).toUpperCase() + newTheme.slice(1);
        }

        function showModal() {
            document.getElementById('testModal').style.display = 'block';
        }

        function hideModal() {
            document.getElementById('testModal').style.display = 'none';
        }

        // Initialize theme display
        document.addEventListener('DOMContentLoaded', function() {
            const theme = document.documentElement.getAttribute('data-theme') || 'light';
            document.getElementById('currentTheme').textContent = 
                theme.charAt(0).toUpperCase() + theme.slice(1);
        });
    </script>
</body>
</html>