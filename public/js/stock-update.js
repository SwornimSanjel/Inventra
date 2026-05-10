(function () {
  var isUserPanel = String(window.location.href || '').indexOf('user/stock-update') !== -1;

  initStockPage({
    productsUrl: isUserPanel ? 'api/products/get_user_products.php' : 'api/products/get_products.php',
    historyUrl: isUserPanel ? 'api/stock/user_list.php' : 'api/stock/list.php',
    updateStatusUrl: isUserPanel ? 'api/stock/user_update_status.php' : 'api/stock/update_status.php'
  });
})();

function initStockPage(config) {
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('stockMovementForm');
    var productSelect = document.getElementById('stockProduct');
    var quantityInput = document.getElementById('stockQuantity');
    var priceInput = document.getElementById('stockPrice');
    var totalInput = document.getElementById('stockTotal');
    var message = document.getElementById('stockFormMessage');
    var historyTable = document.getElementById('stockHistoryTable');
    var stockInBtn = document.getElementById('stockInBtn');
    var stockOutBtn = document.getElementById('stockOutBtn');
    var stockInStatusCard = document.getElementById('stockInStatusCard');
    var stockOutStatusCard = document.getElementById('stockOutStatusCard');
    var incomingStatusSelect = document.getElementById('incomingStatus');
    var movementStatusSelect = document.getElementById('movementStatus');
    var paymentStatusSelect = document.getElementById('paymentStatus');
    var paymentToggle = document.getElementById('paymentMethodToggle');
    var quantityMinus = document.getElementById('quantityMinus');
    var quantityPlus = document.getElementById('quantityPlus');
    var partyName = document.getElementById('partyName');
    var partyContact = document.getElementById('partyContact');
    var stockNotes = document.getElementById('stockNotes');

    if (
      !form || !productSelect || !quantityInput || !priceInput || !totalInput || !message || !historyTable ||
      !stockInBtn || !stockOutBtn || !stockInStatusCard || !stockOutStatusCard || !incomingStatusSelect || !movementStatusSelect ||
      !paymentStatusSelect || !paymentToggle || !partyName || !partyContact || !stockNotes
    ) {
      return;
    }

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
    var movementType = 'in';
    var paymentMethod = 'cash';
    var products = [];
    var productRoot = productSelect.closest('[data-stock-select-root]');
    var productTrigger = productRoot ? productRoot.querySelector('[data-stock-select-trigger]') : null;
    var productMenu = productRoot ? productRoot.querySelector('[data-stock-select-menu]') : null;
    var incomingStatusRoot = incomingStatusSelect.closest('[data-stock-select-root]');
    var movementStatusRoot = movementStatusSelect.closest('[data-stock-select-root]');
    var setPaymentMethod = function (method) {
      paymentMethod = method === 'card' ? 'card' : 'cash';
    };

    initSimpleSelect(paymentStatusSelect);
    initIncomingStatusSelect();
    initProductSelect();
    bindMovementType();
    bindPaymentMethod();
    bindQuantityControls();
    bindCalculatedFields();
    bindForm();
    setMovementType('in');
    updateTotal();
    setMessage('', '');
    setPaymentMethod('cash');

    loadProducts().catch(function (error) {
      console.error('Stock product load failed:', error);
      if (productRoot && productRoot.classList.contains('is-open') && typeof window.renderStockProductOptions === 'function') {
        window.renderStockProductOptions('', 'Unable to load products');
      }
      setMessage(error.message || 'Unable to load products right now.', 'is-error');
    });

    loadHistory().catch(function (error) {
      console.error('Stock movement history load failed:', error);
      historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">Unable to load stock movement data.</td></tr>';
      setMessage(error.message || 'Unable to load stock movement data.', 'is-error');
    });

    function apiJson(url, options) {
      return fetch(url, options || {}).then(function (response) {
        return response.text().then(function (text) {
          var data = null;

          try {
            data = text ? JSON.parse(text) : null;
          } catch (error) {
            throw new Error('Server returned an invalid response.');
          }

          if (!response.ok || (data && data.success === false && !data.movements && !Array.isArray(data))) {
            throw new Error((data && (data.message || data.error || data.detail)) || 'Request failed.');
          }

          return data;
        });
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

    function normalizeStatus(value) {
      return String(value || '').toLowerCase().trim().replace(/\s+/g, '_');
    }

    function setMessage(text, state) {
      message.textContent = text || '';
      message.classList.remove('is-error', 'is-success');
      if (state) {
        message.classList.add(state);
      }
    }

    function setFieldInvalid(field, invalid) {
      if (field) {
        field.classList.toggle('is-invalid', Boolean(invalid));
      }
    }

    function selectedProductOption() {
      return productSelect.options[productSelect.selectedIndex] || null;
    }

    function selectedProductStock() {
      var option = selectedProductOption();
      return option ? parseInt(option.getAttribute('data-stock') || '0', 10) : 0;
    }

    function updateTotal() {
      var quantity = parseFloat(quantityInput.value || '0');
      var price = parseFloat(priceInput.value || '0');
      totalInput.value = (quantity * price).toFixed(2);
    }

    function bindCalculatedFields() {
      quantityInput.addEventListener('input', updateTotal);
      priceInput.addEventListener('input', updateTotal);
      partyContact.addEventListener('input', function () {
        setFieldInvalid(partyContact, false);
      });
      partyName.addEventListener('input', function () {
        setFieldInvalid(partyName, false);
      });
      priceInput.addEventListener('input', function () {
        setFieldInvalid(priceInput, false);
      });
      quantityInput.addEventListener('input', function () {
        setFieldInvalid(quantityInput, false);
      });
    }

    function bindQuantityControls() {
      quantityMinus.addEventListener('click', function () {
        var current = Math.max(1, parseInt(quantityInput.value || '1', 10) || 1);
        quantityInput.value = String(Math.max(1, current - 1));
        updateTotal();
      });

      quantityPlus.addEventListener('click', function () {
        var current = Math.max(1, parseInt(quantityInput.value || '1', 10) || 1);
        quantityInput.value = String(current + 1);
        updateTotal();
      });
    }

    function bindMovementType() {
      stockInBtn.addEventListener('click', function () {
        setMovementType('in');
      });

      stockOutBtn.addEventListener('click', function () {
        setMovementType('out');
      });
    }

    function setMovementType(nextType) {
      movementType = nextType === 'out' ? 'out' : 'in';
      stockInBtn.classList.toggle('is-active', movementType === 'in');
      stockOutBtn.classList.toggle('is-active', movementType === 'out');
      stockInStatusCard.classList.toggle('is-hidden', movementType !== 'in');
      stockOutStatusCard.classList.toggle('is-hidden', movementType !== 'out');
      incomingStatusSelect.required = movementType === 'in';
      movementStatusSelect.required = movementType === 'out';
      if (movementType === 'out') {
        setFieldInvalid(incomingStatusRoot ? incomingStatusRoot.querySelector('[data-stock-select-trigger]') : null, false);
      } else {
        setFieldInvalid(movementStatusRoot ? movementStatusRoot.querySelector('[data-stock-select-trigger]') : null, false);
      }
    }

    function bindPaymentMethod() {
      var hiddenInput = form.querySelector('input[name="payment_method"]');

      if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'payment_method';
        form.appendChild(hiddenInput);
      }

      function syncState(method) {
        paymentMethod = method === 'card' ? 'card' : 'cash';
        hiddenInput.value = paymentMethod;
        paymentToggle.querySelectorAll('[data-method]').forEach(function (toggleButton) {
          var isActive = toggleButton.getAttribute('data-method') === paymentMethod;
          toggleButton.classList.toggle('is-active', isActive);
          toggleButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
      }

      setPaymentMethod = syncState;

      paymentToggle.querySelectorAll('[data-method]').forEach(function (button) {
        button.addEventListener('click', function (event) {
          event.preventDefault();
          syncState(button.getAttribute('data-method') || 'cash');
        });
      });

      syncState(paymentMethod);
    }

    function initSimpleSelect(select) {
      var root = select.closest('[data-stock-select-root]');
      var trigger = root ? root.querySelector('[data-stock-select-trigger]') : null;
      var label = root ? root.querySelector('[data-stock-select-label]') : null;
      var menu = root ? root.querySelector('[data-stock-select-menu]') : null;

      if (!root || !trigger || !label || !menu) {
        return;
      }

      function close() {
        root.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
      }

      function render() {
        menu.innerHTML = '';
        Array.prototype.slice.call(select.options).forEach(function (option) {
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'stock-custom-select__option';
          button.textContent = option.textContent;
          if (option.disabled) {
            button.classList.add('is-disabled');
          }
          if (option.value === select.value) {
            button.classList.add('is-active');
          }
          button.addEventListener('click', function () {
            if (option.disabled) {
              return;
            }
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
          });
          menu.appendChild(button);
        });
      }

      function syncLabel() {
        var selectedOption = select.options[select.selectedIndex];
        label.textContent = selectedOption ? selectedOption.textContent : 'Select option';
      }

      trigger.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        closeAllMenus(root);
        var shouldOpen = menu.hidden;
        if (shouldOpen) {
          render();
        }
        root.classList.toggle('is-open', shouldOpen);
        trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        menu.hidden = !shouldOpen;
      });

      select.addEventListener('change', function () {
        syncLabel();
        render();
      });

      syncLabel();
      render();
    }

    function initIncomingStatusSelect() {
      initSimpleSelect(incomingStatusSelect);
      incomingStatusSelect.addEventListener('change', function () {
        var trigger = incomingStatusRoot ? incomingStatusRoot.querySelector('[data-stock-select-trigger]') : null;
        setFieldInvalid(trigger, false);
      });
      initSimpleSelect(movementStatusSelect);
      movementStatusSelect.addEventListener('change', function () {
        var trigger = movementStatusRoot ? movementStatusRoot.querySelector('[data-stock-select-trigger]') : null;
        setFieldInvalid(trigger, false);
      });
    }

    function initProductSelect() {
      var root = productRoot;
      var trigger = productTrigger;
      var label = root ? root.querySelector('[data-stock-select-label]') : null;
      var menu = productMenu;

      if (!root || !trigger || !label || !menu) {
        return;
      }

      function close() {
        root.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
      }

      function render(query, emptyMessage) {
        var normalizedQuery = String(query || '').toLowerCase().trim();
        var filteredProducts = products.filter(function (product) {
          return [product.name || '', product.sku || ''].join(' ').toLowerCase().indexOf(normalizedQuery) !== -1;
        });
        var selectedValue = productSelect.value;

        menu.innerHTML = ''
          + '<div class="stock-custom-select__search-wrap">'
          + '<input type="search" class="stock-custom-select__search" placeholder="Search product name or SKU" autocomplete="off">'
          + '</div>'
          + '<div class="stock-custom-select__options"></div>';

        var optionsWrap = menu.querySelector('.stock-custom-select__options');

        if (!filteredProducts.length) {
          optionsWrap.innerHTML = '<div class="stock-custom-select__empty">' + escapeHtml(emptyMessage || 'No products found') + '</div>';
        } else {
          filteredProducts.forEach(function (product) {
            var button = document.createElement('button');
            var stock = product.qty != null ? product.qty : (product.stock || 0);
            var displayName = String(product.name || 'Unnamed product') + ' (Stock: ' + stock + ')';
            button.type = 'button';
            button.className = 'stock-custom-select__option';
            button.setAttribute('data-value', String(product.id));
            if (String(product.id) === selectedValue) {
              button.classList.add('is-active');
            }
            button.innerHTML = escapeHtml(displayName) + (product.sku ? '<br><small>' + escapeHtml(product.sku) + '</small>' : '');
            button.addEventListener('click', function () {
              productSelect.value = String(product.id);
              productSelect.dispatchEvent(new Event('change', { bubbles: true }));
              close();
            });
            optionsWrap.appendChild(button);
          });
        }

        var searchInput = menu.querySelector('.stock-custom-select__search');
        if (searchInput) {
          searchInput.value = query || '';
          searchInput.addEventListener('click', function (event) {
            event.stopPropagation();
          });
          searchInput.addEventListener('input', function () {
            render(searchInput.value);
            var nextSearchInput = menu.querySelector('.stock-custom-select__search');
            if (nextSearchInput) {
              nextSearchInput.focus();
              nextSearchInput.setSelectionRange(nextSearchInput.value.length, nextSearchInput.value.length);
            }
          });
        }
      }

      function syncLabelAndPrice() {
        var option = selectedProductOption();
        var price = option ? parseFloat(option.getAttribute('data-price') || '0') : 0;
        var stock = option ? parseInt(option.getAttribute('data-stock') || '0', 10) : 0;
        label.textContent = option && option.value ? String(option.getAttribute('data-name') || option.textContent || 'Select product') + ' (Stock: ' + stock + ')' : 'Select product';
        if (option && option.value && (!priceInput.value || parseFloat(priceInput.value || '0') <= 0) && price > 0) {
          priceInput.value = price.toFixed(2);
        }
        updateTotal();
        setFieldInvalid(trigger, false);
      }

      trigger.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        closeAllMenus(root);
        var shouldOpen = menu.hidden;
        if (shouldOpen) {
          render('');
        }
        root.classList.toggle('is-open', shouldOpen);
        trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        menu.hidden = !shouldOpen;
        if (shouldOpen) {
          var searchInput = menu.querySelector('.stock-custom-select__search');
          if (searchInput) {
            searchInput.focus();
          }
        }
      });

      productSelect.addEventListener('change', syncLabelAndPrice);
      syncLabelAndPrice();
      window.renderStockProductOptions = render;
    }

    function closeAllMenus(exceptRoot) {
      document.querySelectorAll('[data-stock-select-root]').forEach(function (root) {
        if (root === exceptRoot) {
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
      });
      document.querySelectorAll('[data-history-select-root]').forEach(function (root) {
        if (root === exceptRoot) {
          return;
        }
        closeHistorySelect(root);
      });
    }

    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-stock-select-root]') && !event.target.closest('[data-history-select-root]')) {
        closeAllMenus(null);
      }
    });

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

      return [];
    }

    function loadProducts() {
      return apiJson(config.productsUrl).then(function (data) {
        products = normalizeProducts(data).map(function (product) {
          return {
            id: parseInt(product.id, 10),
            name: product.name || 'Unnamed product',
            sku: product.sku || '',
            qty: product.qty != null ? product.qty : (product.stock || 0),
            unit_price: product.unit_price != null ? product.unit_price : (product.price || 0)
          };
        });

        var currentValue = productSelect.value;
        productSelect.innerHTML = '<option value="">Select product</option>';

        products.forEach(function (product) {
          var option = document.createElement('option');
          option.value = String(product.id);
          option.textContent = product.name + ' (Stock: ' + product.qty + ')';
          option.setAttribute('data-name', product.name);
          option.setAttribute('data-sku', product.sku);
          option.setAttribute('data-stock', String(product.qty));
          option.setAttribute('data-price', String(product.unit_price || 0));
          productSelect.appendChild(option);
        });

        if (currentValue && productSelect.querySelector('option[value="' + currentValue + '"]')) {
          productSelect.value = currentValue;
        }

        productSelect.dispatchEvent(new Event('change', { bubbles: true }));

        if (productRoot && productRoot.classList.contains('is-open') && typeof window.renderStockProductOptions === 'function') {
          window.renderStockProductOptions('');
        }
      });
    }

    function validateForm() {
      var quantity = parseInt(quantityInput.value || '0', 10);
      var price = parseFloat(priceInput.value || '0');
      var incomingTrigger = incomingStatusRoot ? incomingStatusRoot.querySelector('[data-stock-select-trigger]') : null;
      var movementTrigger = movementStatusRoot ? movementStatusRoot.querySelector('[data-stock-select-trigger]') : null;
      var contactValue = String(partyContact.value || '').trim();

      setMessage('', '');
      setFieldInvalid(productTrigger, false);
      setFieldInvalid(quantityInput, false);
      setFieldInvalid(priceInput, false);
      setFieldInvalid(partyName, false);
      setFieldInvalid(partyContact, false);
      setFieldInvalid(incomingTrigger, false);
      setFieldInvalid(movementTrigger, false);

      if (!productSelect.value) {
        setFieldInvalid(productTrigger, true);
        setMessage('Please select a product.', 'is-error');
        return false;
      }

      if (!Number.isInteger(quantity) || quantity <= 0) {
        setFieldInvalid(quantityInput, true);
        setMessage('Quantity must be greater than 0.', 'is-error');
        return false;
      }

      if (movementType === 'out' && quantity > selectedProductStock()) {
        setFieldInvalid(quantityInput, true);
        setMessage('Stock out quantity cannot exceed the current product stock.', 'is-error');
        return false;
      }

      if (!partyName.value.trim()) {
        setFieldInvalid(partyName, true);
        setMessage('Full name is required.', 'is-error');
        return false;
      }

      if (!/^[0-9+\-\s()]{7,20}$/.test(contactValue)) {
        setFieldInvalid(partyContact, true);
        setMessage('Please enter a valid contact number.', 'is-error');
        return false;
      }

      if (!Number.isFinite(price) || price <= 0) {
        setFieldInvalid(priceInput, true);
        setMessage('Amount per piece must be greater than 0.', 'is-error');
        return false;
      }

      if (movementType === 'in' && !incomingStatusSelect.value) {
        setFieldInvalid(incomingTrigger, true);
        setMessage('Please select incoming stock status.', 'is-error');
        return false;
      }

      if (movementType === 'out' && !movementStatusSelect.value) {
        setFieldInvalid(movementTrigger, true);
        setMessage('Please select movement status.', 'is-error');
        return false;
      }

      return true;
    }

    function resetFormState() {
      form.reset();
      setPaymentMethod('cash');
      paymentStatusSelect.value = 'paid';
      paymentStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
      incomingStatusSelect.value = '';
      incomingStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
      movementStatusSelect.value = '';
      movementStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
      productSelect.value = '';
      productSelect.dispatchEvent(new Event('change', { bubbles: true }));
      quantityInput.value = '1';
      setMovementType('in');
      updateTotal();
      setMessage('', '');
    }

    function bindForm() {
      form.addEventListener('reset', function () {
        window.setTimeout(resetFormState, 0);
      });

      form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!validateForm()) {
          return;
        }

        var payload = {
          movement_type: movementType,
          product_id: productSelect.value,
          quantity: parseInt(quantityInput.value || '0', 10),
          notes: stockNotes.value,
          full_name: partyName.value.trim(),
          contact: partyContact.value.trim(),
          amount_per_piece: parseFloat(priceInput.value || '0'),
          payment_status: paymentStatusSelect.value,
          payment_method: paymentMethod,
          incoming_status: movementType === 'in' ? incomingStatusSelect.value : '',
          movement_status: movementType === 'out' ? movementStatusSelect.value : ''
        };

        setMessage('Recording movement...', '');

        apiJson('api/stock/create.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).then(function (data) {
          setMessage(data.message || 'Stock movement recorded successfully.', 'is-success');
          resetFormState();
          return Promise.all([loadProducts(), loadHistory()]);
        }).catch(function (error) {
          console.error('Stock movement create failed:', error);
          setMessage(error.message || 'Unable to record stock movement.', 'is-error');
        });
      });
    }

    function historyStatusLabel(movement) {
      if (movement.movement_type === 'in') {
        return incomingStatusLabels[normalizeStatus(movement.incoming_status)] || movement.incoming_status || 'Order Dispatched';
      }

      return movementStatusLabels[normalizeStatus(movement.movement_status)] || movement.movement_status || 'Dispatched from Warehouse';
    }

    function historyStatusValue(movement) {
      return movement.movement_type === 'in'
        ? normalizeStatus(movement.incoming_status)
        : normalizeStatus(movement.movement_status);
    }

    function historyStatusOptions(movement) {
      return movement.movement_type === 'in'
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

    function bindHistorySelects() {
      document.querySelectorAll('[data-history-select-root]').forEach(function (root) {
        var trigger = root.querySelector('[data-history-select-trigger]');
        var menu = root.querySelector('[data-history-select-menu]');
        var label = root.querySelector('[data-history-select-label]');
        var movementId = parseInt(root.getAttribute('data-movement-id') || '0', 10);

        if (!trigger || !menu || !label || movementId <= 0 || root.getAttribute('data-bound') === 'true') {
          return;
        }

        root.setAttribute('data-bound', 'true');

        trigger.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          closeAllMenus(root);
          var shouldOpen = menu.hidden;
          root.classList.toggle('is-open', shouldOpen);
          trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
          menu.hidden = !shouldOpen;
        });

        menu.querySelectorAll('[data-history-select-option]').forEach(function (button) {
          button.addEventListener('click', function () {
            var statusValue = button.getAttribute('data-value') || '';
            label.textContent = button.textContent;
            menu.querySelectorAll('[data-history-select-option]').forEach(function (option) {
              option.classList.toggle('is-active', option === button);
            });
            closeHistorySelect(root);
            apiJson(config.updateStatusUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: movementId, status: statusValue })
            }).then(function (data) {
              setMessage(data.message || 'Stock movement status updated successfully.', 'is-success');
              loadHistory();
            }).catch(function (error) {
              console.error('Stock movement status update failed:', error);
              setMessage(error.message || 'Unable to update stock movement status.', 'is-error');
              loadHistory();
            });
          });
        });
      });
    }

    function renderHistoryRow(movement) {
      var badgeClass = movement.movement_type === 'out' ? 'chip-low' : 'chip-adequate';
      var paymentLabel = [movement.payment_status || '', movement.payment_method || ''].filter(Boolean).join(' / ');
      var currentStatusValue = historyStatusValue(movement);
      var optionsMarkup = historyStatusOptions(movement).map(function (option) {
        return '<button type="button" class="stock-history-select__option' + (option.value === currentStatusValue ? ' is-active' : '') + '" data-history-select-option data-value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label) + '</button>';
      }).join('');

      return ''
        + '<tr>'
        + '<td>' + escapeHtml(movement.reference || '-') + '</td>'
        + '<td>' + escapeHtml(movement.product_name || movement.product || 'Unknown product') + '</td>'
        + '<td><span class="chip ' + badgeClass + '">' + escapeHtml(String(movement.movement_type || movement.type || '').toUpperCase()) + '</span></td>'
        + '<td style="text-align:center">' + escapeHtml(movement.quantity || 0) + '</td>'
        + '<td><span class="stock-status-badge">' + escapeHtml(historyStatusLabel(movement)) + '</span></td>'
        + '<td>' + escapeHtml(movement.full_name || movement.party || 'Not provided') + '</td>'
        + '<td>' + escapeHtml(paymentLabel || '-') + '</td>'
        + '<td>'
        + '<div class="stock-history-select" data-history-select-root data-movement-id="' + escapeHtml(movement.id) + '">'
        + '<button type="button" class="stock-history-select__trigger" data-history-select-trigger aria-expanded="false">'
        + '<span data-history-select-label>' + escapeHtml(historyStatusLabel(movement)) + '</span>'
        + '<svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5"></path></svg>'
        + '</button>'
        + '<div class="stock-history-select__menu" data-history-select-menu hidden>' + optionsMarkup + '</div>'
        + '</div>'
        + '</td>'
        + '<td>' + escapeHtml(movement.created_at_label || movement.created_at || '-') + '</td>'
        + '</tr>';
    }

    function loadHistory() {
      return apiJson(config.historyUrl).then(function (data) {
        var movements = Array.isArray(data.movements) ? data.movements : (Array.isArray(data.data) ? data.data : []);

        if (!movements.length) {
          historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">No stock movements have been recorded yet.</td></tr>';
          return;
        }

        historyTable.innerHTML = movements.map(renderHistoryRow).join('');
        bindHistorySelects();
      });
    }
  });
}
