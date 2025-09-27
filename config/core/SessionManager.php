<?php
/**
 * Optimized Session Manager
 * Secure session handling with automatic regeneration and cleanup
 */

declare(strict_types=1);

class SessionManager {
    private array $config;
    private bool $started = false;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->configureSession();
    }
    
    /**
     * Configure session settings
     */
    private function configureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Security settings
            ini_set('session.cookie_httponly', (string)$this->config['cookie_httponly']);
            ini_set('session.cookie_secure', (string)$this->config['cookie_secure']);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_samesite', $this->config['cookie_samesite']);
            ini_set('session.gc_maxlifetime', (string)$this->config['session_lifetime']);
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
            
            // Custom session name
            session_name($this->config['session_name']);
            
            // Use database for session storage in production
            if (!DEBUG_MODE) {
                session_set_save_handler(
                    [$this, 'sessionOpen'],
                    [$this, 'sessionClose'],
                    [$this, 'sessionRead'],
                    [$this, 'sessionWrite'],
                    [$this, 'sessionDestroy'],
                    [$this, 'sessionGC']
                );
            }
        }
    }
    
    /**
     * Start session with security checks
     */
    public function start(): bool {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        $result = session_start();
        if ($result) {
            $this->started = true;
            $this->validateSession();
            $this->updateActivity();
        }
        
        return $result;
    }
    
    /**
     * Validate session security
     */
    private function validateSession(): void {
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > $this->config['session_lifetime'])) {
            $this->destroy();
            return;
        }
        
        // Check session hijacking
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->destroy();
            return;
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration'] > 300)) {
            $this->regenerate();
        }
    }
    
    /**
     * Update last activity timestamp
     */
    private function updateActivity(): void {
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerate(bool $deleteOld = true): bool {
        $result = session_regenerate_id($deleteOld);
        if ($result) {
            $_SESSION['last_regeneration'] = time();
        }
        return $result;
    }
    
    /**
     * Set session value
     */
    public function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     */
    public function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session has key
     */
    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroy session
     */
    public function destroy(): bool {
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        $this->started = false;
        return session_destroy();
    }
    
    /**
     * Flash message functionality
     */
    public function flash(string $key, ?string $message = null): ?string {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool {
        return $this->has('user_id') && $this->has('user_role');
    }
    
    /**
     * Login user
     */
    public function login(array $userData): void {
        $this->regenerate(true);
        
        $this->set('user_id', $userData['id']);
        $this->set('user_role', $userData['role']);
        $this->set('username', $userData['username']);
        $this->set('login_time', time());
        $this->set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }
    
    /**
     * Logout user
     */
    public function logout(): void {
        $this->destroy();
    }
    
    /**
     * Database session handlers
     */
    public function sessionOpen(string $path, string $name): bool {
        return true;
    }
    
    public function sessionClose(): bool {
        return true;
    }
    
    public function sessionRead(string $id): string {
        try {
            $result = App::db()->fetchOne(
                "SELECT data FROM user_sessions WHERE id = ? AND expires_at > NOW()",
                [$id]
            );
            return $result['data'] ?? '';
        } catch (Exception $e) {
            return '';
        }
    }
    
    public function sessionWrite(string $id, string $data): bool {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $this->config['session_lifetime']);
            App::db()->execute(
                "REPLACE INTO user_sessions (id, data, expires_at, last_activity) VALUES (?, ?, ?, NOW())",
                [$id, $data, $expiresAt]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function sessionDestroy(string $id): bool {
        try {
            App::db()->execute("DELETE FROM user_sessions WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function sessionGC(int $maxLifetime): int {
        try {
            $stmt = App::db()->execute("DELETE FROM user_sessions WHERE expires_at < NOW()");
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }
}
?>