document.addEventListener("DOMContentLoaded", function(){

/* ---------------- LOAD PRODUCTS ---------------- */
async function loadProducts(){
try{
const res = await fetch("/inventory/api/products/list.php");
const products = await res.json();

const select = document.getElementById("product");
select.innerHTML="";

products.forEach(p=>{
select.innerHTML += `
<option value="${p.id}" data-stock="${p.qty}">
${p.name} (Stock: ${p.qty})
</option>`;
});
}catch(e){
console.log("products load error",e);
}
}

loadProducts();


/* ---------------- TOGGLE ---------------- */
const stockInBtn = document.getElementById("stockInBtn");
const stockOutBtn = document.getElementById("stockOutBtn");

const stockInStatus = document.getElementById("stockInStatus");
const stockOutStatus = document.getElementById("stockOutStatus");

stockInBtn.onclick = ()=>{
stockInBtn.classList.add("active");
stockOutBtn.classList.remove("active");
stockInStatus.classList.remove("hidden");
stockOutStatus.classList.add("hidden");
}

stockOutBtn.onclick = ()=>{
stockOutBtn.classList.add("active");
stockInBtn.classList.remove("active");
stockOutStatus.classList.remove("hidden");
stockInStatus.classList.add("hidden");
}


/* ---------------- QTY ---------------- */
const qty = document.getElementById("quantity");

document.getElementById("plus").onclick=()=>{
qty.value = parseInt(qty.value || 0) + 1;
}

document.getElementById("minus").onclick=()=>{
if(qty.value > 1) qty.value--;
}


/* ---------------- TOTAL ---------------- */
const price = document.getElementById("price");
const total = document.getElementById("total");

function calc(){
const q = parseFloat(qty.value || 0);
const p = parseFloat(price.value || 0);
total.value = q * p;
}

qty.addEventListener("input",calc);
price.addEventListener("input",calc);


/* ---------------- PAYMENT TOGGLE ---------------- */
document.querySelectorAll(".payment-toggle button").forEach(btn=>{
btn.addEventListener("click",()=>{
document.querySelectorAll(".payment-toggle button")
.forEach(b=>b.classList.remove("active"));

btn.classList.add("active");
});
});


/* ---------------- SAVE ---------------- */
document.querySelector(".save").addEventListener("click", async ()=>{

try{

const selected = document.getElementById("product");
const stock = parseInt(
selected.options[selected.selectedIndex].dataset.stock
);

const quantity = parseInt(document.getElementById("quantity").value);

/* STOCK OUT VALIDATION */
if(
document.getElementById("stockOutBtn").classList.contains("active")
&& quantity > stock
){
alert("Not enough stock available");
return;
}

const data = {

product_id: document.getElementById("product").value,

movement_type:
document.getElementById("stockOutBtn").classList.contains("active")
? "out" : "in",

quantity: quantity,
notes: document.getElementById("notes").value,

full_name: document.getElementById("fullname").value,
contact: document.getElementById("contact").value,

amount_per_piece: document.getElementById("price").value,

payment_status: document.getElementById("paymentStatus").value,

payment_method:
document.querySelector(".payment-toggle .active")
.innerText.toLowerCase(),

incoming_status:
document.querySelector("input[name='in']:checked")?.value,

movement_status:
document.querySelector("input[name='out']:checked")?.value
};

const res = await fetch("/inventory/api/stock/create.php",{
method:"POST",
headers:{ "Content-Type":"application/json" },
body: JSON.stringify(data)
});

const result = await res.json();

alert(result.message);

/* reset qty after save */
qty.value = 1;
price.value = "";
total.value = "";

}catch(err){
console.error(err);
alert("API Error - check console");
}

});

});