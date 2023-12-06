<?php
include_once('../database/database.php');
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;

function createJWT($data)
{
  $key = "This is my key";
  $payload = [
    'exp' => time() + 3600,
    'data' => [
      'role' => $data->roleId,
      "username" => $data->userName,
      "email" => $data->email
    ]
  ];
  $jwt = JWT::encode($payload, $key, "HS256");
  return $jwt;
}
function respond($status, $message)
{
  echo json_encode([
    'status' => $status,
    'message' => $message
  ]);
}
function checkExistingUser($email, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare("SELECT userId FROM users WHERE email=?");
  $checkQuery->bind_param("s", $email);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  return $result->num_rows;
}
function insertUser($data, $db)
{
  $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT); //variable , algorithm to hash
  $connection = $db->getConnection();
  $insertQuery = $connection->prepare("INSERT INTO users (username,lastname,email,password,roleId) VALUES (?,?,?,?,?)");
  $insertQuery->bind_param("ssssi", $data->userName, $data->lastName, $data->email, $hashedPassword, $data->roleId);
  $insertQuery->execute();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = json_decode(file_get_contents('php://input'));
  if (!empty($data->userName) && !empty($data->lastName) && !empty($data->email) && !empty($data->password) && !empty($data->roleId)) {
    if (checkExistingUser($data->email, $db) == 0) {
      insertUser($data, $db);
      $jwt = createJWT($data);
      respond('1', $jwt);
    } else {
      respond('0', "This email already exists");
    }
  } else {
    respond(0, "Missing credentials");
  }
} else {
  respond('0', "Invalid request method");
}
