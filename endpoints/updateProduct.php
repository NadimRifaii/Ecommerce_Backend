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
function updateProduct($data, $db)
{
  if (!checkExistingProductByName($data->productName, $db)) {
    $connection = $db->getConnection();
    $updateQuery = $connection->prepare("UPDATE products SET productName=?,stockQuantity=?,price=? WHERE productId=?");
    $updateQuery->bind_param("siii", $data->productName, $data->stockQuantity, $data->price, $data->productId);
    $updateQuery->execute();
    return 1;
  } else {
    return 0;
  }
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
function checkExistingProductByName($productName, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare("SELECT productName FROM products WHERE productName=?");
  $checkQuery->bind_param("s", $productName);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  return $result->num_rows;
}
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
  $data = json_decode(file_get_contents('php://input'));

  $jwt = getallheaders()['Authorization'];
  try {
    $decoded = decodeJWT($jwt);
  } catch (Exception $exc) {
    respond('0', $exc->getMessage());
  }
  $userId = $decoded->data->userId;
  if (checkIfAdmin($userId, $db) > 0) {
    if (!empty($data->productName) && !empty($data->price) && !empty($data->stockQuantity)) {
      if (checkExistingProduct($data->productId, $db)) {
        if (updateProduct($data, $db)) {
          respond(1, "product was successfully updated");
        } else {
          respond('0', "Can't update using this name since there's a product that has the same name");
        }
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
