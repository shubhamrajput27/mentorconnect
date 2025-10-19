<?php
require_once '../config/database.php';
requireLogin();

$user = getCurrentUser();

// Get user's files
$files = fetchAll(
    "SELECT f.*, u.first_name, u.last_name, s.title as session_title
     FROM files f
     JOIN users u ON f.uploader_id = u.id
     LEFT JOIN sessions s ON f.session_id = s.id
     WHERE f.uploader_id = ? OR EXISTS (
         SELECT 1 FROM file_permissions fp 
         WHERE fp.file_id = f.id AND fp.user_id = ?
     )
     ORDER BY f.created_at DESC",
    [$user['id'], $user['id']]
);

// Handle file upload
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        try {
            $sessionId = intval($_POST['session_id'] ?? 0);
            $isPublic = isset($_POST['is_public']);
            
            $uploadResult = uploadFile($_FILES['file'], ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip']);
            
            // Insert file record
            executeQuery(
                "INSERT INTO files (uploader_id, original_name, stored_name, file_path, file_size, mime_type, session_id, is_public) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $user['id'],
                    $uploadResult['original_name'],
                    $uploadResult['stored_name'],
                    $uploadResult['file_path'],
                    $uploadResult['file_size'],
                    $uploadResult['mime_type'],
                    $sessionId > 0 ? $sessionId : null,
                    $isPublic
                ]
            );
            
            $fileId = fetchOne("SELECT LAST_INSERT_ID() as id")['id'];
            
            // Log activity
            logActivity($user['id'], 'file_uploaded', 'Uploaded file: ' . $uploadResult['original_name']);
            
            $success = 'File uploaded successfully!';
            
            // Refresh files list
            $files = fetchAll(
                "SELECT f.*, u.first_name, u.last_name, s.title as session_title
                 FROM files f
                 JOIN users u ON f.uploader_id = u.id
                 LEFT JOIN sessions s ON f.session_id = s.id
                 WHERE f.uploader_id = ? OR EXISTS (
                     SELECT 1 FROM file_permissions fp 
                     WHERE fp.file_id = f.id AND fp.user_id = ?
                 )
                 ORDER BY f.created_at DESC",
                [$user['id'], $user['id']]
            );
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            $error = 'Failed to upload file: ' . $e->getMessage();
        }
    }
}

// Get user's sessions for dropdown
$userSessions = fetchAll(
    "SELECT id, title FROM sessions 
     WHERE (mentor_id = ? OR student_id = ?) AND status IN ('scheduled', 'completed')
     ORDER BY scheduled_at DESC",
    [$user['id'], $user['id']]
);

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <h1><?php echo APP_NAME; ?></h1>
                </a>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item">
                    <a href="/dashboard/<?php echo $user['role']; ?>.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/sessions/index.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Sessions</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/messages/index.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/files/index.php" class="nav-link active">
                        <i class="fas fa-folder"></i>
                        <span>Files</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/profile/edit.php" class="nav-link">
                        <i class="fas fa-user-edit"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="/auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Files</h2>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-primary" onclick="showUploadModal()">
                        <i class="fas fa-upload"></i>
                        Upload File
                    </button>
                    
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <div class="user-menu">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . $user['profile_photo'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Profile" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Files Content -->
            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Files Grid -->
                <div class="card">
                    <div class="card-header">
                        <h3>My Files (<?php echo count($files); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($files)): ?>
                            <div class="no-files">
                                <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h3>No files uploaded yet</h3>
                                <p>Upload files to share with your mentors or students during sessions.</p>
                                <button class="btn btn-primary" onclick="showUploadModal()">
                                    <i class="fas fa-upload"></i>
                                    Upload Your First File
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="files-grid">
                                <?php foreach ($files as $file): ?>
                                    <div class="file-card">
                                        <div class="file-icon">
                                            <i class="<?php echo getFileIcon($file['mime_type']); ?>"></i>
                                        </div>
                                        
                                        <div class="file-info">
                                            <h5 class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></h5>
                                            <p class="file-meta">
                                                <span><?php echo formatFileSize($file['file_size']); ?></span>
                                                <span>•</span>
                                                <span><?php echo formatTimeAgo($file['created_at']); ?></span>
                                            </p>
                                            <p class="file-uploader">
                                                by <?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?>
                                            </p>
                                            <?php if ($file['session_title']): ?>
                                                <p class="file-session">
                                                    <i class="fas fa-calendar"></i>
                                                    <?php echo htmlspecialchars($file['session_title']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="file-actions">
                                            <button class="btn btn-sm btn-primary" onclick="downloadFile(<?php echo $file['id']; ?>)">
                                                <i class="fas fa-download"></i>
                                                Download
                                            </button>
                                            
                                            <?php if ($file['uploader_id'] == $user['id']): ?>
                                                <button class="btn btn-sm btn-outline" onclick="shareFile(<?php echo $file['id']; ?>)">
                                                    <i class="fas fa-share"></i>
                                                    Share
                                                </button>
                                                <button class="btn btn-sm btn-ghost" onclick="deleteFile(<?php echo $file['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload File</h3>
                <button class="modal-close" onclick="hideUploadModal()">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="upload">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="file">Select File</label>
                        <div class="file-drop-zone" id="fileDropZone">
                            <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip">
                            <div class="drop-zone-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag and drop a file here, or click to select</p>
                                <small>Supported: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP (Max 10MB)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_id">Associate with Session (Optional)</label>
                        <select id="session_id" name="session_id">
                            <option value="">No specific session</option>
                            <?php foreach ($userSessions as $session): ?>
                                <option value="<?php echo $session['id']; ?>">
                                    <?php echo htmlspecialchars($session['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_public">
                            <span class="checkmark"></span>
                            Make this file publicly accessible
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideUploadModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i>
                        Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .files-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: var(--spacing-lg);
    }

    .file-card {
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-lg);
        background: var(--background-color);
        transition: all var(--transition-fast);
    }

    .file-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--primary-color);
    }

    .file-icon {
        text-align: center;
        margin-bottom: var(--spacing-md);
    }

    .file-icon i {
        font-size: 3rem;
        color: var(--primary-color);
    }

    .file-name {
        margin: 0 0 var(--spacing-sm) 0;
        color: var(--text-primary);
        word-break: break-word;
    }

    .file-meta {
        color: var(--text-muted);
        font-size: 0.875rem;
        margin: 0 0 var(--spacing-xs) 0;
    }

    .file-uploader {
        color: var(--text-secondary);
        font-size: 0.875rem;
        margin: 0 0 var(--spacing-xs) 0;
    }

    .file-session {
        color: var(--primary-color);
        font-size: 0.875rem;
        margin: 0 0 var(--spacing-md) 0;
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
    }

    .file-actions {
        display: flex;
        gap: var(--spacing-sm);
        flex-wrap: wrap;
    }

    .no-files {
        text-align: center;
        padding: var(--spacing-2xl);
        color: var(--text-muted);
    }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: var(--card-color);
        border-radius: var(--radius-xl);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-xl);
    }

    .modal-header {
        padding: var(--spacing-lg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        color: var(--text-primary);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-muted);
    }

    .modal-body {
        padding: var(--spacing-lg);
    }

    .modal-footer {
        padding: var(--spacing-lg);
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: var(--spacing-md);
    }

    .file-drop-zone {
        border: 2px dashed var(--border-color);
        border-radius: var(--radius-lg);
        padding: var(--spacing-2xl);
        text-align: center;
        transition: all var(--transition-fast);
        cursor: pointer;
        position: relative;
    }

    .file-drop-zone:hover,
    .file-drop-zone.dragover {
        border-color: var(--primary-color);
        background: rgba(99, 102, 241, 0.05);
    }

    .file-drop-zone input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .drop-zone-content i {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: var(--spacing-sm);
    }

    .drop-zone-content p {
        margin: 0 0 var(--spacing-xs) 0;
        color: var(--text-primary);
    }

    .drop-zone-content small {
        color: var(--text-muted);
    }

    @media (max-width: 768px) {
        .files-grid {
            grid-template-columns: 1fr;
        }
        
        .file-actions {
            flex-direction: column;
        }
    }
    </style>

    <script>
    function showUploadModal() {
        document.getElementById('uploadModal').style.display = 'flex';
    }

    function hideUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
    }

    function downloadFile(fileId) {
        window.location.href = `/api/files.php?action=download&id=${fileId}`;
    }

    function shareFile(fileId) {
        // Implementation for sharing file
        window.app.showToast('Share functionality coming soon', 'info');
    }

    function deleteFile(fileId) {
        if (confirm('Are you sure you want to delete this file?')) {
            fetch('/api/files.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    file_id: fileId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    window.app.showToast('File deleted successfully', 'success');
                    location.reload();
                } else {
                    window.app.showToast('Failed to delete file', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.app.showToast('Failed to delete file', 'error');
            });
        }
    }

    // Drag and drop functionality
    const dropZone = document.getElementById('fileDropZone');
    const fileInput = document.getElementById('file');

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateDropZoneText(files[0].name);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            updateDropZoneText(e.target.files[0].name);
        }
    });

    function updateDropZoneText(fileName) {
        const content = dropZone.querySelector('.drop-zone-content p');
        content.textContent = `Selected: ${fileName}`;
    }

    // Close modal when clicking outside
    document.getElementById('uploadModal').addEventListener('click', (e) => {
        if (e.target.id === 'uploadModal') {
            hideUploadModal();
        }
    });
    </script>

    <script src="../assets/js/app.js"></script>
</body>
</html>

<?php
function getFileIcon($mimeType) {
    $icons = [
        'application/pdf' => 'fas fa-file-pdf',
        'application/msword' => 'fas fa-file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fas fa-file-word',
        'text/plain' => 'fas fa-file-alt',
        'image/jpeg' => 'fas fa-file-image',
        'image/jpg' => 'fas fa-file-image',
        'image/png' => 'fas fa-file-image',
        'application/zip' => 'fas fa-file-archive'
    ];
    
    return $icons[$mimeType] ?? 'fas fa-file';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
