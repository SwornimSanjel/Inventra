document.addEventListener('DOMContentLoaded', function () {
  window.inventraStaffStockScriptVersion = 'staff-stock-products-2026-05-10-1';
  var movementType = 'in';
  var paymentMethod = 'cash';
  var products = [];

  var productSelect = document.getElementById('stockProduct');
  var quantityInput = document.getElementById('stockQuantity');
  var priceInput = document.getElementById('stockPrice');
  var totalInput = document.getElementById('stockTotal');
  var form = document.getElementById('stockMovementForm');
  var message = document.getElementById('stockFormMessage');
  var historyTable = document.getElementById('stockHistoryTable');

  if (!productSelect || !quantityInput || !priceInput || !totalInput || !form || !message || !historyTable) {
    return;
  }

  var incomingStatusLabels = {
    order_dispatched: 'Order Dispatched',
    in_transit: 'In Transit',
    received: 'Received at Warehouse'
  };

  var incomingStatusMeta = {
    order_dispatched: 'Shipment has left the supplier location',
    in_transit: 'Goods are currently moving to the warehouse',
    received: 'Inventory has arrived and is ready for intake'
  };

  var movementStatusLabels = {
    dispatched: 'Dispatched from Warehouse',
    hub: 'Stock Received at Hub',
    delivered: 'Delivery Confirmed'
  };

  injectStaffStockStyles();
  enableNativeSelect('paymentStatus');
  buildProductPicker();
  buildIncomingStatusCards();
  bindPaymentMethod();
  bindQuantityControls();
  bindForm();
  setMovement('in');
  recalcTotal();

  Promise.all([loadProducts(), loadHistory()]).catch(function (error) {
    setMessage(error.message || 'Unable to load stock movement data.', 'is-error');
    historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">Unable to load stock movement data.</td></tr>';
  });

  window.setTimeout(function () {
    renderProductOptions('');
  }, 800);

  function apiJson(url, options) {
    return fetch(url, options || {}).then(function (response) {
      return response.text().then(function (text) {
        var data = null;

        try {
          data = text ? JSON.parse(text) : null;
        } catch (error) {
          throw new Error('Server returned an invalid response.');
        }

        if (!response.ok) {
          throw new Error((data && (data.message || data.error || data.detail)) || 'Request failed.');
        }

        return data;
      });
    });
  }

  function setMessage(text, state) {
    message.textContent = text || '';
    message.classList.remove('is-error', 'is-success');

    if (state) {
      message.classList.add(state);
    }
  }

<<<<<<< HEAD
  function setMovement(type) {
    var stockInBtn = document.getElementById('stockInBtn');
    var stockOutBtn = document.getElementById('stockOutBtn');
    var stockInStatusCard = document.getElementById('stockInStatusCard');
    var stockOutStatusCard = document.getElementById('stockOutStatusCard');

    movementType = type;

    if (stockInBtn) {
      stockInBtn.classList.toggle('is-active', type === 'in');
    }

    if (stockOutBtn) {
      stockOutBtn.classList.toggle('is-active', type === 'out');
    }

    if (stockInStatusCard) {
      stockInStatusCard.classList.toggle('is-hidden', type !== 'in');
    }

    if (stockOutStatusCard) {
      stockOutStatusCard.classList.toggle('is-hidden', type !== 'out');
    }

    syncStatusSelections();
  }

  function bindMovementButtons() {
    var stockInBtn = document.getElementById('stockInBtn');
    var stockOutBtn = document.getElementById('stockOutBtn');

    if (stockInBtn) {
      stockInBtn.addEventListener('click', function () { setMovement('in'); });
    }

    if (stockOutBtn) {
      stockOutBtn.addEventListener('click', function () { setMovement('out'); });
    }
  }

  bindMovementButtons();

  function enableNativeSelect(selectId) {
    var select = document.getElementById(selectId);
    var root = select ? select.closest('[data-stock-select-root]') : null;

    if (!select || !root) {
      return;
    }

    select.classList.add('staff-native-select');
    root.classList.add('staff-native-select-root');
=======
  function setFieldInvalid(field, invalid) {
    if (field) {
      field.classList.toggle('is-invalid', Boolean(invalid));
    }
  }

  function setProductInvalid(invalid) {
    var trigger = productSelect.closest('[data-stock-select-root]').querySelector('[data-stock-select-trigger]');
    setFieldInvalid(trigger, invalid);
  }

  function validateStockMovementForm(quantity, amountPerPiece) {
    var partyName = document.getElementById('partyName');
    var partyContact = document.getElementById('partyContact');
    var contactValue = (partyContact.value || '').trim();
    var valid = true;

    setMessage('', '');
    setProductInvalid(false);
    [quantityInput, priceInput, partyName, partyContact].forEach(function (field) {
      setFieldInvalid(field, false);
    });

    if (!productSelect.value) {
      setProductInvalid(true);
      setMessage('Please select a product.', 'is-error');
      valid = false;
    } else if (!Number.isInteger(quantity) || quantity <= 0) {
      setFieldInvalid(quantityInput, true);
      setMessage('Quantity must be greater than 0.', 'is-error');
      valid = false;
    } else if ((partyName.value || '').trim() === '') {
      setFieldInvalid(partyName, true);
      setMessage('Full name is required.', 'is-error');
      valid = false;
    } else if (contactValue === '') {
      setFieldInvalid(partyContact, true);
      setMessage('Contact number is required.', 'is-error');
      valid = false;
    } else if (!/^[0-9+\-\s()]{7,20}$/.test(contactValue)) {
      setFieldInvalid(partyContact, true);
      setMessage('Please enter a valid contact number.', 'is-error');
      valid = false;
    } else if (!Number.isFinite(amountPerPiece) || amountPerPiece <= 0) {
      setFieldInvalid(priceInput, true);
      setMessage('Amount per piece must be greater than 0.', 'is-error');
      valid = false;
    }

    return valid;
>>>>>>> dad9c9816375215b01eaef84051b14b80ad35d8e
  }

  function recalcTotal() {
    var quantity = parseFloat(quantityInput.value || '0');
    var price = parseFloat(priceInput.value || '0');
    totalInput.value = (quantity * price).toFixed(2);
  }

  function selectedStock() {
    var option = productSelect.options[productSelect.selectedIndex];
    return option ? parseInt(option.getAttribute('data-stock') || '0', 10) : 0;
  }

  function normalizeProducts(data) {
    if (Array.isArray(data)) {
      return data;
    }

    if (data && Array.isArray(data.products)) {
      return data.products;
    }

    if (data && Array.isArray(data.data)) {
      return data.data;
    }

    if (data && data.success === false) {
      throw new Error(data.message || data.error || 'Unable to load products.');
    }

    return [];
  }

  function loadProducts() {
    return apiJson('api/products/get_user_products.php')
      .catch(function () {
        return apiJson('api/products/get_products.php');
      })
      .then(function (data) {
        products = normalizeProducts(data);
        productSelect.innerHTML = '<option value="">Select product</option>';

        products.forEach(function (product) {
          var stock = product.qty != null ? product.qty : (product.stock || 0);
          var price = product.unit_price != null ? product.unit_price : (product.price || 0);
          var option = document.createElement('option');

          option.value = product.id;
          option.textContent = String(product.name || 'Unnamed product') + ' (Stock: ' + stock + ')';
          option.setAttribute('data-stock', stock);
          option.setAttribute('data-price', price);
          option.setAttribute('data-name', product.name || '');
          option.setAttribute('data-sku', product.sku || '');
          productSelect.appendChild(option);
        });

        renderProductOptions('');
      });
  }

  function buildProductPicker() {
    var root = productSelect.closest('[data-stock-select-root]');
    var trigger = root ? root.querySelector('[data-stock-select-trigger]') : null;
    var menu = root ? root.querySelector('[data-stock-select-menu]') : null;
    var label = root ? root.querySelector('[data-stock-select-label]') : null;

    if (!root || !trigger || !menu || !label) {
      productSelect.classList.add('staff-native-select');
      return;
    }

    productSelect.classList.add('staff-visually-hidden-select');
    menu.innerHTML = ''
      + '<div class="stock-custom-select__search">'
      + '<input type="search" data-product-search placeholder="Search product or SKU">'
      + '</div>'
      + '<div class="stock-custom-select__options" data-product-options></div>';

    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      toggleSelect(root, true);

      var searchInput = menu.querySelector('[data-product-search]');
      window.setTimeout(function () {
        if (searchInput) {
          searchInput.focus();
        }
      }, 0);
    });

    menu.addEventListener('click', function (event) {
      event.stopPropagation();
    });

    var search = menu.querySelector('[data-product-search]');
    if (search) {
      search.addEventListener('input', function () {
        renderProductOptions(search.value);
      });
    }

    document.addEventListener('click', function (event) {
      if (!root.contains(event.target)) {
        toggleSelect(root, false);
      }
    });
  }

  function renderProductOptions(query) {
    var root = productSelect.closest('[data-stock-select-root]');
    var menu = root ? root.querySelector('[data-stock-select-menu]') : null;
    var optionsWrap = menu ? menu.querySelector('[data-product-options]') : null;
    var label = root ? root.querySelector('[data-stock-select-label]') : null;
    var normalizedQuery = String(query || '').toLowerCase().trim();
    var renderedProducts = products.length > 0 ? products : Array.prototype.slice.call(productSelect.options)
      .filter(function (option) {
        return option.value !== '';
      })
      .map(function (option) {
        return {
          id: option.value,
          name: option.getAttribute('data-name') || option.textContent.replace(/\s+\(Stock:.*?\)\s*$/, ''),
          sku: option.getAttribute('data-sku') || '',
          qty: option.getAttribute('data-stock') || 0,
          unit_price: option.getAttribute('data-price') || 0
        };
      });

    if (!optionsWrap) {
      return;
    }

    optionsWrap.innerHTML = '';

    var matches = renderedProducts.filter(function (product) {
      var searchValue = [
        product.name || '',
        product.sku || ''
      ].join(' ').toLowerCase();

      return normalizedQuery === '' || searchValue.indexOf(normalizedQuery) !== -1;
    });

    if (matches.length === 0) {
      optionsWrap.innerHTML = '<div class="stock-custom-select__empty">' + (renderedProducts.length === 0 ? 'No products available' : 'No products found') + '</div>';
    }

    matches.forEach(function (product) {
      var stock = product.qty != null ? product.qty : (product.stock || 0);
      var button = document.createElement('button');

      button.type = 'button';
      button.className = 'stock-custom-select__option';
      button.textContent = String(product.name || 'Unnamed product') + ' (Stock: ' + stock + ')';
      button.addEventListener('click', function () {
        productSelect.value = String(product.id);
        productSelect.dispatchEvent(new Event('change', { bubbles: true }));
        toggleSelect(root, false);
      });

<<<<<<< HEAD
      optionsWrap.appendChild(button);
    });

    if (label) {
      var selectedOption = productSelect.options[productSelect.selectedIndex];
      label.textContent = selectedOption ? selectedOption.textContent : 'Select product';
    }
  }

  function toggleSelect(root, open) {
    var trigger = root.querySelector('[data-stock-select-trigger]');
    var menu = root.querySelector('[data-stock-select-menu]');

    root.classList.toggle('is-open', open);

    if (trigger) {
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (menu) {
      menu.hidden = !open;
    }
  }
=======
  [quantityInput, priceInput, document.getElementById('partyName'), document.getElementById('partyContact')].forEach(function (field) {
    field.addEventListener('input', function () {
      setFieldInvalid(field, false);
    });
  });

  document.querySelectorAll('.status-option input[type="radio"]').forEach(function (input) {
    input.addEventListener('change', syncStatusSelections);
  });
>>>>>>> dad9c9816375215b01eaef84051b14b80ad35d8e

  productSelect.addEventListener('change', function () {
    setProductInvalid(false);
    var option = productSelect.options[productSelect.selectedIndex];
    var price = option ? parseFloat(option.getAttribute('data-price') || '0') : 0;
    var root = productSelect.closest('[data-stock-select-root]');
    var label = root ? root.querySelector('[data-stock-select-label]') : null;

    if (label && option) {
      label.textContent = option.textContent;
    }

    if (price > 0 && parseFloat(priceInput.value || '0') === 0) {
      priceInput.value = price.toFixed(2);
      recalcTotal();
    }
  });

  function buildIncomingStatusCards() {
    var incomingStatusSelect = document.getElementById('incomingStatus');

<<<<<<< HEAD
    if (!incomingStatusSelect) {
      return;
    }

    var root = incomingStatusSelect.closest('[data-stock-select-root]');
    var parent = root ? root.parentNode : incomingStatusSelect.parentNode;
    var selectedValue = incomingStatusSelect.value || 'order_dispatched';
    var statusList = document.createElement('div');
=======
  document.addEventListener('click', function (event) {
    customSelects.forEach(function (root) {
      if (!root.contains(event.target)) {
        closeCustomSelect(root);
      }
    });
  });

  form.addEventListener('reset', function () {
    window.setTimeout(function () {
      setMovement('in');
      paymentMethod = 'cash';
      setProductInvalid(false);
      [quantityInput, priceInput, document.getElementById('partyName'), document.getElementById('partyContact')].forEach(function (field) {
        setFieldInvalid(field, false);
      });
      document.querySelectorAll('.payment-toggle__btn').forEach(function (item, index) {
        item.classList.toggle('is-active', index === 0);
      });
      setMessage('', '');
      recalcTotal();
      syncStatusSelections();
      customSelects.forEach(function (root) {
        rebuildCustomSelect(root);
      });
    }, 0);
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var quantity = Number((quantityInput.value || '').trim());
    var amountPerPiece = parseFloat(priceInput.value || '0');

    if (!validateStockMovementForm(quantity, amountPerPiece)) {
      return;
    }

    if (movementType === 'out' && quantity > selectedStock()) {
      setMessage('Stock out quantity cannot exceed the current product stock.', 'is-error');
      return;
    }

    var payload = {
      stock_type: movementType === 'out' ? 'Stock Out' : 'Stock In',
      product_id: productSelect.value,
      quantity: quantity,
      movement_notes: document.getElementById('stockNotes').value,
      full_name: document.getElementById('partyName').value,
      contact_number: document.getElementById('partyContact').value,
      amount_per_piece: amountPerPiece,
      total_amount: parseFloat(totalInput.value || '0'),
      payment_status: document.getElementById('paymentStatus').value,
      payment_method: paymentMethod === 'card' ? 'Card' : 'Cash',
      incoming_status: movementType === 'in' && form.querySelector('input[name="incoming_status"]:checked')
        ? incomingStatusLabels[form.querySelector('input[name="incoming_status"]:checked').value] || ''
        : '',
      movement_status: movementType === 'out' && form.querySelector('input[name="movement_status"]:checked')
        ? movementStatusLabels[form.querySelector('input[name="movement_status"]:checked').value] || ''
        : ''
    };
>>>>>>> dad9c9816375215b01eaef84051b14b80ad35d8e

    if (root) {
      root.classList.add('is-hidden');
    } else {
      incomingStatusSelect.hidden = true;
    }

    statusList.className = 'status-list status-list--generated';

    Object.keys(incomingStatusLabels).forEach(function (value) {
      var label = document.createElement('label');
      var input = document.createElement('input');
      var body = document.createElement('span');
      var title = document.createElement('span');
      var meta = document.createElement('span');

      label.className = 'status-option';
      input.type = 'radio';
      input.name = 'incoming_status_card';
      input.value = value;
      input.checked = value === selectedValue;
      body.className = 'status-option__body';
      title.className = 'status-option__title';
      title.textContent = incomingStatusLabels[value];
      meta.className = 'status-option__meta';
      meta.textContent = incomingStatusMeta[value];

      input.addEventListener('change', function () {
        if (input.checked) {
          incomingStatusSelect.value = input.value;
          syncStatusSelections();
        }
      });

      body.appendChild(title);
      body.appendChild(meta);
      label.appendChild(input);
      label.appendChild(body);
      statusList.appendChild(label);
    });

    parent.appendChild(statusList);
    syncStatusSelections();
  }

  function bindPaymentMethod() {
    document.querySelectorAll('.payment-toggle__btn').forEach(function (button) {
      button.addEventListener('click', function () {
        paymentMethod = button.getAttribute('data-method') || 'cash';

        document.querySelectorAll('.payment-toggle__btn').forEach(function (item) {
          item.classList.remove('is-active');
        });

        button.classList.add('is-active');
      });
    });
  }

  function bindQuantityControls() {
    var quantityPlus = document.getElementById('quantityPlus');
    var quantityMinus = document.getElementById('quantityMinus');

    if (quantityPlus) {
      quantityPlus.addEventListener('click', function () {
        quantityInput.value = parseInt(quantityInput.value || '0', 10) + 1;
        recalcTotal();
      });
    }

    if (quantityMinus) {
      quantityMinus.addEventListener('click', function () {
        quantityInput.value = Math.max(1, parseInt(quantityInput.value || '1', 10) - 1);
        recalcTotal();
      });
    }

    quantityInput.addEventListener('input', recalcTotal);
    priceInput.addEventListener('input', recalcTotal);

    document.querySelectorAll('input[name="movement_status"]').forEach(function (input) {
      input.addEventListener('change', syncStatusSelections);
    });
  }

  function bindForm() {
    form.addEventListener('reset', function () {
      window.setTimeout(function () {
        setMovement('in');
        paymentMethod = 'cash';
        document.querySelectorAll('.payment-toggle__btn').forEach(function (item, index) {
          item.classList.toggle('is-active', index === 0);
        });
        document.querySelectorAll('input[name="incoming_status_card"]').forEach(function (input) {
          input.checked = input.value === 'order_dispatched';
        });
        var incomingStatusSelect = document.getElementById('incomingStatus');
        if (incomingStatusSelect) {
          incomingStatusSelect.value = 'order_dispatched';
        }
        setMessage('', '');
        recalcTotal();
        syncStatusSelections();
        renderProductOptions('');
      }, 0);
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      var quantity = parseInt(quantityInput.value || '0', 10);
      var incomingStatusSelect = document.getElementById('incomingStatus');
      var movementStatusInput = form.querySelector('input[name="movement_status"]:checked');
      var paymentStatus = document.getElementById('paymentStatus');

      if (!productSelect.value) {
        setMessage('Please select a product.', 'is-error');
        return;
      }

      if (movementType === 'out' && quantity > selectedStock()) {
        setMessage('Stock out quantity cannot exceed the current product stock.', 'is-error');
        return;
      }

      var payload = {
        movement_type: movementType,
        product_id: productSelect.value,
        quantity: quantity,
        notes: document.getElementById('stockNotes').value,
        full_name: document.getElementById('partyName').value,
        contact: document.getElementById('partyContact').value,
        amount_per_piece: parseFloat(priceInput.value || '0'),
        payment_status: paymentStatus ? paymentStatus.value : 'paid',
        payment_method: paymentMethod,
        incoming_status: movementType === 'in' && incomingStatusSelect ? incomingStatusSelect.value : '',
        movement_status: movementType === 'out' && movementStatusInput ? movementStatusInput.value : ''
      };

      setMessage('Recording movement...', '');

      apiJson('api/stock/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.message || 'Stock update failed.');
          }

          setMessage(data.message || 'Movement recorded successfully.', 'is-success');
          form.reset();
          return Promise.all([loadProducts(), loadHistory()]);
        })
        .catch(function (error) {
          setMessage(error.message || 'Unable to record stock movement.', 'is-error');
        });
    });
  }

  function loadHistory() {
    return apiJson('api/stock/user_list.php').then(function (data) {
      if (!data.success) {
        throw new Error(data.message || 'Unable to load stock movement data.');
      }

      var movements = Array.isArray(data.movements) ? data.movements : [];

      if (movements.length === 0) {
        historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">No stock movements have been recorded yet.</td></tr>';
        return;
      }

      historyTable.innerHTML = movements.map(function (movement) {
        var badgeClass = movement.movement_type === 'out' ? 'chip-low' : 'chip-adequate';
        var statusLabel = movement.movement_type === 'in'
          ? incomingStatusLabels[normalizeStatus(movement.incoming_status)] || movement.incoming_status || 'Order Dispatched'
          : movementStatusLabels[normalizeStatus(movement.movement_status)] || movement.movement_status || 'Dispatched from Warehouse';

        return ''
          + '<tr>'
          + '<td>' + escapeHtml(movement.reference) + '</td>'
          + '<td>' + escapeHtml(movement.product_name) + '</td>'
          + '<td><span class="chip ' + badgeClass + '">' + escapeHtml(String(movement.movement_type).toUpperCase()) + '</span></td>'
          + '<td style="text-align:center">' + escapeHtml(movement.quantity) + '</td>'
          + '<td><span class="stock-status-badge">' + escapeHtml(statusLabel) + '</span></td>'
          + '<td>' + escapeHtml(movement.full_name || 'Not provided') + '</td>'
          + '<td>' + escapeHtml((movement.payment_status || '') + ' / ' + (movement.payment_method || '')) + '</td>'
          + '<td>' + escapeHtml(statusLabel) + '</td>'
          + '<td>' + escapeHtml(movement.created_at_label || '') + '</td>'
          + '</tr>';
      }).join('');
    });
  }

  function normalizeStatus(value) {
    return String(value || '').toLowerCase().trim().replace(/\s+/g, '_');
  }
});

function syncStatusSelections() {
  document.querySelectorAll('.status-option').forEach(function (option) {
    var input = option.querySelector('input[type="radio"]');
    option.classList.toggle('is-selected', !!(input && input.checked));
    option.classList.toggle('status-option--success', !!(input && input.checked && (input.value === 'received' || input.value === 'delivered')));
  });
}

function injectStaffStockStyles() {
  if (document.getElementById('staff-stock-script-style')) {
    return;
  }

  var style = document.createElement('style');
  style.id = 'staff-stock-script-style';
  style.textContent = [
    '.staff-visually-hidden-select{position:absolute!important;opacity:0!important;pointer-events:none!important;width:1px!important;height:1px!important;}',
    '.staff-native-select{display:block!important;position:static!important;opacity:1!important;visibility:visible!important;pointer-events:auto!important;width:100%!important;height:44px!important;min-height:44px!important;appearance:auto!important;background:#f8fafc!important;border:1px solid #cbd5e1!important;border-radius:8px!important;color:#111827!important;padding:0 38px 0 12px!important;font:inherit!important;}',
    '.staff-native-select-root [data-stock-select-trigger],.staff-native-select-root [data-stock-select-menu]{display:none!important;}',
    '.stock-custom-select{position:relative;}',
    '.stock-custom-select__menu{z-index:80;max-height:310px;overflow:auto;}',
    '.stock-custom-select__search{position:sticky;top:0;background:#fff;padding:10px;border-bottom:1px solid #e5e7eb;}',
    '.stock-custom-select__search input{width:100%;height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;font:inherit;outline:none;}',
    '.stock-custom-select__search input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.16);}',
    '.stock-custom-select__options{display:flex;flex-direction:column;gap:4px;padding:6px;}',
    '.stock-custom-select__empty{padding:14px;color:#64748b;text-align:center;font-size:13px;}',
    '.status-list--generated{margin-top:12px;}',
    '.status-list--generated .status-option{cursor:pointer;}'
  ].join('');
  document.head.appendChild(style);
}

function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.stock-custom-select > select[data-stock-select-native]').forEach(function (select) {
    select.hidden = true;
    select.setAttribute('aria-hidden', 'true');
    select.setAttribute('tabindex', '-1');
    select.style.display = 'none';
  });

  var productSelect = document.getElementById('stockProduct');

  if (!productSelect) {
    return;
  }

  var productRoot = productSelect.closest('[data-stock-select-root]');

  if (!productRoot) {
    return;
  }

  var productMenu = productRoot.querySelector('[data-stock-select-menu]');
  var productTrigger = productRoot.querySelector('[data-stock-select-trigger]');

  if (!productMenu || !productTrigger) {
    return;
  }

  function enhanceProductSearch() {
    if (!productMenu || productMenu.querySelector('.stock-custom-select__search')) {
      return;
    }

    var searchWrap = document.createElement('div');
    searchWrap.className = 'stock-custom-select__search-wrap';

    var searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.className = 'stock-custom-select__search';
    searchInput.placeholder = 'Search products';
    searchInput.setAttribute('aria-label', 'Search products');

    var emptyState = document.createElement('div');
    emptyState.className = 'stock-custom-select__empty';
    emptyState.textContent = 'No products found';
    emptyState.hidden = true;

    searchWrap.appendChild(searchInput);
    productMenu.insertBefore(searchWrap, productMenu.firstChild);
    productMenu.appendChild(emptyState);

    function filterProducts() {
      var query = searchInput.value.trim().toLowerCase();
      var visibleCount = 0;

      productMenu.querySelectorAll('[data-stock-select-option]').forEach(function (optionButton) {
        var isPlaceholder = optionButton.getAttribute('data-value') === '';
        var searchText = optionButton.textContent.toLowerCase();
        var matchingOption = productSelect.querySelector('option[value="' + optionButton.getAttribute('data-value') + '"]');

        if (matchingOption && matchingOption.getAttribute('data-sku')) {
          searchText += ' ' + matchingOption.getAttribute('data-sku').toLowerCase();
        }

        var matches = !query || (!isPlaceholder && searchText.indexOf(query) !== -1);
        optionButton.hidden = !matches;

        if (matches && !isPlaceholder) {
          visibleCount += 1;
        }
      });

      emptyState.hidden = visibleCount > 0 || !query;
    }

    searchInput.addEventListener('input', filterProducts);
    searchInput.addEventListener('click', function (event) {
      event.stopPropagation();
    });
  }

  productTrigger.addEventListener('click', function () {
    window.setTimeout(function () {
      enhanceProductSearch();
      var searchInput = productMenu.querySelector('.stock-custom-select__search');

      if (searchInput && productRoot.classList.contains('is-open')) {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        searchInput.focus();
      }
    }, 0);
  });

  new MutationObserver(function () {
    if (productRoot.classList.contains('is-open')) {
      enhanceProductSearch();
    }
  }).observe(productMenu, { childList: true });
});
