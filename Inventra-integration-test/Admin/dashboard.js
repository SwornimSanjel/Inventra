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

fetch(url)
.then(res => res.json())
.then(data => {

document.getElementById("totalProducts").innerText =
data.summary.total_products;

document.getElementById("totalCategories").innerText =
data.summary.total_categories;

document.getElementById("activeUsers").innerText =
data.summary.active_users;

document.getElementById("lowStockItems").innerText =
data.summary.low_stock_items;


let rows = "";

data.low_stock.forEach(item => {

let badgeClass = item.status
.toLowerCase()
.replace(/\s/g,'-');

rows += `
<tr>
<td>${item.id}</td>
<td>${item.name}</td>
<td class="low">${item.stock}</td>
<td>${item.threshold}</td>
<td>
<span class="badge ${badgeClass}">
${item.status}
</span>
</td>
</tr>
`;

});

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