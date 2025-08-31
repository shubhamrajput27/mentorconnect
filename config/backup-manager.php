<?php
// Comprehensive Backup and Maintenance System
require_once '../config/config.php';

class BackupManager {
    private $backupDir;
    private $dbConfig;
    private $maxBackups = 30; // Keep 30 days of backups
    private $compressionLevel = 9;
    
    public function __construct() {
        $this->backupDir = __DIR__ . '/../backups';
        $this->dbConfig = [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS
        ];
        $this->ensureBackupDirectory();
    }
    
    /**
     * Create full system backup
     */
    public function createFullBackup($includeUploads = true) {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "full_backup_{$timestamp}";
        $backupPath = $this->backupDir . '/' . $backupName;
        
        try {
            // Create backup directory
            mkdir($backupPath, 0755, true);
            
            $results = [
                'timestamp' => $timestamp,
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'components' => []
            ];
            
            // 1. Database backup
            $dbBackupFile = $backupPath . '/database.sql';
            $results['components']['database'] = $this->backupDatabase($dbBackupFile);
            
            // 2. Application files backup
            $appBackupFile = $backupPath . '/application.tar.gz';
            $results['components']['application'] = $this->backupApplicationFiles($appBackupFile);
            
            // 3. Upload files backup (if requested)
            if ($includeUploads) {
                $uploadsBackupFile = $backupPath . '/uploads.tar.gz';
                $results['components']['uploads'] = $this->backupUploads($uploadsBackupFile);
            }
            
            // 4. Configuration backup
            $configBackupFile = $backupPath . '/config.tar.gz';
            $results['components']['config'] = $this->backupConfiguration($configBackupFile);
            
            // 5. Create backup manifest
            $manifestFile = $backupPath . '/manifest.json';
            $results['components']['manifest'] = $this->createManifest($manifestFile, $results);
            
            // 6. Create compressed archive of entire backup
            $archiveFile = $this->backupDir . "/{$backupName}.tar.gz";
            $results['archive'] = $this->compressBackup($backupPath, $archiveFile);
            
            // 7. Cleanup temporary directory
            $this->removeDirectory($backupPath);
            
            // 8. Cleanup old backups
            $this->cleanupOldBackups();
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Backup failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Backup database using mysqldump
     */
    private function backupDatabase($outputFile) {
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellarg($this->dbConfig['host']),
            escapeshellarg($this->dbConfig['username']),
            escapeshellarg($this->dbConfig['password']),
            escapeshellarg($this->dbConfig['database']),
            escapeshellarg($outputFile)
        );
        
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            // Fallback to PHP-based backup
            return $this->backupDatabasePHP($outputFile);
        }
        
        return [
            'success' => true,
            'method' => 'mysqldump',
            'file' => $outputFile,
            'size' => filesize($outputFile),
            'tables' => $this->getTableCount()
        ];
    }
    
    /**
     * PHP-based database backup fallback
     */
    private function backupDatabasePHP($outputFile) {
        $db = Database::getInstance()->getConnection();
        
        // Get all tables
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $sql = "-- MentorConnect Database Backup\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $result = $db->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch(PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $row[1] . ";\n\n";
            
            // Get table data
            $result = $db->query("SELECT * FROM `{$table}`");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $sql .= "INSERT INTO `{$table}` VALUES (";
                $values = [];
                foreach ($row as $value) {
                    $values[] = $value === null ? 'NULL' : $db->quote($value);
                }
                $sql .= implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        file_put_contents($outputFile, $sql);
        
        return [
            'success' => true,
            'method' => 'php',
            'file' => $outputFile,
            'size' => filesize($outputFile),
            'tables' => count($tables)
        ];
    }
    
    /**
     * Backup application files
     */
    private function backupApplicationFiles($outputFile) {
        $appRoot = dirname(__DIR__);
        $excludePaths = [
            'backups',
            'cache',
            'uploads',
            'vendor',
            'node_modules',
            '.git',
            'logs'
        ];
        
        $excludeArgs = '';
        foreach ($excludePaths as $path) {
            $excludeArgs .= " --exclude='{$path}'";
        }
        
        $command = "tar -czf {$outputFile} -C {$appRoot} {$excludeArgs} .";
        exec($command, $output, $returnVar);
        
        return [
            'success' => $returnVar === 0,
            'file' => $outputFile,
            'size' => file_exists($outputFile) ? filesize($outputFile) : 0,
            'excluded_paths' => $excludePaths
        ];
    }
    
    /**
     * Backup upload files
     */
    private function backupUploads($outputFile) {
        $uploadsDir = dirname(__DIR__) . '/uploads';
        
        if (!is_dir($uploadsDir)) {
            return [
                'success' => true,
                'message' => 'No uploads directory found',
                'size' => 0
            ];
        }
        
        $command = "tar -czf {$outputFile} -C " . dirname($uploadsDir) . " uploads";
        exec($command, $output, $returnVar);
        
        return [
            'success' => $returnVar === 0,
            'file' => $outputFile,
            'size' => file_exists($outputFile) ? filesize($outputFile) : 0,
            'files_count' => $this->countFiles($uploadsDir)
        ];
    }
    
    /**
     * Backup configuration files
     */
    private function backupConfiguration($outputFile) {
        $configDir = __DIR__;
        $command = "tar -czf {$outputFile} -C " . dirname($configDir) . " config";
        exec($command, $output, $returnVar);
        
        return [
            'success' => $returnVar === 0,
            'file' => $outputFile,
            'size' => file_exists($outputFile) ? filesize($outputFile) : 0
        ];
    }
    
    /**
     * Create backup manifest
     */
    private function createManifest($manifestFile, $backupData) {
        $manifest = [
            'version' => '1.0',
            'created_at' => date('c'),
            'php_version' => PHP_VERSION,
            'application_version' => '1.0.0', // Update as needed
            'backup_type' => 'full',
            'components' => $backupData['components'],
            'system_info' => [
                'os' => PHP_OS,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];
        
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
        
        return [
            'success' => true,
            'file' => $manifestFile,
            'size' => filesize($manifestFile)
        ];
    }
    
    /**
     * Compress backup directory
     */
    private function compressBackup($backupPath, $archiveFile) {
        $command = "tar -czf {$archiveFile} -C " . dirname($backupPath) . " " . basename($backupPath);
        exec($command, $output, $returnVar);
        
        return [
            'success' => $returnVar === 0,
            'file' => $archiveFile,
            'size' => file_exists($archiveFile) ? filesize($archiveFile) : 0
        ];
    }
    
    /**
     * Restore from backup
     */
    public function restoreBackup($backupFile, $components = ['database', 'application', 'uploads', 'config']) {
        if (!file_exists($backupFile)) {
            throw new Exception("Backup file not found: {$backupFile}");
        }
        
        $tempDir = $this->backupDir . '/temp_restore_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Extract backup
            $command = "tar -xzf {$backupFile} -C {$tempDir}";
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new Exception("Failed to extract backup file");
            }
            
            // Get backup directory name
            $backupContents = scandir($tempDir);
            $backupDir = null;
            foreach ($backupContents as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item)) {
                    $backupDir = $tempDir . '/' . $item;
                    break;
                }
            }
            
            if (!$backupDir) {
                throw new Exception("Invalid backup structure");
            }
            
            $results = [];
            
            // Restore database
            if (in_array('database', $components)) {
                $dbFile = $backupDir . '/database.sql';
                if (file_exists($dbFile)) {
                    $results['database'] = $this->restoreDatabase($dbFile);
                }
            }
            
            // Restore application files
            if (in_array('application', $components)) {
                $appFile = $backupDir . '/application.tar.gz';
                if (file_exists($appFile)) {
                    $results['application'] = $this->restoreApplicationFiles($appFile);
                }
            }
            
            // Restore uploads
            if (in_array('uploads', $components)) {
                $uploadsFile = $backupDir . '/uploads.tar.gz';
                if (file_exists($uploadsFile)) {
                    $results['uploads'] = $this->restoreUploads($uploadsFile);
                }
            }
            
            // Restore configuration
            if (in_array('config', $components)) {
                $configFile = $backupDir . '/config.tar.gz';
                if (file_exists($configFile)) {
                    $results['config'] = $this->restoreConfiguration($configFile);
                }
            }
            
            return $results;
            
        } finally {
            // Cleanup temporary directory
            $this->removeDirectory($tempDir);
        }
    }
    
    /**
     * Restore database from SQL file
     */
    private function restoreDatabase($sqlFile) {
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($this->dbConfig['host']),
            escapeshellarg($this->dbConfig['username']),
            escapeshellarg($this->dbConfig['password']),
            escapeshellarg($this->dbConfig['database']),
            escapeshellarg($sqlFile)
        );
        
        exec($command, $output, $returnVar);
        
        return [
            'success' => $returnVar === 0,
            'output' => implode("\n", $output)
        ];
    }
    
    /**
     * Get list of available backups
     */
    public function listBackups() {
        $backups = [];
        $pattern = $this->backupDir . '/full_backup_*.tar.gz';
        
        foreach (glob($pattern) as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'filepath' => $file,
                'size' => filesize($file),
                'created_at' => filemtime($file),
                'age_days' => (time() - filemtime($file)) / 86400
            ];
        }
        
        // Sort by creation time (newest first)
        usort($backups, function($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });
        
        return $backups;
    }
    
    /**
     * Cleanup old backups
     */
    private function cleanupOldBackups() {
        $backups = $this->listBackups();
        $removed = 0;
        
        if (count($backups) > $this->maxBackups) {
            $toRemove = array_slice($backups, $this->maxBackups);
            
            foreach ($toRemove as $backup) {
                if (unlink($backup['filepath'])) {
                    $removed++;
                }
            }
        }
        
        return $removed;
    }
    
    /**
     * System maintenance operations
     */
    public function performMaintenance() {
        $results = [
            'timestamp' => date('c'),
            'operations' => []
        ];
        
        // 1. Optimize database tables
        $results['operations']['database_optimization'] = $this->optimizeDatabase();
        
        // 2. Clear expired cache
        if (class_exists('CacheManager')) {
            $cache = new CacheManager();
            $results['operations']['cache_cleanup'] = $cache->cleanup();
        }
        
        // 3. Clear temporary files
        $results['operations']['temp_cleanup'] = $this->cleanupTempFiles();
        
        // 4. Update system statistics
        $results['operations']['stats_update'] = $this->updateSystemStats();
        
        // 5. Check disk space
        $results['operations']['disk_check'] = $this->checkDiskSpace();
        
        return $results;
    }
    
    /**
     * Optimize database tables
     */
    private function optimizeDatabase() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get all tables
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $optimized = 0;
            foreach ($tables as $table) {
                $db->exec("OPTIMIZE TABLE `{$table}`");
                $optimized++;
            }
            
            return [
                'success' => true,
                'tables_optimized' => $optimized
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Helper methods
     */
    private function ensureBackupDirectory() {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // Protect backup directory
        $htaccessFile = $this->backupDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
    
    private function removeDirectory($dir) {
        if (!is_dir($dir)) return false;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    private function countFiles($dir) {
        if (!is_dir($dir)) return 0;
        
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) $count++;
        }
        
        return $count;
    }
    
    private function getTableCount() {
        try {
            $db = Database::getInstance()->getConnection();
            $result = $db->query("SHOW TABLES");
            return $result->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function cleanupTempFiles() {
        $tempDirs = [
            sys_get_temp_dir(),
            __DIR__ . '/../temp',
            __DIR__ . '/../cache/temp'
        ];
        
        $cleaned = 0;
        foreach ($tempDirs as $dir) {
            if (is_dir($dir)) {
                $pattern = $dir . '/mentorconnect_*';
                foreach (glob($pattern) as $file) {
                    if (filemtime($file) < time() - 3600) { // Older than 1 hour
                        if (is_file($file)) {
                            unlink($file);
                        } else {
                            $this->removeDirectory($file);
                        }
                        $cleaned++;
                    }
                }
            }
        }
        
        return $cleaned;
    }
    
    private function updateSystemStats() {
        // This would update various system statistics
        return ['updated' => time()];
    }
    
    private function checkDiskSpace() {
        $freeBytes = disk_free_space(__DIR__);
        $totalBytes = disk_total_space(__DIR__);
        
        return [
            'free_space' => $freeBytes,
            'total_space' => $totalBytes,
            'used_percentage' => ($totalBytes - $freeBytes) / $totalBytes * 100,
            'free_percentage' => $freeBytes / $totalBytes * 100
        ];
    }
}

// CLI interface for backup operations
if (php_sapi_name() === 'cli') {
    $backupManager = new BackupManager();
    
    $command = $argv[1] ?? 'help';
    
    switch ($command) {
        case 'backup':
            echo "Creating full backup...\n";
            $result = $backupManager->createFullBackup();
            echo "Backup completed: " . $result['backup_name'] . "\n";
            break;
            
        case 'list':
            echo "Available backups:\n";
            $backups = $backupManager->listBackups();
            foreach ($backups as $backup) {
                echo sprintf("- %s (%.2f MB, %d days old)\n", 
                    $backup['filename'], 
                    $backup['size'] / 1024 / 1024,
                    round($backup['age_days'])
                );
            }
            break;
            
        case 'maintenance':
            echo "Performing maintenance...\n";
            $result = $backupManager->performMaintenance();
            echo "Maintenance completed.\n";
            print_r($result);
            break;
            
        default:
            echo "Usage: php backup-manager.php [backup|list|maintenance]\n";
            break;
    }
}
?>
