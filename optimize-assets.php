<?php
/**
 * CSS/JS Asset Optimizer and Minifier
 * Run this script to optimize your assets for production
 */

require_once 'config/performance-monitor.php';

class AssetMinifier {
    private $cssFiles = [
        'assets/css/style.css',
        'assets/css/landing.css',
        'assets/css/auth.css',
        'assets/css/modern.css'
    ];
    
    private $jsFiles = [
        'assets/js/app.js',
        'assets/js/landing.js',
        'assets/js/auth.js'
    ];
    
    public function __construct() {
        if (!is_dir('assets/dist')) {
            mkdir('assets/dist', 0755, true);
        }
    }
    
    public function optimizeCSS() {
        echo "Optimizing CSS files...\n";
        
        $combinedCSS = '';
        $totalOriginalSize = 0;
        
        foreach ($this->cssFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $originalSize = strlen($content);
                $totalOriginalSize += $originalSize;
                
                // Process CSS
                $optimized = $this->minifyCSS($content);
                $combinedCSS .= "/* {$file} */\n" . $optimized . "\n\n";
                
                $savedBytes = $originalSize - strlen($optimized);
                $savedPercent = round(($savedBytes / $originalSize) * 100, 1);
                
                echo "  {$file}: " . $this->formatBytes($originalSize) . " → " . 
                     $this->formatBytes(strlen($optimized)) . " (-{$savedPercent}%)\n";
            }
        }
        
        // Save combined and minified CSS
        $outputFile = 'assets/dist/app.min.css';
        file_put_contents($outputFile, $combinedCSS);
        
        $finalSize = strlen($combinedCSS);
        $totalSaved = $totalOriginalSize - $finalSize;
        $totalSavedPercent = round(($totalSaved / $totalOriginalSize) * 100, 1);
        
        echo "Combined CSS: {$outputFile}\n";
        echo "Total: " . $this->formatBytes($totalOriginalSize) . " → " . 
             $this->formatBytes($finalSize) . " (-{$totalSavedPercent}%)\n\n";
        
        return $outputFile;
    }
    
    public function optimizeJS() {
        echo "Optimizing JavaScript files...\n";
        
        $combinedJS = '';
        $totalOriginalSize = 0;
        
        foreach ($this->jsFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $originalSize = strlen($content);
                $totalOriginalSize += $originalSize;
                
                // Process JS
                $optimized = $this->minifyJS($content);
                $combinedJS .= "/* {$file} */\n" . $optimized . "\n\n";
                
                $savedBytes = $originalSize - strlen($optimized);
                $savedPercent = round(($savedBytes / $originalSize) * 100, 1);
                
                echo "  {$file}: " . $this->formatBytes($originalSize) . " → " . 
                     $this->formatBytes(strlen($optimized)) . " (-{$savedPercent}%)\n";
            }
        }
        
        // Save combined and minified JS
        $outputFile = 'assets/dist/app.min.js';
        file_put_contents($outputFile, $combinedJS);
        
        $finalSize = strlen($combinedJS);
        $totalSaved = $totalOriginalSize - $finalSize;
        $totalSavedPercent = round(($totalSaved / $totalOriginalSize) * 100, 1);
        
        echo "Combined JS: {$outputFile}\n";
        echo "Total: " . $this->formatBytes($totalOriginalSize) . " → " . 
             $this->formatBytes($finalSize) . " (-{$totalSavedPercent}%)\n\n";
        
        return $outputFile;
    }
    
    private function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove spaces around specific characters
        $css = str_replace([
            '; ', ' {', '{ ', ' }', '} ', ': ', ', ', ' > ', ' + ', ' ~ '
        ], [
            ';', '{', '{', '}', '}', ':', ',', '>', '+', '~'
        ], $css);
        
        // Remove trailing semicolons
        $css = preg_replace('/;}/', '}', $css);
        
        // Remove empty rules
        $css = preg_replace('/[^{}]+\{\s*\}/', '', $css);
        
        return trim($css);
    }
    
    private function minifyJS($js) {
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace('/(?<!:)\/\/.*$/', '', $js);
        
        // Remove extra whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove whitespace around operators
        $js = preg_replace('/\s*([{}();,=+\-*\/])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 1) . ' ' . $units[$pow];
    }
    
    public function optimizeImages() {
        echo "Optimizing images...\n";
        
        $imageExts = ['jpg', 'jpeg', 'png', 'gif'];
        $optimizedCount = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('assets/images', RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $imageExts)) {
                    $originalSize = $file->getSize();
                    $optimizedPath = ImageOptimizer::optimize($file->getPathname(), 85);
                    
                    if ($optimizedPath && file_exists($optimizedPath)) {
                        $newSize = filesize($optimizedPath);
                        $saved = $originalSize - $newSize;
                        $savedPercent = round(($saved / $originalSize) * 100, 1);
                        
                        echo "  {$file->getFilename()}: " . 
                             $this->formatBytes($originalSize) . " → " . 
                             $this->formatBytes($newSize) . " (-{$savedPercent}%)\n";
                        
                        // Replace original with optimized
                        rename($optimizedPath, $file->getPathname());
                        $optimizedCount++;
                    }
                }
            }
        }
        
        echo "Optimized {$optimizedCount} images\n\n";
    }
    
    public function generateManifest() {
        $manifest = [
            'css' => 'assets/dist/app.min.css',
            'js' => 'assets/dist/app.min.js',
            'version' => time(),
            'hash' => [
                'css' => md5_file('assets/dist/app.min.css'),
                'js' => md5_file('assets/dist/app.min.js')
            ]
        ];
        
        file_put_contents('assets/dist/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        echo "Generated manifest: assets/dist/manifest.json\n";
        
        return $manifest;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    echo "MentorConnect Asset Optimizer\n";
    echo "============================\n\n";
    
    $optimizer = new AssetMinifier();
    
    $cssFile = $optimizer->optimizeCSS();
    $jsFile = $optimizer->optimizeJS();
    $optimizer->optimizeImages();
    $manifest = $optimizer->generateManifest();
    
    echo "Optimization complete!\n";
    echo "To use optimized assets, update your HTML to include:\n";
    echo "  <link rel=\"stylesheet\" href=\"{$manifest['css']}?v={$manifest['version']}\">\n";
    echo "  <script src=\"{$manifest['js']}?v={$manifest['version']}\"></script>\n";
}
?>
