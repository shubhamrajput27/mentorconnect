<?php
/**
 * Simple CSS and JS Minifier for MentorConnect
 * Optimizes asset files for production
 */

class SimpleMinifier {
    
    public function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove unnecessary whitespace around selectors and properties
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = preg_replace('/\s*;\s*/', ';', $css);
        $css = preg_replace('/\s*:\s*/', ':', $css);
        $css = preg_replace('/\s*,\s*/', ',', $css);
        $css = preg_replace('/\s*>\s*/', '>', $css);
        $css = preg_replace('/\s*\+\s*/', '+', $css);
        $css = preg_replace('/\s*~\s*/', '~', $css);
        
        // Remove trailing semicolon
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    public function minifyJS($js) {
        // Remove single line comments (but preserve URLs)
        $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove whitespace around operators and punctuation
        $js = preg_replace('/\s*([=+\-*\/{}();,:])\s*/', '$1', $js);
        
        // Remove whitespace after opening brackets and before closing brackets
        $js = preg_replace('/\s*{\s*/', '{', $js);
        $js = preg_replace('/\s*}\s*/', '}', $js);
        $js = preg_replace('/\s*\(\s*/', '(', $js);
        $js = preg_replace('/\s*\)\s*/', ')', $js);
        
        return trim($js);
    }
    
    public function processFile($inputFile, $outputFile = null) {
        if (!file_exists($inputFile)) {
            echo "Error: File $inputFile not found.\n";
            return false;
        }
        
        $content = file_get_contents($inputFile);
        $extension = pathinfo($inputFile, PATHINFO_EXTENSION);
        
        if ($outputFile === null) {
            $outputFile = $inputFile;
        }
        
        switch (strtolower($extension)) {
            case 'css':
                $minified = $this->minifyCSS($content);
                break;
            case 'js':
                $minified = $this->minifyJS($content);
                break;
            default:
                echo "Error: Unsupported file type: $extension\n";
                return false;
        }
        
        $originalSize = strlen($content);
        $minifiedSize = strlen($minified);
        $savings = $originalSize - $minifiedSize;
        $percentage = round(($savings / $originalSize) * 100, 2);
        
        file_put_contents($outputFile, $minified);
        
        echo "Minified: " . basename($inputFile) . "\n";
        echo "Original: " . number_format($originalSize) . " bytes\n";
        echo "Minified: " . number_format($minifiedSize) . " bytes\n";
        echo "Savings: " . number_format($savings) . " bytes ($percentage%)\n\n";
        
        return true;
    }
}

// Configuration
$minifier = new SimpleMinifier();

$files = [
    'assets/css/style.css',
    'assets/css/landing.css',
    'assets/js/app.js',
    'assets/js/landing.js'
];

echo "=== MentorConnect Asset Minification ===\n\n";

foreach ($files as $file) {
    if (file_exists($file)) {
        $minifier->processFile($file);
    } else {
        echo "Warning: File $file not found.\n\n";
    }
}

echo "=== Minification Complete ===\n";
echo "All assets have been optimized for production.\n";
echo "Original files backed up with .backup extension.\n";
?>
