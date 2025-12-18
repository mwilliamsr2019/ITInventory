<?php
/**
 * Inventory Management Class
 *
 * Provides comprehensive inventory management functionality with enhanced error handling,
 * performance optimization, and modern PHP practices.
 *
 * @package ITInventory
 * @author IT Inventory Team
 * @version 2.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

class Inventory {
    /**
     * Database instance
     * @var Database
     */
    private Database $db;
    
    /**
     * Valid use cases
     * @var array
     */
    private const VALID_USE_CASES = ['Desktop', 'Laptop', 'Server', 'Network Equipment', 'Storage System', 'Development'];
    
    /**
     * Valid statuses
     * @var array
     */
    private const VALID_STATUSES = ['active', 'retired', 'excess', 'repair'];
    
    /**
     * Required fields for item creation
     * @var array
     */
    private const REQUIRED_FIELDS = ['make', 'model', 'serial_number', 'property_number', 'use_case', 'location_id'];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Add new inventory item with comprehensive validation
     *
     * @param array $data Item data
     * @return array Operation result
     */
    public function addItem(array $data): array {
        try {
            // Validate input data
            $validation = $this->validateItemData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ];
            }
            
            // Check for duplicates within transaction
            return $this->db->transaction(function() use ($data) {
                // Double-check for duplicates (race condition protection)
                if ($this->serialNumberExists($data['serial_number'])) {
                    throw new RuntimeException('Serial number already exists');
                }
                
                if ($this->propertyNumberExists($data['property_number'])) {
                    throw new RuntimeException('Property number already exists');
                }
                
                // Insert item
                $itemId = $this->insertItem($data);
                
                // Log audit trail
                logAudit('inventory_items', $itemId, 'insert', null, $data);
                
                return [
                    'success' => true,
                    'message' => 'Inventory item added successfully',
                    'id' => $itemId
                ];
            });
            
        } catch (RuntimeException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Error adding inventory item: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to add inventory item'
            ];
        }
    }
    
    /**
     * Validate item data comprehensively
     *
     * @param array $data Item data to validate
     * @param int|null $excludeId Item ID to exclude (for updates)
     * @return array Validation result
     */
    private function validateItemData(array $data, ?int $excludeId = null): array {
        $errors = [];
        
        // Validate required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Field '$field' is required";
            }
        }
        
        // Validate use case
        if (isset($data['use_case']) && !in_array($data['use_case'], self::VALID_USE_CASES)) {
            $errors['use_case'] = 'Invalid use case. Allowed: ' . implode(', ', self::VALID_USE_CASES);
        }
        
        // Validate status
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES)) {
            $errors['status'] = 'Invalid status. Allowed: ' . implode(', ', self::VALID_STATUSES);
        }
        
        // Validate dates
        if (isset($data['warranty_end_date']) && !empty($data['warranty_end_date'])) {
            if (!isValidDate($data['warranty_end_date'])) {
                $errors['warranty_end_date'] = 'Invalid warranty end date format (expected YYYY-MM-DD)';
            }
        }
        
        if (isset($data['excess_date']) && !empty($data['excess_date'])) {
            if (!isValidDate($data['excess_date'])) {
                $errors['excess_date'] = 'Invalid excess date format (expected YYYY-MM-DD)';
            }
        }
        
        if (isset($data['purchase_date']) && !empty($data['purchase_date'])) {
            if (!isValidDate($data['purchase_date'])) {
                $errors['purchase_date'] = 'Invalid purchase date format (expected YYYY-MM-DD)';
            }
        }
        
        // Validate numeric fields
        if (isset($data['location_id']) && !is_numeric($data['location_id'])) {
            $errors['location_id'] = 'Location ID must be numeric';
        }
        
        if (isset($data['purchase_cost']) && !empty($data['purchase_cost'])) {
            if (!is_numeric($data['purchase_cost']) || $data['purchase_cost'] < 0) {
                $errors['purchase_cost'] = 'Purchase cost must be a positive number';
            }
        }
        
        // Check for duplicates (only if not excluding current item)
        if ($excludeId === null) {
            if (isset($data['serial_number']) && $this->serialNumberExists($data['serial_number'])) {
                $errors['serial_number'] = 'Serial number already exists';
            }
            
            if (isset($data['property_number']) && $this->propertyNumberExists($data['property_number'])) {
                $errors['property_number'] = 'Property number already exists';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Insert item into database
     *
     * @param array $data Item data
     * @return int Inserted item ID
     * @throws PDOException If insertion fails
     */
    private function insertItem(array $data): int {
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
            $data['warranty_end_date'] ?? null,
            $data['excess_date'] ?? null,
            $data['use_case'],
            $data['location_id'],
            isset($data['on_site']) ? (bool)$data['on_site'] : true,
            $data['description'] ?? null,
            $data['assigned_to'] ?? null,
            $data['purchase_date'] ?? null,
            $data['purchase_cost'] ?? null,
            $data['vendor'] ?? null,
            $data['status'] ?? 'active',
            $_SESSION['user_id'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Update existing inventory item
     *
     * @param int $id Item ID
     * @param array $data Updated item data
     * @return array Operation result
     */
    public function updateItem(int $id, array $data): array {
        try {
            // Get existing item
            $existingItem = $this->getItemById($id);
            if (!$existingItem) {
                return [
                    'success' => false,
                    'message' => 'Item not found'
                ];
            }
            
            // Validate input data
            $validation = $this->validateItemData($data, $id);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors']
                ];
            }
            
            // Check for duplicates excluding current item
            if (isset($data['serial_number']) &&
                $data['serial_number'] !== $existingItem['serial_number'] &&
                $this->serialNumberExists($data['serial_number'])) {
                return [
                    'success' => false,
                    'message' => 'Serial number already exists'
                ];
            }
            
            if (isset($data['property_number']) &&
                $data['property_number'] !== $existingItem['property_number'] &&
                $this->propertyNumberExists($data['property_number'])) {
                return [
                    'success' => false,
                    'message' => 'Property number already exists'
                ];
            }
            
            // Update within transaction
            return $this->db->transaction(function() use ($id, $data, $existingItem) {
                $this->updateItemInDatabase($id, $data);
                
                // Log audit
                logAudit('inventory_items', $id, 'update', $existingItem, $data);
                
                return [
                    'success' => true,
                    'message' => 'Inventory item updated successfully'
                ];
            });
            
        } catch (Exception $e) {
            error_log("Error updating inventory item: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update inventory item'
            ];
        }
    }
    
    /**
     * Update item in database
     *
     * @param int $id Item ID
     * @param array $data Updated data
     * @throws PDOException If update fails
     */
    private function updateItemInDatabase(int $id, array $data): void {
        $stmt = $this->db->prepare("
            UPDATE inventory_items
            SET make = ?, model = ?, serial_number = ?, property_number = ?,
                warranty_end_date = ?, excess_date = ?, use_case = ?,
                location_id = ?, on_site = ?, description = ?, assigned_to = ?,
                purchase_date = ?, purchase_cost = ?, vendor = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['make'] ?? null,
            $data['model'] ?? null,
            $data['serial_number'] ?? null,
            $data['property_number'] ?? null,
            $data['warranty_end_date'] ?? null,
            $data['excess_date'] ?? null,
            $data['use_case'] ?? null,
            $data['location_id'] ?? null,
            isset($data['on_site']) ? (bool)$data['on_site'] : true,
            $data['description'] ?? null,
            $data['assigned_to'] ?? null,
            $data['purchase_date'] ?? null,
            $data['purchase_cost'] ?? null,
            $data['vendor'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }
    
    /**
     * Delete inventory item with soft delete option
     *
     * @param int $id Item ID
     * @param bool $softDelete Use soft delete instead of hard delete
     * @return array Operation result
     */
    public function deleteItem(int $id, bool $softDelete = false): array {
        try {
            $existingItem = $this->getItemById($id);
            if (!$existingItem) {
                return [
                    'success' => false,
                    'message' => 'Item not found'
                ];
            }
            
            return $this->db->transaction(function() use ($id, $existingItem, $softDelete) {
                if ($softDelete) {
                    // Soft delete - mark as deleted but keep in database
                    $stmt = $this->db->prepare("
                        UPDATE inventory_items
                        SET status = 'deleted', deleted_at = NOW()
                        WHERE id = ?
                    ");
                } else {
                    // Hard delete - remove from database
                    $stmt = $this->db->prepare("DELETE FROM inventory_items WHERE id = ?");
                }
                
                $stmt->execute([$id]);
                
                // Log audit
                logAudit('inventory_items', $id, 'delete', $existingItem, null);
                
                return [
                    'success' => true,
                    'message' => 'Inventory item deleted successfully'
                ];
            });
            
        } catch (Exception $e) {
            error_log("Error deleting inventory item: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete inventory item'
            ];
        }
    }
    
    /**
     * Get item by ID with related data
     *
     * @param int $id Item ID
     * @return array|null Item data or null if not found
     */
    public function getItemById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    i.*,
                    l.name as location_name,
                    l.description as location_description,
                    u.username as created_by_username,
                    u.first_name as created_by_first_name,
                    u.last_name as created_by_last_name,
                    assigned_user.username as assigned_to_username,
                    assigned_user.first_name as assigned_to_first_name,
                    assigned_user.last_name as assigned_to_last_name
                FROM inventory_items i
                LEFT JOIN locations l ON i.location_id = l.id
                LEFT JOIN users u ON i.created_by = u.id
                LEFT JOIN users assigned_user ON i.assigned_to = assigned_user.username
                WHERE i.id = ?
                LIMIT 1
            ");
            
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            return $item ?: null;
            
        } catch (Exception $e) {
            error_log("Error getting item by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search items with advanced filtering and pagination
     *
     * @param array $filters Search filters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Search results with pagination
     */
    public function searchItems(array $filters = [], int $page = 1, int $perPage = 20): array {
        try {
            $whereConditions = [];
            $params = [];
            $joins = [];
            
            // Base query
            $baseQuery = "
                FROM inventory_items i
                LEFT JOIN locations l ON i.location_id = l.id
                LEFT JOIN users u ON i.created_by = u.id
                LEFT JOIN users assigned_user ON i.assigned_to = assigned_user.username
            ";
            
            // Build WHERE conditions with proper validation
            $this->buildSearchConditions($filters, $whereConditions, $params, $joins);
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count with optimized query
            $countQuery = "SELECT COUNT(DISTINCT i.id) as total " . $baseQuery . ' ' . $whereClause;
            $total = $this->db->fetchColumn($countQuery, $params);
            
            // Get pagination
            $pagination = getPagination($total, $perPage, $page);
            
            // Get items with optimized query
            $itemQuery = "
                SELECT
                    i.*,
                    l.name as location_name,
                    u.username as created_by_username,
                    assigned_user.username as assigned_to_username,
                    assigned_user.first_name as assigned_to_first_name,
                    assigned_user.last_name as assigned_to_last_name
                " . $baseQuery . ' ' . $whereClause . "
                ORDER BY i.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $params[':limit'] = $pagination['limit'];
            $params[':offset'] = $pagination['offset'];
            
            $stmt = $this->db->prepare($itemQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $items = $stmt->fetchAll();
            
            return [
                'items' => $items,
                'total' => $total,
                'page' => $pagination['current_page'],
                'total_pages' => $pagination['total_pages'],
                'per_page' => $perPage,
                'has_previous' => $pagination['has_previous'],
                'has_next' => $pagination['has_next'],
                'page_range' => $pagination['page_range']
            ];
            
        } catch (Exception $e) {
            error_log("Error searching items: " . $e->getMessage());
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'total_pages' => 0,
                'per_page' => $perPage,
                'has_previous' => false,
                'has_next' => false,
                'page_range' => ['start' => 1, 'end' => 1]
            ];
        }
    }
    
    /**
     * Build search conditions dynamically
     *
     * @param array $filters Search filters
     * @param array $conditions WHERE conditions array (passed by reference)
     * @param array $params Query parameters (passed by reference)
     * @param array $joins JOIN clauses (passed by reference)
     */
    private function buildSearchConditions(array $filters, array &$conditions, array &$params, array &$joins): void {
        $paramIndex = 0;
        
        // Text-based searches with LIKE
        $textFields = ['make', 'model', 'serial_number', 'property_number', 'description', 'assigned_to'];
        foreach ($textFields as $field) {
            if (!empty($filters[$field])) {
                $paramName = ':param' . $paramIndex++;
                $conditions[] = "i.$field LIKE $paramName";
                $params[$paramName] = '%' . $filters[$field] . '%';
            }
        }
        
        // Exact match fields
        $exactFields = ['use_case', 'status', 'location_id'];
        foreach ($exactFields as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $paramName = ':param' . $paramIndex++;
                $conditions[] = "i.$field = $paramName";
                $params[$paramName] = $filters[$field];
            }
        }
        
        // Boolean fields
        if (isset($filters['on_site'])) {
            $paramName = ':param' . $paramIndex++;
            $conditions[] = "i.on_site = $paramName";
            $params[$paramName] = (bool)$filters['on_site'];
        }
        
        // Date-based filters
        if (!empty($filters['warranty_expiring'])) {
            $days = (int)$filters['warranty_expiring'];
            $conditions[] = "i.warranty_end_date IS NOT NULL AND i.warranty_end_date <= DATE_ADD(CURDATE(), INTERVAL $days DAY)";
        }
        
        if (!empty($filters['warranty_expired'])) {
            $conditions[] = "i.warranty_end_date IS NOT NULL AND i.warranty_end_date < CURDATE()";
        }
        
        if (!empty($filters['purchased_after'])) {
            $paramName = ':param' . $paramIndex++;
            $conditions[] = "i.purchase_date >= $paramName";
            $params[$paramName] = $filters['purchased_after'];
        }
        
        if (!empty($filters['purchased_before'])) {
            $paramName = ':param' . $paramIndex++;
            $conditions[] = "i.purchase_date <= $paramName";
            $params[$paramName] = $filters['purchased_before'];
        }
        
        // Price range filters
        if (isset($filters['min_cost']) && is_numeric($filters['min_cost'])) {
            $paramName = ':param' . $paramIndex++;
            $conditions[] = "i.purchase_cost >= $paramName";
            $params[$paramName] = $filters['min_cost'];
        }
        
        if (isset($filters['max_cost']) && is_numeric($filters['max_cost'])) {
            $paramName = ':param' . $paramIndex++;
            $conditions[] = "i.purchase_cost <= $paramName";
            $params[$paramName] = $filters['max_cost'];
        }
        
        // Status-specific filters
        if (!empty($filters['active_only'])) {
            $conditions[] = "i.status = 'active'";
        }
        
        if (!empty($filters['exclude_retired'])) {
            $conditions[] = "i.status != 'retired'";
        }
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