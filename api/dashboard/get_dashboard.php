<?php

header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db.php";

/* STOCK STATUS FUNCTION */
function getStockStatus($qty, $lower, $upper){

    if($qty == 0){
        return "Out of Stock";
    }

    if($qty < $lower){
        return "Low";
    }

    if($qty <= ($lower + 5)){
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


/* ================= SUMMARY ================= */

$totalProducts = 0;
$totalCategories = 0;
$activeUsers = 0;
$lowStockCount = 0;

/* total products */
$res = $conn->query("SELECT COUNT(*) as total FROM products");
if($res) $totalProducts = (int)$res->fetch_assoc()['total'];

/* total categories */
$res = $conn->query("SELECT COUNT(*) as total FROM categories");
if($res) $totalCategories = (int)$res->fetch_assoc()['total'];

/* active users */
$res = $conn->query("
SELECT COUNT(*) as total 
FROM users 
WHERE status='active'
");
if($res) $activeUsers = (int)$res->fetch_assoc()['total'];

/* low stock count */
$res = $conn->query("
SELECT COUNT(*) as total 
FROM products 
WHERE qty <= (lower_limit + 5)
");
if($res) $lowStockCount = (int)$res->fetch_assoc()['total'];


/* ================= LOW STOCK LIST ================= */

$query = "
SELECT 
id,
name,
qty,
lower_limit,
upper_limit
FROM products
WHERE qty <= (lower_limit + 5)
ORDER BY qty ASC
";

if(!$viewAll){
$query .= " LIMIT $limit OFFSET $offset";
}

$result = $conn->query($query);

$lowStock = [];

if($result){
while($row = $result->fetch_assoc()){

$lowStock[] = [
"id" => (int)$row['id'],
"name" => $row['name'],
"stock" => (int)$row['qty'],
"threshold" => "L:".$row['lower_limit']." / U:".$row['upper_limit'],
"status" => getStockStatus(
$row['qty'],
$row['lower_limit'],
$row['upper_limit']
)
];

}
}


/* ================= PAGINATION ================= */

$totalLow = 0;

$res = $conn->query("
SELECT COUNT(*) as total 
FROM products 
WHERE qty <= (lower_limit + 5)
");

if($res) $totalLow = (int)$res->fetch_assoc()['total'];

$totalPages = ceil($totalLow / $limit);


/* ================= RESPONSE ================= */

echo json_encode([

"summary" => [
"total_products" => $totalProducts,
"total_categories" => $totalCategories,
"active_users" => $activeUsers,
"low_stock_items" => $lowStockCount
],

"low_stock" => $lowStock,

"pagination" => [
"page" => $page,
"total_pages" => $viewAll ? 1 : $totalPages
]

]);

?>
