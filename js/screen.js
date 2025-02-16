document.addEventListener('DOMContentLoaded', function() {
    let mediaStream = null;
    let ws = null;
    const video = document.getElementById('screenVideo');
    const startButton = document.getElementById('startShare');
    const stopButton = document.getElementById('stopShare');
    const statusIndicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    const shareAudio = document.getElementById('shareAudio');
    const frameRate = document.getElementById('frameRate');
    
    let isSharing = false;
    let serverStartAttempts = 0;
    const MAX_SERVER_START_ATTEMPTS = 3;

    // Check browser support
    async function checkBrowserSupport() {
        // Check if running in Chrome
        const isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
        if (!isChrome) {
            throw new Error('Please use Google Chrome browser for screen sharing.');
        }

        // Check if screen capture is available
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getDisplayMedia !== 'function') {
            // Show instructions for enabling screen capture
            const instructions = `Please follow these steps to enable screen sharing:
1. Open a new tab and go to chrome://flags
2. Search for "Insecure origins treated as secure"
3. Enable the flag
4. Add this URL: http://${window.location.hostname}:8080
5. Click "Relaunch" at the bottom
6. Try again after Chrome restarts`;
            
            throw new Error(instructions);
        }

        // Test if we can actually get display media
        try {
            const testStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
            testStream.getTracks().forEach(track => track.stop());
            return true;
        } catch (err) {
            if (err.name === 'NotAllowedError') {
                return true; // User denied permission, but the API works
            }
            throw new Error('Screen sharing is not properly enabled. Please make sure you\'ve enabled the Chrome flag for insecure origins.');
        }
    }

    // Update status display
    function updateStatus(sharing) {
        isSharing = sharing;
        statusIndicator.className = `status-indicator ${sharing ? 'status-active' : 'status-inactive'}`;
        statusText.textContent = sharing ? 'Sharing Active' : 'Not sharing';
        startButton.disabled = sharing;
        stopButton.disabled = !sharing;
    }

    // Start WebSocket server
    async function ensureServerRunning() {
        try {
            const response = await fetch('server_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check'
            });
            const data = await response.json();
            
            if (!data.running) {
                console.log('Starting WebSocket server...');
                const startResponse = await fetch('server_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=start'
                });
                const startData = await startResponse.json();
                
                if (!startData.success) {
                    throw new Error('Failed to start WebSocket server');
                }
            }
            return true;
        } catch (error) {
            console.error('Server start error:', error);
            return false;
        }
    }

    // Connect to WebSocket server
    async function connectWebSocket() {
        if (serverStartAttempts >= MAX_SERVER_START_ATTEMPTS) {
            throw new Error('Failed to start server after multiple attempts');
        }

        const serverRunning = await ensureServerRunning();
        if (!serverRunning) {
            serverStartAttempts++;
            await new Promise(resolve => setTimeout(resolve, 1000));
            return connectWebSocket();
        }

        ws = new WebSocket(`ws://${window.location.hostname}:8081`);
        
        ws.onopen = () => {
            console.log('WebSocket Connected');
            serverStartAttempts = 0;
            ws.send(JSON.stringify({
                type: 'broadcaster'
            }));
            updateStatus(true);
        };

        ws.onclose = () => {
            console.log('WebSocket Disconnected');
            if (isSharing) {
                setTimeout(() => connectWebSocket(), 1000);
            }
        };

        ws.onerror = (error) => {
            console.error('WebSocket Error:', error);
            if (isSharing) {
                setTimeout(() => connectWebSocket(), 1000);
            }
        };

        return ws;
    }

    // Start screen sharing
    async function startSharing() {
        try {
            await checkBrowserSupport();

            const displayMediaOptions = {
                video: {
                    cursor: "always",
                    frameRate: parseInt(frameRate.value),
                    width: { ideal: 854 },  // 480p width
                    height: { ideal: 480 }, // 480p height
                },
                audio: shareAudio.checked
            };

            mediaStream = await navigator.mediaDevices.getDisplayMedia(displayMediaOptions);
            video.srcObject = mediaStream;
            
            // Connect to WebSocket server
            ws = await connectWebSocket();
            
            // Set up canvas for frame capture
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            let lastFrameTime = 0;
            const frameInterval = 1000 / parseInt(frameRate.value);

            // Track if sharing is active
            isSharing = true;

            // Frame capture and send loop
            async function captureAndSendFrame(timestamp) {
                if (!isSharing) return;

                try {
                    // Throttle frame capture based on frameRate
                    if (timestamp - lastFrameTime >= frameInterval) {
                        // Set canvas size to a smaller resolution
                        canvas.width = 854;  // 480p width
                        canvas.height = 480; // 480p height

                        // Draw current video frame to canvas with smoothing
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                        // Convert canvas to blob and send with more compression
                        if (ws && ws.readyState === WebSocket.OPEN) {
                            try {
                                // Use more aggressive compression (0.5 quality)
                                const dataUrl = canvas.toDataURL('image/jpeg', 0.5);
                                
                                // Only send if the data URL is valid
                                if (dataUrl && dataUrl.startsWith('data:image/jpeg')) {
                                    ws.send(JSON.stringify({
                                        type: 'stream',
                                        data: dataUrl
                                    }));
                                    console.log('Frame sent:', Math.round(dataUrl.length / 1024), 'KB');
                                }
                            } catch (err) {
                                console.error('Error sending frame:', err);
                            }
                        }

                        lastFrameTime = timestamp;
                    }
                } catch (error) {
                    console.error('Frame capture error:', error);
                }

                // Request next frame
                requestAnimationFrame(captureAndSendFrame);
            }

            // Start the capture loop
            requestAnimationFrame(captureAndSendFrame);

            // Handle stream end
            mediaStream.getVideoTracks()[0].onended = () => {
                stopSharing();
            };

        } catch (err) {
            console.error('Error starting screen share:', err);
            updateStatus(false);
        }
    }

    // Stop screen sharing
    function stopSharing() {
        updateStatus(false);
        serverStartAttempts = 0;
        
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
            video.srcObject = null;
            mediaStream = null;
        }
        
        if (ws) {
            ws.close();
            ws = null;
        }
    }

    startButton.addEventListener('click', startSharing);
    stopButton.addEventListener('click', stopSharing);

    // Handle page unload
    window.addEventListener('beforeunload', stopSharing);

    // Initial status
    updateStatus(false);

    // Check browser support on load and show instructions if needed
    checkBrowserSupport().catch(err => {
        alert(err.message);
    });
}); 