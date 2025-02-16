<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Cleanup - Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --background-color: #f8f9fa;
            --text-color: #333;
            --border-radius: 10px;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
        }

        .page-header {
            background: var(--primary-color);
            padding: 1rem;
            color: white;
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
            margin: 0;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 0.75rem;
            }
            .page-title {
                font-size: 1.25rem;
            }
            .user-badge {
                font-size: 0.8rem;
            }
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem;
        }

        .card-body {
            padding: 1rem;
        }

        .storage-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .storage-item {
            margin-bottom: 1rem;
        }

        .storage-label {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .storage-icon {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .progress {
            height: 0.5rem;
            background-color: #e9ecef;
            border-radius: 1rem;
            margin-bottom: 0.25rem;
        }

        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 1rem;
        }

        .cleanup-options {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
        }

        .option-item {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border: 1px solid #eee;
            border-radius: var(--border-radius);
        }

        .option-label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        .option-icon {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .btn-cleanup {
            width: 100%;
            padding: 0.75rem;
            margin-top: 1rem;
            background: var(--primary-color);
            border: none;
            color: white;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .btn-cleanup:hover {
            background: var(--secondary-color);
        }

        .cleanup-progress {
            display: none;
            padding: 1rem;
            background: #fff;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .cleanup-progress.active {
            display: block;
        }

        .back-button {
            padding: 0.5rem 1rem;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .back-button:hover {
            color: white;
            opacity: 0.9;
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 0.75rem;
            }
            
            .option-item {
                padding: 0.5rem;
            }
            
            .storage-label, .option-label {
                font-size: 0.9rem;
            }
            
            .btn-cleanup {
                padding: 0.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-broom me-2"></i>System Cleanup
                </h1>
                <div class="d-flex align-items-center">
                    <span class="user-badge me-3">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="index.php" class="back-button">
                        <i class="fas fa-home me-1"></i>
                        <span class="d-none d-sm-inline">Home</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <!-- Storage Usage Section -->
                <div class="storage-section">
                    <h6 class="mb-3">Storage Usage</h6>
                    <div class="storage-item">
                        <div class="storage-label">
                            <i class="fas fa-folder storage-icon"></i>
                            Uploads Folder
                        </div>
                        <div class="progress">
                            <div class="progress-bar" id="uploadsProgress" role="progressbar"></div>
                        </div>
                        <small class="text-muted" id="uploadsSize">Calculating...</small>
                    </div>
                    <div class="storage-item mb-0">
                        <div class="storage-label">
                            <i class="fas fa-clock storage-icon"></i>
                            Temporary Files
                        </div>
                        <div class="progress">
                            <div class="progress-bar" id="tempProgress" role="progressbar"></div>
                        </div>
                        <small class="text-muted" id="tempSize">Calculating...</small>
                    </div>
                </div>

                <!-- Cleanup Options Section -->
                <div class="cleanup-options">
                    <h6 class="mb-3">Cleanup Options</h6>
                    <div class="option-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cleanUploads">
                            <label class="option-label" for="cleanUploads">
                                <i class="fas fa-folder-minus option-icon"></i>
                                Clean Uploads Folder
                            </label>
                        </div>
                    </div>
                    <div class="option-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cleanTemp">
                            <label class="option-label" for="cleanTemp">
                                <i class="fas fa-eraser option-icon"></i>
                                Clean Temporary Files
                            </label>
                        </div>
                    </div>
                    <div class="option-item">
                        <label class="form-label mb-2">
                            <i class="fas fa-calendar-alt option-icon"></i>
                            Delete files older than:
                        </label>
                        <select class="form-select" id="cleanupDays">
                            <option value="1">1 day</option>
                            <option value="7" selected>7 days</option>
                            <option value="30">30 days</option>
                            <option value="90">90 days</option>
                        </select>
                    </div>
                    <button class="btn-cleanup" id="startCleanup">
                        <i class="fas fa-play me-2"></i>Start Cleanup
                    </button>
                </div>

                <!-- Progress Section -->
                <div class="cleanup-progress mt-3" id="cleanupProgress">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                    </div>
                    <div id="cleanupStatus">Cleaning in progress...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cleanup.js"></script>
</body>
</html> 