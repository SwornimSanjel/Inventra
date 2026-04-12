<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db.php";

$sql = "SELECT id,name,qty FROM products ORDER BY name";
$result = $conn->query($sql);

$data = [];

while($row = $result->fetch_assoc()){
$data[] = $row;
}

echo json_encode($data);