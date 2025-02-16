class WebRTCStreamer {
    constructor() {
        this.peerConnections = new Map();
        this.localStream = null;
        this.ws = null;
        this.isConnected = false;
    }

    async startSharing() {
        try {
            // Get screen stream with better constraints
            this.localStream = await navigator.mediaDevices.getDisplayMedia({
                video: {
                    cursor: "always",
                    frameRate: { ideal: 15, max: 30 },
                    width: { ideal: 1280, max: 1920 },
                    height: { ideal: 720, max: 1080 }
                },
                audio: false
            });

            // Connect to signaling server
            await this.connectSignalingServer();
            
            // Handle stream end
            this.localStream.getVideoTracks()[0].onended = () => {
                this.stopSharing();
            };

            return true;
        } catch (err) {
            console.error('Error starting stream:', err);
            return false;
        }
    }

    connectSignalingServer() {
        return new Promise((resolve, reject) => {
            this.ws = new WebSocket(`ws://${window.location.hostname}:8081`);

            this.ws.onopen = () => {
                console.log('Connected to signaling server');
                this.isConnected = true;
                this.ws.send(JSON.stringify({ type: 'broadcaster' }));
                resolve();
            };

            this.ws.onclose = () => {
                console.log('Disconnected from signaling server');
                this.isConnected = false;
                if (this.localStream) {
                    setTimeout(() => this.connectSignalingServer(), 1000);
                }
            };

            this.ws.onerror = (error) => {
                console.error('WebSocket Error:', error);
                reject(error);
            };

            this.ws.onmessage = async (event) => {
                try {
                    const message = JSON.parse(event.data);
                    console.log('Received message:', message.type);
                    
                    switch (message.type) {
                        case 'viewer_joined':
                            await this.handleViewerJoined(message.viewerId);
                            break;
                        case 'viewer_left':
                            this.handleViewerLeft(message.viewerId);
                            break;
                        case 'ice_candidate':
                            await this.handleIceCandidate(message.viewerId, message.candidate);
                            break;
                        case 'answer':
                            await this.handleAnswer(message.viewerId, message.answer);
                            break;
                    }
                } catch (err) {
                    console.error('Error handling message:', err);
                }
            };
        });
    }

    async handleViewerJoined(viewerId) {
        try {
            console.log('Handling viewer join:', viewerId);
            const peerConnection = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    { 
                        urls: 'turn:numb.viagenie.ca',
                        credential: 'muazkh',
                        username: 'webrtc@live.com'
                    }
                ],
                iceCandidatePoolSize: 10
            });

            this.peerConnections.set(viewerId, peerConnection);

            // Add local stream
            this.localStream.getTracks().forEach(track => {
                console.log('Adding track to peer connection:', track.kind);
                peerConnection.addTrack(track, this.localStream);
            });

            // Handle ICE candidates
            peerConnection.onicecandidate = (event) => {
                if (event.candidate && this.isConnected) {
                    console.log('Sending ICE candidate to viewer:', viewerId);
                    this.ws.send(JSON.stringify({
                        type: 'ice_candidate',
                        viewerId: viewerId,
                        candidate: event.candidate
                    }));
                }
            };

            peerConnection.oniceconnectionstatechange = () => {
                console.log('ICE connection state:', peerConnection.iceConnectionState);
            };

            peerConnection.onconnectionstatechange = () => {
                console.log('Connection state:', peerConnection.connectionState);
            };

            // Create and send offer
            const offer = await peerConnection.createOffer({
                offerToReceiveVideo: true,
                offerToReceiveAudio: false,
                iceRestart: true
            });
            
            console.log('Created offer:', offer.type);
            await peerConnection.setLocalDescription(offer);
            
            if (this.isConnected) {
                console.log('Sending offer to viewer:', viewerId);
                this.ws.send(JSON.stringify({
                    type: 'offer',
                    viewerId: viewerId,
                    offer: offer
                }));
            }
        } catch (err) {
            console.error('Error handling viewer:', err);
        }
    }

    async handleAnswer(viewerId, answer) {
        const peerConnection = this.peerConnections.get(viewerId);
        if (peerConnection) {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
        }
    }

    async handleIceCandidate(viewerId, candidate) {
        const peerConnection = this.peerConnections.get(viewerId);
        if (peerConnection) {
            await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
        }
    }

    handleViewerLeft(viewerId) {
        const peerConnection = this.peerConnections.get(viewerId);
        if (peerConnection) {
            peerConnection.close();
            this.peerConnections.delete(viewerId);
        }
    }

    stopSharing() {
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }
        
        this.peerConnections.forEach(pc => pc.close());
        this.peerConnections.clear();
        
        if (this.ws) {
            this.ws.close();
        }
    }
} 