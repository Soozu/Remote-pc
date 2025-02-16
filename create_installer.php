<?php
// Installer configuration
$config = [
    'app_name' => 'Remote Desktop Control',
    'version' => '1.0.0',
    'publisher' => 'King',
    'website' => 'http://192.168.1.10:8080/project',
    'output_dir' => getenv('USERPROFILE') . '\Desktop',
    'icon_path' => __DIR__ . '\assets\icon.ico'
];

// Create Inno Setup script
$issContent = <<<EOT
[Setup]
AppName={$config['app_name']}
AppVersion={$config['version']}
AppPublisher={$config['publisher']}
AppPublisherURL={$config['website']}
DefaultDirName={pf}\{$config['app_name']}
DefaultGroupName={$config['app_name']}
OutputDir={$config['output_dir']}
OutputBaseFilename={$config['app_name']}_Setup
SetupIconFile={$config['icon_path']}
Compression=lzma
SolidCompression=yes
PrivilegesRequired=admin

[Files]
Source: "RemoteDesktopControl.exe"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\{$config['app_name']}"; Filename: "{app}\RemoteDesktopControl.exe"
Name: "{commondesktop}\{$config['app_name']}"; Filename: "{app}\RemoteDesktopControl.exe"

[Run]
Filename: "{app}\RemoteDesktopControl.exe"; Description: "Launch {$config['app_name']}"; Flags: postinstall nowait

[UninstallDelete]
Type: files; Name: "{app}\*.*"
Type: dirifempty; Name: "{app}"
EOT;

try {
    // Save Inno Setup script
    file_put_contents('installer.iss', $issContent);

    // Compile installer
    $command = '"C:\Program Files (x86)\Inno Setup 6\ISCC.exe" installer.iss';
    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
        throw new Exception("Failed to create installer. Error code: " . $returnCode);
    }

    // Clean up
    unlink('installer.iss');

    echo "Installer created successfully!\n";
    echo "Installer location: {$config['output_dir']}\{$config['app_name']}_Setup.exe\n";

} catch (Exception $e) {
    echo "Installer creation failed: " . $e->getMessage() . "\n";
    error_log("Installer creation failed: " . $e->getMessage());
}
?> 