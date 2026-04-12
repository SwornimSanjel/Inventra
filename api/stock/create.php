<?php

error_reporting(0);
ini_set('display_errors',0);

header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$product_id = $data['product_id'];
$movement_type = $data['movement_type'];
$quantity = (int)$data['quantity'];
$notes = $data['notes'];

$full_name = $data['full_name'];
$contact = $data['contact'];

$price = (float)$data['amount_per_piece'];
$payment_status = $data['payment_status'];
$payment_method = $data['payment_method'];

$incoming_status = $data['incoming_status'] ?? null;
$movement_status = $data['movement_status'] ?? null;

$total = $quantity * $price;

/* get product */
$stmt = $conn->prepare("SELECT qty FROM products WHERE id=?");
$stmt->bind_param("i",$product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if(!$product){
echo json_encode(["success"=>false,"message"=>"Product not found"]);
exit;
}

$current_stock = $product['qty'];

/* validate stock out */
if($movement_type == "out" && $quantity > $current_stock){
echo json_encode([
"success"=>false,
"message"=>"Not enough stock"
]);
exit;
}

/* calculate new stock */
if($movement_type == "in"){
$new_stock = $current_stock + $quantity;
}else{
$new_stock = $current_stock - $quantity;
}

/* update qty */
$update = $conn->prepare("UPDATE products SET qty=? WHERE id=?");
$update->bind_param("ii",$new_stock,$product_id);
$update->execute();

/* reference */
$reference = "STK-" . time();

/* insert movement */
$stmt = $conn->prepare("
INSERT INTO stock_movements
(reference,product_id,movement_type,quantity,notes,
full_name,contact,
amount_per_piece,total_amount,
payment_status,payment_method,
incoming_status,movement_status)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"ssisssddsssss",
$reference,
$product_id,
$movement_type,
$quantity,
$notes,
$full_name,
$contact,
$price,
$total,
$payment_status,
$payment_method,
$incoming_status,
$movement_status
);

$stmt->execute();

echo json_encode([
"success"=>true,
"message"=>"Stock updated successfully",
"new_stock"=>$new_stock,
"reference"=>$reference
]);