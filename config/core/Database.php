<?php
/**
 * Database Singleton Class
 * Provides a singleton instance of the PDO database connection
 */

class Database {
    private static $instance = null;
    private $connection = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connection = getDB();
    }
    
    /**
     * Get the singleton instance of the Database class
     * 
     * @return Database The singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the PDO connection
     * 
     * @return PDO The PDO database connection
     */
    public function getConnection() {
        if ($this->connection === null) {
            $this->connection = getDB();
        }
        return $this->connection;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
