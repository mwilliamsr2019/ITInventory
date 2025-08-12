<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $connection;
    
    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'it_inventory';
        $this->username = $_ENV['DB_USER'] ?? 'it_inventory_user';
        $this->password = $_ENV['DB_PASS'] ?? 'SecurePass123!';
    }
    
    public function connect() {
        $this->connection = null;
        
        try {
            $this->connection = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                )
            );
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        
        return $this->connection;
    }
}
?>
