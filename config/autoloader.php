<?php
/**
 * MentorConnect Autoloader
 * Automatically loads classes and functions
 */

// Prevent direct access
if (!defined('MENTORCONNECT_INIT')) {
    exit('Direct access not allowed');
}

// Autoloader for classes
spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    
    // Possible locations for class files
    $locations = [
        __DIR__ . '/../classes/' . $className . '.php',
        __DIR__ . '/../controllers/' . $className . '.php',
        __DIR__ . '/../models/' . $className . '.php',
        __DIR__ . '/core/' . $className . '.php'
    ];
    
    foreach ($locations as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load core classes if they exist
$coreClasses = [
    'DatabaseManager',
    'SessionManager',
    'SecurityManager',
    'CacheManager',
    'PerformanceMonitor'
];

foreach ($coreClasses as $class) {
    $file = __DIR__ . '/core/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}
