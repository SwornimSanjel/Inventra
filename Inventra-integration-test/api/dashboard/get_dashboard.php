<?php

header("Content-Type: application/json");

require_once __DIR__ . '/../../helpers/session.php';
inventra_bootstrap_session();

/* PROTECT ADMIN ENDPOINT */
if (!inventra_is_authenticated()) {
http_response_code(401);
echo json_encode(["error"=>"Unauthorized"]);
exit;
}

require_once __DIR__ . "/../../config/db.php";


function getStockStatus($qty, $lower, $upper){

    if($qty == 0){
        return "Out of Stock";
    }

    if($qty < $lower){
        return "Low";
    }

    if($qty <= $lower + 5){
        return "Medium";
    }

    if($qty > $upper){
        return "Overstocked";
    }

    return "Adequate";
}


/* PAGINATION */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$viewAll = isset($_GET['view_all']);

$limit = 4;
$offset = ($page - 1) * $limit;


/* SUMMARY */

$totalProducts = $conn->query("SELECT COUNT(*) as total FROM products")
->fetch_assoc()['total'];

$totalCategories = $conn->query("SELECT COUNT(*) as total FROM categories")
->fetch_assoc()['total'];

$activeUsers = $conn->query("
SELECT COUNT(*) as total 
FROM users 
WHERE status='active'
")->fetch_assoc()['total'];

$lowStockCount = $conn->query("
SELECT COUNT(*) as total 
FROM products 
WHERE qty < lower_limit
")->fetch_assoc()['total'];


/* LOW STOCK LIST */

$query = "
SELECT 
id,
name,
qty,
lower_limit,
upper_limit
FROM products
WHERE qty < lower_limit
ORDER BY qty ASC
";

if(!$viewAll){
$query .= " LIMIT $limit OFFSET $offset";
}

$result = $conn->query($query);

$lowStock = [];

while($row = $result->fetch_assoc()){

$lowStock[] = [
"id" => $row['id'],
"name" => $row['name'],
"stock" => $row['qty'],
"threshold" => "L:".$row['lower_limit']." / U:".$row['upper_limit'],
"status" => getStockStatus(
$row['qty'],
$row['lower_limit'],
$row['upper_limit']
)
];

}


/* PAGINATION */

$totalLow = $conn->query("
SELECT COUNT(*) as total 
FROM products 
WHERE qty < lower_limit
")->fetch_assoc()['total'];

$totalPages = ceil($totalLow / $limit);


/* RESPONSE */

echo json_encode([
"summary" => [
"total_products" => (int)$totalProducts,
"total_categories" => (int)$totalCategories,
"active_users" => (int)$activeUsers,
"low_stock_items" => (int)$lowStockCount
],

"low_stock" => $lowStock,

"pagination" => [
"page" => $page,
"total_pages" => $viewAll ? 1 : $totalPages
]

]);

?>
