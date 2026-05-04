document.addEventListener('DOMContentLoaded', function () {
  var state = {
    page: 1,
    totalPages: 1,
    showing: 0,
    total: 0
  };

  var tableBody = document.getElementById('userLowStockTableBody');
  var showingText = document.getElementById('userLowStockShowing');
  var prevBtn = document.getElementById('userLowStockPrev');
  var nextBtn = document.getElementById('userLowStockNext');

  if (!tableBody || !showingText || !prevBtn || !nextBtn) {
    return;
  }

  function setLoading() {
    tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">Loading low stock alerts...</td></tr>';
  }

  function renderRows(rows) {
    if (!rows.length) {
      tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No low stock items right now.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(function (item, index) {
      var status = String(item.status || 'medium');
      var badgeClass = status.toLowerCase().replace(/\s+/g, '_');
      var label = status.replace(/_/g, ' ').toUpperCase();
      var stockClass = Number(item.stock || 0) <= Number(item.lower_limit || 0) ? 'qty-low' : 'user-stock-warn';
      var image = String(item.image || '').trim();
      var productIcon = image
        ? '<span class="user-product-icon"><img src="' + escapeHtml(image) + '" alt=""></span>'
        : '<span class="user-product-icon" aria-hidden="true">' + productFallbackIcon() + '</span>';

      return '<tr>' +
        '<td class="user-alert-sku">' + escapeHtml(item.id || ((state.page - 1) * 5 + index + 1)) + '</td>' +
        '<td><div class="user-alert-product">' + productIcon + '<strong>' + escapeHtml(item.name) + '</strong></div></td>' +
        '<td>' + escapeHtml(item.category_name || 'Uncategorized') + '</td>' +
        '<td style="text-align:center"><span class="' + stockClass + '">' + Number(item.stock || 0) + '</span></td>' +
        '<td style="text-align:center">' + escapeHtml(item.threshold) + '</td>' +
        '<td><span class="chip chip-' + badgeClass + '">' + escapeHtml(label) + '</span></td>' +
        '</tr>';
    }).join('');
  }

  function renderPagination(data) {
    state.page = Number(data.pagination && data.pagination.page ? data.pagination.page : state.page);
    state.totalPages = Number(data.pagination && data.pagination.total_pages ? data.pagination.total_pages : 1);
    state.showing = Number(data.pagination && data.pagination.showing ? data.pagination.showing : 0);
    state.total = Number(data.pagination && data.pagination.total_items ? data.pagination.total_items : 0);

    showingText.textContent = 'Showing ' + state.showing + ' of ' + state.total;
    prevBtn.disabled = state.page <= 1;
    nextBtn.disabled = state.page >= state.totalPages;
  }

  function loadDashboard(page) {
    setLoading();

    fetch('api/dashboard/get_user_dashboard.php?page=' + page)
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data || data.error) {
          throw new Error(data && data.error ? data.error : 'Dashboard request failed');
        }

        document.getElementById('userTotalProducts').textContent = formatNumber(data.summary.total_products || 0);
        document.getElementById('userTotalCategories').textContent = formatNumber(data.summary.total_categories || 0);
        document.getElementById('userActiveUsers').textContent = formatNumber(data.summary.active_users || 0);
        document.getElementById('userLowStockItems').textContent = formatNumber(data.summary.low_stock_items || 0);

        renderRows(data.low_stock || []);
        renderPagination(data);
      })
      .catch(function () {
        tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">Unable to load low stock alerts right now.</td></tr>';
        showingText.textContent = 'Showing 0 of 0';
        prevBtn.disabled = true;
        nextBtn.disabled = true;
      });
  }

  prevBtn.addEventListener('click', function () {
    if (state.page > 1) {
      loadDashboard(state.page - 1);
    }
  });

  nextBtn.addEventListener('click', function () {
    if (state.page < state.totalPages) {
      loadDashboard(state.page + 1);
    }
  });

  loadDashboard(1);
});

function productFallbackIcon() {
  return '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString();
}

function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
