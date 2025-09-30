<?php
/**
 * Missing Utility Functions for MentorConnect
 * Common functions used across the application
 */

if (!function_exists('formatTimeAgo')) {
    /**
     * Format timestamp as "time ago" string
     */
    function formatTimeAgo($datetime) {
        if (empty($datetime)) return 'Unknown';
        
        $time = time() - strtotime($datetime);
        $units = [
            31536000 => 'year',
            2592000  => 'month', 
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
            1        => 'second'
        ];
        
        foreach ($units as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
        }
        
        return 'just now';
    }
}

if (!function_exists('uploadFile')) {
    /**
     * Enhanced file upload function with security validation
     */
    function uploadFile($fileArray, $allowedExtensions = [], $maxSize = 10485760, $uploadDir = 'uploads/') {
        if (!isset($fileArray['error']) || is_array($fileArray['error'])) {
            return ['success' => false, 'error' => 'Invalid file upload parameters'];
        }
        
        // Check for upload errors
        switch ($fileArray['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'No file was uploaded'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'File size exceeds limit'];
            default:
                return ['success' => false, 'error' => 'Unknown upload error'];
        }
        
        // Validate file size
        if ($fileArray['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size too large'];
        }
        
        // Validate file extension
        $fileExtension = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
        if (!empty($allowedExtensions) && !in_array($fileExtension, $allowedExtensions)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }
        
        // Generate secure filename
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $uploadPath = rtrim($uploadDir, '/') . '/' . $filename;
        
        // Create upload directory if it doesn't exist
        $dir = dirname($uploadPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($fileArray['tmp_name'], $uploadPath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $uploadPath,
                'size' => $fileArray['size'],
                'original_name' => $fileArray['name']
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
}

if (!function_exists('generateCSRFToken')) {
    /**
     * Generate CSRF token
     */
    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCSRFToken')) {
    /**
     * Validate CSRF token
     */
    function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Check token expiry (30 minutes)
        if (time() - $_SESSION['csrf_token_time'] > 1800) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('sanitizeFilename')) {
    /**
     * Sanitize filename for safe storage
     */
    function sanitizeFilename($filename) {
        // Remove directory traversal attempts
        $filename = basename($filename);
        
        // Remove potentially dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        return $filename;
    }
}

if (!function_exists('logSecurityEvent')) {
    /**
     * Log security-related events
     */
    function logSecurityEvent($event, $details = [], $severity = 'info') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'severity' => $severity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../logs/security_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        error_log(json_encode($logEntry) . "\n", 3, $logFile);
    }
}

if (!function_exists('rateLimit')) {
    /**
     * Simple rate limiting
     */
    function rateLimit($key, $limit = 10, $window = 60) {
        $cacheKey = 'rate_limit_' . md5($key);
        
        $current = apcu_fetch($cacheKey) ?: 0;
        
        if ($current >= $limit) {
            return false;
        }
        
        apcu_store($cacheKey, $current + 1, $window);
        return true;
    }
}

if (!function_exists('validateImageFile')) {
    /**
     * Validate image file for security
     */
    function validateImageFile($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Check if it's actually an image
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }
        
        // Check for valid image types
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            return false;
        }
        
        return true;
    }
}

if (!function_exists('optimizeImage')) {
    /**
     * Optimize uploaded image
     */
    function optimizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 600, $quality = 85) {
        if (!validateImageFile($sourcePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($sourcePath);
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $sourceType = $imageInfo[2];
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $newWidth = round($sourceWidth * $ratio);
        $newHeight = round($sourceHeight * $ratio);
        
        // Create source image
        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create target image
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($sourceType == IMAGETYPE_PNG || $sourceType == IMAGETYPE_GIF) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefill($targetImage, 0, 0, $transparent);
        }
        
        // Resize
        imagecopyresampled(
            $targetImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $sourceWidth, $sourceHeight
        );
        
        // Save optimized image
        $success = false;
        switch ($sourceType) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($targetImage, $targetPath, $quality);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($targetImage, $targetPath, round(9 * (100 - $quality) / 100));
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($targetImage, $targetPath);
                break;
        }
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        
        return $success;
    }
}

if (!function_exists('apcu_fetch') && !function_exists('apc_fetch')) {
    /**
     * Fallback cache functions when APCu is not available
     */
    function apcu_fetch($key) {
        return false;
    }
    
    function apcu_store($key, $value, $ttl = 0) {
        return false;
    }
    
    function apcu_exists($key) {
        return false;
    }
    
    function apcu_delete($key) {
        return false;
    }
}
?>
