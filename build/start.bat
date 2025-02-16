@echo off
echo Starting Remote Desktop Control...

:: Start MySQL
start "" "C:/xampp/mysql/bin/mysqld.exe" --datadir="C:/xampp/mysql/data"

:: Start PHP Server
start "" "C:/xampp/php/php.exe" -S localhost:54321 -t "C:\xampp\htdocs\project"

:: Wait for services
timeout /t 5

:: Open application
start http://localhost:54321/

:: Keep running
exit