<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    die(json_encode(['success' => false, 'message' => 'Invalid JSON data']));
}

function executeCommand($command) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Add error handling and logging
        $output = [];
        $returnVar = 0;
        
        exec($command . " 2>&1", $output, $returnVar);
        
        if ($returnVar !== 0) {
            error_log("Command execution failed: " . implode("\n", $output));
            return false;
        }
        return true;
    }
    return false;
}

function activateYouTubeWindow() {
    executeCommand('powershell -Command "$chrome = Get-Process chrome | Where-Object {$_.MainWindowTitle -like \'*YouTube*\'}; if ($chrome) { [Microsoft.VisualBasic.Interaction]::AppActivate($chrome.Id) }"');
}

$response = ['success' => false, 'message' => 'Unknown command'];

switch ($data['type']) {
    case 'mouse_move':
        if (isset($data['data']['x']) && isset($data['data']['y'])) {
            $x = intval($data['data']['x']);
            $y = intval($data['data']['y']);
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = "powershell -command \"Add-Type -AssemblyName System.Windows.Forms; ";
                $command .= "[System.Windows.Forms.Cursor]::Position = ";
                $command .= "New-Object System.Drawing.Point(";
                $command .= "(([System.Windows.Forms.Cursor]::Position.X) + $x), ";
                $command .= "(([System.Windows.Forms.Cursor]::Position.Y) + $y))\"";
                executeCommand($command);
                $response = ['success' => true];
            }
        }
        break;

    case 'mouse_click':
        if (isset($data['data']['button'])) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $button = $data['data']['button'];
                
                // Create a PowerShell script for mouse clicks
                $psScript = "Add-Type -MemberDefinition '[DllImport(\"user32.dll\")]public static extern void mouse_event(int flags, int dx, int dy, int cButtons, int info);' -Name U32 -Namespace W;";
                
                if ($button === 'left') {
                    // Left click (0x0002 = MOUSEEVENTF_LEFTDOWN, 0x0004 = MOUSEEVENTF_LEFTUP)
                    $psScript .= "[W.U32]::mouse_event(0x0002, 0, 0, 0, 0);";
                    $psScript .= "[W.U32]::mouse_event(0x0004, 0, 0, 0, 0);";
                } else {
                    // Right click (0x0008 = MOUSEEVENTF_RIGHTDOWN, 0x0010 = MOUSEEVENTF_RIGHTUP)
                    $psScript .= "[W.U32]::mouse_event(0x0008, 0, 0, 0, 0);";
                    $psScript .= "[W.U32]::mouse_event(0x0010, 0, 0, 0, 0);";
                }

                $command = 'powershell -Command "' . $psScript . '"';
                executeCommand($command);
                $response = ['success' => true];
            }
        }
        break;

    case 'system':
        if (isset($data['data']['action'])) {
            $action = $data['data']['action'];
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                switch ($action) {
                    case 'shutdown':
                        executeCommand('shutdown /s /t 0');
                        break;
                    case 'restart':
                        executeCommand('shutdown /r /t 0');
                        break;
                    case 'sleep':
                        executeCommand('rundll32.exe powrprof.dll,SetSuspendState 0,1,0');
                        break;
                    case 'lock':
                        executeCommand('rundll32.exe user32.dll,LockWorkStation');
                        break;
                }
                $response = ['success' => true];
            }
        }
        break;

    case 'youtube':
        if (isset($data['data']['action'])) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $action = $data['data']['action'];
                
                switch ($action) {
                    case 'open':
                        // Close any existing YouTube tabs first
                        executeCommand('powershell -Command "Get-Process chrome | Where-Object {$_.MainWindowTitle -like \'*YouTube*\'} | ForEach-Object { $_.CloseMainWindow() }"');
                        executeCommand('start chrome https://www.youtube.com');
                        break;
                        
                    case 'search':
                        $query = isset($data['data']['query']) ? urlencode($data['data']['query']) : '';
                        if (!empty($query)) {
                            // Close any existing YouTube tabs first
                            executeCommand('powershell -Command "Get-Process chrome | Where-Object {$_.MainWindowTitle -like \'*YouTube*\'} | ForEach-Object { $_.CloseMainWindow() }"');
                            executeCommand('start chrome https://www.youtube.com/results?search_query=' . $query);
                        }
                        break;
                        
                    case 'play':
                        $videoId = isset($data['data']['videoId']) ? $data['data']['videoId'] : '';
                        if (!empty($videoId)) {
                            // Close any existing YouTube tabs first
                            executeCommand('powershell -Command "Get-Process chrome | Where-Object {$_.MainWindowTitle -like \'*YouTube*\'} | ForEach-Object { $_.CloseMainWindow() }"');
                            // Add autoplay parameter to start playing immediately
                            executeCommand('start chrome "https://www.youtube.com/watch?v=' . $videoId . '&autoplay=1"');
                        }
                        break;
                    
                    case 'playPause':
                        activateYouTubeWindow();
                        executeCommand('powershell -Command "$wsh = New-Object -ComObject WScript.Shell; $wsh.SendKeys(\' \')"');
                        break;
                        
                    case 'next':
                        // Simulate Shift+N for next video
                        executeCommand('powershell -Command "$wsh = New-Object -ComObject WScript.Shell; $wsh.SendKeys(\'N\')"');
                        break;
                        
                    case 'prev':
                        // Simulate Shift+P for previous video
                        executeCommand('powershell -Command "$wsh = New-Object -ComObject WScript.Shell; $wsh.SendKeys(\'P\')"');
                        break;
                }
                $response = ['success' => true];
            }
        }
        break;
}

echo json_encode($response); 