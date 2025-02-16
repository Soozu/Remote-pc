document.addEventListener('DOMContentLoaded', function() {
    const touchpad = document.getElementById('touchpad');
    const leftClick = document.getElementById('leftClick');
    const rightClick = document.getElementById('rightClick');
    const shutdown = document.getElementById('shutdown');
    const restart = document.getElementById('restart');
    const sleep = document.getElementById('sleep');
    const lock = document.getElementById('lock');

    let lastX = 0;
    let lastY = 0;
    let isTracking = false;
    let sensitivity = 1.5; // Adjust sensitivity multiplier
    let lastUpdate = 0;
    const updateInterval = 16; // ~60fps

    // Prevent default touch behaviors
    touchpad.addEventListener('touchstart', function(e) {
        e.preventDefault();
    }, { passive: false });

    touchpad.addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, { passive: false });

    // Mouse/Touch movement handling with improved smoothing
    function startTracking(e) {
        isTracking = true;
        lastX = e.clientX;
        lastY = e.clientY;
        touchpad.style.cursor = 'grabbing';
    }

    function handleMouseMove(e) {
        if (!isTracking) return;
        
        const deltaX = e.clientX - lastX;
        const deltaY = e.clientY - lastY;
        
        if (deltaX !== 0 || deltaY !== 0) {
            sendCommand('mouse_move', { x: deltaX, y: deltaY });
        }
        
        lastX = e.clientX;
        lastY = e.clientY;
    }

    function stopTracking() {
        isTracking = false;
        touchpad.style.cursor = 'grab';
    }

    function handleTouchStart(e) {
        e.preventDefault();
        const touch = e.touches[0];
        lastX = touch.clientX;
        lastY = touch.clientY;
        isTracking = true;
    }

    function handleTouchMove(e) {
        e.preventDefault();
        if (!isTracking) return;
        
        const touch = e.touches[0];
        const deltaX = touch.clientX - lastX;
        const deltaY = touch.clientY - lastY;
        
        if (deltaX !== 0 || deltaY !== 0) {
            sendCommand('mouse_move', { x: deltaX, y: deltaY });
        }
        
        lastX = touch.clientX;
        lastY = touch.clientY;
    }

    function handleTouchEnd(e) {
        e.preventDefault();
        isTracking = false;
    }

    // Mouse events
    touchpad.addEventListener('mousedown', startTracking);
    touchpad.addEventListener('mousemove', handleMouseMove);
    touchpad.addEventListener('mouseup', stopTracking);
    touchpad.addEventListener('mouseleave', stopTracking);

    // Touch events
    touchpad.addEventListener('touchstart', handleTouchStart);
    touchpad.addEventListener('touchmove', handleTouchMove);
    touchpad.addEventListener('touchend', handleTouchEnd);

    // Double tap for double click
    let lastTap = 0;
    touchpad.addEventListener('touchend', function(e) {
        const now = Date.now();
        const DOUBLE_TAP_DELAY = 300;
        
        if (now - lastTap < DOUBLE_TAP_DELAY) {
            sendCommand('mouse_click', { button: 'left', double: true });
            lastTap = 0;
        } else {
            lastTap = now;
        }
    });

    // Mouse clicks with improved handling and feedback
    leftClick.addEventListener('click', async () => {
        console.log('Left click initiated');
        leftClick.classList.add('active');
        
        try {
            const result = await sendCommand('mouse_click', { button: 'left' });
            console.log('Left click result:', result);
            
            if (result.success) {
                // Visual feedback
                leftClick.style.backgroundColor = '#0056b3';
                setTimeout(() => {
                    leftClick.style.backgroundColor = '';
                }, 100);
            } else {
                console.error('Left click failed');
            }
        } catch (error) {
            console.error('Left click error:', error);
        } finally {
            leftClick.classList.remove('active');
        }
    });

    rightClick.addEventListener('click', async () => {
        console.log('Right click initiated');
        rightClick.classList.add('active');
        
        try {
            const result = await sendCommand('mouse_click', { button: 'right' });
            console.log('Right click result:', result);
            
            if (result.success) {
                // Visual feedback
                rightClick.style.backgroundColor = '#0056b3';
                setTimeout(() => {
                    rightClick.style.backgroundColor = '';
                }, 100);
            } else {
                console.error('Right click failed');
            }
        } catch (error) {
            console.error('Right click error:', error);
        } finally {
            rightClick.classList.remove('active');
        }
    });

    // System controls
    shutdown.addEventListener('click', () => {
        if (confirm('Are you sure you want to shutdown the computer?')) {
            sendCommand('system', { action: 'shutdown' });
        }
    });
    
    restart.addEventListener('click', () => {
        if (confirm('Are you sure you want to restart the computer?')) {
            sendCommand('system', { action: 'restart' });
        }
    });
    
    sleep.addEventListener('click', () => sendCommand('system', { action: 'sleep' }));
    lock.addEventListener('click', () => sendCommand('system', { action: 'lock' }));

    // Function to send commands to the server
    async function sendCommand(type, data) {
        try {
            const response = await fetch('control.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: type,
                    data: data
                })
            });

            const result = await response.json();
            if (!result.success) {
                console.error('Command failed:', result.message);
            }
            return result;
        } catch (error) {
            console.error('Error sending command:', error);
            return { success: false, message: 'Error sending command' };
        }
    }

    // Add visual feedback to touchpad
    touchpad.style.cursor = 'grab';
    touchpad.style.backgroundColor = '#f8f9fa';
    touchpad.style.transition = 'background-color 0.2s';
    
    touchpad.addEventListener('mousedown', function() {
        touchpad.style.backgroundColor = '#e9ecef';
    });
    
    touchpad.addEventListener('mouseup', function() {
        touchpad.style.backgroundColor = '#f8f9fa';
    });

    function checkServerStatus() {
        fetch('server_manager.php?action=status')
            .then(response => response.json())
            .then(data => {
                if (!data.running) {
                    showNotification('Warning', 'WebSocket server is not running. Attempting to start...', 'warning');
                    startServer();
                }
            });
    }

    function startServer() {
        fetch('server_manager.php?action=start')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Success', 'WebSocket server started', 'success');
                    setTimeout(initializeWebSocket, 2000); // Reinitialize WebSocket after server starts
                }
            });
    }

    // Check server status periodically
    setInterval(checkServerStatus, 30000);
    // Initial check
    checkServerStatus();
}); 