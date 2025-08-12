<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../classes/user.php';

$database = new Database();
$db = $database->connect();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password)) {
    $user->username = $data->username;
    $user->password = $data->password;
    
    if ($user->login()) {
        http_response_code(200);
        
        $token = array(
            "iss" => "http://localhost",
            "aud" => "http://localhost",
            "iat" => time(),
            "nbf" => time(),
            "exp" => time() + 3600,
            "data" => array(
                "id" => $user->id,
                "username" => $user->username,
                "role" => $user->role
            )
        );
        
        echo json_encode(array(
            "message" => "Successful login.",
            "token" => $token,
            "user" => array(
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "role" => $user->role
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Login failed."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete data."));
}
?>