<?php
class Inventory {
    private $conn;
    private $table_name = "inventory";
    
    public $id;
    public $make;
    public $model;
    public $serial_number;
    public $property_number;
    public $warranty_end_date;
    public $excess_date;
    public $use_case;
    public $location_id;
    public $on_site;
    public $description;
    public $created_by;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (make, model, serial_number, property_number, warranty_end_date, excess_date, 
                   use_case, location_id, on_site, description, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $this->make,
            $this->model,
            $this->serial_number,
            $this->property_number,
            $this->warranty_end_date,
            $this->excess_date,
            $this->use_case,
            $this->location_id,
            $this->on_site,
            $this->description,
            $this->created_by
        ]);
    }
    
    public function read($search = '', $limit = 50, $offset = 0) {
        $query = "SELECT i.*, l.location_name, u.username as created_by_username 
                  FROM " . $this->table_name . " i
                  LEFT JOIN locations l ON i.location_id = l.id
                  LEFT JOIN users u ON i.created_by = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (i.make LIKE ? OR i.model LIKE ? OR i.serial_number LIKE ? 
                       OR i.property_number LIKE ? OR i.use_case LIKE ? OR l.location_name LIKE ?)";
            $search_param = "%$search%";
            $params = array_fill(0, 6, $search_param);
        }
        
        $query .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function readOne() {
        $query = "SELECT i.*, l.location_name, u.username as created_by_username 
                  FROM " . $this->table_name . " i
                  LEFT JOIN locations l ON i.location_id = l.id
                  LEFT JOIN users u ON i.created_by = u.id
                  WHERE i.id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id]);
        return $stmt->fetch();
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET make = ?, model = ?, serial_number = ?, property_number = ?, 
                      warranty_end_date = ?, excess_date = ?, use_case = ?, 
                      location_id = ?, on_site = ?, description = ? 
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $this->make,
            $this->model,
            $this->serial_number,
            $this->property_number,
            $this->warranty_end_date,
            $this->excess_date,
            $this->use_case,
            $this->location_id,
            $this->on_site,
            $this->description,
            $this->id
        ]);
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$this->id]);
    }
    
    public function serialNumberExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE serial_number = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->serial_number]);
        return $stmt->rowCount() > 0;
    }
    
    public function propertyNumberExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE property_number = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->property_number]);
        return $stmt->rowCount() > 0;
    }
    
    public function getUseCases() {
        return ['Desktop', 'Laptop', 'Server', 'Network Equipment', 'Storage System', 'Development'];
    }
    
    public function getOnSiteOptions() {
        return ['On Site', 'Remote'];
    }
}
?>