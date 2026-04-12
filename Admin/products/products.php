<?php
include "../../config/db.php";

$status = $_GET['status'] ?? '';
$search = strtolower(trim($_GET['search'] ?? ''));

/* pagination */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM products WHERE 1";

if($search){
$sql .= " AND (name LIKE '%$search%' OR category LIKE '%$search%')";
}

/* total rows */
$totalResult = $conn->query($sql);
$totalRows = $totalResult->num_rows;
$totalPages = ceil($totalRows / $limit);

/* apply pagination */
$sql .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

$products = [];

while($row = $result->fetch_assoc()){

$qty = $row['qty'];
$low = $row['lower_limit'];
$up  = $row['upper_limit'];

if($qty == 0){
$stock = "OUT OF STOCK";
}
elseif($qty < $low){
$stock = "LOW";
}
elseif($qty >= $low && $qty <= $up){
$stock = "MEDIUM";
}
elseif($qty > $up && $qty <= ($up*1.5)){
$stock = "ADEQUATE";
}
else{
$stock = "OVERSTOCK";
}

$row['status'] = $stock;
$row['threshold'] = $low."/".$up;

if($status && $stock != $status) continue;

$products[] = $row;
}

$count = $totalRows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products</title>

<link rel="stylesheet" href="../style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
.modal{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.4);
display:none;
align-items:center;
justify-content:center;
z-index:9999;
}

.modal-box{
background:white;
padding:25px;
border-radius:10px;
width:500px;
}

.modal-header{
display:flex;
justify-content:space-between;
margin-bottom:15px;
}

.close{
cursor:pointer;
font-size:22px;
}

.form-grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:10px;
}

.form-grid textarea{
grid-column:span 2;
height:80px;
}

.save-btn{
margin-top:15px;
width:100%;
padding:12px;
background:#111827;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
}

.pagination{
display:flex;
gap:8px;
margin-top:20px;
}

.pagination a{
padding:8px 12px;
border:1px solid #e5e7eb;
border-radius:6px;
text-decoration:none;
color:#111;
font-size:13px;
}

.pagination a.active{
background:#111827;
color:white;
}
</style>

</head>
<body>

<div class="dashboard">

<!-- SIDEBAR -->
<aside class="sidebar">
<div class="sidebar-top">
<div class="logo">
<h2>Inventra</h2>
<p>Inventory Management</p>
</div>

<nav class="menu">

<a href="../index.php">
<i class="fa-solid fa-chart-line"></i>
Dashboard
</a>

<a href="#">
<i class="fa-solid fa-users"></i>
Users
</a>

<a class="active" href="products.php">
<i class="fa-solid fa-box"></i>
Products
</a>

<a href="../stock-update/index.html">
<i class="fa-solid fa-arrows-rotate"></i>
Stock Update
</a>

<a href="#">
<i class="fa-solid fa-gear"></i>
Settings
</a>

</nav>
</div>

<div class="sidebar-bottom">
<button class="logout">
<i class="fa-solid fa-right-from-bracket"></i>
Logout
</button>
</div>
</aside>

<!-- MAIN -->
<div class="main">

<header class="header">
<div class="header-inner">

<div class="search-wrapper">
<i class="fa-solid fa-magnifying-glass"></i>
<input type="text" placeholder="Search anything...">
</div>

<div class="header-right">
<i class="fa-regular fa-bell notification"></i>

<div class="profile">
<div>
<h4>Dipana</h4>
<span>System Admin</span>
</div>
<img src="https://i.pravatar.cc/40">
</div>

</div>
</div>
</header>

<main class="content">
<div class="container">

<div class="page-header">
<h1>Products</h1>

<div class="header-actions">
<div class="left-controls">

<form method="GET" class="table-search">
<input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

<select name="status" onchange="this.form.submit()">
<option value="">All Status</option>
<option value="LOW" <?= $status=="LOW"?'selected':'' ?>>Low</option>
<option value="MEDIUM" <?= $status=="MEDIUM"?'selected':'' ?>>Medium</option>
<option value="OUT OF STOCK" <?= $status=="OUT OF STOCK"?'selected':'' ?>>Out of Stock</option>
<option value="OVERSTOCK" <?= $status=="OVERSTOCK"?'selected':'' ?>>Overstock</option>
<option value="ADEQUATE" <?= $status=="ADEQUATE"?'selected':'' ?>>Adequate</option>
</select>

<button type="submit">Search</button>

<div class="product-count">
<?= $count ?> products
</div>

</form>
</div>

<button class="add-product" onclick="openModal()">
<i class="fa-solid fa-plus"></i>
Add Product
</button>

</div>
</div>

<div class="panel">
<table>

<thead>
<tr>
<th>S/N</th>
<th>PRODUCT NAME</th>
<th>CATEGORY</th>
<th>QUANTITY</th>
<th>THRESHOLD</th>
<th>PRICE</th>
<th>STATUS</th>
<th>ACTIONS</th>
</tr>
</thead>

<tbody>
<?php $i=1; foreach($products as $p): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $p['name'] ?></td>
<td><?= $p['category'] ?></td>
<td><?= $p['qty'] ?></td>
<td><?= $p['threshold'] ?></td>
<td>Rs. <?= $p['unit_price'] ?></td>

<td>
<span class="badge <?= strtolower(str_replace(' ','-',$p['status'])) ?>">
<?= $p['status'] ?>
</span>
</td>

<td>
<i class="fa-solid fa-pen" onclick='editProduct(<?= json_encode($p) ?>)'></i>
<i class="fa-solid fa-trash" onclick="deleteProduct(<?= $p['id'] ?>)"></i>
</td>
</tr>
<?php endforeach; ?>
</tbody>

</table>

</div>

</div>
</main>
</div>
</div>

<script>
function deleteProduct(id){
if(!confirm("Delete product?")) return

fetch("../../api/products/delete_product.php",{
method:"POST",
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:"id="+id
})
.then(res=>res.json())
.then(()=>location.reload())
}
</script>

</body>
</html>