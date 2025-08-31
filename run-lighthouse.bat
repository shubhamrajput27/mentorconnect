@echo off
echo Running Lighthouse Performance Audit...
lighthouse http://localhost:8000 --output=html --output-path=lighthouse-report.html --chrome-flags="--headless --disable-gpu --no-sandbox"
echo.
echo Report generated: lighthouse-report.html
pause
