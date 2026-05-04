document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('stockMovementForm');

  if (!form) {
    return;
  }

  var productSelect = document.getElementById('movementProduct');
  var quantityInput = document.getElementById('movementQuantity');
  var amountInput = document.getElementById('amountPerPiece');
  var totalAmount = document.getElementById('totalAmount');
  var incomingStatus = document.getElementById('incomingStatus');
  var selectedStatus = document.getElementById('selectedIncomingStatus');
  var decreaseQty = document.getElementById('decreaseQty');
  var increaseQty = document.getElementById('increaseQty');
  var message = document.getElementById('stockMovementMessage');
  var page = document.querySelector('.stock-movement-page');
  var statusTitle = document.getElementById('movementStatusTitle');
  var statusOptions = document.getElementById('movementStatusOptions');

  var statusSets = {
    in: [
      {
        value: 'Order Dispatched',
        label: 'Order Dispatched',
        description: 'Supplier has released the stock'
      },
      {
        value: 'In Transit',
        label: 'In Transit',
        description: 'Shipment is on the way'
      },
      {
        value: 'Received at Warehouse',
        label: 'Received at Warehouse',
        description: 'Stock is ready for storage'
      }
    ],
    out: [
      {
        value: 'Dispatched from Warehouse',
        label: 'Dispatched from Warehouse',
        description: 'Vehicle in transit to destination'
      },
      {
        value: 'Stock Received at Hub',
        label: 'Stock Received at Hub',
        description: 'Logging local storage check'
      },
      {
        value: 'Delivery Confirmed',
        label: 'Delivery Confirmed',
        description: 'Final handover to client'
      }
    ]
  };

  function updateChoiceStyles(selector) {
    Array.prototype.slice.call(document.querySelectorAll(selector)).forEach(function (label) {
      var input = label.querySelector('input');
      label.classList.toggle('is-selected', Boolean(input && input.checked));
    });
  }

  function updateTotal() {
    var qty = Math.max(1, Number(quantityInput.value || 1));
    var amount = Math.max(0, Number(amountInput.value || 0));
    quantityInput.value = qty;
    totalAmount.value = 'Rs. ' + (qty * amount).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function updateIncomingStatus() {
    var value = incomingStatus.value || 'No status selected';
    selectedStatus.textContent = value;

    Array.prototype.slice.call(statusOptions.querySelectorAll('.movement-status-option')).forEach(function (option) {
      option.classList.toggle('is-selected', option.getAttribute('data-status') === incomingStatus.value);
    });
  }

  function getMovementType() {
    var checked = form.querySelector('input[name="movement_type"]:checked');
    return checked ? checked.value : 'in';
  }

  function getCheckedValue(name, fallback) {
    var checked = form.querySelector('input[name="' + name + '"]:checked');
    return checked ? checked.value : fallback;
  }

  function getFieldValue(selectors) {
    for (var index = 0; index < selectors.length; index += 1) {
      var field = form.querySelector(selectors[index]);

      if (field && String(field.value || '').trim() !== '') {
        return String(field.value).trim();
      }
    }

    return '';
  }

  function getTextInputFallback(position) {
    var excluded = [quantityInput, amountInput, totalAmount];
    var fields = Array.prototype.slice.call(form.querySelectorAll('input, textarea')).filter(function (field) {
      var type = String(field.type || '').toLowerCase();

      return excluded.indexOf(field) === -1 &&
        ['radio', 'checkbox', 'hidden', 'button', 'submit', 'reset'].indexOf(type) === -1 &&
        String(field.value || '').trim() !== '';
    });

    return fields[position] ? String(fields[position].value || '').trim() : '';
  }

  function numericAmount(value) {
    var amount = Number(String(value || '').replace(/[^0-9.-]/g, ''));
    return Number.isFinite(amount) ? amount : 0;
  }

  function setMessage(text, type) {
    message.textContent = text;
    message.classList.toggle('is-error', type === 'error');
    message.classList.toggle('is-success', type === 'success');
  }

  function statusIcon() {
    return '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
  }

  function renderStatusOptions() {
    var type = getMovementType();
    var options = statusSets[type];
    var selectedValue = incomingStatus.value || (type === 'in' ? 'Received at Warehouse' : 'Dispatched from Warehouse');

    incomingStatus.innerHTML = '<option value="">Select status</option>' + options.map(function (item) {
      return '<option value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</option>';
    }).join('');

    incomingStatus.value = selectedValue;

    statusOptions.innerHTML = options.map(function (item) {
      var selected = item.value === selectedValue ? ' is-selected' : '';
      return '<button class="movement-status-option' + selected + '" type="button" data-status="' + escapeHtml(item.value) + '">' +
        '<span class="movement-status-option__main">' +
        '<span class="movement-status-option__icon" aria-hidden="true">' + statusIcon() + '</span>' +
        '<span><strong>' + escapeHtml(item.label) + '</strong><small>' + escapeHtml(item.description) + '</small></span>' +
        '</span>' +
        '<span class="movement-status-option__radio" aria-hidden="true"></span>' +
        '</button>';
    }).join('');

    selectedStatus.textContent = selectedValue;
  }

  function updateMovementMode() {
    var type = getMovementType();
    page.classList.toggle('is-stock-out', type === 'out');
    statusTitle.textContent = type === 'out' ? 'Movement Status' : 'Incoming Stock Status';
    renderStatusOptions();
  }

  function loadProducts() {
    fetch('api/products/get_user_products.php')
      .then(function (response) { return response.json(); })
      .then(function (products) {
        if (!Array.isArray(products)) {
          throw new Error('Products response was not a list');
        }

        productSelect.innerHTML = '<option value="">Select product</option>' + products.map(function (product) {
          return '<option value="' + escapeHtml(product.id) + '" data-price="' + escapeHtml(product.unit_price || 0) + '">' +
            escapeHtml(product.name) +
            '</option>';
        }).join('');
      })
      .catch(function () {
        productSelect.innerHTML = '<option value="">Unable to load products</option>';
      });
  }

  Array.prototype.slice.call(form.querySelectorAll('input[name="movement_type"]')).forEach(function (input) {
    input.addEventListener('change', function () {
      updateChoiceStyles('.movement-type-option');
      updateMovementMode();
    });
  });

  Array.prototype.slice.call(form.querySelectorAll('input[name="payment_method"]')).forEach(function (input) {
    input.addEventListener('change', function () {
      updateChoiceStyles('.payment-method');
    });
  });

  productSelect.addEventListener('change', function () {
    var option = productSelect.options[productSelect.selectedIndex];
    var price = option ? Number(option.getAttribute('data-price') || 0) : 0;

    if (price > 0 && !amountInput.value) {
      amountInput.value = price.toFixed(2);
      updateTotal();
    }
  });

  decreaseQty.addEventListener('click', function () {
    quantityInput.value = Math.max(1, Number(quantityInput.value || 1) - 1);
    updateTotal();
  });

  increaseQty.addEventListener('click', function () {
    quantityInput.value = Math.max(1, Number(quantityInput.value || 1) + 1);
    updateTotal();
  });

  quantityInput.addEventListener('input', updateTotal);
  amountInput.addEventListener('input', updateTotal);
  incomingStatus.addEventListener('change', updateIncomingStatus);
  statusOptions.addEventListener('click', function (event) {
    var button = event.target.closest('[data-status]');

    if (!button) {
      return;
    }

    selectedStatus.textContent = button.getAttribute('data-status');
    incomingStatus.value = button.getAttribute('data-status');

    Array.prototype.slice.call(statusOptions.querySelectorAll('.movement-status-option')).forEach(function (option) {
      option.classList.toggle('is-selected', option === button);
    });
  });

  form.addEventListener('reset', function () {
    window.setTimeout(function () {
      updateChoiceStyles('.movement-type-option');
      updateChoiceStyles('.payment-method');
      updateTotal();
      updateMovementMode();
      message.textContent = '';
    }, 0);
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    form.classList.add('was-submitted');

    var movementType = getMovementType();
    var submitButton = form.querySelector('[type="submit"]');
    var paymentMethod = getCheckedValue('payment_method', 'cash');
    var payload = {
      stock_type: movementType === 'out' ? 'Stock Out' : 'Stock In',
      product_id: Number(productSelect.value || 0),
      quantity: Number(quantityInput.value || 0),
      movement_notes: getFieldValue(['[name="movement_notes"]', '#movementNotes', 'textarea']),
      incoming_status: movementType === 'in' ? incomingStatus.value : '',
      movement_status: movementType === 'out' ? incomingStatus.value : '',
      full_name: getFieldValue([
        '[name="full_name"]',
        '[name="name"]',
        '[name="supplier_name"]',
        '[name="buyer_name"]',
        '[name="seller_name"]',
        '#fullName',
        '#supplierName',
        '#buyerName',
        '#sellerName',
        '#buyerSellerName'
      ]) || getTextInputFallback(1),
      contact_number: getFieldValue([
        '[name="contact_number"]',
        '[name="contact"]',
        '[name="phone"]',
        '[name="supplier_contact"]',
        '[name="buyer_contact"]',
        '[name="seller_contact"]',
        '#contactNumber',
        '#contact',
        '#phone',
        '#supplierContact',
        '#buyerContact',
        '#sellerContact',
        '#buyerSellerContact'
      ]) || getTextInputFallback(2),
      amount_per_piece: numericAmount(amountInput.value),
      total_amount: numericAmount(totalAmount.value),
      payment_status: getFieldValue(['[name="payment_status"]', '#paymentStatus']) || 'Paid',
      payment_method: paymentMethod === 'card' ? 'Card' : 'Cash'
    };

    if (!payload.product_id) {
      setMessage('Please select a product.', 'error');
      return;
    }

    if (payload.quantity <= 0) {
      setMessage('Quantity must be greater than 0.', 'error');
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;
    }

    setMessage('Recording stock movement...', '');

    fetch('api/stock_movements.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.success) {
            throw data;
          }

          return data;
        });
      })
      .then(function (data) {
        setMessage(data.message || 'Stock movement recorded successfully.', 'success');
      })
      .catch(function (error) {
        var errorMessage = error && error.message ? error.message : 'Unable to record stock movement.';

        if (error && error.errors) {
          errorMessage = Object.keys(error.errors).map(function (key) {
            return key + ': ' + error.errors[key];
          }).join(' ');
        }

        setMessage(errorMessage, 'error');
      })
      .finally(function () {
        if (submitButton) {
          submitButton.disabled = false;
        }
      });
  });

  loadProducts();
  updateTotal();
  updateMovementMode();
});

function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
