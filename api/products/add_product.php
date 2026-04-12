<?php
include "../../config/db.php";

$name = $_POST['name'];
$category = $_POST['category'];
$qty = $_POST['qty'];
$price = $_POST['price'];
$lower = $_POST['lower'];
$upper = $_POST['upper'];
$description = $_POST['description'];

$image = "";

if(isset($_FILES['image']['name']) && $_FILES['image']['name'] != ""){
    $image = time() . "_" . $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/".$image);
}

$sql = "INSERT INTO products 
(name,category,qty,unit_price,lower_limit,upper_limit,image,description)
VALUES
('$name','$category','$qty','$price','$lower','$upper','$image','$description')";

$conn->query($sql);

echo json_encode(["success"=>true]);