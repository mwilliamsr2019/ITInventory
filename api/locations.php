<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../classes/location.php';

$database = new Database();
$db = $database->connect();

$location = new Location($db);
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $location->read();
        $locations = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode($locations);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->location_name)) {
            $location->location_name = $data->location_name;
            $location->description = $data->description ?? '';
            
            if ($location->locationExists()) {
                http_response_code(409);
                echo json_encode(array("message" => "Location already exists."));
                return;
            }
            
            if ($location->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Location created."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create location."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Location name is required."));
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>