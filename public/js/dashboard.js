document.addEventListener('DOMContentLoaded', function () {
  var tableHead = document.getElementById('dashboardTableHead');
  var tableBody = document.getElementById('dashboardTableBody');
  var panelEyebrow = document.getElementById('dashboardPanelEyebrow');
  var panelTitle = document.getElementById('dashboardPanelTitle');
  var panelDescription = document.getElementById('dashboardPanelDescription');
  var viewAllBtn = document.getElementById('viewAllBtn');
  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-dashboard-view]'));
  var dashboardData = null;
  var activeView = 'low_stock';

  var panelConfig = {
    products: {
      eyebrow: 'Inventory list',
      title: 'All products overview',
      description: 'A full look at all products currently tracked in inventory.',
      href: 'index.php?url=admin/products'
    },
    categories: {
      eyebrow: 'Catalog list',
      title: 'All categories overview',
      description: 'All category groups currently available in the inventory system.',
      href: 'index.php?url=admin/products'
    },
    users: {
      eyebrow: 'Access list',
      title: 'Active users overview',
      description: 'Team members who currently have active access to the system.',
      href: 'index.php?url=admin/users'
    },
    low_stock: {
      eyebrow: 'Priority list',
      title: 'System-wide low stock alerts',
      description: 'Products that are below their threshold or close to running out.',
      href: 'index.php?url=admin/products'
    }
  };

  function setActiveCard(view) {
    cards.forEach(function (card) {
      var isActive = card.getAttribute('data-dashboard-view') === view;
      card.classList.toggle('is-active', isActive);
      card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  }

  function renderProducts(rows) {
    tableHead.innerHTML = '<tr><th>ID</th><th>Product</th><th>Category</th><th style="text-align:center">Stock</th><th style="text-align:center">Price</th><th>Status</th></tr>';
    if (!rows.length) {
      tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No products available right now.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(function (item) {
      var badgeClass = String(item.status || 'adequate').toLowerCase().replace(/\s+/g, '_');
      var label = String(item.status || 'adequate').replace(/_/g, ' ').toUpperCase();
      return '<tr><td>' + item.id + '</td><td>' + escapeHtml(item.name) + '</td><td>' + escapeHtml(item.category_name) + '</td><td style="text-align:center">' + item.stock + '</td><td style="text-align:center">Rs. ' + Number(item.price || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td><td><span class="chip chip-' + badgeClass + '">' + escapeHtml(label) + '</span></td></tr>';
    }).join('');
  }

  function renderCategories(rows) {
    tableHead.innerHTML = '<tr><th>ID</th><th>Category</th><th>Description</th><th style="text-align:center">Products</th></tr>';
    if (!rows.length) {
      tableBody.innerHTML = '<tr><td colspan="4" class="empty-state">No categories available right now.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(function (item) {
      return '<tr><td>' + item.id + '</td><td>' + escapeHtml(item.name) + '</td><td>' + escapeHtml(item.description || 'No description added yet.') + '</td><td style="text-align:center">' + item.product_count + '</td></tr>';
    }).join('');
  }

  function renderUsers(rows) {
    tableHead.innerHTML = '<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th></tr>';
    if (!rows.length) {
      tableBody.innerHTML = '<tr><td colspan="5" class="empty-state">No active users found right now.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(function (item) {
      return '<tr><td>' + item.id + '</td><td>' + escapeHtml(item.full_name) + '</td><td>' + escapeHtml(item.email) + '</td><td>' + escapeHtml(item.role) + '</td><td>' + escapeHtml(item.status) + '</td></tr>';
    }).join('');
  }

  function renderLowStock(rows) {
    tableHead.innerHTML = '<tr><th>ID</th><th>Product</th><th>Category</th><th style="text-align:center">Stock</th><th style="text-align:center">Threshold</th><th>Status</th></tr>';
    if (!rows.length) {
      tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No low stock items right now.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(function (item) {
      var badgeClass = String(item.status || 'adequate').toLowerCase().replace(/\s+/g, '_');
      var label = String(item.status || 'adequate').replace(/_/g, ' ').toUpperCase();
      return '<tr><td>' + item.id + '</td><td>' + escapeHtml(item.name) + '</td><td>' + escapeHtml(item.category_name || 'Uncategorized') + '</td><td style="text-align:center">' + item.stock + '</td><td style="text-align:center">' + escapeHtml(item.threshold) + '</td><td><span class="chip chip-' + badgeClass + '">' + escapeHtml(label) + '</span></td></tr>';
    }).join('');
  }

  function renderView(view) {
    if (!dashboardData) {
      return;
    }

    activeView = view;
    setActiveCard(view);
    panelEyebrow.textContent = panelConfig[view].eyebrow;
    panelTitle.textContent = panelConfig[view].title;
    panelDescription.textContent = panelConfig[view].description;
    viewAllBtn.href = panelConfig[view].href;

    if (view === 'products') {
      renderProducts(dashboardData.products || []);
    } else if (view === 'categories') {
      renderCategories(dashboardData.categories || []);
    } else if (view === 'users') {
      renderUsers(dashboardData.users || []);
    } else {
      renderLowStock(dashboardData.low_stock || []);
    }
  }

  cards.forEach(function (card) {
    function activate() {
      renderView(card.getAttribute('data-dashboard-view'));
    }

    card.addEventListener('click', activate);
    card.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        activate();
      }
    });
  });

  fetch('api/dashboard/get_dashboard.php')
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (!data || data.error) {
        throw new Error(data && data.error ? data.error : 'Dashboard request failed');
      }

      dashboardData = data;
      document.getElementById('totalProducts').textContent = data.summary.total_products || 0;
      document.getElementById('totalCategories').textContent = data.summary.total_categories || 0;
      document.getElementById('activeUsers').textContent = data.summary.active_users || 0;
      document.getElementById('lowStockItems').textContent = data.summary.low_stock_items || 0;
      renderView(activeView);
    })
    .catch(function () {
      tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">Unable to load dashboard data right now.</td></tr>';
    });
});

function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
