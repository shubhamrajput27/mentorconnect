<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'download':
            handleDownload();
            break;
            
        case 'delete':
            handleDelete();
            break;
            
        case 'share':
            handleShare();
            break;
            
        case 'get_permissions':
            handleGetPermissions();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleDownload() {
    global $user;
    
    $fileId = intval($_GET['id'] ?? 0);
    if (!$fileId) {
        throw new Exception('File ID is required');
    }
    
    // Check if user has access to this file
    $file = fetchOne(
        "SELECT f.*, u.first_name, u.last_name 
         FROM files f
         JOIN users u ON f.uploader_id = u.id
         WHERE f.id = ? AND (
             f.uploader_id = ? OR 
             f.is_public = 1 OR 
             EXISTS (SELECT 1 FROM file_permissions fp WHERE fp.file_id = f.id AND fp.user_id = ?)
         )",
        [$fileId, $user['id'], $user['id']]
    );
    
    if (!$file) {
        throw new Exception('File not found or access denied');
    }
    
    $filePath = '../' . $file['file_path'];
    if (!file_exists($filePath)) {
        throw new Exception('File not found on server');
    }
    
    // Log download activity
    logActivity($user['id'], 'file_downloaded', 'Downloaded file: ' . $file['original_name']);
    
    // Set headers for file download
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output file
    readfile($filePath);
    exit;
}

function handleDelete() {
    global $user;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = intval($input['file_id'] ?? 0);
    
    if (!$fileId) {
        throw new Exception('File ID is required');
    }
    
    // Check if user owns this file
    $file = fetchOne(
        "SELECT * FROM files WHERE id = ? AND uploader_id = ?",
        [$fileId, $user['id']]
    );
    
    if (!$file) {
        throw new Exception('File not found or access denied');
    }
    
    // Delete physical file
    $filePath = '../' . $file['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete from database
    executeQuery("DELETE FROM files WHERE id = ?", [$fileId]);
    executeQuery("DELETE FROM file_permissions WHERE file_id = ?", [$fileId]);
    
    // Log activity
    logActivity($user['id'], 'file_deleted', 'Deleted file: ' . $file['original_name']);
    
    echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
}

function handleShare() {
    global $user;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = intval($input['file_id'] ?? 0);
    $userIds = $input['user_ids'] ?? [];
    
    if (!$fileId) {
        throw new Exception('File ID is required');
    }
    
    // Check if user owns this file
    $file = fetchOne(
        "SELECT * FROM files WHERE id = ? AND uploader_id = ?",
        [$fileId, $user['id']]
    );
    
    if (!$file) {
        throw new Exception('File not found or access denied');
    }
    
    // Remove existing permissions
    executeQuery("DELETE FROM file_permissions WHERE file_id = ?", [$fileId]);
    
    // Add new permissions
    foreach ($userIds as $userId) {
        $userId = intval($userId);
        if ($userId > 0 && $userId != $user['id']) {
            executeQuery(
                "INSERT INTO file_permissions (file_id, user_id, granted_by, permission_type) VALUES (?, ?, ?, 'read')",
                [$fileId, $userId, $user['id']]
            );
        }
    }
    
    // Log activity
    logActivity($user['id'], 'file_shared', 'Shared file: ' . $file['original_name'] . ' with ' . count($userIds) . ' users');
    
    echo json_encode(['success' => true, 'message' => 'File shared successfully']);
}

function handleGetPermissions() {
    global $user;
    
    $fileId = intval($_GET['file_id'] ?? 0);
    
    if (!$fileId) {
        throw new Exception('File ID is required');
    }
    
    // Check if user owns this file
    $file = fetchOne(
        "SELECT * FROM files WHERE id = ? AND uploader_id = ?",
        [$fileId, $user['id']]
    );
    
    if (!$file) {
        throw new Exception('File not found or access denied');
    }
    
    // Get current permissions
    $permissions = fetchAll(
        "SELECT fp.*, u.first_name, u.last_name, u.email
         FROM file_permissions fp
         JOIN users u ON fp.user_id = u.id
         WHERE fp.file_id = ?",
        [$fileId]
    );
    
    // Get potential users to share with (mentors/students user has interacted with)
    $potentialUsers = fetchAll(
        "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.role
         FROM users u
         WHERE u.id != ? AND u.id IN (
             SELECT CASE 
                 WHEN sender_id = ? THEN receiver_id 
                 ELSE sender_id 
             END
             FROM messages 
             WHERE sender_id = ? OR receiver_id = ?
             UNION
             SELECT CASE 
                 WHEN mentor_id = ? THEN student_id 
                 ELSE mentor_id 
             END
             FROM sessions 
             WHERE mentor_id = ? OR student_id = ?
         )
         ORDER BY u.first_name, u.last_name",
        [$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]
    );
    
    echo json_encode([
        'success' => true,
        'permissions' => $permissions,
        'potential_users' => $potentialUsers
    ]);
}
?>
