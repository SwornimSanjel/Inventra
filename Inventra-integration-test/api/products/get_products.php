<?php
include "../../config/db.php";

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM products WHERE name LIKE '%$search%'";
$result = $conn->query($sql);

$data = [];

while($row = $result->fetch_assoc()){

    $qty = $row['qty'];
    $low = $row['lower_limit'];
    $up  = $row['upper_limit'];

    if($qty == 0){
        $stock = "Out of Stock";
    }
    elseif($qty < $low){
        $stock = "Low";
    }
    elseif($qty >= $low && $qty <= $up){
        $stock = "Medium";
    }
    elseif($qty > $up && $qty <= ($up*1.5)){
        $stock = "Adequate";
    }
    else{
        $stock = "Overstocked";
    }

    if($status != "" && $status != "All" && $status != $stock){
        continue;
    }

    $row['status'] = $stock;
    $data[] = $row;
}

echo json_encode($data);