document.addEventListener("DOMContentLoaded", function(){

fetchDashboard();

/* VIEW ALL BUTTON */
const btn = document.getElementById("viewAllBtn");

btn.addEventListener("click", function(){
window.location.href = "products/products.php?status=low";
});

});

function fetchDashboard(){

let url = "http://localhost/inventory/api/dashboard/get_dashboard.php";

fetch(url)
.then(res => res.json())
.then(data => {

/* SUMMARY */
document.getElementById("totalProducts").innerText =
data.summary.total_products;

document.getElementById("totalCategories").innerText =
data.summary.total_categories;

document.getElementById("activeUsers").innerText =
data.summary.active_users;

document.getElementById("lowStockItems").innerText =
data.summary.low_stock_items;

/* TABLE */
let rows = "";

if(data.low_stock.length === 0){
rows = `

<tr>
<td colspan="5" style="text-align:center">
No low stock items
</td>
</tr>
`;
}else{

data.low_stock.forEach(item => {

let badgeClass = item.status
.toLowerCase()
.replace(/\s/g,'-');

rows += `

<tr>
<td>${item.id}</td>
<td>${item.name}</td>
<td>${item.stock}</td>
<td>${item.threshold}</td>
<td>
<span class="badge ${badgeClass}">
${item.status}
</span>
</td>
</tr>
`;

});

}

document.getElementById("lowStockTable").innerHTML = rows;

})
.catch(err=>{
console.error("Dashboard error:",err);
});

}
