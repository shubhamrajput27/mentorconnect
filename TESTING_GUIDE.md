# MentorConnect Production Testing & Verification Guide

## Pre-Deployment Testing

### 1. Local Testing Checklist

Before deploying to production, run these tests locally:

```bash
# Test 1: Database Connection
php -r "
require_once 'config/production-config.php';
try {
    \$db = new ProductionDB();
    echo 'Database connection: ✅ SUCCESS\n';
} catch (Exception \$e) {
    echo 'Database connection: ❌ FAILED - ' . \$e->getMessage() . '\n';
}
"

# Test 2: Check all required PHP extensions
php -m | grep -E "(mysqli|pdo|gd|curl|mbstring|json|session)"
```

### 2. Automated Test Suite

Create `tests/production-tests.php`:

```php
<?php
// Production Test Suite

class ProductionTests {
    private $db;
    private $testResults = [];
    
    public function __construct() {
        require_once '../config/production-config.php';
        $this->db = new ProductionDB();
    }
    
    public function runAllTests() {
        echo "🧪 MentorConnect Production Test Suite\n";
        echo "=====================================\n\n";
        
        $this->testDatabaseConnection();
        $this->testCriticalFunctions();
        $this->testSecurityHeaders();
        $this->testFileUploads();
        $this->testCaching();
        $this->testPerformance();
        
        $this->displayResults();
    }
    
    private function testDatabaseConnection() {
        echo "Testing database connection...\n";
        
        try {
            // Test basic connection
            $result = $this->db->query("SELECT 1 as test");
            $this->recordTest('Database Connection', true, 'Connected successfully');
            
            // Test connection pooling
            $connections = [];
            for ($i = 0; $i < 5; $i++) {
                $connections[] = $this->db->getConnection();
            }
            $this->recordTest('Connection Pooling', true, 'Multiple connections handled');
            
        } catch (Exception $e) {
            $this->recordTest('Database Connection', false, $e->getMessage());
        }
    }
    
    private function testCriticalFunctions() {
        echo "Testing critical functions...\n";
        
        // Test formatTimeAgo function
        if (function_exists('formatTimeAgo')) {
            $timeAgo = formatTimeAgo(date('Y-m-d H:i:s', strtotime('-2 hours')));
            $this->recordTest('formatTimeAgo Function', !empty($timeAgo), 'Returns: ' . $timeAgo);
        } else {
            $this->recordTest('formatTimeAgo Function', false, 'Function not found');
        }
        
        // Test CSRF functions
        if (function_exists('generateCSRFToken')) {
            $token = generateCSRFToken();
            $this->recordTest('CSRF Token Generation', !empty($token), 'Token generated');
        } else {
            $this->recordTest('CSRF Token Generation', false, 'Function not found');
        }
        
        // Test password hashing
        $testPassword = 'TestPassword123!';
        $hash = password_hash($testPassword, PASSWORD_ARGON2ID);
        $verify = password_verify($testPassword, $hash);
        $this->recordTest('Password Hashing', $verify, 'Argon2ID working correctly');
    }
    
    private function testSecurityHeaders() {
        echo "Testing security configuration...\n";
        
        // Test session settings
        $sessionSecure = ini_get('session.cookie_secure');
        $this->recordTest('Secure Session Cookies', $sessionSecure == '1', 'session.cookie_secure = ' . $sessionSecure);
        
        $sessionHttpOnly = ini_get('session.cookie_httponly');
        $this->recordTest('HttpOnly Session Cookies', $sessionHttpOnly == '1', 'session.cookie_httponly = ' . $sessionHttpOnly);
        
        // Test file upload limits
        $maxFileSize = ini_get('upload_max_filesize');
        $this->recordTest('File Upload Limit', !empty($maxFileSize), 'Max file size: ' . $maxFileSize);
    }
    
    private function testFileUploads() {
        echo "Testing file upload functionality...\n";
        
        // Create test directory
        $testDir = '../uploads/test';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        // Test write permissions
        $testFile = $testDir . '/test.txt';
        $writeTest = file_put_contents($testFile, 'test content');
        
        if ($writeTest !== false) {
            $this->recordTest('File Write Permissions', true, 'Can write to uploads directory');
            unlink($testFile); // Clean up
        } else {
            $this->recordTest('File Write Permissions', false, 'Cannot write to uploads directory');
        }
        
        // Test image optimization (if GD is available)
        if (extension_loaded('gd')) {
            $this->recordTest('Image Processing (GD)', true, 'GD extension loaded');
        } else {
            $this->recordTest('Image Processing (GD)', false, 'GD extension not available');
        }
    }
    
    private function testCaching() {
        echo "Testing caching system...\n";
        
        // Test cache directory
        $cacheDir = '../cache';
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            $this->recordTest('Cache Directory', true, 'Cache directory writable');
        } else {
            $this->recordTest('Cache Directory', false, 'Cache directory not writable');
        }
        
        // Test OPcache
        if (function_exists('opcache_get_status')) {
            $opcacheStatus = opcache_get_status();
            $this->recordTest('OPcache', $opcacheStatus['opcache_enabled'], 'OPcache enabled: ' . ($opcacheStatus['opcache_enabled'] ? 'Yes' : 'No'));
        } else {
            $this->recordTest('OPcache', false, 'OPcache not available');
        }
    }
    
    private function testPerformance() {
        echo "Testing performance metrics...\n";
        
        // Test query performance
        $start = microtime(true);
        $this->db->query("SELECT COUNT(*) FROM users");
        $queryTime = (microtime(true) - $start) * 1000;
        
        $this->recordTest('Database Query Speed', $queryTime < 100, sprintf('Query time: %.2f ms', $queryTime));
        
        // Test memory usage
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $this->recordTest('Memory Usage', $memoryUsage < 32, sprintf('Memory usage: %.2f MB', $memoryUsage));
        
        // Test prepared statement caching
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
        $this->recordTest('Prepared Statements', $stmt !== false, 'Statement preparation working');
    }
    
    private function recordTest($testName, $passed, $message = '') {
        $this->testResults[] = [
            'name' => $testName,
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    private function displayResults() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TEST RESULTS\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $totalTests = count($this->testResults);
        $passedTests = 0;
        
        foreach ($this->testResults as $test) {
            $status = $test['passed'] ? '✅ PASS' : '❌ FAIL';
            echo sprintf("%-30s %s\n", $test['name'], $status);
            
            if (!empty($test['message'])) {
                echo "   └─ " . $test['message'] . "\n";
            }
            
            if ($test['passed']) {
                $passedTests++;
            }
            echo "\n";
        }
        
        echo str_repeat("-", 50) . "\n";
        echo sprintf("SUMMARY: %d/%d tests passed (%.1f%%)\n", 
            $passedTests, $totalTests, ($passedTests / $totalTests) * 100);
        
        if ($passedTests === $totalTests) {
            echo "🎉 All tests passed! Ready for production deployment.\n";
        } else {
            echo "⚠️ Some tests failed. Please fix issues before deploying.\n";
        }
    }
}

// Run tests
$tests = new ProductionTests();
$tests->runAllTests();
?>
```

### 3. Load Testing

```bash
# Install Apache Bench for load testing
sudo apt-get install apache2-utils

# Test concurrent users
ab -n 100 -c 10 http://your-domain.com/

# Test login endpoint
ab -n 50 -c 5 -p login-data.txt -T application/x-www-form-urlencoded http://your-domain.com/auth/login.php
```

Create `login-data.txt`:
```
username=testuser&password=testpass
```

### 4. Security Testing

```bash
# Test for common vulnerabilities
# Install nikto scanner
sudo apt-get install nikto

# Run security scan
nikto -h http://your-domain.com

# Test SSL configuration (if SSL enabled)
testssl.sh your-domain.com
```

### 5. Post-Deployment Verification

#### Health Check Endpoint
Access `http://your-domain.com/health-check.php` and verify response:

```json
{
    "status": "ok",
    "timestamp": "2024-01-15T10:30:00+00:00",
    "checks": {
        "database": {
            "status": "ok",
            "response_time_ms": 2.45
        },
        "disk_space": {
            "status": "ok",
            "free_bytes": 5368709120
        },
        "active_sessions": {
            "status": "ok",
            "count": 0
        }
    }
}
```

#### Manual Testing Checklist

- [ ] **Homepage loads correctly**
- [ ] **User registration works**
- [ ] **User login/logout functions**
- [ ] **Password reset functionality**
- [ ] **File upload works (if applicable)**
- [ ] **Database queries execute properly**
- [ ] **Error pages display correctly (404, 500)**
- [ ] **Security headers are present**
- [ ] **HTTPS redirects work (if SSL enabled)**
- [ ] **Cache headers are set correctly**

#### Performance Verification

```bash
# Test page load times
curl -w "@curl-format.txt" -o /dev/null -s http://your-domain.com/

# Create curl-format.txt:
echo '
     time_namelookup:  %{time_namelookup}\n
        time_connect:  %{time_connect}\n
     time_appconnect:  %{time_appconnect}\n
    time_pretransfer:  %{time_pretransfer}\n
       time_redirect:  %{time_redirect}\n
  time_starttransfer:  %{time_starttransfer}\n
                     ----------\n
          time_total:  %{time_total}\n
' > curl-format.txt
```

### 6. Monitoring Setup

#### Error Monitoring
```php
// Add to your error handler
function logProductionError($error) {
    $logFile = '/var/www/mentorconnect/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] ERROR: $error\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
```

#### Performance Monitoring
```bash
# Monitor server resources
htop

# Monitor disk usage
df -h

# Monitor MySQL processes
mysql -e "SHOW PROCESSLIST;"

# Monitor slow queries
mysql -e "SHOW VARIABLES LIKE 'slow_query_log';"
```

### 7. Rollback Plan

If deployment fails, follow these steps:

```bash
# 1. Stop services
sudo systemctl stop nginx php8.1-fpm

# 2. Restore previous backup
gunzip -c /var/backups/mentorconnect/backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u mentorconnect -p mentorconnect_prod

# 3. Restore application files
sudo rm -rf /var/www/mentorconnect/*
sudo tar -xzf /var/backups/mentorconnect/app_backup_YYYYMMDD.tar.gz -C /var/www/mentorconnect/

# 4. Restart services
sudo systemctl start nginx php8.1-fpm

# 5. Verify rollback
curl -I http://your-domain.com/
```

### 8. Go-Live Checklist

- [ ] **All tests pass locally and on staging**
- [ ] **Database migration completed successfully**
- [ ] **SSL certificate configured and tested**
- [ ] **DNS records updated and propagated**
- [ ] **Monitoring systems configured**
- [ ] **Backup systems operational**
- [ ] **Error logging enabled**
- [ ] **Performance monitoring active**
- [ ] **Security headers configured**
- [ ] **File permissions set correctly**
- [ ] **Cron jobs configured**
- [ ] **Admin user created with strong password**
- [ ] **Default passwords changed**
- [ ] **Rollback plan tested**

Your MentorConnect application is now ready for production! 🚀