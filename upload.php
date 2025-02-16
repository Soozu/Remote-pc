<?php
// Set proper content type for JSON response
header('Content-Type: application/json');

// Disable error display but log them
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session
session_start();

// Basic authentication check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Function to send JSON response
function sendJsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Invalid request method');
    }

    // Check if file was uploaded
    if (empty($_FILES['file'])) {
        sendJsonResponse(false, 'No file uploaded');
    }

    $file = $_FILES['file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = match($file['error']) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
        sendJsonResponse(false, $message);
    }

    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            sendJsonResponse(false, 'Failed to create upload directory');
        }
    }

    // Generate safe filename
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        sendJsonResponse(false, 'Failed to save file');
    }

    // Set file permissions
    chmod($targetPath, 0644);

    // Store file information in database
    require_once 'db_config.php';
    $stmt = $pdo->prepare("INSERT INTO file_uploads (filename, uploader, device_type, file_size) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $fileName,
        $_SESSION['username'],
        $_SESSION['role'],
        $file['size']
    ]);

    // Send success response
    sendJsonResponse(true, 'File uploaded successfully', [
        'fileName' => $fileName,
        'path' => $targetPath,
        'size' => $file['size'],
        'uploader' => $_SESSION['username'],
        'device' => $_SESSION['role']
    ]);

} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    sendJsonResponse(false, 'Upload failed: ' . $e->getMessage());
} 