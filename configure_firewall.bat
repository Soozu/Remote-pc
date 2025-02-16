@echo off
echo Configuring Windows Firewall for Remote Desktop Control...

:: Add rule for Apache (port 8080)
netsh advfirewall firewall delete rule name="Remote Desktop Control - HTTP" >nul 2>&1
netsh advfirewall firewall add rule name="Remote Desktop Control - HTTP" dir=in action=allow protocol=TCP localport=8080

:: Add rule for WebSocket Server (port 8081)
netsh advfirewall firewall delete rule name="Remote Desktop Control - WebSocket" >nul 2>&1
netsh advfirewall firewall add rule name="Remote Desktop Control - WebSocket" dir=in action=allow protocol=TCP localport=8081

echo Firewall rules added successfully!
pause 