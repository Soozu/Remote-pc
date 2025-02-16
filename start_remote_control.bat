@echo off
echo Starting Remote Desktop Control...

:: Stop and remove existing services
call restart_apache.bat

:: Configure firewall
call configure_firewall.bat

:: Start XAMPP MySQL
cd /d "C:/xampp/mysql/bin"
start /min mysqld.exe

:: Start WebSocket Server
cd /d "C:/xampp/htdocs/project"
start /min cmd /c "php stream_server.php"

:: Wait for services to start
timeout /t 5

:: Test Apache
echo Testing Apache...
powershell -Command "Invoke-WebRequest -Uri http://localhost:8080/project/ -Method HEAD" 2>nul
if %errorlevel% equ 0 (
    echo Apache is running successfully!
) else (
    echo Apache may not be running correctly. Check the logs.
)

:: Test connection
echo.
echo Testing network connection...
powershell -Command "Test-NetConnection -ComputerName 192.168.1.10 -Port 8080"

:: Open the application
start http://192.168.1.10:8080/project/

echo.
echo If you cannot access the site, try these steps:
echo 1. Access through localhost: http://localhost:8080/project/
echo 2. Check Apache logs: C:\xampp\apache\logs\error.log
echo 3. Try disabling Windows Firewall temporarily
echo 4. Make sure no other service is using port 8080
echo.

:: Keep window open
pause