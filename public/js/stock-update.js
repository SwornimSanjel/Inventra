document.addEventListener('click', function (event) {
    var button = event.target.closest('#paymentMethodToggle [data-method]');
    var toggle;
    var form;
    var input;
    var method;

    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    toggle = button.closest('#paymentMethodToggle');
    form = document.getElementById('stockMovementForm');
    method = button.getAttribute('data-method') || 'cash';

    if (!toggle) {
        return;
    }

    toggle.querySelectorAll('[data-method]').forEach(function (toggleButton) {
        toggleButton.type = 'button';
        toggleButton.classList.toggle('is-active', toggleButton.getAttribute('data-method') === method);
    });

    input = form ? form.querySelector('input[name="payment_method"]') : null;
    if (!input && form) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'payment_method';
        form.appendChild(input);
    }

    if (input) {
        input.value = method;
    }
}, true);

document.addEventListener('click', function (event) {
    var button = event.target.closest('#recordMovementBtn');
    var form;
    var message;
    var formData;
    var activeTypeButton;
    var movementType;
    var productValue;
    var incomingStatus;

    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    form = document.getElementById('stockMovementForm');
    if (!form) {
        return;
    }

    button.type = 'button';

    message = document.getElementById('stockFormMessage');
    if (!message) {
        message = document.createElement('p');
        message.id = 'stockFormMessage';
        message.className = 'form-message';
        form.appendChild(message);
    }

    function ensureProductId() {
        var productInput = form.querySelector('input[name="product_id"]');
        var nativeProductSelect = null;
        var productLabel = '';
        var matchedProduct = null;
        var products = Array.isArray(window.stockPageProducts) ? window.stockPageProducts : [];

        document.querySelectorAll('select').forEach(function (select) {
            var selectedOption = select.options && select.options[select.selectedIndex];

            if (nativeProductSelect || !selectedOption || !selectedOption.value) {
                return;
            }

            if ((selectedOption.textContent || '').indexOf('(Stock:') !== -1 || select.name === 'product_id' || select.id === 'stockProduct') {
                nativeProductSelect = select;
            }
        });

        if (!productInput) {
            productInput = document.createElement('input');
            productInput.type = 'hidden';
            productInput.name = 'product_id';
            productInput.id = 'stockProductSubmitValue';
            form.appendChild(productInput);
        }

        if (productInput.value) {
            return productInput.value;
        }

        if (nativeProductSelect && nativeProductSelect.value) {
            productInput.value = nativeProductSelect.value;
            return productInput.value;
        }

        productLabel = ((document.querySelector('.stock-product-search__trigger span') || document.querySelector('[data-stock-select-label]') || {}).textContent || '').trim();

        if (!productLabel || productLabel.toLowerCase() === 'select product') {
            return '';
        }

        products.forEach(function (product) {
            if (!matchedProduct) {
                var stock = product.qty || product.stock || 0;
                var label = String(product.name || product.product_name || 'Unnamed product') + ' (Stock: ' + stock + ')';

                if (label === productLabel || String(product.name || product.product_name || '') === productLabel.replace(/\s+\(Stock:\s*.*?\)\s*$/i, '')) {
                    matchedProduct = product;
                }
            }
        });

        if (matchedProduct) {
            productInput.value = matchedProduct.id || matchedProduct.product_id || '';
        }

        return productInput.value;
    }

    ensureProductId();
    formData = new FormData(form);
    activeTypeButton = document.querySelector('.stock-toggle__btn.is-active');
    movementType = activeTypeButton ? activeTypeButton.getAttribute('data-type') : 'in';
    productValue = formData.get('product_id');
    incomingStatus = formData.get('incoming_status');

    if (!productValue) {
        message.textContent = 'Please select a product.';
        message.className = 'form-message is-error';
        return;
    }

    if (movementType === 'in' && !incomingStatus) {
        message.textContent = 'Please select incoming stock status.';
        message.className = 'form-message is-error';
        return;
    }

    formData.set('type', movementType);

    button.disabled = true;
    button.textContent = 'Recording...';
    message.textContent = 'Recording stock movement...';
    message.className = 'form-message';

    function stockPayloadFromForm() {
        var totalInput = document.getElementById('stockTotal');
        var movementStatusInput = form.querySelector('input[name="movement_status"]:checked');

        return {
            product_id: formData.get('product_id'),
            quantity: formData.get('quantity'),
            type: movementType,
            movement_type: movementType,
            stock_type: movementType,
            notes: formData.get('notes') || '',
            full_name: formData.get('full_name') || '',
            party_name: formData.get('full_name') || '',
            contact: formData.get('contact') || '',
            contact_number: formData.get('contact') || '',
            amount_per_piece: formData.get('amount_per_piece') || '0',
            unit_price: formData.get('amount_per_piece') || '0',
            total_amount: totalInput ? totalInput.value : '0',
            payment_status: formData.get('payment_status') || 'paid',
            payment_method: formData.get('payment_method') || 'cash',
            incoming_status: formData.get('incoming_status') || '',
            movement_status: movementStatusInput ? movementStatusInput.value : (formData.get('movement_status') || ''),
            status: movementType === 'in' ? (formData.get('incoming_status') || '') : (movementStatusInput ? movementStatusInput.value : '')
        };
    }

    (function submitStockMovement(endpointIndex) {
        var basePath = window.location.pathname.split('/index.php')[0].replace(/\/$/, '') || '';
        var endpoints = [
            '/api/stock/save.php',
            '/api/stock/add.php',
            '/api/stock/create.php',
            '/api/stock/store.php',
            '/api/stock/movement.php',
            '/api/stock/update.php'
        ];

        if (endpointIndex >= endpoints.length) {
            return Promise.reject(new Error('Stock movement API endpoint was not found.'));
        }

        return fetch(basePath + endpoints[endpointIndex], {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(stockPayloadFromForm())
        }).then(function (response) {
            if (response.status === 404) {
                return submitStockMovement(endpointIndex + 1);
            }

            return response;
        });
    }(0))
        .then(function (response) {
            return response.text().then(function (text) {
                var data = {};

                try {
                    data = text ? JSON.parse(text) : {};
                } catch (error) {
                    throw new Error(text || 'Unable to record movement.');
                }

                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Unable to record movement.');
                }

                return data;
            });
        })
        .then(function (data) {
            message.textContent = data.message || 'Stock movement recorded successfully.';
            message.className = 'form-message is-success';
        })
        .catch(function (error) {
            message.textContent = error.message || 'Unable to record movement.';
            message.className = 'form-message is-error';
        })
        .finally(function () {
            button.disabled = false;
            button.textContent = 'Record Movement';
        });
}, true);

document.addEventListener('submit', function (event) {
    var form = event.target;
    var button;

    if (!form || form.id !== 'stockMovementForm') {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    button = document.getElementById('recordMovementBtn');
    if (button) {
        button.click();
    }
}, true);

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('stockMovementForm');
    var stockInBtn = document.getElementById('stockInBtn');
    var stockOutBtn = document.getElementById('stockOutBtn');
    var quantityInput = document.getElementById('stockQuantity');
    var quantityMinus = document.getElementById('quantityMinus');
    var quantityPlus = document.getElementById('quantityPlus');
    var priceInput = document.getElementById('stockPrice');
    var totalInput = document.getElementById('stockTotal');
    var message = document.getElementById('stockFormMessage');
    var currentType = 'in';

    function appUrl(path) {
        var base = window.location.pathname.split('/index.php')[0].replace(/\/$/, '');
        return base + '/' + path.replace(/^\//, '');
    }

    function money(value) {
        return (parseFloat(value || '0') || 0).toFixed(2);
    }

    function updateTotal() {
        if (!quantityInput || !priceInput || !totalInput) {
            return;
        }

        totalInput.value = money((parseFloat(quantityInput.value || '0') || 0) * (parseFloat(priceInput.value || '0') || 0));
    }

    function setQuantity(value) {
        if (!quantityInput) {
            return;
        }

        quantityInput.value = Math.max(1, parseInt(value || '1', 10) || 1);
        updateTotal();
    }

    function setStockType(type) {
        var stockInStatusCard = document.getElementById('stockInStatusCard');
        var stockOutStatusCard = document.getElementById('stockOutStatusCard');

        currentType = type === 'out' ? 'out' : 'in';

        if (stockInBtn) {
            stockInBtn.classList.toggle('is-active', currentType === 'in');
        }

        if (stockOutBtn) {
            stockOutBtn.classList.toggle('is-active', currentType === 'out');
        }

        if (stockInStatusCard) {
            stockInStatusCard.classList.toggle('is-hidden', currentType !== 'in');
        }

        if (stockOutStatusCard) {
            stockOutStatusCard.classList.toggle('is-hidden', currentType !== 'out');
        }
    }

    function findProductField() {
        var fields = document.querySelectorAll('label.field, .field');
        var index;

        for (index = 0; index < fields.length; index += 1) {
            if ((fields[index].textContent || '').toLowerCase().indexOf('product name') !== -1) {
                return fields[index];
            }
        }

        return null;
    }

    function productFromOption(option) {
        var name = option.getAttribute('data-name') || (option.textContent || '').replace(/\s+\(Stock:\s*.*?\)\s*$/i, '').trim();

        return {
            id: String(option.value || ''),
            name: name || 'Unnamed product',
            sku: String(option.getAttribute('data-sku') || ''),
            stock: option.getAttribute('data-stock') || '',
            price: option.getAttribute('data-price') || '',
            label: option.textContent || name || 'Unnamed product'
        };
    }

    function normalizeProduct(product) {
        var stock = product.qty || product.stock || 0;
        var name = product.name || product.product_name || 'Unnamed product';

        return {
            id: String(product.id || product.product_id || ''),
            name: String(name),
            sku: String(product.sku || ''),
            stock: stock,
            price: product.unit_price || product.price || 0,
            label: String(name) + ' (Stock: ' + stock + ')'
        };
    }

    function fetchProducts() {
        var endpoints = [
            'api/products/get_user_products.php',
            'api/products/list.php',
            'api/products/get_products.php',
            'api/products/read.php',
            'api/product/list.php',
            'api/products.php'
        ];

        function tryEndpoint(index) {
            if (index >= endpoints.length) {
                return Promise.resolve([]);
            }

            return fetch(appUrl(endpoints[index]), { cache: 'no-store' })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Product endpoint failed');
                    }

                    return response.json();
                })
                .then(function (data) {
                    var rows = Array.isArray(data) ? data : [];
                    if (!rows.length && data && Array.isArray(data.products)) {
                        rows = data.products;
                    }
                    if (!rows.length && data && Array.isArray(data.data)) {
                        rows = data.data;
                    }
                    if (!rows.length && data && Array.isArray(data.records)) {
                        rows = data.records;
                    }
                    if (!rows.length && data && Array.isArray(data.items)) {
                        rows = data.items;
                    }
                    var products = rows.map(normalizeProduct).filter(function (product) {
                        return product.id;
                    });

                    return products.length ? products : tryEndpoint(index + 1);
                })
                .catch(function () {
                    return tryEndpoint(index + 1);
                });
        }

        return tryEndpoint(0);
    }

    function installProductStyles() {
        var style = document.createElement('style');

        if (document.getElementById('stockProductSearchStyle')) {
            return;
        }

        style.id = 'stockProductSearchStyle';
        style.textContent = ''
            + '.stock-product-field select{display:none!important;}'
            + '.stock-product-search{position:relative;width:100%;}'
            + '.stock-product-search__trigger{display:flex;align-items:center;justify-content:space-between;width:100%;min-height:44px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#0f172a;padding:0 12px;font:inherit;text-align:left;cursor:pointer;}'
            + '.stock-product-search__menu{position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:9999;overflow:hidden;border:1px solid #cbd5e1;border-radius:8px;background:#fff;box-shadow:0 16px 36px rgba(15,23,42,.18);}'
            + '.stock-product-search__search{padding:8px;border-bottom:1px solid #e2e8f0;background:#f8fafc;}'
            + '.stock-product-search__search input{width:100%;min-height:38px;border:1px solid #cbd5e1;border-radius:6px;padding:0 10px;color:#111827;font:inherit;outline:none;}'
            + '.stock-product-search__options{max-height:260px;overflow-y:auto;padding:4px;}'
            + '.stock-product-search__option,.stock-product-search__empty{display:block;width:100%;min-height:40px;padding:9px 10px;border:0;border-radius:6px;background:transparent;color:#111827;font:inherit;text-align:left;}'
            + '.stock-product-search__option{cursor:pointer;}'
            + '.stock-product-search__option:hover,.stock-product-search__option.is-selected{background:#eef2ff;}'
            + '.stock-product-search__meta{display:block;margin-top:2px;color:#64748b;font-size:12px;}'
            + '.stock-product-search__empty{color:#64748b;}';
        document.head.appendChild(style);
    }

    function buildProductSearch(products) {
        var field = findProductField();
        var label;
        var selectedValue = '';
        var hidden;
        var root;
        var trigger;
        var triggerLabel;
        var menu;
        var search;
        var optionsWrap;

        products = Array.isArray(products) ? products : [];

        if (!field || field.getAttribute('data-product-search-ready') === 'true') {
            return;
        }

        field.querySelectorAll('select').forEach(function (select) {
            if (!selectedValue && select.value) {
                selectedValue = select.value;
            }
        });

        field.classList.add('stock-product-field');
        field.setAttribute('data-product-search-ready', 'true');

        label = field.querySelector('span');
        field.innerHTML = '';

        if (!label) {
            label = document.createElement('span');
            label.textContent = 'Product Name';
        }

        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'stockProduct';
        hidden.name = 'product_id';
        hidden.required = true;
        hidden.value = selectedValue;

        root = document.createElement('div');
        root.className = 'stock-product-search';

        trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'stock-product-search__trigger';
        trigger.setAttribute('aria-expanded', 'false');

        triggerLabel = document.createElement('span');
        triggerLabel.textContent = 'Select product';
        trigger.appendChild(triggerLabel);
        trigger.insertAdjacentHTML('beforeend', '<span aria-hidden="true">⌄</span>');

        menu = document.createElement('div');
        menu.className = 'stock-product-search__menu';
        menu.hidden = true;
        menu.innerHTML = '<div class="stock-product-search__search"><input type="search" placeholder="Search product or SKU" autocomplete="off"></div><div class="stock-product-search__options"></div>';

        search = menu.querySelector('input');
        optionsWrap = menu.querySelector('.stock-product-search__options');

        root.appendChild(hidden);
        root.appendChild(trigger);
        root.appendChild(menu);
        field.appendChild(label);
        field.appendChild(root);

        function selectedProduct() {
            return products.find(function (product) {
                return product.id === hidden.value;
            });
        }

        function updateTriggerLabel() {
            var product = selectedProduct();
            triggerLabel.textContent = product ? product.label : 'Select product';
        }

        function render(query) {
            var normalized = String(query || '').toLowerCase().trim();
            var matches = products.filter(function (product) {
                return (product.name + ' ' + product.sku).toLowerCase().indexOf(normalized) !== -1;
            });

            optionsWrap.innerHTML = '';

            if (!matches.length) {
                optionsWrap.innerHTML = '<div class="stock-product-search__empty">No products found</div>';
                return;
            }

            matches.forEach(function (product) {
                var button = document.createElement('button');

                button.type = 'button';
                button.className = 'stock-product-search__option';
                button.textContent = product.label;

                if (product.id === hidden.value) {
                    button.classList.add('is-selected');
                }

                if (product.sku) {
                    button.insertAdjacentHTML('beforeend', '<span class="stock-product-search__meta">SKU: ' + product.sku + '</span>');
                }

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    hidden.value = product.id;
                    hidden.dataset.stock = product.stock;
                    hidden.dataset.price = product.price;
                    hidden.dataset.name = product.name;
                    hidden.dataset.sku = product.sku;
                    hidden.dispatchEvent(new Event('change', { bubbles: true }));
                    updateTriggerLabel();
                    if (priceInput && product.price && parseFloat(priceInput.value || '0') === 0) {
                        priceInput.value = money(product.price);
                    }
                    updateTotal();
                    menu.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                });

                optionsWrap.appendChild(button);
            });
        }

        trigger.addEventListener('click', function (event) {
            var open = menu.hidden;

            event.preventDefault();
            event.stopPropagation();
            menu.hidden = !open;
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                search.value = '';
                render('');
                window.setTimeout(function () {
                    search.focus();
                }, 0);
            }
        });

        search.addEventListener('input', function () {
            render(search.value);
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        updateTriggerLabel();
        render('');
    }

    function initProductSearch() {
        var field = findProductField();
        var products = [];

        installProductStyles();

        if (!field) {
            return;
        }

        field.querySelectorAll('select option').forEach(function (option) {
            var product = productFromOption(option);
            if (product.id) {
                products.push(product);
            }
        });

        if (products.length) {
            buildProductSearch(products);
            return;
        }

        buildProductSearch([]);

        fetchProducts().then(function (fetchedProducts) {
            var field = findProductField();

            if (field) {
                field.removeAttribute('data-product-search-ready');
            }

            buildProductSearch(fetchedProducts);
        });
    }

    if (stockInBtn) {
        stockInBtn.addEventListener('click', function () {
            setStockType('in');
        });
    }

    if (stockOutBtn) {
        stockOutBtn.addEventListener('click', function () {
            setStockType('out');
        });
    }

    if (quantityMinus) {
        quantityMinus.addEventListener('click', function () {
            setQuantity((parseInt(quantityInput.value || '1', 10) || 1) - 1);
        });
    }

    if (quantityPlus) {
        quantityPlus.addEventListener('click', function () {
            setQuantity((parseInt(quantityInput.value || '1', 10) || 1) + 1);
        });
    }

    if (quantityInput) {
        quantityInput.addEventListener('input', updateTotal);
    }

    if (priceInput) {
        priceInput.addEventListener('input', updateTotal);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            var formData = new FormData(form);
            var recordButton = document.getElementById('recordMovementBtn');
            var productValue = formData.get('product_id');
            var incomingStatus = formData.get('incoming_status');

            event.preventDefault();
            formData.set('type', currentType);

            if (!productValue) {
                if (message) {
                    message.textContent = 'Please select a product.';
                    message.className = 'form-message is-error';
                }
                return;
            }

            if (currentType === 'in' && !incomingStatus) {
                if (message) {
                    message.textContent = 'Please select incoming stock status.';
                    message.className = 'form-message is-error';
                }
                return;
            }

            if (message) {
                message.textContent = 'Recording stock movement...';
                message.className = 'form-message';
            }

            if (recordButton) {
                recordButton.disabled = true;
                recordButton.textContent = 'Recording...';
            }

            fetch(appUrl('api/stock/save.php'), {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data || data.success === false) {
                        throw new Error(data && data.message ? data.message : 'Unable to record movement.');
                    }

                    if (message) {
                        message.textContent = data.message || 'Stock movement recorded successfully.';
                        message.className = 'form-message is-success';
                    }

                    form.reset();
                    setQuantity(1);
                    updateTotal();
                    initProductSearch();
                })
                .catch(function (error) {
                    if (message) {
                        message.textContent = error.message || 'Unable to record movement.';
                        message.className = 'form-message is-error';
                    }
                })
                .finally(function () {
                    if (recordButton) {
                        recordButton.disabled = false;
                        recordButton.textContent = 'Record Movement';
                    }
                });
        });

        form.addEventListener('reset', function () {
            window.setTimeout(function () {
                var productTrigger = document.querySelector('.stock-product-search__trigger span');
                var productInput = form.querySelector('input[name="product_id"]');
                var incomingSelect = document.getElementById('incomingStatus');
                var incomingLabel = document.querySelector('#incomingStatusCard [data-stock-select-label]');

                if (productInput) {
                    productInput.value = '';
                }

                if (productTrigger) {
                    productTrigger.textContent = 'Select product';
                }

                if (incomingSelect) {
                    incomingSelect.value = '';
                }

                if (incomingLabel) {
                    incomingLabel.textContent = 'Select incoming stock status';
                }

                setQuantity(1);
                updateTotal();

                if (message) {
                    message.textContent = '';
                    message.className = 'form-message';
                }
            }, 0);
        });
    }

    initProductSearch();
    setStockType('in');
    updateTotal();
});

document.addEventListener('DOMContentLoaded', function () {
    function initPaymentMethodToggle() {
        var toggle = document.getElementById('paymentMethodToggle');
        var form = document.getElementById('stockMovementForm');
        var input;

        if (!toggle || toggle.getAttribute('data-payment-toggle-ready') === 'true') {
            return;
        }

        toggle.setAttribute('data-payment-toggle-ready', 'true');

        input = form ? form.querySelector('input[name="payment_method"]') : null;
        if (!input && form) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'payment_method';
            form.appendChild(input);
        }

        function setMethod(method) {
            var selectedMethod = method || 'cash';

            toggle.querySelectorAll('[data-method]').forEach(function (button) {
                button.classList.toggle('is-active', button.getAttribute('data-method') === selectedMethod);
            });

            if (input) {
                input.value = selectedMethod;
            }
        }

        toggle.querySelectorAll('[data-method]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                setMethod(button.getAttribute('data-method'));
            });
        });

        setMethod((toggle.querySelector('[data-method].is-active') || toggle.querySelector('[data-method]') || {}).dataset ? (toggle.querySelector('[data-method].is-active') || toggle.querySelector('[data-method]')).dataset.method : 'cash');
    }

    initPaymentMethodToggle();
    window.setTimeout(initPaymentMethodToggle, 300);
    window.setTimeout(initPaymentMethodToggle, 1000);
});

document.addEventListener('DOMContentLoaded', function () {
    function initBasicStockSelect(root) {
        var select = root.querySelector('select');
        var trigger = root.querySelector('[data-stock-select-trigger]');
        var label = root.querySelector('[data-stock-select-label]');
        var menu = root.querySelector('[data-stock-select-menu]');

        if (!select || !trigger || !label || !menu || root.getAttribute('data-basic-select-ready') === 'true') {
            return;
        }

        root.setAttribute('data-basic-select-ready', 'true');
        select.style.setProperty('display', 'none', 'important');

        function closeSelect() {
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        }

        function updateLabel() {
            var selectedOption = select.options[select.selectedIndex];
            label.textContent = selectedOption ? selectedOption.textContent : 'Select';
        }

        function renderOptions() {
            menu.innerHTML = '';

            Array.prototype.slice.call(select.options).forEach(function (option) {
                var button = document.createElement('button');

                button.type = 'button';
                button.className = 'stock-custom-select__option';
                button.textContent = option.textContent;
                button.disabled = option.disabled;

                if (option.value === select.value) {
                    button.classList.add('is-selected');
                }

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (option.disabled) {
                        return;
                    }

                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateLabel();
                    closeSelect();
                });

                menu.appendChild(button);
            });
        }

        trigger.addEventListener('click', function (event) {
            var shouldOpen = menu.hidden;

            event.preventDefault();
            event.stopPropagation();

            document.querySelectorAll('[data-stock-select-root].is-open').forEach(function (openRoot) {
                var openTrigger = openRoot.querySelector('[data-stock-select-trigger]');
                var openMenu = openRoot.querySelector('[data-stock-select-menu]');

                openRoot.classList.remove('is-open');
                if (openTrigger) {
                    openTrigger.setAttribute('aria-expanded', 'false');
                }
                if (openMenu) {
                    openMenu.hidden = true;
                }
            });

            renderOptions();
            root.classList.toggle('is-open', shouldOpen);
            trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            menu.hidden = !shouldOpen;
        });

        select.addEventListener('change', updateLabel);
        updateLabel();
        renderOptions();
    }

    function installBasicSelectStyles() {
        var style;

        if (document.getElementById('basicStockSelectStyles')) {
            return;
        }

        style = document.createElement('style');
        style.id = 'basicStockSelectStyles';
        style.textContent = ''
            + '[data-stock-select-root]{position:relative;}'
            + '[data-stock-select-root] [data-stock-select-menu]{position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:9999;max-height:220px;overflow-y:auto;padding:4px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;box-shadow:0 16px 36px rgba(15,23,42,.16);}'
            + '[data-stock-select-root] .stock-custom-select__option{display:block;width:100%;min-height:38px;padding:8px 10px;border:0;border-radius:6px;background:transparent;color:#111827;font:inherit;text-align:left;cursor:pointer;}'
            + '[data-stock-select-root] .stock-custom-select__option:hover,[data-stock-select-root] .stock-custom-select__option.is-selected{background:#eef2ff;}'
            + '[data-stock-select-root] .stock-custom-select__option:disabled{color:#94a3b8;cursor:not-allowed;}';
        document.head.appendChild(style);
    }

    installBasicSelectStyles();

    document.querySelectorAll('[data-stock-select-root]').forEach(function (root) {
        if (root.querySelector('#stockProduct') || root.classList.contains('stock-product-search') || root.classList.contains('admin-product-search')) {
            return;
        }

        initBasicStockSelect(root);
    });

    document.addEventListener('click', function (event) {
        document.querySelectorAll('[data-stock-select-root].is-open').forEach(function (root) {
            var trigger = root.querySelector('[data-stock-select-trigger]');
            var menu = root.querySelector('[data-stock-select-menu]');

            if (root.contains(event.target)) {
                return;
            }

            root.classList.remove('is-open');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
            if (menu) {
                menu.hidden = true;
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    function installIncomingDropdownStyles() {
        var style;

        if (document.getElementById('incomingStatusDropdownStyles')) {
            return;
        }

        style = document.createElement('style');
        style.id = 'incomingStatusDropdownStyles';
        style.textContent = ''
            + '.stock-custom-select--status [data-stock-select-menu][hidden]{display:none!important;}'
            + '.stock-custom-select--status [data-stock-select-menu]:not([hidden]){position:static!important;display:grid;gap:22px;max-height:none;overflow:visible;margin-top:28px;padding:0;border:0!important;border-radius:0;background:transparent!important;box-shadow:none!important;}'
            + '.stock-custom-select--status .stock-custom-select__trigger{min-height:38px;}'
            + '.stock-custom-select--status .stock-custom-select__option{display:flex;align-items:center;gap:18px;width:100%;min-height:54px;padding:0 26px;border:2px solid transparent;border-radius:7px;background:#f8fafc;color:#64748b;font:inherit;font-size:14px;font-weight:500;text-align:left;cursor:pointer;}'
            + '.stock-custom-select--status .stock-custom-select__option::before{content:"";width:5px;height:5px;border-radius:999px;background:#cbd5e1;flex:0 0 auto;}'
            + '.stock-custom-select--status .stock-custom-select__option::after{content:"✓";display:none;align-items:center;justify-content:center;width:18px;height:18px;margin-left:auto;border:2px solid #22c55e;border-radius:999px;color:#22c55e;background:transparent;font-size:12px;font-weight:800;line-height:1;}'
            + '.stock-custom-select--status .stock-custom-select__option:hover{background:#f1f5f9;color:#0f172a;}'
            + '.stock-custom-select--status .stock-custom-select__option.is-selected{border-color:#86efac;background:#ecfdf3;color:#166534;font-weight:700;}'
            + '.stock-custom-select--status .stock-custom-select__option.is-selected::before{background:#22c55e;}'
            + '.stock-custom-select--status .stock-custom-select__option.is-selected::after{display:flex;}'
            + '.stock-custom-select--status .stock-custom-select__option:disabled{display:none;}';
        document.head.appendChild(style);
    }

    function initIncomingStatusDropdown() {
        var select = document.getElementById('incomingStatus');
        var root = select ? select.closest('[data-stock-select-root]') : null;
        var trigger = root ? root.querySelector('[data-stock-select-trigger]') : null;
        var label = root ? root.querySelector('[data-stock-select-label]') : null;
        var menu = root ? root.querySelector('[data-stock-select-menu]') : null;

        if (!select || !root || !trigger || !label || !menu) {
            return;
        }

        installIncomingDropdownStyles();
        root.style.display = '';
        root.classList.add('stock-custom-select--status');
        select.required = true;

        if (!select.value) {
            label.textContent = 'Select incoming stock status';
        }
    }

    initIncomingStatusDropdown();
    window.setTimeout(initIncomingStatusDropdown, 300);
    window.setTimeout(initIncomingStatusDropdown, 1000);
});
