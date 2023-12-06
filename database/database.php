<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,PUT,DELETE,GET');
header('Content-Type:application/json;charset=UTF-8');
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
class Database
{
  private $host;
  private $userName;
  private $password;
  private $dbName;
  private $mysqli;
  public function __construct()
  {
    $this->host = 'localhost';
    $this->userName = 'root';
    $this->password = null;
    $this->dbName = 'ecommerce_v1';
  }
  public function connect()
  {
    $this->mysqli = new mysqli($this->host, $this->userName, $this->password, $this->dbName);
    if ($this->mysqli->connect_error) {
      die('Connection failed: ' . $this->mysqli->connect_error);
    } else {
      echo "<h1>Connection was successful!</h1>";
    }
  }
  public function getConnection()
  {
    return $this->mysqli;
  }
}
$db = new Database();
$db->connect();
