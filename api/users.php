
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../classes/user.php';

session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(array("message" => "Access denied. Admin privileges required."));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single user
            $user->id = $_GET['id'];
            $user_data = $user->readOne();
            
            if ($user_data) {
                http_response_code(200);
                echo json_encode($user_data);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "User not found."));
            }
        } else {
            // Get all users
            $stmt = $user->read();
            $users_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user_item = array(
                    "id" => $id,
                    "username" => $username,
                    "email" => $email,
                    "role" => $role,
                    "auth_type" => $auth_type,
                    "active" => $active,
                    "created_at" => $created_at
                );
                array_push($users_arr, $user_item);
            }
            
            http_response_code(200);
            echo json_encode($users_arr);
        }
        break;

    case 'POST':
        // Create new user
        $data = json_decode(file_get_contents("php://input"));
        
        if (empty($data->username) || empty($data->email) || empty($data->password) || empty($data->role)) {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields."));
            exit();
        }
        
        // Validate email format
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid email format."));
            exit();
        }
        
        // Check if username already exists
        $user->username = $data->username;
        if ($user->usernameExists()) {
            http_response_code(400);
            echo json_encode(array("message" => "Username already exists."));
            exit();
        }
        
        // Check if email already exists
        $user->email = $data->email;
        if ($user->emailExists()) {
            http_response_code(400);
            echo json_encode(array("message" => "Email already exists."));
            exit();
        }
        
        // Set user properties
        $user->username = $data->username;
        $user->email = $data->email;
        $user->password = $data->password;
        $user->role = $data->role;
        $user->auth_type = 'local';
        $user->active = isset($data->active) ? $data->active : true;
        
        if ($user->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "User created successfully."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Unable to create user."));
        }
        break;

    case 'PUT':
        // Update user
        $data = json_decode(file_get_contents("php://input"));
        
        if (empty($data->id) || empty($data->username) || empty($data->email) || empty($data->role)) {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields."));
            exit();
        }
        
        // Validate email format
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid email format."));
            exit();
        }
        
        $user->id = $data->id;
        
        // Check if user exists
        $existing_user = $user->readOne();
        if (!$existing_user) {
            http_response_code(404);
            echo json_encode(array("message" => "User not found."));
            exit();
        }
        
        // Set user properties
        $user->username = $data->username;
        $user->email = $data->email;
        $user->role = $data->role;
        $user->active = isset($data->active) ? $data->active : true;
        
        if ($user->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "User updated successfully."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Unable to update user."));
        }
        break;

    case 'DELETE':
        // Delete user
        $data = json_decode(file_get_contents("php://input"));
        
        if (empty($data->id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Missing user ID."));
            exit();
        }
        
        $user->id = $data->id;
        
        // Check if user exists
        $existing_user = $user->readOne();
        if (!$existing_user) {
            http_response_code(404);
            echo json_encode(array("message" => "User not found."));
            exit();
        }
        
        if ($user->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "User deleted successfully."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Unable to delete user."));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>
