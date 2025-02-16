<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has computer role
if ($_SESSION['role'] !== 'computer') {
    header("Location: viewer.php");
    exit();
}

// Check if it's a mobile device
function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

// Redirect mobile devices to viewer page
if (isMobile() && !isset($_GET['force_share'])) {
    header("Location: viewer.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Sharing - Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #screenVideo {
            width: 100%;
            max-height: 80vh;
            background: #000;
            border-radius: 10px;
        }
        .control-panel {
            margin: 20px 0;
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-active {
            background: #28a745;
        }
        .status-inactive {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Screen Sharing</h1>
            <a href="index.php" class="btn btn-outline-primary">Back to Control Panel</a>
        </div>

        <div class="row">
            <div class="col-md-9">
                <video id="screenVideo" autoplay playsinline></video>
            </div>
            <div class="col-md-3">
                <div class="control-panel">
                    <h5>Controls</h5>
                    <div class="mb-3">
                        <span class="status-indicator" id="statusIndicator"></span>
                        <span id="statusText">Not sharing</span>
                    </div>
                    <button id="startShare" class="btn btn-primary w-100 mb-2">Start Sharing</button>
                    <button id="stopShare" class="btn btn-danger w-100 mb-2" disabled>Stop Sharing</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/webrtc.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('screenVideo');
            const startButton = document.getElementById('startShare');
            const stopButton = document.getElementById('stopShare');
            const statusIndicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            
            let streamer = null;

            function updateStatus(sharing) {
                statusIndicator.className = `status-indicator ${sharing ? 'status-active' : 'status-inactive'}`;
                statusText.textContent = sharing ? 'Sharing Active' : 'Not sharing';
                startButton.disabled = sharing;
                stopButton.disabled = !sharing;
            }

            startButton.addEventListener('click', async () => {
                try {
                    streamer = new WebRTCStreamer();
                    const success = await streamer.startSharing();
                    if (success) {
                        updateStatus(true);
                    }
                } catch (err) {
                    console.error('Failed to start sharing:', err);
                    alert('Failed to start screen sharing. Please make sure you\'re using a supported browser.');
                }
            });

            stopButton.addEventListener('click', () => {
                if (streamer) {
                    streamer.stopSharing();
                    streamer = null;
                }
                updateStatus(false);
            });

            // Handle page unload
            window.addEventListener('beforeunload', () => {
                if (streamer) {
                    streamer.stopSharing();
                }
            });

            // Check if running on Chrome mobile
            const isChromeOnMobile = /Android|webOS|iPhone|iPad|iPod/i.test(navigator.userAgent) 
                && /Chrome/i.test(navigator.userAgent);

            if (isChromeOnMobile) {
                // Add mobile-specific constraints
                const mobileConstraints = {
                    video: {
                        cursor: "always",
                        frameRate: { ideal: 15, max: 30 },
                        width: { ideal: 640, max: 1280 },
                        height: { ideal: 480, max: 720 }
                    },
                    audio: false
                };

                // Override getDisplayMedia for mobile
                const originalGetDisplayMedia = navigator.mediaDevices.getDisplayMedia;
                navigator.mediaDevices.getDisplayMedia = async function(constraints) {
                    return originalGetDisplayMedia.call(this, mobileConstraints);
                };
            }
        });
    </script>
</body>
</html> 