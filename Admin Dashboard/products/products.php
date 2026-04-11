<?php
$status = $_GET['status'] ?? '';
$search = strtolower(trim($_GET['search'] ?? ''));

$products = [
["name"=>"MacBook Pro 14\" M2","category"=>"Electronics","stock"=>4,"threshold"=>"10/40","price"=>"Rs. 1,999","status"=>"LOW"],
["name"=>"HP LaserJet Pro 400","category"=>"Office","stock"=>22,"threshold"=>"15/45","price"=>"Rs. 280","status"=>"MEDIUM"],
["name"=>"Logitech MX Master 3S","category"=>"Accessories","stock"=>0,"threshold"=>"20/50","price"=>"Rs. 99","status"=>"OUT OF STOCK"],
["name"=>"Dell UltraSharp 27\"","category"=>"Electronics","stock"=>45,"threshold"=>"10/30","price"=>"$699.00","status"=>"OVERSTOCK"],
["name"=>"iPad Air Gen 5","category"=>"Electronics","stock"=>18,"threshold"=>"5/20","price"=>"$599.00","status"=>"ADEQUATE"]
];

$count = 0;
foreach($products as $p){
if($status && $p['status']!=$status) continue;

if(
$search &&
!str_contains(strtolower($p['name']),$search) &&
!str_contains(strtolower($p['category']),$search) &&
!str_contains(strtolower($p['status']),$search)
) continue;

$count++;
}
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
.suggestions{
position:absolute;
top:40px;
left:0;
right:0;
background:white;
border:1px solid #e5e7eb;
border-radius:8px;
display:none;
z-index:999;
box-shadow:0 10px 20px rgba(0,0,0,0.05);
}

.suggestions div{
padding:10px;
cursor:pointer;
font-size:13px;
}

.suggestions div:hover{
background:#f3f4f6;
}

.search-box{
position:relative;
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
<a href="../index.html"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
<a><i class="fa-solid fa-users"></i>Users</a>
<a class="active"><i class="fa-solid fa-box"></i>Products</a>
<a><i class="fa-solid fa-arrows-rotate"></i>Stock Update</a>
<a><i class="fa-solid fa-gear"></i>Settings</a>
</nav>

</div>

<div class="sidebar-bottom">
<button class="logout">
<i class="fa-solid fa-right-from-bracket"></i>
Logout
</button>
</div>
</aside>

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

<form method="GET" class="table-search" id="searchForm">

<div class="search-box">
<i class="fa fa-search"></i>

<input 
type="text"
id="productSearch"
name="search"
placeholder="Search products..."
value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
autocomplete="off"
>

<div id="suggestions" class="suggestions"></div>

</div>

<select name="status" onchange="this.form.submit()">
<option value="">All Status</option>
<option value="LOW" <?= $status=="LOW"?'selected':'' ?>>Low</option>
<option value="MEDIUM" <?= $status=="MEDIUM"?'selected':'' ?>>Medium</option>
<option value="OUT OF STOCK" <?= $status=="OUT OF STOCK"?'selected':'' ?>>Out of Stock</option>
<option value="OVERSTOCK" <?= $status=="OVERSTOCK"?'selected':'' ?>>Overstock</option>
<option value="ADEQUATE" <?= $status=="ADEQUATE"?'selected':'' ?>>Adequate</option>
</select>

<button type="submit" class="search-btn-text">
Search
</button>

<div class="product-count">
<?= $count ?> products
</div>

</form>

</div>

<button class="add-product">
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

<?php
$i=1;
foreach($products as $p):

if($status && $p['status']!=$status) continue;

if(
$search &&
!str_contains(strtolower($p['name']),$search) &&
!str_contains(strtolower($p['category']),$search) &&
!str_contains(strtolower($p['status']),$search)
) continue;
?>

<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($p['name']) ?></td>
<td><?= htmlspecialchars($p['category']) ?></td>

<td class="<?= strtolower(str_replace(' ','-',$p['status'])) ?>">
<?= $p['stock'] ?>
</td>

<td><?= $p['threshold'] ?></td>
<td><?= htmlspecialchars($p['price']) ?></td>

<td>
<span class="badge <?= strtolower(str_replace(' ','-',$p['status'])) ?>">
<?= $p['status'] ?>
</span>
</td>

<td>
<i class="fa-solid fa-pen"></i>
<i class="fa-solid fa-trash"></i>
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

const products = [
"MacBook Pro 14\" M2",
"HP LaserJet Pro 400",
"Logitech MX Master 3S",
"Dell UltraSharp 27\"",
"iPad Air Gen 5"
];

const input = document.getElementById("productSearch");
const suggestions = document.getElementById("suggestions");
const form = document.getElementById("searchForm");

input.addEventListener("keyup", function(){

let value = this.value.toLowerCase();
suggestions.innerHTML="";

if(value.length === 0){
suggestions.style.display="none";
return;
}

let filtered = products.filter(p =>
p.toLowerCase().includes(value)
);

filtered.forEach(item =>{

let div = document.createElement("div");
div.innerText = item;

div.onclick = function(){
input.value = item;
suggestions.style.display="none";
form.submit();
}

suggestions.appendChild(div);

});

suggestions.style.display = filtered.length ? "block" : "none";

});

document.addEventListener("click", function(e){
if(!document.querySelector(".search-box").contains(e.target)){
suggestions.style.display="none";
}
});

</script>

</body>
</html>