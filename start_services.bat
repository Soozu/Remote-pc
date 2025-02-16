@echo off
start "" "C:/xampp/apache_start.bat"
start "" "C:/xampp/mysql_start.bat"
timeout /t 10
start http://localhost/project/
exit