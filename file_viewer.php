<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$file = isset($_GET['file']) ? $_GET['file'] : '';
$uploadDir = __DIR__ . '/uploads/';
$filePath = $uploadDir . $file;

// Security check
if (!file_exists($filePath) || !is_file($filePath) || strpos(realpath($filePath), realpath($uploadDir)) !== 0) {
    die("Invalid file access");
}

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'webm'];

if (!in_array($extension, $allowedExtensions)) {
    die("File type not supported");
}

// Determine file type
$fileType = match($extension) {
    'pdf' => 'pdf',
    'jpg', 'jpeg', 'png', 'gif' => 'image',
    'mp4', 'webm' => 'video',
    'mp3', 'wav' => 'audio',
    default => 'unknown'
};

// Get file icon
$fileIcon = match($fileType) {
    'pdf' => 'fa-file-pdf',
    'image' => 'fa-image',
    'video' => 'fa-video',
    'audio' => 'fa-music',
    default => 'fa-file'
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Viewer - Remote Desktop Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --background-color: #f8f9fa;
            --text-color: #2d3436;
            --border-radius: 12px;
            --header-height: 60px;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        .viewer-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-back {
            background: var(--background-color);
            color: var(--text-color);
            border: none;
        }

        .btn-download {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .viewer-content {
            margin-top: var(--header-height);
            height: calc(100vh - var(--header-height));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: var(--background-color);
        }

        .file-viewer {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: var(--border-radius);
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .image-viewer {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .file-icon {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .viewer-header {
                padding: 0 15px;
            }

            .header-title {
                font-size: 1rem;
            }

            .header-title .file-name {
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }

            .btn span {
                display: none;
            }

            .viewer-content {
                padding: 10px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --background-color: #1a1a1a;
                --text-color: #ffffff;
            }

            .viewer-header {
                background: #2d2d2d;
            }

            .btn-back {
                background: #3d3d3d;
                color: white;
            }

            .file-viewer,
            .image-viewer {
                background: #2d2d2d;
                border: 1px solid #3d3d3d;
            }
        }

        /* Loading animation */
        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 3px solid var(--background-color);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Add new styles for media players */
        .media-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .video-viewer {
            width: 100%;
            max-height: 80vh;
            border-radius: var(--border-radius);
            background: black;
        }

        .audio-viewer {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--border-radius);
            color: white;
        }

        .audio-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .audio-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        /* Custom video controls */
        .video-viewer::-webkit-media-controls-panel,
        .audio-viewer::-webkit-media-controls-panel {
            background: rgba(0,0,0,0.7);
        }

        .video-viewer::-webkit-media-controls-play-button,
        .audio-viewer::-webkit-media-controls-play-button {
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        /* Dark mode additions */
        @media (prefers-color-scheme: dark) {
            .media-container {
                background: #2d2d2d;
            }

            .audio-viewer {
                background: linear-gradient(135deg, #2d3436, #1a1a1a);
            }
        }

        /* Mobile optimizations for media players */
        @media (max-width: 768px) {
            .media-container {
                border-radius: 0;
            }

            .video-viewer {
                border-radius: 0;
            }

            .audio-viewer {
                padding: 15px;
            }

            .audio-icon {
                font-size: 36px;
            }
        }

        .pdf-viewer {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .pdf-controls {
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .nav-controls, .zoom-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #pageInfo {
            font-size: 0.9rem;
            color: #666;
        }

        .pdf-container {
            flex: 1;
            overflow: auto;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background: #f1f1f1;
            -webkit-overflow-scrolling: touch;
        }

        #pdfCanvas {
            max-width: none;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .pdf-controls {
                padding: 8px;
                justify-content: space-between;
            }

            .nav-controls, .zoom-controls {
                width: 100%;
                justify-content: center;
            }

            .zoom-controls {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid #eee;
            }

            #pageInfo {
                font-size: 0.9rem;
            }

            .pdf-container {
                padding: 10px;
            }

            #pdfCanvas {
                max-width: 100%;
                height: auto !important;
            }

            .btn {
                padding: 4px 8px;
                font-size: 0.9rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .pdf-controls {
                background: #2d2d2d;
                border-bottom: 1px solid #3d3d3d;
            }

            .pdf-container {
                background: #1a1a1a;
            }

            #pageInfo {
                color: #aaa;
            }
        }
    </style>
</head>
<body>
    <div class="viewer-header">
        <div class="header-title">
            <i class="fas <?php echo $fileIcon; ?> file-icon"></i>
            <span class="file-name"><?php echo htmlspecialchars($file); ?></span>
        </div>
        <div class="header-actions">
            <a href="transfer.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-1"></i>
                <span>Back</span>
            </a>
            <a href="uploads/<?php echo urlencode($file); ?>" download class="btn btn-download">
                <i class="fas fa-download me-1"></i>
                <span>Download</span>
            </a>
        </div>
    </div>

    <div class="viewer-content">
        <div class="loading" id="loading"></div>
        <div class="media-container">
            <?php switch($fileType): 
                case 'pdf': ?>
                    <div class="pdf-viewer">
                        <div class="pdf-controls">
                            <div class="nav-controls">
                                <button class="btn btn-sm btn-primary" id="prevPage">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <span id="pageInfo">Page <span id="currentPage">1</span> of <span id="pageCount">-</span></span>
                                <button class="btn btn-sm btn-primary" id="nextPage">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="zoom-controls">
                                <button class="btn btn-sm btn-secondary" id="zoomOut">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <span id="zoomLevel">100%</span>
                                <button class="btn btn-sm btn-secondary" id="zoomIn">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" id="fitWidth">
                                    <i class="fas fa-arrows-alt-h"></i>
                                </button>
                            </div>
                        </div>
                        <div class="pdf-container" id="pdfContainer">
                            <canvas id="pdfCanvas"></canvas>
                        </div>
                    </div>
                    
                    <!-- Add PDF.js library -->
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
                    <script>
                        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

                        let pdfDoc = null;
                        let pageNum = 1;
                        let scale = 1.0;
                        const canvas = document.getElementById('pdfCanvas');
                        const ctx = canvas.getContext('2d');
                        const container = document.getElementById('pdfContainer');

                        // Function to calculate scale to fit width
                        function calculateFitScale(page) {
                            const viewport = page.getViewport({ scale: 1 });
                            const containerWidth = container.clientWidth - 40;
                            return containerWidth / viewport.width;
                        }

                        // Function to render page
                        function renderPage(num, shouldResize = false) {
                            pdfDoc.getPage(num).then(function(page) {
                                // If shouldResize is true, recalculate scale
                                if (shouldResize) {
                                    scale = calculateFitScale(page);
                                }

                                const viewport = page.getViewport({ scale: scale });

                                // Set canvas dimensions
                                canvas.height = viewport.height;
                                canvas.width = viewport.width;

                                // Render PDF page
                                const renderContext = {
                                    canvasContext: ctx,
                                    viewport: viewport,
                                    enableWebGL: true
                                };

                                page.render(renderContext).promise.then(() => {
                                    document.getElementById('currentPage').textContent = num;
                                    document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
                                });
                            });
                        }

                        // Load the PDF
                        pdfjsLib.getDocument('uploads/<?php echo urlencode($file); ?>').promise
                            .then(function(pdf) {
                                pdfDoc = pdf;
                                document.getElementById('pageCount').textContent = pdf.numPages;
                                document.getElementById('loading').style.display = 'none';
                                
                                // Initial render
                                pdfDoc.getPage(pageNum).then(function(page) {
                                    scale = calculateFitScale(page);
                                    renderPage(pageNum);
                                });
                            })
                            .catch(function(error) {
                                console.error('Error loading PDF:', error);
                                alert('Error loading PDF file');
                            });

                        // Navigation controls
                        document.getElementById('prevPage').addEventListener('click', () => {
                            if (pageNum <= 1) return;
                            pageNum--;
                            renderPage(pageNum);
                        });

                        document.getElementById('nextPage').addEventListener('click', () => {
                            if (pageNum >= pdfDoc.numPages) return;
                            pageNum++;
                            renderPage(pageNum);
                        });

                        // Zoom controls
                        document.getElementById('zoomIn').addEventListener('click', () => {
                            scale = Math.min(scale * 1.2, 3.0);
                            renderPage(pageNum);
                        });

                        document.getElementById('zoomOut').addEventListener('click', () => {
                            scale = Math.max(scale / 1.2, 0.5);
                            renderPage(pageNum);
                        });

                        // Fit to width button
                        document.getElementById('fitWidth').addEventListener('click', () => {
                            renderPage(pageNum, true);
                        });

                        // Handle window resize
                        let resizeTimeout;
                        window.addEventListener('resize', () => {
                            clearTimeout(resizeTimeout);
                            resizeTimeout = setTimeout(() => {
                                renderPage(pageNum, true);
                            }, 200);
                        });

                        // Keyboard navigation
                        document.addEventListener('keydown', (e) => {
                            switch(e.key) {
                                case 'ArrowLeft':
                                    document.getElementById('prevPage').click();
                                    break;
                                case 'ArrowRight':
                                    document.getElementById('nextPage').click();
                                    break;
                                case '+':
                                    document.getElementById('zoomIn').click();
                                    break;
                                case '-':
                                    document.getElementById('zoomOut').click();
                                    break;
                            }
                        });

                        // Touch controls
                        let touchStartX = 0;
                        let touchStartY = 0;

                        container.addEventListener('touchstart', e => {
                            touchStartX = e.touches[0].clientX;
                            touchStartY = e.touches[0].clientY;
                        });

                        container.addEventListener('touchmove', e => {
                            if (scale > calculateFitScale(page)) {
                                e.stopPropagation();
                            } else {
                                e.preventDefault();
                            }
                        });

                        container.addEventListener('touchend', e => {
                            const touchEndX = e.changedTouches[0].clientX;
                            const touchEndY = e.changedTouches[0].clientY;
                            const deltaX = touchEndX - touchStartX;
                            const deltaY = Math.abs(touchEndY - touchStartY);

                            // Only handle horizontal swipes
                            if (Math.abs(deltaX) > 50 && deltaY < 50) {
                                if (deltaX > 0 && pageNum > 1) {
                                    pageNum--;
                                    renderPage(pageNum);
                                } else if (deltaX < 0 && pageNum < pdfDoc.numPages) {
                                    pageNum++;
                                    renderPage(pageNum);
                                }
                            }
                        });
                    </script>
                <?php break; ?>

                <?php case 'image': ?>
                    <img src="uploads/<?php echo urlencode($file); ?>" 
                         class="image-viewer" 
                         alt="<?php echo htmlspecialchars($file); ?>"
                         onload="document.getElementById('loading').style.display='none'">
                    <?php break; ?>

                <?php case 'video': ?>
                    <video class="video-viewer" controls autoplay 
                           onloadeddata="document.getElementById('loading').style.display='none'">
                        <source src="uploads/<?php echo urlencode($file); ?>" 
                                type="video/<?php echo $extension; ?>">
                        Your browser does not support the video tag.
                    </video>
                    <?php break; ?>

                <?php case 'audio': ?>
                    <div class="audio-viewer">
                        <div class="audio-info">
                            <i class="fas fa-music audio-icon"></i>
                            <h4><?php echo htmlspecialchars($file); ?></h4>
                        </div>
                        <audio controls autoplay 
                               onloadeddata="document.getElementById('loading').style.display='none'"
                               style="width: 100%;">
                            <source src="uploads/<?php echo urlencode($file); ?>" 
                                    type="audio/<?php echo $extension; ?>">
                            Your browser does not support the audio tag.
                        </audio>
                    </div>
                    <?php break; ?>
            <?php endswitch; ?>
        </div>
    </div>

    <script>
        // Add touch gestures for images
        const imageViewer = document.querySelector('.image-viewer');
        if (imageViewer) {
            let touchStartX = 0;
            let touchEndX = 0;

            imageViewer.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            });

            imageViewer.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });

            function handleSwipe() {
                const swipeDistance = touchEndX - touchStartX;
                if (Math.abs(swipeDistance) > 100) {
                    window.location.href = 'transfer.php';
                }
            }
        }

        // Add keyboard controls for media
        document.addEventListener('keydown', function(e) {
            const video = document.querySelector('video');
            const audio = document.querySelector('audio');
            const media = video || audio;

            if (media) {
                switch(e.key) {
                    case ' ':
                        e.preventDefault();
                        media.paused ? media.play() : media.pause();
                        break;
                    case 'ArrowLeft':
                        media.currentTime = Math.max(media.currentTime - 10, 0);
                        break;
                    case 'ArrowRight':
                        media.currentTime = Math.min(media.currentTime + 10, media.duration);
                        break;
                    case 'ArrowUp':
                        media.volume = Math.min(media.volume + 0.1, 1);
                        break;
                    case 'ArrowDown':
                        media.volume = Math.max(media.volume - 0.1, 0);
                        break;
                }
            }
        });
    </script>
</body>
</html> 