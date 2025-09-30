# Database Migration and Setup Scripts

## Production Database Setup

### 1. Create Production Database Schema
```sql
-- Run this in your production MySQL
CREATE DATABASE mentorconnect_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'mentorconnect'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON mentorconnect_prod.* TO 'mentorconnect'@'localhost';
FLUSH PRIVILEGES;

USE mentorconnect_prod;
```

### 2. Import Base Schema
```bash
# Import the main database schema
mysql -u mentorconnect -p mentorconnect_prod < database/database.sql

# Apply performance optimizations
mysql -u mentorconnect -p mentorconnect_prod < database/optimize_indexes.sql
```

### 3. Production-Specific Optimizations
```sql
-- Additional production optimizations
-- Run these after importing the base schema

-- Session cleanup job (run daily)
CREATE EVENT IF NOT EXISTS cleanup_sessions
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM user_sessions WHERE expires_at < NOW();

-- Performance monitoring tables
CREATE TABLE IF NOT EXISTS performance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    endpoint VARCHAR(255),
    response_time DECIMAL(8,3),
    memory_usage INT,
    query_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint_time (endpoint, created_at)
);

-- Error logging table
CREATE TABLE IF NOT EXISTS error_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    error_type VARCHAR(100),
    error_message TEXT,
    file_path VARCHAR(500),
    line_number INT,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_time (error_type, created_at),
    INDEX idx_user_time (user_id, created_at)
);

-- Rate limiting table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45),
    endpoint VARCHAR(255),
    requests INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ip_endpoint (ip_address, endpoint),
    INDEX idx_window_start (window_start)
);
```

### 4. Data Migration Script
```php
<?php
// migrate-to-production.php - Run this once to migrate data

require_once 'config/production-config.php';

echo "Starting production migration...\n";

try {
    $db = new ProductionDB();
    
    // 1. Migrate existing users (if any)
    echo "Migrating user data...\n";
    
    // 2. Update password hashes to stronger algorithm
    $users = $db->query("SELECT id, password FROM users WHERE password NOT LIKE '$2y$%'");
    foreach ($users as $user) {
        $newHash = password_hash($user['password'], PASSWORD_ARGON2ID);
        $db->query("UPDATE users SET password = ? WHERE id = ?", [$newHash, $user['id']]);
    }
    
    // 3. Initialize system settings
    echo "Setting up system configuration...\n";
    $db->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
        ('site_name', 'MentorConnect'),
        ('maintenance_mode', '0'),
        ('registration_enabled', '1'),
        ('max_file_size', '10485760'),
        ('session_timeout', '3600'),
        ('rate_limit_requests', '100'),
        ('rate_limit_window', '3600')
    ");
    
    // 4. Create default admin user (change credentials immediately!)
    echo "Creating default admin user...\n";
    $adminPassword = password_hash('ChangeMeNow123!', PASSWORD_ARGON2ID);
    $db->query("INSERT IGNORE INTO users (username, email, password, role, created_at) VALUES 
        ('admin', 'admin@yoursite.com', ?, 'admin', NOW())", [$adminPassword]);
    
    echo "Migration completed successfully!\n";
    echo "⚠️ IMPORTANT: Change the admin password immediately!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
```

### 5. Backup and Restore Procedures

#### Automated Backup Script
```bash
#!/bin/bash
# backup-database.sh - Run daily via cron

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/mentorconnect"
DB_NAME="mentorconnect_prod"
DB_USER="mentorconnect"

mkdir -p $BACKUP_DIR

# Create database backup
mysqldump -u $DB_USER -p$DB_PASS --single-transaction --routines --triggers $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/backup_$DATE.sql

# Keep only last 7 days of backups
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete

echo "Backup completed: backup_$DATE.sql.gz"
```

#### Restore Script
```bash
#!/bin/bash
# restore-database.sh

if [ $# -eq 0 ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    exit 1
fi

BACKUP_FILE=$1
DB_NAME="mentorconnect_prod"
DB_USER="mentorconnect"

echo "⚠️ This will overwrite the current database. Are you sure? (y/N)"
read -r response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "Restoring database from $BACKUP_FILE..."
    
    # Extract and restore
    gunzip -c $BACKUP_FILE | mysql -u $DB_USER -p $DB_NAME
    
    echo "Database restored successfully!"
else
    echo "Restore cancelled."
fi
```

### 6. Health Check Script
```php
<?php
// health-check.php - Monitor database health

require_once 'config/production-config.php';

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'checks' => []
];

try {
    $db = new ProductionDB();
    
    // Database connectivity
    $start = microtime(true);
    $db->query("SELECT 1");
    $dbTime = (microtime(true) - $start) * 1000;
    
    $health['checks']['database'] = [
        'status' => 'ok',
        'response_time_ms' => round($dbTime, 2)
    ];
    
    // Check disk space
    $diskUsage = disk_free_space('/');
    $health['checks']['disk_space'] = [
        'status' => $diskUsage > 1073741824 ? 'ok' : 'warning', // 1GB threshold
        'free_bytes' => $diskUsage
    ];
    
    // Check active sessions
    $activeSessions = $db->query("SELECT COUNT(*) as count FROM user_sessions WHERE expires_at > NOW()")[0]['count'];
    $health['checks']['active_sessions'] = [
        'status' => 'ok',
        'count' => (int)$activeSessions
    ];
    
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['error'] = $e->getMessage();
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
```

### 7. Cron Jobs Setup
```bash
# Add these to your crontab (crontab -e)

# Daily database backup at 2 AM
0 2 * * * /var/www/mentorconnect/scripts/backup-database.sh

# Clean up old sessions every hour
0 * * * * mysql -u mentorconnect -p'password' mentorconnect_prod -e "DELETE FROM user_sessions WHERE expires_at < NOW()"

# Clean up old logs weekly
0 0 * * 0 find /var/www/mentorconnect/logs -name "*.log" -mtime +30 -delete

# Health check every 5 minutes (optional - for monitoring)
*/5 * * * * curl -s http://localhost/health-check.php > /dev/null
```

### 8. Performance Monitoring Queries
```sql
-- Check slow queries
SELECT 
    endpoint,
    AVG(response_time) as avg_response,
    MAX(response_time) as max_response,
    COUNT(*) as request_count
FROM performance_logs 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY endpoint 
ORDER BY avg_response DESC;

-- Check error frequency
SELECT 
    error_type,
    COUNT(*) as error_count,
    MAX(created_at) as last_occurrence
FROM error_logs 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY error_type 
ORDER BY error_count DESC;

-- Check database growth
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE table_schema = 'mentorconnect_prod'
ORDER BY (data_length + index_length) DESC;
```

This migration guide ensures your MentorConnect application runs smoothly in production with proper monitoring, backups, and optimization.