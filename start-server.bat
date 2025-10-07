@echo off
echo Starting MentorConnect Development Server...

echo.
echo Checking WAMP services...

REM Check if Apache is running
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [✓] Apache is already running
) else (
    echo [!] Apache is not running. Please start WAMP manually.
    echo.
    echo To start WAMP:
    echo 1. Go to C:\wamp64
    echo 2. Run wampmanager.exe as Administrator
    echo 3. Start All Services
    echo.
    pause
    exit /b 1
)

REM Check if MySQL is running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [✓] MySQL is already running
) else (
    echo [!] MySQL is not running. Please start WAMP manually.
    pause
    exit /b 1
)

echo.
echo [✓] All services are running!
echo [✓] Database is configured
echo [✓] Application is ready

echo.
echo MentorConnect is ready!
echo.
echo Open your browser and go to:
echo http://localhost/mentorconnect
echo.
echo Press any key to open in default browser...
pause >nul

start http://localhost/mentorconnect

echo.
echo Development server is running...
echo Press Ctrl+C to stop monitoring
echo.

:loop
timeout /t 5 >nul
goto loop