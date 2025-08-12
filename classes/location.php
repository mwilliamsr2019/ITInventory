<?php
class Location {
    private $conn;
    private $table_name = "locations";
    
    public $id;
    public $location_name;
    public $description;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY location_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (location_name, description) VALUES (?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->location_name, $this->description]);
    }
    
    public function locationExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE location_name = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->location_name]);
        return $stmt->rowCount() > 0;
    }
}
?>