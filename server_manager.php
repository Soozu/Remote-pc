<?php
function isWebSocketServerRunning() {
    exec("netstat -an | findstr :8080", $output);
    return !empty($output);
}

function startWebSocketServer() {
    $projectPath = __DIR__;
    $cmd = "start /min cmd /c \"php {$projectPath}/stream_server.php\"";
    pclose(popen($cmd, 'r'));
}

function stopWebSocketServer() {
    exec("taskkill /F /IM php.exe /FI \"WINDOWTITLE eq stream_server.php\"");
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'status':
            echo json_encode(['running' => isWebSocketServerRunning()]);
            break;
        case 'start':
            if (!isWebSocketServerRunning()) {
                startWebSocketServer();
                echo json_encode(['success' => true, 'message' => 'Server started']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Server already running']);
            }
            break;
        case 'stop':
            stopWebSocketServer();
            echo json_encode(['success' => true, 'message' => 'Server stopped']);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
?> 