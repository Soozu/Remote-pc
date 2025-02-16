<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has phone role or is a computer user viewing their own stream
if ($_SESSION['role'] !== 'phone' && $_SESSION['role'] !== 'computer') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Viewer - Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
        }
        #remoteVideo {
            width: 100%;
            height: auto;
            max-height: 90vh;
            background: #000;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            object-fit: contain;
            -webkit-transform: translateZ(0);
            -webkit-backface-visibility: hidden;
            -webkit-perspective: 1000;
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000;
        }
        .status-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
        }
        .fullscreen-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            opacity: 0.7;
            transition: opacity 0.3s;
            padding: 10px;
            border-radius: 50%;
        }
        .fullscreen-btn:hover {
            opacity: 1;
        }
        .video-container {
            position: relative;
            margin-top: 20px;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
        }
        .connection-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            z-index: 1000;
            display: none;
        }
        .connection-overlay.show {
            display: block;
        }
        .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1001;
        }
        .play-overlay i {
            font-size: 64px;
            color: white;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        .play-overlay:hover i {
            opacity: 1;
        }
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            #remoteVideo {
                max-height: 80vh;
            }
            
            .play-overlay i {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="text-light mb-0">Remote Screen</h4>
            <div>
                <a href="index.php" class="btn btn-outline-light btn-sm">Control Panel</a>
            </div>
        </div>
        
        <div class="video-container">
            <video id="remoteVideo" autoplay playsinline muted style="width: 100%; height: auto; max-height: 90vh; background: #000;"></video>
            <div id="connectionOverlay" class="connection-overlay">
                <div class="spinner-border text-light mb-3" role="status"></div>
                <h5 class="text-light">Waiting for connection...</h5>
            </div>
            <button id="fullscreenBtn" class="btn btn-dark fullscreen-btn">
                <i class="fas fa-expand"></i>
            </button>
        </div>
        
        <div id="statusBadge" class="status-badge badge bg-secondary">
            <i class="fas fa-circle-notch fa-spin me-2"></i>
            Connecting...
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('remoteVideo');
            const statusBadge = document.getElementById('statusBadge');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const connectionOverlay = document.getElementById('connectionOverlay');
            
            connectionOverlay.classList.add('show');

            // Connect to WebSocket
            function connectWebSocket() {
                const ws = new WebSocket(`ws://${window.location.hostname}:8081`);
                let reconnectAttempts = 0;
                const maxReconnectAttempts = 5;

                ws.onopen = () => {
                    console.log('WebSocket connected');
                    reconnectAttempts = 0;
                    statusBadge.innerHTML = '<i class="fas fa-plug me-2"></i>Connected';
                    statusBadge.className = 'status-badge badge bg-info';
                    
                    // Send viewer type
                    ws.send(JSON.stringify({
                        type: 'viewer'
                    }));
                };

                ws.onclose = () => {
                    statusBadge.innerHTML = '<i class="fas fa-times-circle me-2"></i>Disconnected';
                    statusBadge.className = 'status-badge badge bg-danger';
                    connectionOverlay.classList.add('show');
                    
                    // Try to reconnect with exponential backoff
                    if (reconnectAttempts < maxReconnectAttempts) {
                        const timeout = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000);
                        reconnectAttempts++;
                        console.log(`Attempting to reconnect in ${timeout/1000} seconds...`);
                        setTimeout(connectWebSocket, timeout);
                    } else {
                        console.log('Max reconnection attempts reached');
                        statusBadge.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Connection Failed';
                    }
                };

                ws.onmessage = async (event) => {
                    try {
                        const message = JSON.parse(event.data);
                        console.log('Received message type:', message.type);
                        
                        switch (message.type) {
                            case 'broadcaster_connected':
                                console.log('Broadcaster is available');
                                statusBadge.innerHTML = '<i class="fas fa-link me-2"></i>Broadcaster Connected';
                                statusBadge.className = 'status-badge badge bg-info';
                                break;
                                
                            case 'stream':
                                try {
                                    if (!message.data || !message.data.startsWith('data:image/jpeg')) {
                                        console.error('Invalid stream data received');
                                        return;
                                    }

                                    // Create blob URL directly from the base64 data
                                    const response = await fetch(message.data);
                                    const blob = await response.blob();
                                    
                                    if (blob.size === 0) {
                                        console.error('Received empty blob');
                                        return;
                                    }

                                    // Clean up old URL if it exists
                                    if (video.src) {
                                        URL.revokeObjectURL(video.src);
                                    }
                                    
                                    const videoUrl = URL.createObjectURL(blob);
                                    video.src = videoUrl;
                                    
                                    // Update UI
                                    connectionOverlay.classList.remove('show');
                                    statusBadge.innerHTML = '<i class="fas fa-video me-2"></i>Streaming';
                                    statusBadge.className = 'status-badge badge bg-success';

                                    // Ensure video is playing
                                    if (video.paused) {
                                        try {
                                            await video.play();
                                            console.log('Playback started');
                                        } catch (playError) {
                                            console.error('Play error:', playError);
                                            if (playError.name === 'NotAllowedError') {
                                                video.muted = true;
                                                await video.play();
                                            }
                                        }
                                    }
                                } catch (streamError) {
                                    console.error('Stream processing error:', streamError);
                                }
                                break;
                        }
                    } catch (e) {
                        console.error('Error processing message:', e);
                    }
                };

                ws.onerror = (error) => {
                    console.error('WebSocket error:', error);
                    statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Connection Error';
                    statusBadge.className = 'status-badge badge bg-warning';
                };

                return ws;
            }

            // Initialize
            let ws = connectWebSocket();

            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                if (ws) {
                    ws.close();
                }
                if (video.src) {
                    URL.revokeObjectURL(video.src);
                }
            });

            // Fullscreen handling
            fullscreenBtn.addEventListener('click', () => {
                if (!document.fullscreenElement) {
                    video.requestFullscreen().catch(err => {
                        console.log('Error attempting to enable fullscreen:', err);
                    });
                } else {
                    document.exitFullscreen();
                }
            });

            // Update fullscreen button icon
            document.addEventListener('fullscreenchange', () => {
                fullscreenBtn.innerHTML = document.fullscreenElement ? 
                    '<i class="fas fa-compress"></i>' : 
                    '<i class="fas fa-expand"></i>';
            });

            // Handle video errors
            video.addEventListener('error', (e) => {
                console.error('Video error:', e);
                video.src = ''; // Clear the source
            });
        });

        class WebRTCViewer {
            constructor(videoElement) {
                this.video = videoElement;
                this.peerConnection = null;
                this.ws = null;
                this.isConnected = false;
                this.reconnectAttempts = 0;
                this.maxReconnectAttempts = 5;
                
                // Check if running on mobile Chrome
                this.isMobileChrome = /Android|webOS|iPhone|iPad|iPod/i.test(navigator.userAgent) 
                    && /Chrome/i.test(navigator.userAgent);
            }

            async connect() {
                try {
                    this.ws = new WebSocket(`ws://${window.location.hostname}:8081`);
                    
                    this.ws.onopen = () => {
                        console.log('Connected to signaling server');
                        this.isConnected = true;
                        this.reconnectAttempts = 0;
                        this.ws.send(JSON.stringify({ type: 'viewer' }));
                    };

                    this.ws.onclose = () => {
                        console.log('Disconnected from signaling server');
                        this.isConnected = false;
                        this.handleDisconnect();
                    };

                    this.ws.onerror = (error) => {
                        console.error('WebSocket Error:', error);
                    };

                    this.ws.onmessage = async (event) => {
                        try {
                            const message = JSON.parse(event.data);
                            console.log('Received message:', message.type);
                            
                            switch (message.type) {
                                case 'offer':
                                    await this.handleOffer(message.offer);
                                    break;
                                case 'ice_candidate':
                                    await this.handleIceCandidate(message.candidate);
                                    break;
                            }
                        } catch (err) {
                            console.error('Error handling message:', err);
                        }
                    };
                } catch (err) {
                    console.error('Connection error:', err);
                    this.handleDisconnect();
                }
            }

            handleDisconnect() {
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    const timeout = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 10000);
                    this.reconnectAttempts++;
                    console.log(`Attempting to reconnect in ${timeout/1000} seconds...`);
                    setTimeout(() => this.connect(), timeout);
                }
            }

            async handleOffer(offer) {
                try {
                    console.log('Handling offer');
                    
                    // Close existing connection if any
                    if (this.peerConnection) {
                        this.peerConnection.close();
                    }

                    // Configure RTCPeerConnection for mobile
                    const configuration = {
                        iceServers: [
                            { urls: 'stun:stun.l.google.com:19302' },
                            { urls: 'stun:stun1.l.google.com:19302' },
                            { urls: 'stun:stun2.l.google.com:19302' },
                            { urls: 'stun:stun3.l.google.com:19302' },
                            { urls: 'stun:stun4.l.google.com:19302' }
                        ],
                        iceCandidatePoolSize: 10,
                        bundlePolicy: 'max-bundle',
                        rtcpMuxPolicy: 'require',
                        sdpSemantics: 'unified-plan'
                    };

                    if (this.isMobileChrome) {
                        // Add additional configuration for mobile Chrome
                        configuration.iceTransportPolicy = 'all';
                        configuration.iceServers.push({
                            urls: 'turn:turn.example.com:3478',
                            username: 'webrtc',
                            credential: 'turnserver'
                        });
                    }

                    this.peerConnection = new RTCPeerConnection(configuration);

                    this.peerConnection.ontrack = async (event) => {
                        console.log('Received track:', event.track.kind);
                        try {
                            const stream = event.streams[0];
                            if (this.video.srcObject !== stream) {
                                console.log('Setting video source');
                                
                                // Special handling for mobile Chrome
                                if (this.isMobileChrome) {
                                    this.video.srcObject = null;
                                    await new Promise(resolve => setTimeout(resolve, 100));
                                }
                                
                                this.video.srcObject = stream;
                                this.video.muted = true;
                                
                                try {
                                    await this.video.play();
                                    document.getElementById('connectionOverlay').classList.remove('show');
                                    console.log('Playback started');
                                } catch (playError) {
                                    console.error('Play error:', playError);
                                    if (playError.name === 'NotAllowedError') {
                                        this.showPlayButton();
                                    }
                                }
                            }
                        } catch (err) {
                            console.error('Error handling track:', err);
                        }
                    };

                    // Add connection state logging
                    this.peerConnection.oniceconnectionstatechange = () => {
                        const state = this.peerConnection.iceConnectionState;
                        console.log('ICE connection state:', state);
                        
                        if (state === 'checking') {
                            console.log('Establishing connection...');
                        } else if (state === 'connected' || state === 'completed') {
                            document.getElementById('connectionOverlay').classList.remove('show');
                        } else if (state === 'failed' || state === 'disconnected') {
                            document.getElementById('connectionOverlay').classList.add('show');
                            // Try reconnecting on failure
                            this.reconnect();
                        }
                    };

                    // Set up data channel for more reliable connection
                    const dataChannel = this.peerConnection.createDataChannel('keepalive', {
                        ordered: true
                    });
                    dataChannel.onopen = () => console.log('Data channel open');
                    dataChannel.onclose = () => console.log('Data channel closed');

                    // Set the remote description first
                    console.log('Setting remote description');
                    await this.peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
                    
                    // Create and set local description
                    console.log('Creating answer');
                    const answer = await this.peerConnection.createAnswer();
                    console.log('Setting local description');
                    await this.peerConnection.setLocalDescription(answer);

                    if (this.isConnected) {
                        console.log('Sending answer');
                        this.ws.send(JSON.stringify({
                            type: 'answer',
                            answer: answer
                        }));
                    }
                } catch (err) {
                    console.error('Error handling offer:', err);
                    document.getElementById('connectionOverlay').classList.add('show');
                }
            }

            showPlayButton() {
                const playOverlay = document.createElement('div');
                playOverlay.className = 'play-overlay';
                playOverlay.innerHTML = '<i class="fas fa-play-circle"></i>';
                this.video.parentElement.appendChild(playOverlay);
                
                playOverlay.onclick = async () => {
                    try {
                        await this.video.play();
                        playOverlay.remove();
                        document.getElementById('connectionOverlay').classList.remove('show');
                    } catch (err) {
                        console.error('Manual play failed:', err);
                    }
                };
            }

            async reconnect() {
                try {
                    if (this.peerConnection) {
                        this.peerConnection.close();
                    }
                    await this.connect();
                } catch (err) {
                    console.error('Reconnection failed:', err);
                }
            }

            async handleIceCandidate(candidate) {
                try {
                    if (this.peerConnection) {
                        await this.peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                    }
                } catch (err) {
                    console.error('Error adding ICE candidate:', err);
                }
            }

            disconnect() {
                if (this.peerConnection) {
                    this.peerConnection.close();
                    this.peerConnection = null;
                }
                if (this.ws) {
                    this.ws.close();
                    this.ws = null;
                }
                this.isConnected = false;
            }
        }

        // Initialize the viewer
        const viewer = new WebRTCViewer(document.getElementById('remoteVideo'));
        viewer.connect();
    </script>
</body>
</html> 