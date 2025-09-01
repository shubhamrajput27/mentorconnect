<?php
/**
 * Enhanced API Endpoint Manager for MentorConnect
 * Provides centralized API routing, validation, and error handling
 */

class APIManager {
    private static $instance = null;
    private $routes = [];
    private $middleware = [];
    private $security;
    private $optimizer;
    
    private function __construct() {
        $this->security = SecurityEnhancement::getInstance();
        $this->optimizer = DatabaseOptimizer::getInstance();
        $this->initializeRoutes();
        $this->initializeMiddleware();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize API routes
     */
    private function initializeRoutes() {
        $this->routes = [
            'GET' => [
                '/api/users/profile' => ['controller' => 'UserController', 'method' => 'getProfile'],
                '/api/users/search' => ['controller' => 'UserController', 'method' => 'search'],
                '/api/messages' => ['controller' => 'MessageController', 'method' => 'getMessages'],
                '/api/notifications' => ['controller' => 'NotificationController', 'method' => 'getNotifications'],
                '/api/sessions' => ['controller' => 'SessionController', 'method' => 'getSessions'],
                '/api/mentors/matching' => ['controller' => 'MentorController', 'method' => 'findMatches'],
                '/api/analytics/dashboard' => ['controller' => 'AnalyticsController', 'method' => 'getDashboard'],
                '/api/files' => ['controller' => 'FileController', 'method' => 'getFiles'],
            ],
            'POST' => [
                '/api/users/register' => ['controller' => 'UserController', 'method' => 'register'],
                '/api/users/login' => ['controller' => 'UserController', 'method' => 'login'],
                '/api/users/logout' => ['controller' => 'UserController', 'method' => 'logout'],
                '/api/messages/send' => ['controller' => 'MessageController', 'method' => 'sendMessage'],
                '/api/sessions/create' => ['controller' => 'SessionController', 'method' => 'createSession'],
                '/api/files/upload' => ['controller' => 'FileController', 'method' => 'uploadFile'],
                '/api/reviews/create' => ['controller' => 'ReviewController', 'method' => 'createReview'],
            ],
            'PUT' => [
                '/api/users/profile' => ['controller' => 'UserController', 'method' => 'updateProfile'],
                '/api/sessions/{id}' => ['controller' => 'SessionController', 'method' => 'updateSession'],
                '/api/notifications/{id}/read' => ['controller' => 'NotificationController', 'method' => 'markAsRead'],
            ],
            'DELETE' => [
                '/api/sessions/{id}' => ['controller' => 'SessionController', 'method' => 'deleteSession'],
                '/api/files/{id}' => ['controller' => 'FileController', 'method' => 'deleteFile'],
                '/api/messages/{id}' => ['controller' => 'MessageController', 'method' => 'deleteMessage'],
            ]
        ];
    }
    
    /**
     * Initialize middleware stack
     */
    private function initializeMiddleware() {
        $this->middleware = [
            'cors' => [$this, 'handleCORS'],
            'rate_limit' => [$this, 'checkRateLimit'],
            'auth' => [$this, 'checkAuthentication'],
            'validation' => [$this, 'validateRequest'],
            'security' => [$this, 'securityCheck'],
            'logging' => [$this, 'logRequest']
        ];
    }
    
    /**
     * Handle incoming API request
     */
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Remove base path if necessary
            $basePath = '/mentorconnect';
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
            
            // Apply middleware
            foreach ($this->middleware as $name => $middleware) {
                $result = call_user_func($middleware, $method, $path);
                if ($result !== true) {
                    return $this->sendResponse(['error' => $result], 400);
                }
            }
            
            // Find matching route
            $route = $this->findRoute($method, $path);
            if (!$route) {
                return $this->sendResponse(['error' => 'Route not found'], 404);
            }
            
            // Execute controller method
            return $this->executeController($route, $path);
            
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return $this->sendResponse(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Find matching route
     */
    private function findRoute($method, $path) {
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        foreach ($this->routes[$method] as $pattern => $route) {
            if ($this->matchRoute($pattern, $path)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Match route pattern with path
     */
    private function matchRoute($pattern, $path) {
        // Convert route pattern to regex
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        return preg_match($regex, $path);
    }
    
    /**
     * Execute controller method
     */
    private function executeController($route, $path) {
        $controllerClass = $route['controller'];
        $method = $route['method'];
        
        // Extract path parameters
        $params = $this->extractPathParams($path);
        
        // Load controller
        $controllerFile = __DIR__ . "/../controllers/{$controllerClass}.php";
        if (!file_exists($controllerFile)) {
            return $this->sendResponse(['error' => 'Controller not found'], 500);
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerClass)) {
            return $this->sendResponse(['error' => 'Controller class not found'], 500);
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            return $this->sendResponse(['error' => 'Controller method not found'], 500);
        }
        
        // Execute method with parameters
        $result = call_user_func_array([$controller, $method], [$params]);
        
        return $this->sendResponse($result);
    }
    
    /**
     * Extract parameters from path
     */
    private function extractPathParams($path) {
        $segments = explode('/', trim($path, '/'));
        $params = [];
        
        // Simple parameter extraction - in production, use more sophisticated routing
        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                $params[] = (int)$segment;
            }
        }
        
        return $params;
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    // Middleware methods
    
    /**
     * Handle CORS
     */
    private function handleCORS($method, $path) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        return true;
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit($method, $path) {
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $action = $method . ':' . $path;
        
        if (!$this->security->checkAdvancedRateLimit($identifier, $action, 60, 3600)) {
            return 'Rate limit exceeded';
        }
        
        return true;
    }
    
    /**
     * Check authentication
     */
    private function checkAuthentication($method, $path) {
        // Skip auth for public endpoints
        $publicEndpoints = [
            '/api/users/register',
            '/api/users/login'
        ];
        
        if (in_array($path, $publicEndpoints)) {
            return true;
        }
        
        // Check session authentication
        if (!$this->security->validateSession()) {
            return 'Authentication required';
        }
        
        return true;
    }
    
    /**
     * Validate request data
     */
    private function validateRequest($method, $path) {
        // Get request data
        $data = [];
        
        if ($method === 'GET') {
            $data = $_GET;
        } else {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
        }
        
        // Apply validation rules based on endpoint
        $validationRules = $this->getValidationRules($method, $path);
        
        foreach ($validationRules as $field => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($data[$field])) {
                return "Required field missing: {$field}";
            }
            
            if (isset($data[$field])) {
                try {
                    $data[$field] = $this->security->validateAndSanitize(
                        $data[$field], 
                        $rules['type'] ?? 'general',
                        $rules['options'] ?? []
                    );
                } catch (Exception $e) {
                    return "Validation error for {$field}: " . $e->getMessage();
                }
            }
        }
        
        // Store validated data for use in controller
        $_REQUEST['validated_data'] = $data;
        
        return true;
    }
    
    /**
     * Security checks
     */
    private function securityCheck($method, $path) {
        // Check for suspicious activity
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Basic bot detection
        $suspiciousAgents = ['curl', 'wget', 'python-requests', 'bot', 'crawler'];
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $this->security->logSecurityEvent('suspicious_user_agent', [
                    'user_agent' => $userAgent,
                    'ip' => $ip,
                    'path' => $path
                ]);
                break;
            }
        }
        
        // Check for SQL injection in query parameters
        foreach ($_GET as $key => $value) {
            if ($this->containsSQLInjection($value)) {
                $this->security->logSecurityEvent('sql_injection_attempt', [
                    'parameter' => $key,
                    'value' => $value,
                    'ip' => $ip
                ]);
                return 'Security violation detected';
            }
        }
        
        return true;
    }
    
    /**
     * Log API requests
     */
    private function logRequest($method, $path) {
        $logData = [
            'method' => $method,
            'path' => $path,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        // Log to file or database
        error_log("API Request: " . json_encode($logData));
        
        return true;
    }
    
    /**
     * Get validation rules for specific endpoints
     */
    private function getValidationRules($method, $path) {
        $rules = [];
        
        switch ($path) {
            case '/api/users/register':
                $rules = [
                    'email' => ['required' => true, 'type' => 'email'],
                    'password' => ['required' => true, 'type' => 'password'],
                    'first_name' => ['required' => true, 'type' => 'general'],
                    'last_name' => ['required' => true, 'type' => 'general'],
                    'role' => ['required' => true, 'type' => 'general']
                ];
                break;
                
            case '/api/users/login':
                $rules = [
                    'email' => ['required' => true, 'type' => 'email'],
                    'password' => ['required' => true, 'type' => 'general']
                ];
                break;
                
            case '/api/messages/send':
                $rules = [
                    'receiver_id' => ['required' => true, 'type' => 'numeric'],
                    'message' => ['required' => true, 'type' => 'general'],
                    'subject' => ['required' => false, 'type' => 'general']
                ];
                break;
                
            case '/api/sessions/create':
                $rules = [
                    'mentor_id' => ['required' => true, 'type' => 'numeric'],
                    'title' => ['required' => true, 'type' => 'general'],
                    'scheduled_at' => ['required' => true, 'type' => 'general'],
                    'duration_minutes' => ['required' => false, 'type' => 'numeric']
                ];
                break;
        }
        
        return $rules;
    }
    
    /**
     * Basic SQL injection detection
     */
    private function containsSQLInjection($input) {
        $patterns = [
            '/(\s|^)(select|insert|update|delete|drop|create|alter|exec|execute)\s/i',
            '/union\s+select/i',
            '/\'\s*or\s*\'/i',
            '/\'\s*and\s*\'/i',
            '/--/i',
            '/\/\*/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add custom route
     */
    public function addRoute($method, $path, $controller, $methodName) {
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }
        
        $this->routes[$method][$path] = [
            'controller' => $controller,
            'method' => $methodName
        ];
    }
    
    /**
     * Add custom middleware
     */
    public function addMiddleware($name, $callback) {
        $this->middleware[$name] = $callback;
    }
}

// API Response helper class
class APIResponse {
    public static function success($data = [], $message = 'Success') {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];
    }
    
    public static function error($message, $code = 'GENERAL_ERROR', $data = []) {
        return [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => $data
            ],
            'timestamp' => date('c')
        ];
    }
    
    public static function paginated($data, $total, $page, $limit) {
        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ],
            'timestamp' => date('c')
        ];
    }
}

// Initialize API Manager if this is an API request
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    $apiManager = APIManager::getInstance();
    $apiManager->handleRequest();
}
?>
