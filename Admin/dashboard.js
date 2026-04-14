<<<<<<< HEAD
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

=======
let currentPage = 1;
let viewAllMode = false;

fetchDashboard();

function fetchDashboard(viewAll = false, page = 1){

viewAllMode = viewAll;
currentPage = page;

let url = "http://localhost/inventory/api/dashboard/get_dashboard.php";

if(viewAll){
url += "?view_all=true";
}else{
url += "?page=" + page;
}

>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
fetch(url)
.then(res => res.json())
.then(data => {

<<<<<<< HEAD
/* SUMMARY */
=======
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
document.getElementById("totalProducts").innerText =
data.summary.total_products;

document.getElementById("totalCategories").innerText =
data.summary.total_categories;

document.getElementById("activeUsers").innerText =
data.summary.active_users;

document.getElementById("lowStockItems").innerText =
data.summary.low_stock_items;

<<<<<<< HEAD
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

=======

let rows = "";

>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
data.low_stock.forEach(item => {

let badgeClass = item.status
.toLowerCase()
.replace(/\s/g,'-');

rows += `
<<<<<<< HEAD

<tr>
<td>${item.id}</td>
<td>${item.name}</td>
<td>${item.stock}</td>
=======
<tr>
<td>${item.id}</td>
<td>${item.name}</td>
<td class="low">${item.stock}</td>
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
<td>${item.threshold}</td>
<td>
<span class="badge ${badgeClass}">
${item.status}
</span>
</td>
</tr>
`;

});

<<<<<<< HEAD
}

document.getElementById("lowStockTable").innerHTML = rows;

})
.catch(err=>{
console.error("Dashboard error:",err);
});

}
=======
document.getElementById("lowStockTable").innerHTML = rows;

document.getElementById("pageInfo").innerText =
"Page " + data.pagination.page + " of " + data.pagination.total_pages;

});
}


/* VIEW ALL */

document.getElementById("viewAllBtn").onclick = (e) => {
e.preventDefault();
fetchDashboard(true,1);
};


/* NEXT */

document.getElementById("nextBtn").onclick = () => {
if(!viewAllMode){
currentPage++;
fetchDashboard(false,currentPage);
}
};


/* PREVIOUS */

document.getElementById("prevBtn").onclick = () => {
if(!viewAllMode && currentPage > 1){
currentPage--;
fetchDashboard(false,currentPage);
}
};
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
