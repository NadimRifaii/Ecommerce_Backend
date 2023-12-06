<?php
include_once('../database/database.php');
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;

function respond($status, $message)
{
  echo json_encode([
    'status' => $status,
    'message' => $message
  ]);
}
function checkExistingProduct($productName, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare("SELECT productName FROM products WHERE productName=?");
  $checkQuery->bind_param("s", $productName);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  return $result->num_rows;
}
function insertProduct($data, $db)
{
  $connection = $db->getConnection();
  $insertQuery = $connection->prepare("INSERT INTO products (productName,stockQuantity,price) VALUES (?,?,?)");
  $insertQuery->bind_param("sii", $data->productName, $data->stockQuantity, $data->price);
  $insertQuery->execute();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = json_decode(file_get_contents('php://input'));
  if (!empty($data->productName) && !empty($data->price) && !empty($data->stockQuantity)) {
    if (!checkExistingProduct($data->productName, $db)) {
      insertProduct($data, $db);
      respond(1, "product was successfully created");
    } else {
      respond(0, 'Product already exists');
    }
  } else {
    respond(0, "Missing credentials");
  }
} else {
  respond('0', "Invalid request method");
}
