<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../classes/inventory.php';
include_once '../classes/location.php';

$database = new Database();
$db = $database->connect();

$inventory = new Inventory($db);
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $search = $_GET['search'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $stmt = $inventory->read($search, $limit, $offset);
        $items = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode($items);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->make) && !empty($data->model) && !empty($data->serial_number) && 
            !empty($data->property_number) && !empty($data->use_case) && !empty($data->location_id)) {
            
            $inventory->make = $data->make;
            $inventory->model = $data->model;
            $inventory->serial_number = $data->serial_number;
            $inventory->property_number = $data->property_number;
            $inventory->warranty_end_date = $data->warranty_end_date ?? null;
            $inventory->excess_date = $data->excess_date ?? null;
            $inventory->use_case = $data->use_case;
            $inventory->location_id = $data->location_id;
            $inventory->on_site = $data->on_site ?? 'On Site';
            $inventory->description = $data->description ?? '';
            $inventory->created_by = $data->created_by ?? 1;
            
            // Check for duplicates
            if ($inventory->serialNumberExists()) {
                http_response_code(409);
                echo json_encode(array("message" => "Serial number already exists."));
                return;
            }
            
            if ($inventory->propertyNumberExists()) {
                http_response_code(409);
                echo json_encode(array("message" => "Property number already exists."));
                return;
            }
            
            if ($inventory->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Inventory item created."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create inventory item."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id) && !empty($data->make) && !empty($data->model) && 
            !empty($data->serial_number) && !empty($data->property_number) && 
            !empty($data->use_case) && !empty($data->location_id)) {
            
            $inventory->id = $data->id;
            $inventory->make = $data->make;
            $inventory->model = $data->model;
            $inventory->serial_number = $data->serial_number;
            $inventory->property_number = $data->property_number;
            $inventory->warranty_end_date = $data->warranty_end_date ?? null;
            $inventory->excess_date = $data->excess_date ?? null;
            $inventory->use_case = $data->use_case;
            $inventory->location_id = $data->location_id;
            $inventory->on_site = $data->on_site ?? 'On Site';
            $inventory->description = $data->description ?? '';
            
            // Check for duplicates excluding current record
            $query = "SELECT id FROM inventory WHERE (serial_number = ? OR property_number = ?) AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$inventory->serial_number, $inventory->property_number, $inventory->id]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(array("message" => "Duplicate serial or property number found."));
                return;
            }
            
            if ($inventory->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Inventory item updated."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to update inventory item."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
        }
        break;
        
    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id)) {
            $inventory->id = $data->id;
            
            if ($inventory->delete()) {
                http_response_code(200);
                echo json_encode(array("message" => "Inventory item deleted."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to delete inventory item."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data."));
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>