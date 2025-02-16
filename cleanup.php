<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once 'db_config.php';

function getDirectorySize($path) {
    $size = 0;
    if (is_dir($path)) {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_file($filePath)) {
                    $size += filesize($filePath);
                }
            }
        }
    }
    return $size;
}

function cleanDirectory($path, $daysOld = 7) {
    global $pdo;
    $cleaned = 0;
    $failed = 0;
    $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

    if (!is_dir($path)) {
        error_log("Directory does not exist: " . $path);
        return ['cleaned' => 0, 'failed' => 0];
    }

    try {
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_file($filePath)) {
                    try {
                        // Delete from database first
                        $stmt = $pdo->prepare("DELETE FROM file_uploads WHERE filename = ?");
                        $stmt->execute([$file]);
                        
                        // Then delete the file
                        if (unlink($filePath)) {
                            $cleaned++;
                            error_log("Successfully deleted: " . $filePath);
                        } else {
                            $failed++;
                            error_log("Failed to delete file: " . $filePath);
                        }
                    } catch (Exception $e) {
                        $failed++;
                        error_log("Error processing file {$file}: " . $e->getMessage());
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Directory scan error: " . $e->getMessage());
        return ['cleaned' => $cleaned, 'failed' => $failed, 'error' => $e->getMessage()];
    }

    return ['cleaned' => $cleaned, 'failed' => $failed];
}

try {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];
    $uploadsDir = 'C:/xampp/htdocs/project/uploads'; // Specific path to uploads folder

    switch ($action) {
        case 'get_sizes':
            $response = [
                'success' => true,
                'data' => [
                    'uploads' => getDirectorySize($uploadsDir),
                    'temp' => 0 // We're not dealing with temp files
                ]
            ];
            break;

        case 'cleanup':
            $options = json_decode($_POST['options'] ?? '{}', true);
            $stats = [
                'uploads' => ['cleaned' => 0, 'failed' => 0],
                'temp' => ['cleaned' => 0, 'failed' => 0]
            ];

            if (!empty($options['uploads'])) {
                if (!is_dir($uploadsDir)) {
                    error_log("Uploads directory not found: " . $uploadsDir);
                } else {
                    error_log("Starting cleanup of: " . $uploadsDir);
                    $stats['uploads'] = cleanDirectory($uploadsDir, intval($options['daysOld'] ?? 7));
                }
            }

            $response = [
                'success' => true,
                'message' => 'Cleanup completed',
                'stats' => $stats
            ];
            break;
    }

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Cleanup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during cleanup: ' . $e->getMessage()
    ]);
} 