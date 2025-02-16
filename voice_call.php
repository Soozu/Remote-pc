<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role']; // 'computer' or 'phone'

// Update security headers for local development
header("Permissions-Policy: microphone=*");
header("Feature-Policy: microphone *");
header("Access-Control-Allow-Origin: *");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Voice Call - Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --background-color: #f8f9fa;
            --text-color: #2d3436;
        }

        body {
            background-color: var(--background-color);
            min-height: 100vh;
        }

        .call-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .call-status {
            text-align: center;
            margin: 2rem 0;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .call-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-call {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-call:hover {
            transform: scale(1.1);
        }

        .btn-start-call {
            background: #2ecc71;
            color: white;
            border: none;
        }

        .btn-end-call {
            background: #e74c3c;
            color: white;
            border: none;
            display: none;
        }

        .btn-mute {
            background: #95a5a6;
            color: white;
            border: none;
        }

        .btn-mute.active {
            background: #34495e;
        }

        .audio-level {
            width: 100%;
            height: 20px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .audio-level-bar {
            width: 0%;
            height: 100%;
            background: var(--primary-color);
            transition: width 0.1s ease;
        }

        @media (max-width: 768px) {
            .call-container {
                margin: 1rem;
                padding: 1rem;
            }
        }
    </style>
    <!-- Update permissions meta tag -->
    <meta http-equiv="Permissions-Policy" content="microphone=*">
</head>
<body data-role="<?php echo htmlspecialchars($role); ?>">
    <!-- Add a permission status container -->
    <div class="permission-status alert alert-warning d-none" id="permissionAlert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span id="permissionMessage">Please allow microphone access</span>
    </div>

    <!-- Add this after the permission alert div -->
    <div class="permission-settings text-center mb-3 d-none" id="permissionSettings">
        <button class="btn btn-primary" onclick="openBrowserSettings()">
            <i class="fas fa-cog me-2"></i>Open Microphone Settings
        </button>
    </div>

    <div class="container">
        <div class="call-container">
            <h3 class="text-center mb-4">
                <i class="fas fa-phone me-2"></i>Voice Call
            </h3>

            <div class="call-status" id="callStatus">
                <?php if ($role === 'computer'): ?>
                    Ready to call phone
                <?php else: ?>
                    Ready to call computer
                <?php endif; ?>
            </div>

            <div class="audio-level">
                <div class="audio-level-bar" id="audioLevel"></div>
            </div>

            <audio id="remoteAudio" autoplay></audio>

            <div class="call-controls">
                <button class="btn btn-call btn-start-call" id="startCall">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="btn btn-call btn-end-call" id="endCall">
                    <i class="fas fa-phone-slash"></i>
                </button>
                <button class="btn btn-call btn-mute" id="toggleMute">
                    <i class="fas fa-microphone"></i>
                </button>
            </div>

            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
    // Add this before loading voice_call.js
    async function checkAndRequestPermissions() {
        try {
            const permissionStatus = await navigator.permissions.query({ name: 'microphone' });
            const permissionAlert = document.getElementById('permissionAlert');
            const permissionMessage = document.getElementById('permissionMessage');

            if (permissionStatus.state === 'denied') {
                permissionAlert.classList.remove('d-none');
                permissionMessage.textContent = 'Microphone access denied. Please enable it in your browser settings.';
            } else if (permissionStatus.state === 'prompt') {
                permissionAlert.classList.remove('d-none');
                permissionMessage.textContent = 'Please allow microphone access when prompted.';
            }

            permissionStatus.onchange = function() {
                if (this.state === 'granted') {
                    permissionAlert.classList.add('d-none');
                } else {
                    permissionAlert.classList.remove('d-none');
                    permissionMessage.textContent = 'Microphone access is required for voice calls.';
                }
            };
        } catch (error) {
            console.error('Permission check error:', error);
        }
    }

    // Call this function before initializing VoiceCall
    checkAndRequestPermissions();
    </script>
    <script src="js/voice_call.js"></script>
</body>
</html> 