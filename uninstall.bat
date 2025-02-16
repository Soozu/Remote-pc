@echo off
echo Removing Remote Desktop Control...

:: Remove shortcuts
del "C:\Users\kingp\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup\RemoteControl.lnk"
del "%userprofile%\Desktop\Remote Desktop Control.lnk"

:: Remove batch file
del "C:/xampp/htdocs/project/start_remote_control.bat"

echo Uninstallation completed.
pause