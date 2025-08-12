<?php
class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $username;
    public $email;
    public $password;
    public $password_hash;
    public $auth_type;
    public $role;
    public $active;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login() {
        $query = "SELECT id, username, email, password_hash, role, auth_type FROM " . $this->table_name . " WHERE username = ? AND active = 1 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->username]);
        
        if ($row = $stmt->fetch()) {
            if (password_verify($this->password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->auth_type = $row['auth_type'];
                return true;
            }
        }
        
        return false;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, email, password_hash, role, auth_type) VALUES (?, ?, ?, ?, ?)";
        
        $this->password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->username, $this->email, $this->password_hash, $this->role, $this->auth_type]);
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET email = ?, role = ?, active = ? WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->email, $this->role, $this->active, $this->id]);
    }
    
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password_hash = ? WHERE id = ?";
        
        $this->password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->password_hash, $this->id]);
    }
    
    public function read() {
        $query = "SELECT id, username, email, role, auth_type, active, created_at FROM " . $this->table_name . " ORDER BY username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    public function readOne() {
        $query = "SELECT id, username, email, role, auth_type, active, created_at FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        return $stmt->fetch();
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->id]);
    }
    
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->username]);
        return $stmt->rowCount() > 0;
    }
    
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->email]);
        return $stmt->rowCount() > 0;
    }
}
?>