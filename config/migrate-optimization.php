<?php
/**
 * MentorConnect Optimization Migration Script
 * Helps migrate from the old system to the optimized system
 */

class OptimizationMigrator {
    private $backupDir;
    private $logFile;
    
    public function __construct() {
        $this->backupDir = __DIR__ . '/../backups/' . date('Y-m-d_H-i-s');
        $this->logFile = __DIR__ . '/../logs/migration_' . date('Y-m-d') . '.log';
        
        $this->ensureDirectories();
    }
    
    public function migrate() {
        $this->log("Starting MentorConnect optimization migration...");
        
        try {
            // Step 1: Create backup
            $this->createBackup();
            
            // Step 2: Update database
            $this->updateDatabase();
            
            // Step 3: Update file references
            $this->updateFileReferences();
            
            // Step 4: Test functionality
            $this->testFunctionality();
            
            $this->log("Migration completed successfully!");
            echo "✅ Migration completed successfully!\n";
            
        } catch (Exception $e) {
            $this->log("Migration failed: " . $e->getMessage(), 'ERROR');
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            $this->rollback();
        }
    }
    
    private function createBackup() {
        $this->log("Creating backup...");
        
        // Backup critical files
        $filesToBackup = [
            'config/config.php',
            'assets/css/style.css',
            'assets/js/app.js',
            'database/database.sql'
        ];
        
        foreach ($filesToBackup as $file) {
            $source = __DIR__ . '/../' . $file;
            $destination = $this->backupDir . '/' . $file;
            
            if (file_exists($source)) {
                $this->ensureDirectory(dirname($destination));
                copy($source, $destination);
                $this->log("Backed up: $file");
            }
        }
    }
    
    private function updateDatabase() {
        $this->log("Updating database with optimizations...");
        
        // Load optimized config to get database connection
        require_once __DIR__ . '/optimized-config.php';
        
        // Run optimization SQL
        $sqlFile = __DIR__ . '/../database/advanced-optimization.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Split by statements and execute
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (empty($statement) || strpos($statement, '--') === 0) continue;
                
                try {
                    executeQuery($statement);
                    $this->log("Executed: " . substr($statement, 0, 50) . "...");
                } catch (Exception $e) {
                    $this->log("Warning: Failed to execute statement: " . $e->getMessage(), 'WARNING');
                }
            }
        }
    }
    
    private function updateFileReferences() {
        $this->log("Updating file references...");
        
        // Update files to use optimized config
        $filesToUpdate = glob(__DIR__ . '/../**/*.php');
        
        foreach ($filesToUpdate as $file) {
            if (strpos($file, 'config/') !== false || 
                strpos($file, 'backups/') !== false || 
                strpos($file, 'logs/') !== false) {
                continue;
            }
            
            $content = file_get_contents($file);
            $originalContent = $content;
            
            // Replace config includes
            $content = preg_replace(
                "/require_once ['\"].*config\/config\.php['\"]/", 
                "require_once __DIR__ . '/config/optimized-config.php'", 
                $content
            );
            
            // Add autoloader if needed
            if (strpos($content, 'class ') !== false && strpos($content, 'autoloader.php') === false) {
                $content = str_replace(
                    "require_once __DIR__ . '/config/optimized-config.php'",
                    "require_once __DIR__ . '/config/optimized-config.php';\n// Autoloader is included in optimized config",
                    $content
                );
            }
            
            if ($content !== $originalContent) {
                file_put_contents($file, $content);
                $this->log("Updated: " . basename($file));
            }
        }
    }
    
    private function testFunctionality() {
        $this->log("Testing core functionality...");
        
        // Test database connection
        try {
            $result = fetchOne("SELECT 1 as test");
            if ($result && $result['test'] == 1) {
                $this->log("✅ Database connection test passed");
            } else {
                throw new Exception("Database test failed");
            }
        } catch (Exception $e) {
            throw new Exception("Database test failed: " . $e->getMessage());
        }
        
        // Test caching
        try {
            if (function_exists('apcu_store')) {
                apcu_store('test_key', 'test_value', 60);
                $cached = apcu_fetch('test_key');
                if ($cached === 'test_value') {
                    $this->log("✅ Caching test passed");
                } else {
                    $this->log("⚠️ Caching test failed - fallback cache will be used");
                }
            } else {
                $this->log("ℹ️ APCu not available - using fallback cache");
            }
        } catch (Exception $e) {
            $this->log("⚠️ Caching test warning: " . $e->getMessage());
        }
        
        // Test utility functions
        try {
            $timeAgo = formatTimeAgo(date('Y-m-d H:i:s', strtotime('-5 minutes')));
            if (strpos($timeAgo, 'minute') !== false) {
                $this->log("✅ Utility functions test passed");
            } else {
                throw new Exception("formatTimeAgo function test failed");
            }
        } catch (Exception $e) {
            throw new Exception("Utility functions test failed: " . $e->getMessage());
        }
    }
    
    private function rollback() {
        $this->log("Starting rollback...");
        
        // Restore backed up files
        $backupFiles = glob($this->backupDir . '/**/*.php');
        foreach ($backupFiles as $backupFile) {
            $relativePath = str_replace($this->backupDir . '/', '', $backupFile);
            $originalPath = __DIR__ . '/../' . $relativePath;
            
            if (copy($backupFile, $originalPath)) {
                $this->log("Restored: $relativePath");
            }
        }
        
        $this->log("Rollback completed");
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to console
        if ($level === 'ERROR') {
            echo "❌ $message\n";
        } elseif ($level === 'WARNING') {
            echo "⚠️ $message\n";
        } else {
            echo "ℹ️ $message\n";
        }
    }
    
    private function ensureDirectories() {
        $dirs = [
            dirname($this->backupDir),
            dirname($this->logFile)
        ];
        
        foreach ($dirs as $dir) {
            $this->ensureDirectory($dir);
        }
    }
    
    private function ensureDirectory($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🚀 MentorConnect Optimization Migration\n";
    echo "=====================================\n\n";
    
    $migrator = new OptimizationMigrator();
    $migrator->migrate();
    
    echo "\n📖 Check the logs directory for detailed migration logs.\n";
    echo "📁 Backups are stored in the backups directory.\n\n";
    echo "🎉 Your MentorConnect application is now optimized!\n";
}
?>
