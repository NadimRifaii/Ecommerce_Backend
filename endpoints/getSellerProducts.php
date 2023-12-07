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
function getAllProducts($userId, $db)
{
  $connection = $db->getConnection();
  $getQuery = $connection->prepare("
  SELECT products.* FROM products , sells , users
  WHERE users.userId=sells.sellerId AND
  sells.productId=products.productId
  AND users.userId=?");
  $getQuery->bind_param('i', $userId);
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
function checkIfSellerAdmin($userId, $db)
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
  if ($roleId == 1 || $roleId == 3) {
    return 1;
  }
  return -1;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = json_decode(file_get_contents('php://input'));
  if (!empty($data->sellerId)) {
    $jwt = getallheaders()['Authorization'];
    try {
      $decoded = decodeJWT($jwt);
      $userId = $decoded->data->userId;
      if (checkIfSellerAdmin($userId, $db) > 0) {
        respond('1', getAllProducts($userId, $db));
      } else {
        respond('0', "Only admin and seller of these products can see the list of products");
      }
    } catch (Exception $exc) {
      respond('0', $exc->getMessage());
    }
  } else {
    respond('0', "Missing credentials");
  }
} else {
  respond('0', "Invalid request method");
}
