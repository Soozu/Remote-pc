<?php
session_start();
header('Content-Type: application/json');
require_once 'db_config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$fileName = $data['fileName'] ?? '';

if (empty($fileName)) {
    echo json_encode(['success' => false, 'message' => 'No file specified']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Delete database entry
    $stmt = $pdo->prepare("DELETE FROM file_uploads WHERE filename = ?");
    $stmt->execute([$fileName]);

    // Delete file
    $filePath = __DIR__ . '/uploads/' . basename($fileName);
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            throw new Exception('Failed to delete file');
        }
    }

    // Commit transaction
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'File deleted successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 