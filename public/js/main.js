var notifBtn = document.getElementById('notifBtn');
var notifDrop = document.getElementById('notifDropdown');

if (notifBtn && notifDrop) {
  notifBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    notifDrop.classList.toggle('open');
  });

  document.addEventListener('click', function() {
    notifDrop.classList.remove('open');
  });

  notifDrop.addEventListener('click', function(e) {
    e.stopPropagation();
  });
}

var gSearch = document.getElementById('globalSearch');
if (gSearch) {
  gSearch.addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.data-table tbody tr').forEach(function(row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}