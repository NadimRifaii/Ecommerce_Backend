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
function checkIfProductExists($productId, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare('SELECT * FROM products where productId=?');
  $checkQuery->bind_param('i', $productId);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  if ($result->num_rows > 0) {
    return 1;
  }
  return 0;
}
function decodeJWT($jwt)
{
  $key = "This is my key";
  $decoded = JWT::decode($jwt, new Key($key, "HS256"));
  return $decoded;
}
function insertOrder($userId, $productId, $orderDate, $db)
{
  $connection = $db->getConnection();
  $insertQuery = $connection->prepare('INSERT INTO orders (userId,productId,OrderDate) VALUES (?,?,?)');
  $insertQuery->bind_param('iis', $userId, $productId, $orderDate);
  $insertQuery->execute();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = json_decode(file_get_contents('php://input'));
  if (!empty($data->productId) && !empty($data->OrderDate)) {
    if (checkIfProductExists($data->productId, $db)) {
      $jwt = getallheaders()['Authorization'];
      try {
        $decoded = decodeJWT($jwt);
      } catch (Exception $exc) {
        respond('0', $exc->getMessage());
      }
      $userId = $decoded->data->userId;
      insertOrder($userId, $data->productId, $data->OrderDate, $db);
      respond('1', "Order was made successfully");
    } else {
      respond('0', "Product doesn't exist");
    }
  } else {
    respond('0', "Missing credentials");
  }
} else {
  respond('0', "Invalid request method");
}
