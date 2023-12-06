<?php
include_once('../database/database.php');
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;

function createJWT($roleName)
{
  $key = "This is my key";
  $payload = [
    'exp' => time() + 3600,
    'data' => [
      'role' => $roleName,
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
function checkExistingUser($data, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare("SELECT * FROM users WHERE email=?");
  $checkQuery->bind_param("s", $data->email);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  $row = $result->fetch_assoc();
  if ($result->num_rows > 0 && password_verify($data->password, $row['password'])) {
    return $row['roleId'];
  } else {
    return -1;
  }
}
function getRole($roleId, $db)
{
  $connection = $db->getConnection();
  $getRoleQuery = $connection->prepare("SELECT r.* FROM roletypes r where roleId=?");
  $getRoleQuery->bind_param('s', $roleId);
  $getRoleQuery->execute();
  $result = $getRoleQuery->get_result();
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $roleName = $row['roleName'];
    return $roleName;
  } else {
    return null;
  }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = json_decode(file_get_contents('php://input'));
  if (!empty($data->email) && !empty($data->password)) {
    if ($roleId = checkExistingUser($data, $db)) {
      if ($roleName = getRole($roleId, $db)) {
        $jwt = createJWT($roleName);
        respond('1', $jwt);
      } else {
        respond('0', "Wrong credentials");
      }
    } else {
      respond('0', "Wrong credentials");
    }
  } else {
    respond(0, "Missing credentials");
  }
} else {
  respond('0', "Invalid request method");
}
