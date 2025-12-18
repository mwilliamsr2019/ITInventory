<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

class Inventory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function addItem($data) {
        // Validate required fields
        $requiredFields = ['make', 'model', 'serial_number', 'property_number', 'use_case', 'location_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required"];
            }
        }
        
        // Validate use case
        if (!validateUseCase($data['use_case'])) {
            return ['success' => false, 'message' => 'Invalid use case'];
        }
        
        // Check for duplicates
        if ($this->serialNumberExists($data['serial_number'])) {
            return ['success' => false, 'message' => 'Serial number already exists'];
        }
        
        if ($this->propertyNumberExists($data['property_number'])) {
            return ['success' => false, 'message' => 'Property number already exists'];
        }
        
        // Validate dates
        if (!empty($data['warranty_end_date']) && !isValidDate($data['warranty_end_date'])) {
            return ['success' => false, 'message' => 'Invalid warranty end date'];
        }
        
        if (!empty($data['excess_date']) && !isValidDate($data['excess_date'])) {
            return ['success' => false, 'message' => 'Invalid excess date'];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO inventory_items (
                    make, model, serial_number, property_number, warranty_end_date, excess_date,
                    use_case, location_id, on_site, description, assigned_to, purchase_date,
                    purchase_cost, vendor, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['make'],
                $data['model'],
                $data['serial_number'],
                $data['property_number'],
                $data['warranty_end_date'] ?: null,
                $data['excess_date'] ?: null,
                $data['use_case'],
                $data['location_id'],
                isset($data['on_site']) ? (bool)$data['on_site'] : true,
                $data['description'] ?: null,
                $data['assigned_to'] ?: null,
                $data['purchase_date'] ?: null,
                $data['purchase_cost'] ?: null,
                $data['vendor'] ?: null,
                $data['status'] ?: 'active',
                $_SESSION['user_id']
            ]);
            
            $itemId = $this->db->lastInsertId();
            
            // Log audit
            logAudit('inventory_items', $itemId, 'insert', null, $data);
            
            return ['success' => true, 'message' => 'Inventory item added successfully', 'id' => $itemId];
            
        } catch (PDOException $e) {
            error_log("Error adding inventory item: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add inventory item'];
        }
    }
    
    public function updateItem($id, $data) {
        // Get existing item
        $existingItem = $this->getItemById($id);
        if (!$existingItem) {
            return ['success' => false, 'message' => 'Item not found'];
        }
        
        // Validate required fields
        $requiredFields = ['make', 'model', 'serial_number', 'property_number', 'use_case', 'location_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required"];
            }
        }
        
        // Check for duplicate serial number (excluding current item)
        if ($data['serial_number'] !== $existingItem['serial_number'] && 
            $this->serialNumberExists($data['serial_number'])) {
            return ['success' => false, 'message' => 'Serial number already exists'];
        }
        
        // Check for duplicate property number (excluding current item)
        if ($data['property_number'] !== $existingItem['property_number'] && 
            $this->propertyNumberExists($data['property_number'])) {
            return ['success' => false, 'message' => 'Property number already exists'];
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE inventory_items 
                SET make = ?, model = ?, serial_number = ?, property_number = ?, 
                    warranty_end_date = ?, excess_date = ?, use_case = ?, 
                    location_id = ?, on_site = ?, description = ?, assigned_to = ?, 
                    purchase_date = ?, purchase_cost = ?, vendor = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['make'],
                $data['model'],
                $data['serial_number'],
                $data['property_number'],
                $data['warranty_end_date'] ?: null,
                $data['excess_date'] ?: null,
                $data['use_case'],
                $data['location_id'],
                isset($data['on_site']) ? (bool)$data['on_site'] : true,
                $data['description'] ?: null,
                $data['assigned_to'] ?: null,
                $data['purchase_date'] ?: null,
                $data['purchase_cost'] ?: null,
                $data['vendor'] ?: null,
                $data['status'] ?: 'active',
                $id
            ]);
            
            // Log audit
            logAudit('inventory_items', $id, 'update', $existingItem, $data);
            
            return ['success' => true, 'message' => 'Inventory item updated successfully'];
            
        } catch (PDOException $e) {
            error_log("Error updating inventory item: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update inventory item'];
        }
    }
    
    public function deleteItem($id) {
        $existingItem = $this->getItemById($id);
        if (!$existingItem) {
            return ['success' => false, 'message' => 'Item not found'];
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM inventory_items WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log audit
            logAudit('inventory_items', $id, 'delete', $existingItem, null);
            
            return ['success' => true, 'message' => 'Inventory item deleted successfully'];
            
        } catch (PDOException $e) {
            error_log("Error deleting inventory item: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete inventory item'];
        }
    }
    
    public function getItemById($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, l.name as location_name, u.username as created_by_username
            FROM inventory_items i
            JOIN locations l ON i.location_id = l.id
            JOIN users u ON i.created_by = u.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function searchItems($filters = [], $page = 1, $perPage = 20) {
        $whereConditions = [];
        $params = [];
        
        // Build WHERE conditions
        if (!empty($filters['make'])) {
            $whereConditions[] = "i.make LIKE ?";
            $params[] = '%' . $filters['make'] . '%';
        }
        
        if (!empty($filters['model'])) {
            $whereConditions[] = "i.model LIKE ?";
            $params[] = '%' . $filters['model'] . '%';
        }
        
        if (!empty($filters['serial_number'])) {
            $whereConditions[] = "i.serial_number LIKE ?";
            $params[] = '%' . $filters['serial_number'] . '%';
        }
        
        if (!empty($filters['property_number'])) {
            $whereConditions[] = "i.property_number LIKE ?";
            $params[] = '%' . $filters['property_number'] . '%';
        }
        
        if (!empty($filters['use_case'])) {
            $whereConditions[] = "i.use_case = ?";
            $params[] = $filters['use_case'];
        }
        
        if (!empty($filters['location_id'])) {
            $whereConditions[] = "i.location_id = ?";
            $params[] = $filters['location_id'];
        }
        
        if (isset($filters['on_site'])) {
            $whereConditions[] = "i.on_site = ?";
            $params[] = $filters['on_site'];
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "i.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $whereConditions[] = "i.assigned_to LIKE ?";
            $params[] = '%' . $filters['assigned_to'] . '%';
        }
        
        if (!empty($filters['warranty_expiring'])) {
            $whereConditions[] = "i.warranty_end_date <= DATE_ADD(NOW(), INTERVAL ? DAY)";
            $params[] = (int)$filters['warranty_expiring'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM inventory_items i
            JOIN locations l ON i.location_id = l.id
            $whereClause
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get pagination
        $pagination = getPagination($total, $perPage, $page);
        
        // Get items
        $stmt = $this->db->prepare("
            SELECT i.*, l.name as location_name, u.username as created_by_username
            FROM inventory_items i
            JOIN locations l ON i.location_id = l.id
            JOIN users u ON i.created_by = u.id
            $whereClause
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $pagination['current_page'],
            'total_pages' => $pagination['total_pages'],
            'per_page' => $perPage
        ];
    }
    
    public function getItemsForCsv($filters = []) {
        $whereConditions = [];
        $params = [];
        
        // Build WHERE conditions (same as searchItems)
        if (!empty($filters['make'])) {
            $whereConditions[] = "i.make LIKE ?";
            $params[] = '%' . $filters['make'] . '%';
        }
        
        if (!empty($filters['model'])) {
            $whereConditions[] = "i.model LIKE ?";
            $params[] = '%' . $filters['model'] . '%';
        }
        
        if (!empty($filters['serial_number'])) {
            $whereConditions[] = "i.serial_number LIKE ?";
            $params[] = '%' . $filters['serial_number'] . '%';
        }
        
        if (!empty($filters['property_number'])) {
            $whereConditions[] = "i.property_number LIKE ?";
            $params[] = '%' . $filters['property_number'] . '%';
        }
        
        if (!empty($filters['use_case'])) {
            $whereConditions[] = "i.use_case = ?";
            $params[] = $filters['use_case'];
        }
        
        if (!empty($filters['location_id'])) {
            $whereConditions[] = "i.location_id = ?";
            $params[] = $filters['location_id'];
        }
        
        if (isset($filters['on_site'])) {
            $whereConditions[] = "i.on_site = ?";
            $params[] = $filters['on_site'];
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "i.status = ?";
            $params[] = $filters['status'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $stmt = $this->db->prepare("
            SELECT 
                i.make, i.model, i.serial_number, i.property_number,
                i.warranty_end_date, i.excess_date, i.use_case,
                l.name as location, i.on_site, i.description,
                i.assigned_to, i.purchase_date, i.purchase_cost,
                i.vendor, i.status, i.created_at
            FROM inventory_items i
            JOIN locations l ON i.location_id = l.id
            $whereClause
            ORDER BY i.created_at DESC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function importFromCsv($file) {
        // Validate file
        $validation = validateFileUpload($file, ['text/csv', 'text/plain', 'application/csv'], UPLOAD_MAX_SIZE);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Read CSV data
        $csvData = csvToArray($file['tmp_name']);
        if (!$csvData) {
            return ['success' => false, 'message' => 'Failed to read CSV file'];
        }
        
        if (count($csvData) > CSV_MAX_ROWS) {
            return ['success' => false, 'message' => 'CSV file contains too many rows (max: ' . CSV_MAX_ROWS . ')'];
        }
        
        $imported = 0;
        $errors = [];
        
        foreach ($csvData as $index => $row) {
            // Map CSV columns to database fields
            $itemData = [
                'make' => $row['Make'] ?? $row['make'] ?? '',
                'model' => $row['Model'] ?? $row['model'] ?? '',
                'serial_number' => $row['Serial Number'] ?? $row['serial_number'] ?? '',
                'property_number' => $row['Property Number'] ?? $row['property_number'] ?? '',
                'warranty_end_date' => $row['Warranty End Date'] ?? $row['warranty_end_date'] ?? '',
                'excess_date' => $row['Excess Date'] ?? $row['excess_date'] ?? '',
                'use_case' => $row['Use Case'] ?? $row['use_case'] ?? '',
                'location_id' => $this->getLocationIdByName($row['Location'] ?? $row['location'] ?? ''),
                'on_site' => strtolower($row['On Site'] ?? $row['on_site'] ?? 'yes') === 'yes',
                'description' => $row['Description'] ?? $row['description'] ?? '',
                'assigned_to' => $row['Assigned To'] ?? $row['assigned_to'] ?? '',
                'purchase_date' => $row['Purchase Date'] ?? $row['purchase_date'] ?? '',
                'purchase_cost' => $row['Purchase Cost'] ?? $row['purchase_cost'] ?? '',
                'vendor' => $row['Vendor'] ?? $row['vendor'] ?? '',
                'status' => $row['Status'] ?? $row['status'] ?? 'active'
            ];
            
            $result = $this->addItem($itemData);
            if ($result['success']) {
                $imported++;
            } else {
                $errors[] = "Row " . ($index + 2) . ": " . $result['message'];
            }
        }
        
        return [
            'success' => true,
            'message' => "Import completed. $imported items imported successfully.",
            'imported' => $imported,
            'total' => count($csvData),
            'errors' => $errors
        ];
    }
    
    private function serialNumberExists($serialNumber) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM inventory_items WHERE serial_number = ?");
        $stmt->execute([$serialNumber]);
        return $stmt->fetch()['count'] > 0;
    }
    
    private function propertyNumberExists($propertyNumber) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM inventory_items WHERE property_number = ?");
        $stmt->execute([$propertyNumber]);
        return $stmt->fetch()['count'] > 0;
    }
    
    private function getLocationIdByName($locationName) {
        if (empty($locationName)) {
            return 1; // Default location
        }
        
        $stmt = $this->db->prepare("SELECT id FROM locations WHERE name = ?");
        $stmt->execute([$locationName]);
        $location = $stmt->fetch();
        
        if ($location) {
            return $location['id'];
        } else {
            // Create new location if it doesn't exist
            $stmt = $this->db->prepare("INSERT INTO locations (name) VALUES (?)");
            $stmt->execute([$locationName]);
            return $this->db->lastInsertId();
        }
    }
    
    public function getUseCases() {
        return ['Desktop', 'Laptop', 'Server', 'Network Equipment', 'Storage System', 'Development'];
    }
    
    public function getStatuses() {
        return ['active', 'retired', 'excess', 'repair'];
    }
}
?>