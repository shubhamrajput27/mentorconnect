<?php
/**
 * Enhanced User Controller for MentorConnect API
 * Handles all user-related operations with advanced security and validation
 */

class UserController {
    private $db;
    private $security;
    private $cache;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = SecurityEnhancement::getInstance();
        $this->cache = Cache::getInstance();
    }
    
    /**
     * Get user profile
     */
    public function getProfile($params = []) {
        try {
            $userId = $_SESSION['user_id'];
            $cacheKey = "user_profile_{$userId}";
            
            // Try cache first
            $profile = $this->cache->get($cacheKey);
            if ($profile === false) {
                $sql = "
                    SELECT u.id, u.username, u.email, u.first_name, u.last_name, 
                           u.role, u.profile_photo, u.bio, u.phone, u.location, 
                           u.timezone, u.status, u.created_at,
                           up.theme, up.language, up.email_notifications, up.push_notifications,
                           CASE 
                               WHEN u.role = 'mentor' THEN mp.title
                               ELSE sp.interests
                           END as role_specific_info
                    FROM users u
                    LEFT JOIN user_preferences up ON u.id = up.user_id
                    LEFT JOIN mentor_profiles mp ON u.id = mp.user_id AND u.role = 'mentor'
                    LEFT JOIN student_profiles sp ON u.id = sp.user_id AND u.role = 'student'
                    WHERE u.id = ?
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($profile) {
                    // Get user skills
                    $profile['skills'] = $this->getUserSkills($userId);
                    
                    // Get statistics
                    $profile['statistics'] = $this->getUserStatistics($userId);
                    
                    // Cache for 5 minutes
                    $this->cache->set($cacheKey, $profile, 300);
                } else {
                    return APIResponse::error('User not found', 'USER_NOT_FOUND');
                }
            }
            
            // Remove sensitive information
            unset($profile['email']);
            
            return APIResponse::success($profile);
            
        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            return APIResponse::error('Failed to retrieve profile', 'PROFILE_ERROR');
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($params = []) {
        try {
            $userId = $_SESSION['user_id'];
            $data = $_REQUEST['validated_data'] ?? [];
            
            // Build update query dynamically
            $updateFields = [];
            $updateParams = [];
            
            $allowedFields = ['first_name', 'last_name', 'bio', 'phone', 'location', 'timezone'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $updateParams[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return APIResponse::error('No valid fields to update', 'NO_UPDATE_FIELDS');
            }
            
            $updateParams[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($updateParams);
            
            // Update role-specific profile if provided
            if (isset($data['role_specific'])) {
                $this->updateRoleSpecificProfile($userId, $data['role_specific']);
            }
            
            // Update preferences if provided
            if (isset($data['preferences'])) {
                $this->updateUserPreferences($userId, $data['preferences']);
            }
            
            // Clear cache
            $this->cache->delete("user_profile_{$userId}");
            
            // Log activity
            logActivity($userId, 'profile_update', 'Profile updated successfully');
            
            return APIResponse::success([], 'Profile updated successfully');
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return APIResponse::error('Failed to update profile', 'UPDATE_ERROR');
        }
    }
    
    /**
     * User registration
     */
    public function register($params = []) {
        try {
            $data = $_REQUEST['validated_data'] ?? [];
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$data['email']]);
            
            if ($stmt->rowCount() > 0) {
                return APIResponse::error('Email already registered', 'EMAIL_EXISTS');
            }
            
            // Generate unique username
            $username = $this->generateUniqueUsername($data['email']);
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $this->db->beginTransaction();
            
            try {
                // Insert user
                $stmt = $this->db->prepare("
                    INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $username,
                    strtolower($data['email']),
                    $hashedPassword,
                    $data['first_name'],
                    $data['last_name'],
                    $data['role'],
                    $data['phone'] ?? null
                ]);
                
                $userId = $this->db->lastInsertId();
                
                // Create role-specific profile
                if ($data['role'] === 'mentor') {
                    $stmt = $this->db->prepare("INSERT INTO mentor_profiles (user_id) VALUES (?)");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $this->db->prepare("INSERT INTO student_profiles (user_id) VALUES (?)");
                    $stmt->execute([$userId]);
                }
                
                // Create user preferences with defaults
                $stmt = $this->db->prepare("
                    INSERT INTO user_preferences (user_id, theme, language, email_notifications, push_notifications) 
                    VALUES (?, 'light', 'en', TRUE, TRUE)
                ");
                $stmt->execute([$userId]);
                
                $this->db->commit();
                
                // Log activity
                logActivity($userId, 'user_registration', 'New user registered');
                
                return APIResponse::success([
                    'user_id' => $userId,
                    'username' => $username,
                    'message' => 'Account created successfully'
                ]);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return APIResponse::error('Registration failed', 'REGISTRATION_ERROR');
        }
    }
    
    /**
     * User login
     */
    public function login($params = []) {
        try {
            $data = $_REQUEST['validated_data'] ?? [];
            $email = $data['email'];
            $password = $data['password'];
            $remember = $data['remember'] ?? false;
            
            // Rate limiting
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!$this->security->checkAdvancedRateLimit($email, 'login', 5, 900)) {
                return APIResponse::error('Too many login attempts', 'RATE_LIMITED');
            }
            
            // Get user
            $stmt = $this->db->prepare("
                SELECT id, username, email, password_hash, role, status, email_verified, first_name, last_name
                FROM users 
                WHERE LOWER(email) = LOWER(?)
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->security->logSecurityEvent('failed_login', [
                    'email' => $email,
                    'ip' => $ip
                ]);
                return APIResponse::error('Invalid credentials', 'INVALID_CREDENTIALS');
            }
            
            if ($user['status'] !== 'active') {
                return APIResponse::error('Account is not active', 'ACCOUNT_INACTIVE');
            }
            
            if (!$user['email_verified']) {
                return APIResponse::error('Email not verified', 'EMAIL_NOT_VERIFIED');
            }
            
            // Create session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['fingerprint'] = $this->security->generateSessionFingerprint();
            
            // Store session in database
            $stmt = $this->db->prepare("
                INSERT INTO user_sessions (id, user_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                session_id(),
                $user['id'],
                $ip,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = $this->security->generateSecureToken();
                setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', false, true);
                
                // Store token in database
                $stmt = $this->db->prepare("
                    INSERT INTO remember_tokens (user_id, token, expires_at) 
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                ");
                $stmt->execute([$user['id'], hash('sha256', $token)]);
            }
            
            // Log activity
            logActivity($user['id'], 'login', 'User logged in successfully');
            
            return APIResponse::success([
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $user['role']
                ],
                'session_id' => session_id()
            ]);
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return APIResponse::error('Login failed', 'LOGIN_ERROR');
        }
    }
    
    /**
     * User logout
     */
    public function logout($params = []) {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if ($userId) {
                // Remove session from database
                $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE id = ?");
                $stmt->execute([session_id()]);
                
                // Log activity
                logActivity($userId, 'logout', 'User logged out');
            }
            
            // Destroy session
            $this->security->destroySession();
            
            // Clear remember me cookie
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
                
                // Remove token from database
                $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?");
                $stmt->execute([hash('sha256', $_COOKIE['remember_token'])]);
            }
            
            return APIResponse::success([], 'Logged out successfully');
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return APIResponse::error('Logout failed', 'LOGOUT_ERROR');
        }
    }
    
    /**
     * Search users
     */
    public function search($params = []) {
        try {
            $query = $_GET['q'] ?? '';
            $role = $_GET['role'] ?? 'all';
            $limit = min((int)($_GET['limit'] ?? 20), 50);
            $offset = max((int)($_GET['offset'] ?? 0), 0);
            
            if (strlen($query) < 2) {
                return APIResponse::error('Query too short', 'QUERY_TOO_SHORT');
            }
            
            $whereConditions = ["u.status = 'active'"];
            $params = [];
            
            // Role filter
            if ($role !== 'all') {
                $whereConditions[] = "u.role = ?";
                $params[] = $role;
            }
            
            // Search conditions
            $searchTerms = explode(' ', $query);
            $searchConditions = [];
            
            foreach ($searchTerms as $term) {
                if (strlen(trim($term)) > 1) {
                    $searchConditions[] = "(
                        u.first_name LIKE ? OR 
                        u.last_name LIKE ? OR 
                        u.username LIKE ? OR
                        u.bio LIKE ?
                    )";
                    $searchTerm = '%' . trim($term) . '%';
                    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                }
            }
            
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total results
            $countSql = "SELECT COUNT(*) as total FROM users u WHERE {$whereClause}";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get results
            $sql = "
                SELECT u.id, u.username, u.first_name, u.last_name, u.role, 
                       u.profile_photo, u.bio, u.location, u.created_at
                FROM users u
                WHERE {$whereClause}
                ORDER BY u.first_name, u.last_name
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return APIResponse::paginated($results, $total, floor($offset / $limit) + 1, $limit);
            
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return APIResponse::error('Search failed', 'SEARCH_ERROR');
        }
    }
    
    // Helper methods
    
    private function getUserSkills($userId) {
        $stmt = $this->db->prepare("
            SELECT s.id, s.name, s.category, us.proficiency_level, us.skill_type
            FROM user_skills us
            JOIN skills s ON us.skill_id = s.id
            WHERE us.user_id = ?
            ORDER BY s.category, s.name
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserStatistics($userId) {
        $role = $_SESSION['user_role'];
        $stats = [];
        
        if ($role === 'mentor') {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                    COUNT(*) as total_sessions,
                    AVG(r.rating) as average_rating,
                    COUNT(DISTINCT r.id) as total_reviews
                FROM sessions s
                LEFT JOIN reviews r ON s.id = r.session_id
                WHERE s.mentor_id = ?
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                    COUNT(*) as total_sessions,
                    COUNT(DISTINCT s.mentor_id) as unique_mentors
                FROM sessions s
                WHERE s.student_id = ?
            ");
        }
        
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function generateUniqueUsername($email) {
        $baseUsername = strtolower(explode('@', $email)[0]);
        $username = $baseUsername;
        $counter = 1;
        
        do {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $username = $baseUsername . $counter;
                $counter++;
            } else {
                break;
            }
        } while ($counter < 100);
        
        return $username;
    }
    
    private function updateRoleSpecificProfile($userId, $data) {
        $role = $_SESSION['user_role'];
        
        if ($role === 'mentor') {
            $fields = ['title', 'company', 'experience_years', 'hourly_rate', 'availability', 'languages'];
        } else {
            $fields = ['interests', 'goals', 'preferred_learning_style'];
        }
        
        $updateFields = [];
        $updateParams = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "{$field} = ?";
                $updateParams[] = $data[$field];
            }
        }
        
        if (!empty($updateFields)) {
            $table = $role === 'mentor' ? 'mentor_profiles' : 'student_profiles';
            $updateParams[] = $userId;
            
            $sql = "UPDATE {$table} SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($updateParams);
        }
    }
    
    private function updateUserPreferences($userId, $preferences) {
        $allowedPrefs = ['theme', 'language', 'email_notifications', 'push_notifications'];
        $updateFields = [];
        $updateParams = [];
        
        foreach ($allowedPrefs as $pref) {
            if (isset($preferences[$pref])) {
                $updateFields[] = "{$pref} = ?";
                $updateParams[] = $preferences[$pref];
            }
        }
        
        if (!empty($updateFields)) {
            $updateParams[] = $userId;
            
            $sql = "UPDATE user_preferences SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($updateParams);
        }
    }
}
?>
