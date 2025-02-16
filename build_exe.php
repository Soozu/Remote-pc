<?php
// Configuration
$config = [
    'app_name' => 'Remote Desktop Control',
    'version' => '1.0.0',
    'output_dir' => getenv('USERPROFILE') . '\Desktop',
    'icon_path' => __DIR__ . '\assets\icon.ico',
    'project_path' => 'C:/xampp/htdocs/project'
];

try {
    // Create temporary build directory
    $buildDir = __DIR__ . '/temp_build';
    if (!file_exists($buildDir)) {
        mkdir($buildDir);
    }

    // Create batch script content
    $batchContent = <<<EOT
@echo off
echo Starting {$config['app_name']}...

:: Kill existing processes
taskkill /F /IM httpd.exe /T 2>nul
taskkill /F /IM mysqld.exe /T 2>nul

:: Start XAMPP Apache
cd /d "C:/xampp/apache/bin"
start /min httpd.exe

:: Start XAMPP MySQL
cd /d "C:/xampp/mysql/bin"
start /min mysqld.exe

:: Start WebSocket Server
cd /d "{$config['project_path']}"
start /min cmd /c "php stream_server.php"

:: Wait for services
timeout /t 5

:: Configure firewall
netsh advfirewall firewall delete rule name="Remote Desktop Control - HTTP" >nul 2>&1
netsh advfirewall firewall add rule name="Remote Desktop Control - HTTP" dir=in action=allow protocol=TCP localport=8080

netsh advfirewall firewall delete rule name="Remote Desktop Control - WebSocket" >nul 2>&1
netsh advfirewall firewall add rule name="Remote Desktop Control - WebSocket" dir=in action=allow protocol=TCP localport=8081

:: Open application
start http://localhost:8080/project/

exit
EOT;

    // Save batch script
    file_put_contents($buildDir . '/launcher.bat', $batchContent);

    // Create PS1 script to convert batch to exe
    $ps1Content = <<<EOT
\$scriptPath = '{$buildDir}\launcher.bat'
\$outputPath = '{$config['output_dir']}\RemoteDesktopControl.exe'
\$iconPath = '{$config['icon_path']}'

# Create the executable
\$code = @'
using System;
using System.Diagnostics;
using System.Windows.Forms;

public class Program {
    public static void Main() {
        try {
            ProcessStartInfo startInfo = new ProcessStartInfo();
            startInfo.FileName = "cmd.exe";
            startInfo.Arguments = "/c " + @"{$buildDir}\launcher.bat";
            startInfo.WindowStyle = ProcessWindowStyle.Hidden;
            startInfo.CreateNoWindow = true;
            startInfo.UseShellExecute = true;
            startInfo.Verb = "runas"; // Run as administrator

            Process.Start(startInfo);
        }
        catch (Exception ex) {
            MessageBox.Show("Error starting application: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
'@

Add-Type -TypeDefinition \$code -OutputAssembly \$outputPath -OutputType WindowsApplication
EOT;

    // Save PS1 script
    file_put_contents($buildDir . '/build.ps1', $ps1Content);

    // Execute PowerShell script
    $command = "powershell -ExecutionPolicy Bypass -File \"{$buildDir}/build.ps1\"";
    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
        throw new Exception("Failed to create executable. Error code: " . $returnCode);
    }

    // Clean up
    array_map('unlink', glob($buildDir . '/*'));
    rmdir($buildDir);

    echo "Build completed successfully!\n";
    echo "Executable created at: {$config['output_dir']}\RemoteDesktopControl.exe\n";

} catch (Exception $e) {
    echo "Build failed: " . $e->getMessage() . "\n";
    error_log("Build failed: " . $e->getMessage());
}
?> 