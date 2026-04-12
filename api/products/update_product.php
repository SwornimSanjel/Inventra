<?php
include "../../config/db.php";

$id = $_POST['id'];
$name = $_POST['name'];
$category = $_POST['category'];
$qty = $_POST['qty'];
$price = $_POST['price'];
$lower = $_POST['lower'];
$upper = $_POST['upper'];
$description = $_POST['description'];

$sql = "UPDATE products SET
name='$name',
category='$category',
qty='$qty',
unit_price='$price',
lower_limit='$lower',
upper_limit='$upper',
description='$description'
WHERE id=$id";

$conn->query($sql);

echo json_encode(["success"=>true]);