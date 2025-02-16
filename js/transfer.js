document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');

    // Handle drag and drop events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('dragover');
    }

    function unhighlight(e) {
        dropZone.classList.remove('dragover');
    }

    // Handle file selection
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFiles);
    dropZone.addEventListener('drop', handleDrop);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles({ target: { files: files } });
    }

    function handleFiles(e) {
        const files = [...e.target.files];
        files.forEach(uploadFile);
    }

    function uploadFile(file) {
        const fileId = 'file-' + Date.now();
        
        // Create file item UI
        const fileItem = createFileItem(file, fileId);
        fileList.insertBefore(fileItem, fileList.firstChild);

        // Create FormData
        const formData = new FormData();
        formData.append('file', file);

        // Upload file
        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return text ? JSON.parse(text) : null;
                } catch (e) {
                    console.error('JSON Parse error:', e);
                    console.log('Raw response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (!data) {
                throw new Error('Empty response from server');
            }
            
            if (data.success) {
                updateFileItemSuccess(fileId);
                showNotification('Success', data.message || 'File uploaded successfully', 'success');
            } else {
                throw new Error(data.message || 'Upload failed');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            const errorMessage = error.message || 'Upload failed';
            updateFileItemError(fileId, errorMessage);
            showNotification('Error', errorMessage, 'error');
        });
    }

    function createFileItem(file, fileId) {
        const div = document.createElement('div');
        div.className = 'file-item';
        div.id = fileId;
        
        div.innerHTML = `
            <div class="file-info">
                <i class="fas fa-file file-icon"></i>
                <div>
                    <div class="file-name">${file.name}</div>
                    <div class="file-size text-muted">${formatFileSize(file.size)}</div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="transfer-actions">
                <button class="btn btn-danger btn-icon cancel-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        return div;
    }

    function updateFileItemSuccess(fileId) {
        const fileItem = document.getElementById(fileId);
        const progressBar = fileItem.querySelector('.progress-bar');
        progressBar.style.width = '100%';
        progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
        progressBar.classList.add('bg-success');
    }

    function updateFileItemError(fileId, message) {
        const fileItem = document.getElementById(fileId);
        if (!fileItem) return;

        const progressBar = fileItem.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = '100%';
            progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            progressBar.classList.add('bg-danger');
        }
        
        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-danger small mt-1';
        errorDiv.textContent = message;
        
        const fileInfo = fileItem.querySelector('.file-info div');
        if (fileInfo) {
            // Remove any existing error message
            const existingError = fileInfo.querySelector('.text-danger');
            if (existingError) {
                existingError.remove();
            }
            fileInfo.appendChild(errorDiv);
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}); 