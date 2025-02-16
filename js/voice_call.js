class VoiceCall {
    constructor() {
        this.peerConnection = null;
        this.localStream = null;
        this.ws = null;
        this.isCallActive = false;
        this.isMuted = false;
        this.remoteStream = null;
        this.role = document.body.dataset.role; // Get user role from data attribute

        // UI elements
        this.startCallBtn = document.getElementById('startCall');
        this.endCallBtn = document.getElementById('endCall');
        this.muteBtn = document.getElementById('toggleMute');
        this.statusDiv = document.getElementById('callStatus');
        this.audioLevelBar = document.getElementById('audioLevel');
        this.remoteAudio = document.getElementById('remoteAudio');
        this.permissionSettings = document.getElementById('permissionSettings');

        // Bind event listeners
        this.startCallBtn.addEventListener('click', () => this.startCall());
        this.endCallBtn.addEventListener('click', () => this.endCall());
        this.muteBtn.addEventListener('click', () => this.toggleMute());

        // Initialize WebSocket connection
        this.initializeWebSocket();

        // Check for microphone permission on init
        this.checkMicrophonePermission();
    }

    initializeWebSocket() {
        // Use ws:// for local development
        const wsProtocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
        this.ws = new WebSocket(`${wsProtocol}${window.location.hostname}:8081`);
        
        this.ws.onopen = () => {
            this.updateStatus('Connected to server');
            this.startCallBtn.disabled = false;
            // Register role with server
            this.ws.send(JSON.stringify({
                type: 'register',
                role: this.role
            }));
        };

        this.ws.onclose = () => {
            this.updateStatus('Disconnected from server');
            this.startCallBtn.disabled = true;
            if (this.isCallActive) {
                this.endCall();
            }
        };

        this.ws.onmessage = async (event) => {
            const message = JSON.parse(event.data);
            console.log('Received message:', message);
            
            switch (message.type) {
                case 'offer':
                    await this.handleOffer(message.offer);
                    break;
                case 'answer':
                    await this.handleAnswer(message.answer);
                    break;
                case 'ice-candidate':
                    await this.handleIceCandidate(message.candidate);
                    break;
                case 'call-ended':
                    this.endCall();
                    break;
                case 'incoming-call':
                    this.handleIncomingCall();
                    break;
            }
        };
    }

    async handleOffer(offer) {
        try {
            this.peerConnection = this.createPeerConnection();
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
            
            // Get local stream for answering
            this.localStream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: false
            });
            
            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });

            const answer = await this.peerConnection.createAnswer();
            await this.peerConnection.setLocalDescription(answer);

            this.ws.send(JSON.stringify({
                type: 'answer',
                answer: answer
            }));

            this.isCallActive = true;
            this.startCallBtn.style.display = 'none';
            this.endCallBtn.style.display = 'flex';
            this.updateStatus('Call connected');
            this.startAudioLevelMonitoring();
        } catch (error) {
            console.error('Error handling offer:', error);
            this.updateStatus('Failed to handle incoming call');
        }
    }

    handleIncomingCall() {
        const accept = confirm('Incoming call. Accept?');
        if (accept) {
            this.startCall();
        } else {
            this.ws.send(JSON.stringify({ type: 'call-rejected' }));
        }
    }

    createPeerConnection() {
        const pc = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        });

        pc.onicecandidate = (event) => {
            if (event.candidate) {
                this.ws.send(JSON.stringify({
                    type: 'ice-candidate',
                    candidate: event.candidate
                }));
            }
        };

        pc.ontrack = (event) => {
            this.remoteAudio.srcObject = event.streams[0];
        };

        return pc;
    }

    async checkMicrophonePermission() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.updateStatus('Your browser does not support voice calls');
                this.startCallBtn.disabled = true;
                this.showPermissionSettings();
                return;
            }

            // Request permission
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            stream.getTracks().forEach(track => track.stop());
            this.updateStatus('Ready to make calls');
            this.startCallBtn.disabled = false;
            this.permissionSettings.classList.add('d-none');
        } catch (error) {
            console.error('Permission error:', error);
            if (error.name === 'NotAllowedError') {
                this.updateStatus('Please allow microphone access to make calls');
                this.showPermissionSettings();
            } else if (error.name === 'NotFoundError') {
                this.updateStatus('No microphone found');
            } else {
                this.updateStatus('Error accessing microphone: ' + error.message);
            }
            this.startCallBtn.disabled = true;
        }
    }

    showPermissionSettings() {
        this.permissionSettings.classList.remove('d-none');
    }

    openBrowserSettings() {
        if (navigator.userAgent.indexOf("Chrome") !== -1) {
            // For Chrome
            window.open('chrome://settings/content/microphone');
        } else if (navigator.userAgent.indexOf("Firefox") !== -1) {
            // For Firefox
            window.open('about:preferences#privacy');
        } else if (navigator.userAgent.indexOf("Edge") !== -1) {
            // For Edge
            window.open('edge://settings/content/microphone');
        } else {
            // For other browsers
            alert('Please open your browser settings and allow microphone access for this site.');
        }
    }

    async startCall() {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Your browser does not support voice calls');
            }

            // Show permission request dialog if needed
            const permissionResult = await navigator.permissions.query({ name: 'microphone' });
            if (permissionResult.state === 'denied') {
                throw new Error('Microphone access denied. Please enable it in your browser settings.');
            }

            this.updateStatus('Accessing microphone...');
            
            this.localStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                },
                video: false
            });

            this.peerConnection = this.createPeerConnection();

            this.localStream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, this.localStream);
            });

            const offer = await this.peerConnection.createOffer();
            await this.peerConnection.setLocalDescription(offer);

            this.ws.send(JSON.stringify({
                type: 'offer',
                offer: offer,
                target: this.role === 'computer' ? 'phone' : 'computer'
            }));

            this.isCallActive = true;
            this.startCallBtn.style.display = 'none';
            this.endCallBtn.style.display = 'flex';
            this.updateStatus('Calling...');
            this.startAudioLevelMonitoring();

        } catch (error) {
            console.error('Error starting call:', error);
            this.updateStatus('Failed to start call: ' + error.message);
            // Show a more user-friendly error message
            if (error.name === 'NotAllowedError') {
                alert('Please allow microphone access to make calls. You may need to click the camera icon in your browser\'s address bar.');
            } else if (error.name === 'NotFoundError') {
                alert('No microphone found. Please connect a microphone and try again.');
            } else {
                alert('Error starting call: ' + error.message);
            }
        }
    }

    endCall() {
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }

        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }

        this.isCallActive = false;
        this.startCallBtn.style.display = 'flex';
        this.endCallBtn.style.display = 'none';
        this.updateStatus('Call ended');
        this.audioLevelBar.style.width = '0%';

        // Notify other party
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({ type: 'call-ended' }));
        }
    }

    toggleMute() {
        if (this.localStream) {
            this.isMuted = !this.isMuted;
            this.localStream.getAudioTracks().forEach(track => {
                track.enabled = !this.isMuted;
            });
            this.muteBtn.classList.toggle('active');
            this.muteBtn.querySelector('i').className = 
                this.isMuted ? 'fas fa-microphone-slash' : 'fas fa-microphone';
        }
    }

    updateStatus(message) {
        this.statusDiv.textContent = message;
    }

    startAudioLevelMonitoring() {
        if (!this.localStream) return;

        const audioContext = new AudioContext();
        const source = audioContext.createMediaStreamSource(this.localStream);
        const analyser = audioContext.createAnalyser();
        analyser.fftSize = 256;

        source.connect(analyser);
        const dataArray = new Uint8Array(analyser.frequencyBinCount);

        const updateLevel = () => {
            if (!this.isCallActive) return;

            analyser.getByteFrequencyData(dataArray);
            const average = dataArray.reduce((a, b) => a + b) / dataArray.length;
            const level = (average / 255) * 100;
            this.audioLevelBar.style.width = `${level}%`;

            requestAnimationFrame(updateLevel);
        };

        updateLevel();
    }
}

// Add this outside the class
function openBrowserSettings() {
    const userAgent = navigator.userAgent.toLowerCase();
    
    if (userAgent.includes('chrome')) {
        // For Chrome/Edge
        if (confirm('Would you like to open Chrome microphone settings?')) {
            // First try the settings page
            const settingsWindow = window.open('chrome://settings/content/microphone');
            
            // If that doesn't work, provide manual instructions
            setTimeout(() => {
                if (!settingsWindow || settingsWindow.closed) {
                    alert('To enable microphone access:\n\n' +
                          '1. Click the lock/site settings icon in the address bar\n' +
                          '2. Click on "Site settings"\n' +
                          '3. Find and click "Microphone"\n' +
                          '4. Allow access for this site');
                }
            }, 1000);
        }
    } else if (userAgent.includes('firefox')) {
        // For Firefox
        if (confirm('Would you like to open Firefox permissions settings?')) {
            window.open('about:preferences#privacy');
            alert('In the Privacy & Security settings:\n\n' +
                  '1. Scroll to "Permissions"\n' +
                  '2. Click "Settings..." next to Microphone\n' +
                  '3. Find this website and select "Allow"');
        }
    } else {
        // Generic instructions
        alert('To enable microphone access:\n\n' +
              '1. Click the lock/site settings icon in the address bar\n' +
              '2. Find the microphone settings\n' +
              '3. Allow access for this site');
    }
}

// Initialize voice call when page loads
document.addEventListener('DOMContentLoaded', () => {
    new VoiceCall();
}); 