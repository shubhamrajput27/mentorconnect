<?php
// Advanced Image Optimization and WebP Support
class ImageOptimizer {
    private $maxWidth = 1920;
    private $maxHeight = 1080;
    private $quality = 85;
    private $webpQuality = 80;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    public function __construct($options = []) {
        $this->maxWidth = $options['maxWidth'] ?? $this->maxWidth;
        $this->maxHeight = $options['maxHeight'] ?? $this->maxHeight;
        $this->quality = $options['quality'] ?? $this->quality;
        $this->webpQuality = $options['webpQuality'] ?? $this->webpQuality;
    }
    
    /**
     * Process uploaded image with optimization and WebP conversion
     */
    public function processImage($inputPath, $outputDir, $filename = null) {
        if (!file_exists($inputPath)) {
            throw new Exception('Input file does not exist');
        }
        
        $imageInfo = getimagesize($inputPath);
        if (!$imageInfo || !in_array($imageInfo['mime'], $this->allowedTypes)) {
            throw new Exception('Invalid image type');
        }
        
        // Create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $filename = $filename ?: pathinfo($inputPath, PATHINFO_FILENAME);
        $results = [];
        
        // Load source image
        $sourceImage = $this->loadImage($inputPath, $imageInfo['mime']);
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        
        // Calculate new dimensions
        $dimensions = $this->calculateDimensions($originalWidth, $originalHeight);
        
        // Create optimized image
        $optimizedImage = imagecreatetruecolor($dimensions['width'], $dimensions['height']);
        
        // Preserve transparency for PNG and GIF
        if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
            $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
            imagefill($optimizedImage, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled(
            $optimizedImage, $sourceImage,
            0, 0, 0, 0,
            $dimensions['width'], $dimensions['height'],
            $originalWidth, $originalHeight
        );
        
        // Save original format (optimized)
        $originalPath = $outputDir . '/' . $filename . '.' . $this->getExtensionFromMime($imageInfo['mime']);
        $this->saveImage($optimizedImage, $originalPath, $imageInfo['mime']);
        $results['original'] = [
            'path' => $originalPath,
            'size' => filesize($originalPath),
            'dimensions' => $dimensions
        ];
        
        // Save WebP version if supported
        if (function_exists('imagewebp')) {
            $webpPath = $outputDir . '/' . $filename . '.webp';
            imagewebp($optimizedImage, $webpPath, $this->webpQuality);
            $results['webp'] = [
                'path' => $webpPath,
                'size' => filesize($webpPath),
                'dimensions' => $dimensions
            ];
        }
        
        // Generate thumbnails
        $thumbnailSizes = [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
            'large' => ['width' => 600, 'height' => 600]
        ];
        
        foreach ($thumbnailSizes as $size => $dims) {
            $thumbDimensions = $this->calculateDimensions($originalWidth, $originalHeight, $dims['width'], $dims['height']);
            $thumbnail = imagecreatetruecolor($thumbDimensions['width'], $thumbDimensions['height']);
            
            // Preserve transparency
            if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }
            
            imagecopyresampled(
                $thumbnail, $sourceImage,
                0, 0, 0, 0,
                $thumbDimensions['width'], $thumbDimensions['height'],
                $originalWidth, $originalHeight
            );
            
            // Save thumbnail in original format
            $thumbPath = $outputDir . '/' . $filename . '_' . $size . '.' . $this->getExtensionFromMime($imageInfo['mime']);
            $this->saveImage($thumbnail, $thumbPath, $imageInfo['mime']);
            $results['thumbnails'][$size] = [
                'path' => $thumbPath,
                'size' => filesize($thumbPath),
                'dimensions' => $thumbDimensions
            ];
            
            // Save WebP thumbnail
            if (function_exists('imagewebp')) {
                $thumbWebpPath = $outputDir . '/' . $filename . '_' . $size . '.webp';
                imagewebp($thumbnail, $thumbWebpPath, $this->webpQuality);
                $results['thumbnails'][$size . '_webp'] = [
                    'path' => $thumbWebpPath,
                    'size' => filesize($thumbWebpPath),
                    'dimensions' => $thumbDimensions
                ];
            }
            
            imagedestroy($thumbnail);
        }
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);
        
        return $results;
    }
    
    /**
     * Load image from file based on MIME type
     */
    private function loadImage($path, $mime) {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                throw new Exception('Unsupported image type: ' . $mime);
        }
    }
    
    /**
     * Save image to file based on MIME type
     */
    private function saveImage($image, $path, $mime) {
        switch ($mime) {
            case 'image/jpeg':
                return imagejpeg($image, $path, $this->quality);
            case 'image/png':
                return imagepng($image, $path, 9); // Max compression for PNG
            case 'image/gif':
                return imagegif($image, $path);
            case 'image/webp':
                return imagewebp($image, $path, $this->webpQuality);
            default:
                throw new Exception('Unsupported image type for saving: ' . $mime);
        }
    }
    
    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateDimensions($originalWidth, $originalHeight, $maxWidth = null, $maxHeight = null) {
        $maxWidth = $maxWidth ?: $this->maxWidth;
        $maxHeight = $maxHeight ?: $this->maxHeight;
        
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            return ['width' => $originalWidth, 'height' => $originalHeight];
        }
        
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        return [
            'width' => round($originalWidth * $ratio),
            'height' => round($originalHeight * $ratio)
        ];
    }
    
    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMime($mime) {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $mimeMap[$mime] ?? 'jpg';
    }
    
    /**
     * Generate responsive image HTML with WebP support
     */
    public static function generateResponsiveImage($imagePath, $alt = '', $class = '', $sizes = null) {
        $pathInfo = pathinfo($imagePath);
        $basePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        
        // Check if WebP version exists
        $webpPath = $basePath . '.webp';
        $hasWebp = file_exists($_SERVER['DOCUMENT_ROOT'] . $webpPath);
        
        // Generate srcset for different sizes
        $srcset = [];
        $webpSrcset = [];
        
        $sizes = $sizes ?: ['small', 'medium', 'large'];
        foreach ($sizes as $size) {
            $sizePath = $basePath . '_' . $size . '.' . $extension;
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $sizePath)) {
                $imageSize = getimagesize($_SERVER['DOCUMENT_ROOT'] . $sizePath);
                $srcset[] = $sizePath . ' ' . $imageSize[0] . 'w';
                
                if ($hasWebp) {
                    $webpSizePath = $basePath . '_' . $size . '.webp';
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $webpSizePath)) {
                        $webpSrcset[] = $webpSizePath . ' ' . $imageSize[0] . 'w';
                    }
                }
            }
        }
        
        $html = '<picture>';
        
        // WebP source
        if ($hasWebp && !empty($webpSrcset)) {
            $html .= '<source type="image/webp" srcset="' . implode(', ', $webpSrcset) . '">';
        }
        
        // Original format source
        if (!empty($srcset)) {
            $html .= '<source type="image/' . $extension . '" srcset="' . implode(', ', $srcset) . '">';
        }
        
        // Fallback img tag
        $html .= '<img src="' . $imagePath . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . '" loading="lazy">';
        $html .= '</picture>';
        
        return $html;
    }
    
    /**
     * Clean up old image files
     */
    public static function cleanupOldImages($directory, $daysOld = 30) {
        if (!is_dir($directory)) {
            return false;
        }
        
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $cleaned = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    unlink($file->getPathname());
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}

// Helper function for quick image optimization
function optimizeAndSaveImage($inputPath, $outputDir, $filename = null, $options = []) {
    $optimizer = new ImageOptimizer($options);
    return $optimizer->processImage($inputPath, $outputDir, $filename);
}
?>
