@echo off
echo Stopping Apache...
net stop Apache2.4
taskkill /F /IM httpd.exe /T 2>nul

echo Removing existing service...
"C:/xampp/apache/bin/httpd.exe" -k uninstall -n "Apache2.4"

echo Installing Apache service...
"C:/xampp/apache/bin/httpd.exe" -k install -n "Apache2.4"

echo Starting Apache...
net start Apache2.4

echo Done!
pause 