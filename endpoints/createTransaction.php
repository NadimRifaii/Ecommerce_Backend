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
function checkIfOrderExists($orderId, $db)
{
  $connection = $db->getConnection();
  $checkQuery = $connection->prepare('SELECT * FROM orders where orderId=?');
  $checkQuery->bind_param('i', $orderId);
  $checkQuery->execute();
  $result = $checkQuery->get_result();
  if ($result->num_rows > 0) {
    return 1;
  }
  return 0;
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
function decodeJWT($jwt)
{
  $key = "This is my key";
  $decoded = JWT::decode($jwt, new Key($key, "HS256"));
  return $decoded;
}
function insertTransaction($data, $db)
{
  $connection = $db->getConnection();
  $insertQuery = $connection->prepare("INSERT INTO transactions (orderId,TransactionDate,PaymentMethod) VALUES (?,?,?)");
  $insertQuery->bind_param('iss', $data->orderId, $data->transactionDate, $data->paymentMethod);
  $insertQuery->execute();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = json_decode(file_get_contents('php://input'));
  $jwt = getallheaders()['Authorization'];
  try {
    $decoded = decodeJWT($jwt);
    if (!empty($data->orderId) && !empty($data->transactionDate) && !empty($data->paymentMethod)) {
      if (checkIfAdmin($decoded->data->userId, $db) > 0) {
        if (checkIfOrderExists($data->orderId, $db)) {
          insertTransaction($data, $db);
          respond(1, "Transaction created successfully");
        } else {
          respond(0, 'Order is not defined');
        }
      } else {
        respond(0, 'Only admins are allowed to create a transaction');
      }
    } else {
      respond('0', "Missing credentials");
    }
  } catch (Exception $exc) {
    respond('0', $exc->getMessage());
  }
} else {
  respond('0', "Invalid request method");
}
