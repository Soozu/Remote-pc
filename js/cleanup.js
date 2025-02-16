document.addEventListener('DOMContentLoaded', function() {
    const startCleanupBtn = document.getElementById('startCleanup');
    const cleanupProgress = document.querySelector('.cleanup-progress');
    const cleanupStatus = document.getElementById('cleanupStatus');

    // Update storage information
    function updateStorageInfo() {
        fetch('cleanup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_sizes'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const uploadsSize = formatSize(data.data.uploads);
                const tempSize = formatSize(data.data.temp);
                
                document.getElementById('uploadsSize').textContent = uploadsSize;
                document.getElementById('tempSize').textContent = tempSize;
                
                // Update progress bars
                document.getElementById('uploadsProgress').style.width = 
                    Math.min((data.data.uploads / (1024 * 1024 * 1024)) * 100, 100) + '%';
                document.getElementById('tempProgress').style.width = 
                    Math.min((data.data.temp / (1024 * 1024 * 1024)) * 100, 100) + '%';
            }
        })
        .catch(error => {
            console.error('Error updating storage info:', error);
        });
    }

    // Format bytes to human-readable size
    function formatSize(bytes) {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
    }

    // Start cleanup
    startCleanupBtn.addEventListener('click', function() {
        const options = {
            uploads: document.getElementById('cleanUploads').checked,
            temp: document.getElementById('cleanTemp').checked,
            daysOld: document.getElementById('cleanupDays').value
        };

        if (!options.uploads && !options.temp) {
            alert('Please select at least one cleanup option');
            return;
        }

        if (confirm('Are you sure you want to clean these files? This action cannot be undone.')) {
            startCleanupBtn.disabled = true;
            cleanupProgress.classList.remove('d-none');
            cleanupStatus.textContent = 'Cleaning in progress...';

            fetch('cleanup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=cleanup&options=${JSON.stringify(options)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = 'Cleanup completed:\n\n';
                    if (options.uploads) {
                        message += `Uploads Folder:\n`;
                        message += `- ${data.stats.uploads.cleaned} files cleaned\n`;
                        if (data.stats.uploads.failed > 0) {
                            message += `- ${data.stats.uploads.failed} files failed to clean\n`;
                        }
                        message += '\n';
                    }
                    if (options.temp) {
                        message += `Temporary Files:\n`;
                        message += `- ${data.stats.temp.cleaned} files cleaned\n`;
                        if (data.stats.temp.failed > 0) {
                            message += `- ${data.stats.temp.failed} files failed to clean\n`;
                        }
                    }
                    alert(message);

                    // Refresh the storage info
                    updateStorageInfo();
                    
                    // Reload the page if uploads were cleaned
                    if (options.uploads && data.stats.uploads.cleaned > 0) {
                        window.location.reload();
                    }
                } else {
                    alert('Cleanup failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error during cleanup:', error);
                alert('Error during cleanup. Please check the console for details.');
            })
            .finally(() => {
                startCleanupBtn.disabled = false;
                cleanupProgress.classList.add('d-none');
                cleanupStatus.textContent = '';
            });
        }
    });

    // Initial storage info update
    updateStorageInfo();
    // Update storage info every 5 minutes
    setInterval(updateStorageInfo, 300000);
}); 