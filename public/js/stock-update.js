document.addEventListener('DOMContentLoaded', function () {
  var movementType = 'in';
  var paymentMethod = 'cash';
  var productSelect = document.getElementById('stockProduct');
  var customSelects = Array.prototype.slice.call(document.querySelectorAll('[data-stock-select-root]'));
  var quantityInput = document.getElementById('stockQuantity');
  var priceInput = document.getElementById('stockPrice');
  var totalInput = document.getElementById('stockTotal');
  var form = document.getElementById('stockMovementForm');
  var message = document.getElementById('stockFormMessage');
  var historyTable = document.getElementById('stockHistoryTable');
  var historySelectsBound = false;

  var incomingStatusLabels = {
    order_dispatched: 'Order Dispatched',
    in_transit: 'In Transit',
    received: 'Received at Warehouse'
  };

  var movementStatusLabels = {
    dispatched: 'Dispatched from Warehouse',
    hub: 'Stock Received at Hub',
    delivered: 'Delivery Confirmed'
  };

  function setMovement(type) {
    movementType = type;
    document.getElementById('stockInBtn').classList.toggle('is-active', type === 'in');
    document.getElementById('stockOutBtn').classList.toggle('is-active', type === 'out');
    document.getElementById('stockInStatusCard').classList.toggle('is-hidden', type !== 'in');
    document.getElementById('stockOutStatusCard').classList.toggle('is-hidden', type !== 'out');
  }

  function setMessage(text, state) {
    message.textContent = text || '';
    message.classList.remove('is-error', 'is-success');
    if (state) {
      message.classList.add(state);
    }
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

  function getStatusLabel(movement) {
    if (movement.movement_type === 'in') {
      return incomingStatusLabels[movement.incoming_status] || 'Order Dispatched';
    }

    return movementStatusLabels[movement.movement_status] || 'Dispatched from Warehouse';
  }

  function getStatusToneClass(movement) {
    var statusValue = movement.movement_type === 'in' ? movement.incoming_status : movement.movement_status;

    if (statusValue === 'received' || statusValue === 'delivered') {
      return 'stock-status-badge--success';
    }

    if (statusValue === 'in_transit' || statusValue === 'hub') {
      return 'stock-status-badge--info';
    }

    return 'stock-status-badge--neutral';
  }

  function getStatusOptionsMarkup(movement) {
    var options = movement.movement_type === 'in'
      ? [
          { value: 'order_dispatched', label: 'Order Dispatched' },
          { value: 'in_transit', label: 'In Transit' },
          { value: 'received', label: 'Received at Warehouse' }
        ]
      : [
          { value: 'dispatched', label: 'Dispatched from Warehouse' },
          { value: 'hub', label: 'Stock Received at Hub' },
          { value: 'delivered', label: 'Delivery Confirmed' }
        ];

    return '' +
      '<div class="stock-history-select" data-history-select-root data-movement-id="' + movement.id + '">' +
        '<button type="button" class="stock-history-select__trigger" data-history-select-trigger aria-expanded="false">' +
          '<span data-history-select-label>' + escapeHtml(getStatusLabel(movement)) + '</span>' +
          '<svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5"></path></svg>' +
        '</button>' +
        '<div class="stock-history-select__menu" data-history-select-menu hidden>' +
          options.map(function (option) {
            var isActive = option.value === (movement.movement_type === 'in' ? movement.incoming_status : movement.movement_status);
            return '<button type="button" class="stock-history-select__option' + (isActive ? ' is-active' : '') + '" data-history-select-option data-value="' + option.value + '">' + escapeHtml(option.label) + '</button>';
          }).join('') +
        '</div>' +
      '</div>';
  }

  function closeHistorySelect(root) {
    if (!root) {
      return;
    }

    root.classList.remove('is-open');
    var trigger = root.querySelector('[data-history-select-trigger]');
    var menu = root.querySelector('[data-history-select-menu]');

    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }

    if (menu) {
      menu.hidden = true;
    }
  }

  function initializeHistorySelects() {
    var historySelects = Array.prototype.slice.call(document.querySelectorAll('[data-history-select-root]'));

    function closeAllHistorySelects(exceptRoot) {
      historySelects.forEach(function (root) {
        if (root !== exceptRoot) {
          closeHistorySelect(root);
        }
      });
    }

    historySelects.forEach(function (root) {
      var trigger = root.querySelector('[data-history-select-trigger]');
      var menu = root.querySelector('[data-history-select-menu]');
      var movementId = parseInt(root.getAttribute('data-movement-id') || '0', 10);

      if (!trigger || !menu || movementId <= 0) {
        return;
      }

      trigger.addEventListener('click', function () {
        var willOpen = !root.classList.contains('is-open');
        closeAllHistorySelects(root);
        root.classList.toggle('is-open', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        menu.hidden = !willOpen;
      });

      root.querySelectorAll('[data-history-select-option]').forEach(function (optionButton) {
        optionButton.addEventListener('click', function () {
          var nextStatus = optionButton.getAttribute('data-value') || '';

          fetch('api/stock/update_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              id: movementId,
              status: nextStatus
            })
          })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (!data.success) {
                throw new Error(data.message || 'Unable to update stock movement status.');
              }

              setMessage(data.message || 'Stock movement status updated successfully.', 'is-success');
              return loadHistory();
            })
            .catch(function (error) {
              setMessage(error.message || 'Unable to update stock movement status.', 'is-error');
            });

          closeHistorySelect(root);
        });
      });
    });

    if (!historySelectsBound) {
      document.addEventListener('click', function (event) {
        Array.prototype.slice.call(document.querySelectorAll('[data-history-select-root]')).forEach(function (root) {
          if (!root.contains(event.target)) {
            closeHistorySelect(root);
          }
        });
      });
      historySelectsBound = true;
    }
  }

  function closeCustomSelect(root) {
    if (!root) {
      return;
    }

    root.classList.remove('is-open');
    var trigger = root.querySelector('[data-stock-select-trigger]');
    var menu = root.querySelector('[data-stock-select-menu]');

    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }

    if (menu) {
      menu.hidden = true;
    }
  }

  function closeAllCustomSelects(exceptRoot) {
    customSelects.forEach(function (root) {
      if (root !== exceptRoot) {
        closeCustomSelect(root);
      }
    });
  }

  function syncCustomSelect(root) {
    if (!root) {
      return;
    }

    var select = root.querySelector('[data-stock-select-native]');
    var label = root.querySelector('[data-stock-select-label]');
    var menu = root.querySelector('[data-stock-select-menu]');

    if (!select || !label || !menu) {
      return;
    }

    var selectedOption = select.options[select.selectedIndex] || select.options[0];
    label.textContent = selectedOption ? selectedOption.textContent : '';

    menu.querySelectorAll('[data-stock-select-option]').forEach(function (optionButton) {
      optionButton.classList.toggle('is-active', optionButton.getAttribute('data-value') === select.value);
    });
  }

  function rebuildCustomSelect(root) {
    if (!root) {
      return;
    }

    var select = root.querySelector('[data-stock-select-native]');
    var menu = root.querySelector('[data-stock-select-menu]');

    if (!select || !menu) {
      return;
    }

    menu.innerHTML = '';

    Array.prototype.slice.call(select.options).forEach(function (option, index) {
      var optionButton = document.createElement('button');
      optionButton.type = 'button';
      optionButton.className = 'stock-custom-select__option';
      optionButton.setAttribute('data-stock-select-option', 'true');
      optionButton.setAttribute('data-value', option.value);
      optionButton.textContent = option.textContent;
      optionButton.classList.toggle('is-active', option.selected || (!select.value && index === 0));
      optionButton.addEventListener('click', function () {
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        closeCustomSelect(root);
      });
      menu.appendChild(optionButton);
    });

    syncCustomSelect(root);
  }

  function initializeCustomSelect(root) {
    var select = root.querySelector('[data-stock-select-native]');
    var trigger = root.querySelector('[data-stock-select-trigger]');

    if (!select || !trigger) {
      return;
    }

    rebuildCustomSelect(root);

    trigger.addEventListener('click', function () {
      var willOpen = !root.classList.contains('is-open');
      closeAllCustomSelects(root);
      root.classList.toggle('is-open', willOpen);
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      root.querySelector('[data-stock-select-menu]').hidden = !willOpen;
    });

    select.addEventListener('change', function () {
      syncCustomSelect(root);
    });
  }

  function loadProducts() {
    return fetch('api/products/get_products.php')
      .then(function (response) { return response.json(); })
      .then(function (products) {
        productSelect.innerHTML = '<option value="">Select product</option>';

        if (!Array.isArray(products) || products.length === 0) {
          productSelect.innerHTML = '<option value="">No products available</option>';
          rebuildCustomSelect(productSelect.closest('[data-stock-select-root]'));
          return;
        }

        products.forEach(function (product) {
          var option = document.createElement('option');
          option.value = product.id;
          option.textContent = product.name + ' (Stock: ' + product.qty + ')';
          option.setAttribute('data-stock', product.qty);
          productSelect.appendChild(option);
        });

        rebuildCustomSelect(productSelect.closest('[data-stock-select-root]'));
      });
  }

  function loadHistory() {
    return fetch('api/stock/list.php')
      .then(function (response) { return response.json(); })
      .then(function (data) {
        var movements = Array.isArray(data.movements) ? data.movements : [];

        if (movements.length === 0) {
          historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">No stock movements have been recorded yet.</td></tr>';
          return;
        }

        historyTable.innerHTML = movements.map(function (movement) {
          var badgeClass = movement.movement_type === 'out' ? 'chip-low' : 'chip-adequate';
          return '' +
            '<tr>' +
              '<td>' + escapeHtml(movement.reference) + '</td>' +
              '<td>' + escapeHtml(movement.product_name) + '</td>' +
              '<td><span class="chip ' + badgeClass + '">' + escapeHtml(String(movement.movement_type).toUpperCase()) + '</span></td>' +
              '<td style="text-align:center">' + movement.quantity + '</td>' +
              '<td><span class="stock-status-badge ' + getStatusToneClass(movement) + '">' + escapeHtml(getStatusLabel(movement)) + '</span></td>' +
              '<td>' + escapeHtml(movement.full_name || 'Not provided') + '</td>' +
              '<td>' + escapeHtml(movement.payment_status + ' / ' + movement.payment_method) + '</td>' +
              '<td>' + getStatusOptionsMarkup(movement) + '</td>' +
              '<td>' + escapeHtml(movement.created_at_label) + '</td>' +
            '</tr>';
        }).join('');

        initializeHistorySelects();
      });
  }

  document.getElementById('stockInBtn').addEventListener('click', function () { setMovement('in'); });
  document.getElementById('stockOutBtn').addEventListener('click', function () { setMovement('out'); });

  document.querySelectorAll('.payment-toggle__btn').forEach(function (button) {
    button.addEventListener('click', function () {
      paymentMethod = button.getAttribute('data-method') || 'cash';
      document.querySelectorAll('.payment-toggle__btn').forEach(function (item) {
        item.classList.remove('is-active');
      });
      button.classList.add('is-active');
    });
  });

  document.getElementById('quantityPlus').addEventListener('click', function () {
    quantityInput.value = parseInt(quantityInput.value || '0', 10) + 1;
    recalcTotal();
  });

  document.getElementById('quantityMinus').addEventListener('click', function () {
    quantityInput.value = Math.max(1, parseInt(quantityInput.value || '1', 10) - 1);
    recalcTotal();
  });

  quantityInput.addEventListener('input', recalcTotal);
  priceInput.addEventListener('input', recalcTotal);

  document.querySelectorAll('.status-option input[type="radio"]').forEach(function (input) {
    input.addEventListener('change', syncStatusSelections);
  });

  customSelects.forEach(initializeCustomSelect);

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

    var quantity = parseInt(quantityInput.value || '0', 10);

    if (movementType === 'out' && quantity > selectedStock()) {
      setMessage('Stock out quantity cannot exceed the current product stock.', 'is-error');
      return;
    }

    var payload = {
      product_id: productSelect.value,
      movement_type: movementType,
      quantity: quantity,
      notes: document.getElementById('stockNotes').value,
      full_name: document.getElementById('partyName').value,
      contact: document.getElementById('partyContact').value,
      amount_per_piece: parseFloat(priceInput.value || '0'),
      payment_status: document.getElementById('paymentStatus').value,
      payment_method: paymentMethod,
      incoming_status: form.querySelector('input[name="incoming_status"]:checked') ? form.querySelector('input[name="incoming_status"]:checked').value : '',
      movement_status: form.querySelector('input[name="movement_status"]:checked') ? form.querySelector('input[name="movement_status"]:checked').value : ''
    };

    setMessage('Recording movement...', '');

    fetch('api/stock/create.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'Stock update failed');
        }

        setMessage(data.message || 'Movement recorded successfully.', 'is-success');
        form.reset();
        return Promise.all([loadProducts(), loadHistory()]);
      })
      .catch(function (error) {
        setMessage(error.message || 'Unable to record stock movement.', 'is-error');
      });
  });

  Promise.all([loadProducts(), loadHistory()]).catch(function () {
    historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">Unable to load stock movement data.</td></tr>';
  });
  recalcTotal();
  syncStatusSelections();
});

function syncStatusSelections() {
  document.querySelectorAll('.status-option').forEach(function (option) {
    var input = option.querySelector('input[type="radio"]');
    option.classList.toggle('is-selected', !!(input && input.checked));

    if (input && input.value === 'received') {
      option.classList.toggle('status-option--success', input.checked);
    } else {
      option.classList.remove('status-option--success');
    }
  });
}

function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
