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
function checkIfSeller($userId, $db)
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
  if ($roleId == 1) {
    return 1;
  }
  return -1;
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
function addProductToSeller($userId, $productId, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare('INSERT INTO sells (sellerId,productId) VALUES (?,?)');
  $checkQuery->bind_param('ii', $userId, $productId);
  $checkQuery->execute();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $jwt = getallheaders()['Authorization'];
  try {
    $decoded = decodeJWT($jwt);
  } catch (Exception $exc) {
  }
  $userId = $decoded->data->userId;
  if (checkIfSeller($userId, $db) > 0) {
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->productId)) {
      if (checkIfProductExists($data->productId, $db)) {
        try {
          addProductToSeller($userId, $data->productId, $db);
          respond('1', "Added product to seller");
        } catch (Exception $exc) {
          respond('0', $exc->getMessage());
        }
      } else {
        respond('0', "product doesn't exists");
      }
    } else {
      respond('0', 'Missing credentials');
    }
  } else {
    respond('0', "Only sellers can add products to sell");
  }
} else {
  respond('0', "Invalid request method");
}
