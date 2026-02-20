<?php
// Load environment variables
require_once __DIR__ . '/env.php';

class DatabaseConfig {
    private static $instance = null;
    
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    private function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbname = getenv('DB_NAME') ?: 'deal_machan';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->charset = 'utf8mb4';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getHost() { return $this->host; }
    public function getDbName() { return $this->dbname; }
    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public function getCharset() { return $this->charset; }
    
    public function getDSN() {
        return "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
    }
}
