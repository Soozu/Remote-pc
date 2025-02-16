<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get username from session
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-color: #2b2d42;
            --border-radius: 15px;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .nav-buttons .btn {
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .nav-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            background: var(--card-background);
            margin-bottom: 25px;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: var(--card-background);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }

        .card-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .touchpad {
            background: linear-gradient(145deg, #ffffff, #f5f5f5);
            border: 2px solid rgba(0,0,0,0.05);
            border-radius: var(--border-radius);
            height: 200px;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .touchpad::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 2px dashed rgba(0,0,0,0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .mouse-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .mouse-buttons button {
            flex: 1;
            padding: 12px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .control-btn {
            width: 100%;
            padding: 15px;
            margin: 8px 0;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .control-btn i {
            font-size: 1.2rem;
        }

        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .page-header {
                padding: 15px 0;
                margin-bottom: 20px;
            }

            .nav-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-buttons .btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }

            .touchpad {
                height: 150px;
            }

            .card-header h5 {
                font-size: 1.1rem;
            }

            .control-btn {
                padding: 12px;
                font-size: 0.9rem;
            }
        }

        .user-info .badge {
            padding: 8px 15px;
            font-size: 0.9rem;
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Feature cards styles */
        .features-section {
            margin-bottom: 40px;
        }

        .feature-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .feature-content {
            padding: 25px;
            text-align: center;
            color: var(--text-color);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }

        .feature-content h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .feature-content p {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        /* Mobile styles update */
        @media (max-width: 768px) {
            .page-header {
                padding: 15px 0;
            }

            .user-info {
                display: none;
            }

            .feature-card {
                margin-bottom: 15px;
            }

            .feature-content {
                padding: 20px;
            }

            .feature-icon {
                font-size: 2rem;
            }

            .feature-content h3 {
                font-size: 1.1rem;
            }

            .feature-content p {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h1 class="mb-0">Remote Desktop Control</h1>
                    <div class="user-info ms-4">
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($username); ?>
                        </span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container mb-4">
        <div class="features-section">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="feature-card">
                        <a href="viewer.php" class="text-decoration-none">
                            <div class="feature-content">
                                <i class="fas fa-desktop feature-icon"></i>
                                <h3>View Screen</h3>
                                <p>View remote desktop screen in real-time</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <a href="screen.php" class="text-decoration-none">
                            <div class="feature-content">
                                <i class="fas fa-share-square feature-icon"></i>
                                <h3>Share Screen</h3>
                                <p>Share your screen with other devices</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <a href="youtube.php" class="text-decoration-none">
                            <div class="feature-content">
                                <i class="fab fa-youtube feature-icon"></i>
                                <h3>YouTube</h3>
                                <p>Control YouTube playback remotely</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <a href="transfer.php" class="text-decoration-none">
                            <div class="feature-content">
                                <i class="fas fa-file-upload feature-icon"></i>
                                <h3>File Transfer</h3>
                                <p>Upload and transfer files between devices</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <a href="cleanup_view.php" class="text-decoration-none">
                            <div class="feature-content">
                                <i class="fas fa-broom feature-icon"></i>
                                <h3>System Cleanup</h3>
                                <p>Clean temporary files and manage storage</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <a href="voice_call.php" class="text-decoration-none">
                            <div class="feature-content">
                                <i class="fas fa-phone feature-icon"></i>
                                <h3>Voice Call</h3>
                                <p>Make voice calls between devices</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-mouse me-2"></i>Mouse Control</h5>
                    </div>
                    <div class="card-body">
                        <div id="touchpad" class="touchpad"></div>
                        <div class="mouse-buttons">
                            <button class="btn btn-primary" id="leftClick">
                                <i class="fas fa-mouse-pointer me-2"></i>Left Click
                            </button>
                            <button class="btn btn-primary" id="rightClick">
                                <i class="fas fa-hand-pointer me-2"></i>Right Click
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs me-2"></i>System Controls</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-danger control-btn" id="shutdown">
                            <i class="fas fa-power-off"></i>Shutdown
                        </button>
                        <button class="btn btn-warning control-btn" id="restart">
                            <i class="fas fa-redo"></i>Restart
                        </button>
                        <button class="btn btn-info control-btn" id="sleep">
                            <i class="fas fa-moon"></i>Sleep
                        </button>
                        <button class="btn btn-success control-btn" id="lock">
                            <i class="fas fa-lock"></i>Lock Screen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/control.js"></script>
</body>
</html> 