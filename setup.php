<?php
// Installation configuration
$config = [
    'xampp_path' => 'C:/xampp',
    'project_path' => 'C:/xampp/htdocs/project',
    'startup_path' => getenv('APPDATA') . '\Microsoft\Windows\Start Menu\Programs\Startup',
    'server_port' => '8080',
    'server_ip' => '192.168.1.10'
];

try {
    // Create startup batch file
    $batchContent = <<<EOT
@echo off
echo Starting Remote Desktop Control...

:: Start XAMPP Apache
cd /d "{$config['xampp_path']}/apache/bin"
start /min httpd.exe

:: Start XAMPP MySQL
cd /d "{$config['xampp_path']}/mysql/bin"
start /min mysqld.exe

:: Start WebSocket Server
cd /d "{$config['project_path']}"
start /min cmd /c "php stream_server.php"

:: Wait for services to start
timeout /t 10

:: Open the application
start http://{$config['server_ip']}:{$config['server_port']}/project/
exit
EOT;

    // Create the startup batch file
    $batchPath = $config['project_path'] . '/start_remote_control.bat';
    file_put_contents($batchPath, $batchContent);

    // Create a VBS script to create the shortcut (since we can't use COM)
    $vbsContent = <<<EOT
Set WshShell = CreateObject("WScript.Shell")
Set shortcut = WshShell.CreateShortcut("{$config['startup_path']}/RemoteControl.lnk")
shortcut.TargetPath = "{$batchPath}"
shortcut.WorkingDirectory = "{$config['project_path']}"
shortcut.Save
EOT;

    // Save and execute the VBS script
    $vbsPath = $config['project_path'] . '/create_shortcut.vbs';
    file_put_contents($vbsPath, $vbsContent);
    
    // Execute the VBS script
    exec("cscript //nologo {$vbsPath}");
    
    // Clean up the VBS script
    unlink($vbsPath);

    // Create desktop shortcut
    $desktopPath = getenv('USERPROFILE') . '\Desktop';
    $vbsDesktopContent = <<<EOT
Set WshShell = CreateObject("WScript.Shell")
Set shortcut = WshShell.CreateShortcut("{$desktopPath}/Remote Desktop Control.lnk")
shortcut.TargetPath = "{$batchPath}"
shortcut.WorkingDirectory = "{$config['project_path']}"
shortcut.IconLocation = "{$config['project_path']}/assets/icon.ico"
shortcut.Save
EOT;

    $vbsDesktopPath = $config['project_path'] . '/create_desktop_shortcut.vbs';
    file_put_contents($vbsDesktopPath, $vbsDesktopContent);
    exec("cscript //nologo {$vbsDesktopPath}");
    unlink($vbsDesktopPath);

    // Create setup verification file
    $setupInfo = [
        'installation_date' => date('Y-m-d H:i:s'),
        'xampp_path' => $config['xampp_path'],
        'project_path' => $config['project_path'],
        'startup_enabled' => true
    ];
    file_put_contents($config['project_path'] . '/setup_info.json', json_encode($setupInfo, JSON_PRETTY_PRINT));

    echo "Installation completed successfully!\n";
    echo "Shortcuts created:\n";
    echo "1. Startup folder: {$config['startup_path']}/RemoteControl.lnk\n";
    echo "2. Desktop: {$desktopPath}/Remote Desktop Control.lnk\n";
    echo "The application will start automatically with Windows.\n";
    echo "You can also start it manually from the desktop shortcut.\n";

} catch (Exception $e) {
    echo "Installation failed: " . $e->getMessage() . "\n";
    // Log the error
    error_log("Setup failed: " . $e->getMessage());
}

// Create an uninstall script
$uninstallContent = <<<EOT
@echo off
echo Removing Remote Desktop Control...

:: Remove shortcuts
del "{$config['startup_path']}\RemoteControl.lnk"
del "%userprofile%\Desktop\Remote Desktop Control.lnk"

:: Remove batch file
del "{$batchPath}"

echo Uninstallation completed.
pause
EOT;

file_put_contents($config['project_path'] . '/uninstall.bat', $uninstallContent);
?> 