<?php
// Application configuration
$config = [
    'app_name' => 'Remote Desktop Control',
    'main_window' => [
        'title' => 'Remote Desktop Control',
        'icon' => 'assets/icon.ico',
        'width' => 1024,
        'height' => 768,
        'min_width' => 800,
        'min_height' => 600,
    ],
    'php_server' => [
        'php_path' => 'C:/xampp/php/php.exe',
        'document_root' => __DIR__,
        'router' => 'index.php',
        'port' => 54321,
    ],
    'mysql_server' => [
        'path' => 'C:/xampp/mysql/bin/mysqld.exe',
        'data_dir' => 'C:/xampp/mysql/data',
    ]
];

// Create build directory
$buildDir = __DIR__ . '/build';
if (!file_exists($buildDir)) {
    mkdir($buildDir);
}

// Copy project files
function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while($file = readdir($dir)) {
        if ($file != '.' && $file != '..') {
            if (is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Create launcher script
$launcherScript = <<<EOT
@echo off
echo Starting Remote Desktop Control...

:: Start MySQL
start "" "{$config['mysql_server']['path']}" --datadir="{$config['mysql_server']['data_dir']}"

:: Start PHP Server
start "" "{$config['php_server']['php_path']}" -S localhost:{$config['php_server']['port']} -t "{$config['php_server']['document_root']}"

:: Wait for services
timeout /t 5

:: Open application
start http://localhost:{$config['php_server']['port']}/

:: Keep running
exit
EOT;

file_put_contents($buildDir . '/start.bat', $launcherScript);

// Create application manifest
$manifest = [
    'name' => $config['app_name'],
    'version' => '1.0.0',
    'main_window' => $config['main_window'],
    'dependencies' => [
        'php' => '^7.4',
        'mysql' => '^5.7'
    ]
];

file_put_contents($buildDir . '/app.json', json_encode($manifest, JSON_PRETTY_PRINT));

echo "Build completed! Application files are in the 'build' directory.\n";
?> 