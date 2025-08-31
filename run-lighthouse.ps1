# PowerShell Lighthouse Runner
Write-Host "Starting Lighthouse Performance Audit..." -ForegroundColor Green

# Check if server is running
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8000" -Method Head -TimeoutSec 5
    Write-Host "Server is running on http://localhost:8000" -ForegroundColor Green
} catch {
    Write-Host "Error: Server not accessible at http://localhost:8000" -ForegroundColor Red
    exit 1
}

# Run Lighthouse
Write-Host "Running Lighthouse audit..." -ForegroundColor Yellow

# Basic audit
$lighthouseCmd = "lighthouse http://localhost:8000 --output html --output-path lighthouse-report.html --chrome-flags=`"--headless --disable-gpu --no-sandbox`""

try {
    Invoke-Expression $lighthouseCmd
    Write-Host "Lighthouse audit completed successfully!" -ForegroundColor Green
    Write-Host "Report saved as: lighthouse-report.html" -ForegroundColor Cyan
    
    # Check if file was created
    if (Test-Path "lighthouse-report.html") {
        Write-Host "Report file confirmed - opening in browser..." -ForegroundColor Green
        Start-Process "lighthouse-report.html"
    } else {
        Write-Host "Warning: Report file not found" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Error running Lighthouse: $($_.Exception.Message)" -ForegroundColor Red
}

# Also run JSON output for detailed analysis
Write-Host "Generating JSON report for analysis..." -ForegroundColor Yellow
$lighthouseJsonCmd = "lighthouse http://localhost:8000 --output json --output-path lighthouse-report.json --chrome-flags=`"--headless --disable-gpu --no-sandbox`""

try {
    Invoke-Expression $lighthouseJsonCmd
    Write-Host "JSON report generated: lighthouse-report.json" -ForegroundColor Cyan
} catch {
    Write-Host "Error generating JSON report: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "Lighthouse audit process completed!" -ForegroundColor Green
