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
    <title>YouTube Control - Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --youtube-red: #ff0000;
            --youtube-dark-red: #cc0000;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-color: #2b2d42;
            --border-radius: 15px;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--youtube-red), var(--youtube-dark-red));
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .search-container {
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--youtube-red);
            box-shadow: 0 0 0 3px rgba(255,0,0,0.1);
            outline: none;
        }

        .btn-youtube {
            background-color: var(--youtube-red);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-youtube:hover {
            background-color: var(--youtube-dark-red);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            color: white;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .video-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .video-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
        }

        .video-info {
            padding: 15px;
        }

        .video-title {
            font-weight: 600;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-channel {
            color: #666;
            font-size: 0.9rem;
        }

        .playback-controls {
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-top: 30px;
        }

        .control-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }

        .control-btn {
            padding: 15px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .video-grid {
                grid-template-columns: 1fr;
            }

            .control-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="fab fa-youtube me-2"></i>YouTube Control
                </h1>
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="search-container">
            <div class="search-form">
                <input type="text" id="searchQuery" class="search-input" 
                       placeholder="Search YouTube videos...">
                <button id="searchButton" class="btn btn-youtube">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
        </div>

        <div id="searchResults" class="video-grid">
            <!-- Search results will appear here -->
        </div>

        <div class="playback-controls">
            <h4 class="mb-3">
                <i class="fas fa-sliders-h me-2"></i>Playback Controls
            </h4>
            <button class="btn btn-youtube w-100 mb-3" id="playPauseBtn">
                <i class="fas fa-play me-2"></i>Play/Pause
            </button>
            <div class="control-group">
                <button class="btn btn-youtube" id="prevBtn">
                    <i class="fas fa-backward me-2"></i>Previous
                </button>
                <button class="btn btn-youtube" id="stopBtn">
                    <i class="fas fa-stop me-2"></i>Stop
                </button>
                <button class="btn btn-youtube" id="nextBtn">
                    <i class="fas fa-forward me-2"></i>Next
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchButton = document.getElementById('searchButton');
            const searchQuery = document.getElementById('searchQuery');
            const searchResults = document.getElementById('searchResults');
            const playPauseBtn = document.getElementById('playPauseBtn');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const stopBtn = document.getElementById('stopBtn');

            // Initialize YouTube API
            async function searchYouTube(query) {
                try {
                    const API_KEY = 'AIzaSyCWkqFxi5-YP2QRoh9KFIV5OVXC4Af_VRA'; // Replace with your actual API key
                    const response = await fetch(`https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=10&q=${encodeURIComponent(query)}&type=video&key=${API_KEY}`);
                    const data = await response.json();
                    
                    if (data.error) {
                        console.error('YouTube API error:', data.error);
                        return [];
                    }

                    return data.items;
                } catch (error) {
                    console.error('Search error:', error);
                    return [];
                }
            }

            // Search button click handler
            if (searchButton) {
                searchButton.addEventListener('click', async () => {
                    const query = searchQuery.value.trim();
                    if (query) {
                        const results = await searchYouTube(query);
                        displaySearchResults(results);
                        sendCommand('youtube', { 
                            action: 'search',
                            query: query
                        });
                    }
                });
            }

            // Search input enter key handler
            if (searchQuery) {
                searchQuery.addEventListener('keypress', async (e) => {
                    if (e.key === 'Enter') {
                        const query = searchQuery.value.trim();
                        if (query) {
                            const results = await searchYouTube(query);
                            displaySearchResults(results);
                            sendCommand('youtube', { 
                                action: 'search',
                                query: query
                            });
                        }
                    }
                });
            }

            // Playback control handlers
            if (playPauseBtn) {
                playPauseBtn.addEventListener('click', () => {
                    sendCommand('youtube', { action: 'playPause' });
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    sendCommand('youtube', { action: 'prev' });
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    sendCommand('youtube', { action: 'next' });
                });
            }

            if (stopBtn) {
                stopBtn.addEventListener('click', () => {
                    sendCommand('youtube', { action: 'stop' });
                });
            }

            // Helper function to send commands
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

            // Display search results
            function displaySearchResults(results) {
                if (!searchResults) return;

                searchResults.innerHTML = results.map(video => `
                    <div class="video-card" data-video-id="${video.id.videoId}">
                        <img class="video-thumbnail" 
                             src="${video.snippet.thumbnails.medium.url}" 
                             alt="${video.snippet.title}">
                        <div class="video-info">
                            <div class="video-title">${video.snippet.title}</div>
                            <div class="video-channel">
                                <i class="fas fa-user-circle me-2"></i>${video.snippet.channelTitle}
                            </div>
                        </div>
                    </div>
                `).join('');

                // Add click handlers to video cards
                document.querySelectorAll('.video-card').forEach(card => {
                    card.addEventListener('click', () => {
                        const videoId = card.dataset.videoId;
                        sendCommand('youtube', { 
                            action: 'play',
                            videoId: videoId
                        });
                    });
                });
            }
        });
    </script>
</body>
</html> 