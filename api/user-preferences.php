<?php
require_once '../config/config.php';
// Database connection is already loaded via config.php

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    switch ($method) {
        case 'GET':
            // Get user preferences
            $stmt = $conn->prepare("
                SELECT preference_key, preference_value 
                FROM user_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $preferences = $stmt->fetchAll();
            
            $formattedPrefs = [];
            foreach ($preferences as $pref) {
                $formattedPrefs[$pref['preference_key']] = json_decode($pref['preference_value'], true);
            }
            
            echo json_encode([
                'success' => true,
                'preferences' => $formattedPrefs
            ]);
            break;
            
        case 'POST':
        case 'PUT':
            // Update preferences
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['preferences'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid preferences data']);
                exit;
            }
            
            $conn->beginTransaction();
            
            foreach ($input['preferences'] as $key => $value) {
                // Validate preference key
                $allowedKeys = [
                    'theme', 'language', 'timezone', 'notifications',
                    'email_frequency', 'privacy_settings', 'dashboard_layout'
                ];
                
                if (!in_array($key, $allowedKeys)) {
                    continue;
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO user_preferences (user_id, preference_key, preference_value, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    preference_value = VALUES(preference_value),
                    updated_at = NOW()
                ");
                
                $stmt->execute([$userId, $key, json_encode($value)]);
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences updated successfully'
            ]);
            break;
            
        case 'DELETE':
            // Reset preferences to defaults
            $stmt = $conn->prepare("DELETE FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences reset to defaults'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    error_log("User preferences error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to manage preferences'
    ]);
}
?>
