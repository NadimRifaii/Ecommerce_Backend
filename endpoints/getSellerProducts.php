<?php
include_once('../database/database.php');
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function respond($status, $message)
{
  echo json_encode([
    'status' => $status,
    'message' => $message
  ]);
}
function getAllProducts($db)
{
  $connection = $db->getConnection();
  $getQuery = $connection->prepare("SELECT * FROM products");
  $getQuery->execute();
  $result = $getQuery->get_result();
  $products = [];
  while ($row = $result->fetch_assoc()) {
    $products[] = $row;
  }
  return $products;
}
function decodeJWT($jwt)
{
  $key = "This is my key";
  $decoded = JWT::decode($jwt, new Key($key, "HS256"));
  return $decoded;
}
function checkIfAdmin($userId, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare('SELECT roleId FROM users where userId=?');
  $checkQuery->bind_param('i', $userId);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  if ($result->num_rows == 0) {
    return -1;
  }
  $roleId = $result->fetch_assoc()['roleId'];
  if ($roleId == 3) {
    return 1;
  }
  return -1;
}
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

  $jwt = getallheaders()['Authorization'];
  try {
    $decoded = decodeJWT($jwt);
  } catch (Exception $exc) {
    respond('0', $exc->getMessage());
  }
  $userId = $decoded->data->userId;
  if (checkIfAdmin($userId, $db) > 0) {
    respond('1', getAllProducts($db));
  } else {
    respond('0', "Only admin can see the list of products");
  }
} else {
  respond('0', "Invalid request method");
}
