<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once 'db_config.php';

// Function to get list of uploaded files
function getUploadedFiles() {
    global $pdo;
    $uploadDir = __DIR__ . '/uploads/';
    $files = [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM file_uploads ORDER BY upload_date DESC");
        while ($row = $stmt->fetch()) {
            $filePath = $uploadDir . $row['filename'];
            if (file_exists($filePath)) {
                $files[] = [
                    'name' => $row['filename'],
                    'size' => $row['file_size'],
                    'date' => strtotime($row['upload_date']),
                    'type' => pathinfo($row['filename'], PATHINFO_EXTENSION),
                    'device' => $row['device_type'],
                    'uploader' => $row['uploader']
                ];
            }
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
    
    return $files;
}

// Add error handling for database connection
if (!isset($pdo)) {
    die("Database connection failed. Please check your configuration.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Transfer - Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #2ecc71;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --border-radius: 15px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .upload-area {
            border: 2px dashed #ccc;
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            background: var(--background-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: var(--primary-color);
            background: #e8f0fe;
        }

        .upload-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .file-list {
            margin-top: 20px;
        }

        .file-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .file-icon {
            font-size: 24px;
            color: var(--primary-color);
        }

        .progress {
            height: 5px;
            margin-top: 5px;
        }

        .transfer-actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            padding: 5px 10px;
            border-radius: 5px;
        }

        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 350px;
        }

        .notification {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            border-left: 4px solid var(--success-color);
        }

        .notification.error {
            border-left: 4px solid #dc3545;
        }

        .notification-icon {
            font-size: 20px;
            color: var(--primary-color);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .notification-message {
            font-size: 0.9rem;
            color: #666;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .received-files {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .received-file-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .received-file-icon {
            font-size: 20px;
            color: var(--primary-color);
        }

        .received-file-info {
            flex: 1;
        }

        .received-file-name {
            font-weight: 500;
            margin-bottom: 2px;
        }

        .received-file-time {
            font-size: 0.8rem;
            color: #666;
        }

        .transferred-file-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .transferred-file-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .file-icon {
            font-size: 24px;
            color: var(--primary-color);
        }

        .file-details {
            font-size: 0.85rem;
            color: #6c757d;
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 5px;
        }

        .file-actions {
            display: flex;
            gap: 5px;
        }

        .nav-tabs .nav-link {
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            font-weight: 500;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .page-header {
                padding: 15px 0;
            }

            .file-details {
                flex-wrap: wrap;
                gap: 8px;
            }

            .transferred-file-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .file-actions {
                width: 100%;
                justify-content: flex-end;
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #eee;
            }

            .file-info {
                width: 100%;
            }

            .badge {
                font-size: 0.7rem;
            }
        }

        /* General Improvements */
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
        }

        .upload-area {
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 15px;
            font-weight: 500;
            color: #666;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        .transferred-file-item {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .transferred-file-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .file-name {
            font-weight: 500;
            margin-bottom: 5px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="notification-container" id="notificationContainer">
        <!-- Notifications will appear here -->
    </div>

    <div class="page-header">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <h1 class="h3 mb-0">File Transfer</h1>
                    <span class="badge bg-primary ms-3">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                </div>
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <!-- Upload Section -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        <h5 class="mb-0">Upload Files</h5>
                    </div>
                    <div class="card-body">
                        <div class="upload-area" id="dropZone">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <h4 class="mt-3">Drag & Drop Files Here</h4>
                            <p class="text-muted">or click to select files</p>
                            <input type="file" id="fileInput" multiple class="d-none">
                        </div>
                        <div class="file-list mt-3" id="fileList"></div>
                    </div>
                </div>
            </div>

            <!-- Files Section -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-exchange-alt me-2"></i>
                        <h5 class="mb-0">Transferred Files</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Mobile Filter Dropdown -->
                        <div class="d-md-none p-3">
                            <select class="form-select" id="mobileFilter">
                                <option value="all">All Files</option>
                                <option value="computer">From Computer</option>
                                <option value="phone">From Phone</option>
                            </select>
                        </div>

                        <!-- Desktop Tabs -->
                        <div class="d-none d-md-block">
                            <ul class="nav nav-tabs nav-fill" id="transferTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                                        <i class="fas fa-folder me-2"></i>All Files
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="computer-tab" data-bs-toggle="tab" data-bs-target="#computer" type="button">
                                        <i class="fas fa-desktop me-2"></i>From Computer
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="phone-tab" data-bs-toggle="tab" data-bs-target="#phone" type="button">
                                        <i class="fas fa-mobile-alt me-2"></i>From Phone
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content p-3" id="transferTabContent">
                            <div class="tab-pane fade show active" id="all" role="tabpanel">
                                <div class="transferred-files">
                                    <?php displayFiles(getUploadedFiles()); ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="computer" role="tabpanel">
                                <div class="transferred-files">
                                    <?php displayFiles(getUploadedFiles(), 'computer'); ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="phone" role="tabpanel">
                                <div class="transferred-files">
                                    <?php displayFiles(getUploadedFiles(), 'phone'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/transfer.js"></script>
    <script>
        function showNotification(title, message, type = 'success') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
            `;

            container.appendChild(notification);

            // Remove notification after 5 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // WebSocket connection for real-time notifications
        const ws = new WebSocket(`ws://${window.location.hostname}:8081`);
        
        ws.onopen = () => {
            console.log('Connected to notification server');
            ws.send(JSON.stringify({
                type: 'register',
                role: 'file_receiver'
            }));
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.type === 'file_received') {
                showNotification('New File Received', `${data.fileName} has been received`);
                addReceivedFile(data.fileName, data.fileSize);
            }
        };

        function addReceivedFile(fileName, fileSize) {
            const receivedFilesList = document.getElementById('receivedFilesList');
            const fileItem = document.createElement('div');
            fileItem.className = 'received-file-item';
            
            fileItem.innerHTML = `
                <i class="fas fa-file received-file-icon"></i>
                <div class="received-file-info">
                    <div class="received-file-name">${fileName}</div>
                    <div class="received-file-time">
                        Received ${new Date().toLocaleTimeString()}
                    </div>
                </div>
                <button class="btn btn-sm btn-primary" onclick="downloadFile('${fileName}')">
                    <i class="fas fa-download"></i>
                </button>
            `;

            receivedFilesList.insertBefore(fileItem, receivedFilesList.firstChild);
        }

        function downloadFile(fileName) {
            window.location.href = `download.php?file=${encodeURIComponent(fileName)}`;
        }

        function deleteFile(fileName) {
            if (confirm('Are you sure you want to delete this file?')) {
                fetch('delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ fileName: fileName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        showNotification('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error', 'Failed to delete file', 'error');
                });
            }
        }

        // Remove the old tab click handlers and add this instead
        const triggerTabList = document.querySelectorAll('#transferTabs button');
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                tabTrigger.show();
            });
        });

        document.getElementById('mobileFilter')?.addEventListener('change', function(e) {
            const value = e.target.value;
            const tab = document.querySelector(`#transferTabs button[data-bs-target="#${value}"]`);
            if (tab) {
                const tabTrigger = new bootstrap.Tab(tab);
                tabTrigger.show();
            }
        });
    </script>
</body>
</html>

<?php
// Add this function at the top of your PHP section
function displayFiles($files, $deviceFilter = null) {
    if (empty($files)) {
        echo '<div class="text-center text-muted py-4">No files transferred yet</div>';
        return;
    }

    foreach ($files as $file) {
        if ($deviceFilter && $file['device'] !== $deviceFilter) {
            continue;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $previewable = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'webm']);
        $icon = match($extension) {
            'pdf' => 'fa-file-pdf',
            'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image',
            'mp4', 'webm' => 'fa-video',
            'mp3', 'wav' => 'fa-music',
            default => 'fa-file'
        };
        ?>
        <div class="transferred-file-item">
            <div class="file-info">
                <i class="fas <?php echo $icon; ?> file-icon"></i>
                <div>
                    <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                    <div class="file-details">
                        <span class="file-size"><?php echo number_format($file['size'] / 1024, 2); ?> KB</span>
                        <span class="file-date"><?php echo date('M d, Y H:i', $file['date']); ?></span>
                        <span class="badge <?php echo $file['device'] === 'computer' ? 'bg-primary' : 'bg-success'; ?>">
                            <i class="fas <?php echo $file['device'] === 'computer' ? 'fa-desktop' : 'fa-mobile-alt'; ?>"></i>
                            <?php echo ucfirst($file['device']); ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($file['uploader']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="file-actions">
                <?php if ($previewable): ?>
                <a href="file_viewer.php?file=<?php echo urlencode($file['name']); ?>" 
                   class="btn btn-sm btn-info" title="Preview">
                    <i class="fas fa-eye"></i>
                </a>
                <?php endif; ?>
                <button class="btn btn-sm btn-primary download-btn" 
                        onclick="downloadFile('<?php echo htmlspecialchars($file['name']); ?>')">
                    <i class="fas fa-download"></i>
                </button>
                <?php if ($file['uploader'] === $_SESSION['username']): ?>
                <button class="btn btn-sm btn-danger delete-btn" 
                        onclick="deleteFile('<?php echo htmlspecialchars($file['name']); ?>')">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?> 