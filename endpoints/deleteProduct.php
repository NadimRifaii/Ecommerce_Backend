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
function checkExistingProduct($productId, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare("SELECT productName FROM products WHERE productId=?");
  $checkQuery->bind_param("s", $productId);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  return $result->num_rows;
}
function deleteProduct($data, $db)
{
  $connection = $db->getConnection();
  $deleteQuery = $connection->prepare("DELETE FROM products  WHERE productId=?");
  $deleteQuery->bind_param("i", $data->productId);
  $deleteQuery->execute();
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
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
  $data = json_decode(file_get_contents('php://input'));

  $jwt = getallheaders()['Authorization'];
  try {
    $decoded = decodeJWT($jwt);
  } catch (Exception $exc) {
    respond('0', $exc->getMessage());
  }
  $userId = $decoded->data->userId;
  if (checkIfAdmin($userId, $db) > 0) {
    if (!empty($data->productId)) {
      if (checkExistingProduct($data->productId, $db)) {
        deleteProduct($data, $db);
        respond(1, "product was successfully deleted");
      } else {
        respond(0, 'Product doesn\'t exists');
      }
    } else {
      respond(0, "Missing credentials");
    }
  } else {
    respond('0', 'Only admins are allowed to update a product');
  }
} else {
  respond('0', "Invalid request method");
}
