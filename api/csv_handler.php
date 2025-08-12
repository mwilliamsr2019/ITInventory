<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../classes/inventory.php';
include_once '../classes/location.php';

$database = new Database();
$db = $database->connect();

$inventory = new Inventory($db);
$location = new Location($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Export CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.csv"');
        
        $stmt = $inventory->read('', 10000, 0);
        $items = $stmt->fetchAll();
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Make', 'Model', 'Serial Number', 'Property Number', 
            'Warranty End Date', 'Excess Date', 'Use Case', 'Location', 
            'On Site', 'Description', 'Created At'
        ]);
        
        foreach ($items as $item) {
            fputcsv($output, [
                $item['id'],
                $item['make'],
                $item['model'],
                $item['serial_number'],
                $item['property_number'],
                $item['warranty_end_date'],
                $item['excess_date'],
                $item['use_case'],
                $item['location_name'],
                $item['on_site'],
                $item['description'],
                $item['created_at']
            ]);
        }
        
        fclose($output);
        break;
        
    case 'POST':
        // Import CSV
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(array("message" => "No file uploaded or upload error."));
            exit;
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if ($handle === false) {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to read file."));
            exit;
        }
        
        $headers = fgetcsv($handle);
        if ($headers === false) {
            http_response_code(400);
            echo json_encode(array("message" => "Empty file."));
            exit;
        }
        
        $required_headers = ['Make', 'Model', 'Serial Number', 'Property Number', 'Use Case', 'Location'];
        foreach ($required_headers as $header) {
            if (!in_array($header, $headers)) {
                http_response_code(400);
                echo json_encode(array("message" => "Missing required column: $header"));
                exit;
            }
        }
        
        $imported = 0;
        $errors = [];
        $row = 2; // Start from row 2 (after headers)
        
        while (($data = fgetcsv($handle)) !== false) {
            $item_data = array_combine($headers, $data);
            
            // Validate required fields
            if (empty($item_data['Make']) || empty($item_data['Model']) || 
                empty($item_data['Serial Number']) || empty($item_data['Property Number']) || 
                empty($item_data['Use Case']) || empty($item_data['Location'])) {
                $errors[] = "Row $row: Missing required fields";
                $row++;
                continue;
            }
            
            // Check if location exists or create it
            $location->location_name = $item_data['Location'];
            if (!$location->locationExists()) {
                $location->description = 'Imported from CSV';
                $location->create();
            }
            
            // Get location ID
            $query = "SELECT id FROM locations WHERE location_name = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$item_data['Location']]);
            $location_data = $stmt->fetch();
            $location_id = $location_data['id'];
            
            // Check for duplicates
            $inventory->serial_number = $item_data['Serial Number'];
            $inventory->property_number = $item_data['Property Number'];
            
            if ($inventory->serialNumberExists()) {
                $errors[] = "Row $row: Serial number already exists";
                $row++;
                continue;
            }
            
            if ($inventory->propertyNumberExists()) {
                $errors[] = "Row $row: Property number already exists";
                $row++;
                continue;
            }
            
            // Create inventory item
            $inventory->make = $item_data['Make'];
            $inventory->model = $item_data['Model'];
            $inventory->serial_number = $item_data['Serial Number'];
            $inventory->property_number = $item_data['Property Number'];
            $inventory->warranty_end_date = !empty($item_data['Warranty End Date']) ? $item_data['Warranty End Date'] : null;
            $inventory->excess_date = !empty($item_data['Excess Date']) ? $item_data['Excess Date'] : null;
            $inventory->use_case = $item_data['Use Case'];
            $inventory->location_id = $location_id;
            $inventory->on_site = $item_data['On Site'] ?? 'On Site';
            $inventory->description = $item_data['Description'] ?? '';
            $inventory->created_by = $_POST['user_id'] ?? 1;
            
            if ($inventory->create()) {
                $imported++;
            } else {
                $errors[] = "Row $row: Failed to create inventory item";
            }
            
            $row++;
        }
        
        fclose($handle);
        
        http_response_code(200);
        echo json_encode(array(
            "message" => "Import completed",
            "imported" => $imported,
            "errors" => $errors
        ));
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>