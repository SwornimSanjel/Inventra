<div class="stock-page">
    <div class="page-header stock-page__header">
        <div class="stock-page__heading">
            <p class="page-subtitle stock-page__intro">Log precise inventory flow and transaction details for ledger accuracy.</p>
        </div>
    </div>

    <div class="stock-layout">
        <section class="section-card stock-form-card">
            <form id="stockMovementForm" class="stock-form" novalidate>
                <div class="stock-block">
                    <div class="stock-card-head">
                        <div>
                            <h2 class="stock-card-title">Stock Details</h2>
                        </div>
                        <div class="stock-toggle" role="tablist" aria-label="Stock movement type">
                            <button type="button" class="stock-toggle__btn is-active" id="stockInBtn" data-type="in">Stock In</button>
                            <button type="button" class="stock-toggle__btn" id="stockOutBtn" data-type="out">Stock Out</button>
                        </div>
                    </div>

                    <div class="form-grid form-grid--stock">
                        <label class="field field--full">
                            <span>Product Name <span class="required-marker" aria-hidden="true">*</span></span>
                            <div class="stock-custom-select" data-stock-select-root>
                                <select id="stockProduct" name="product_id" required class="stock-native-select" data-stock-select-native hidden aria-hidden="true" tabindex="-1" style="display: none;">
                                    <option value="">Select product</option>
                                </select>
                                <button type="button" class="stock-custom-select__trigger" data-stock-select-trigger aria-expanded="false">
                                    <span data-stock-select-label>Select product</span>
                                    <svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5"></path></svg>
                                </button>
                                <div class="stock-custom-select__menu" data-stock-select-menu hidden></div>
                            </div>
                        </label>

                        <label class="field field--quantity">
                            <span>Quantity <span class="required-marker" aria-hidden="true">*</span></span>
                            <div class="qty-control">
                                <button type="button" class="qty-control__btn" id="quantityMinus" aria-label="Decrease quantity">-</button>
                                <input type="number" id="stockQuantity" name="quantity" min="1" value="1" required>
                                <button type="button" class="qty-control__btn" id="quantityPlus" aria-label="Increase quantity">+</button>
                            </div>
                        </label>

                        <label class="field field--full">
                            <span>Movement Notes</span>
                            <textarea id="stockNotes" name="notes" rows="4" placeholder="Add specific details about the batch condition or carrier..."></textarea>
                        </label>
                    </div>
                </div>

                <div class="stock-block stock-block--subsection">
                    <h2 class="stock-card-title">Buyer / Seller Details</h2>
                    <div class="form-grid">
                        <label class="field">
                            <span>Full Name <span class="required-marker" aria-hidden="true">*</span></span>
                            <input type="text" id="partyName" name="full_name" placeholder="Supplier or customer name" required>
                        </label>

                        <label class="field">
                            <span>Contact Number <span class="required-marker" aria-hidden="true">*</span></span>
                            <input type="tel" id="partyContact" name="contact" placeholder="Phone number" pattern="[0-9+\-\s()]{7,20}" required>
                        </label>

                        <label class="field">
                            <span>Amount Per Piece <span class="required-marker" aria-hidden="true">*</span></span>
                            <input type="number" id="stockPrice" name="amount_per_piece" min="0.01" step="0.01" value="" required>
                        </label>

                        <label class="field">
                            <span>Total Amount</span>
                            <input type="text" id="stockTotal" value="0.00" readonly>
                        </label>
                    </div>
                </div>

                <div class="stock-form__footer">
                    <p class="form-message" id="stockFormMessage" aria-live="polite"></p>
                </div>
            </form>
        </section>

        <aside class="stock-sidebar">
            <section class="section-card stock-side-card">
                <div class="stock-card-head stock-card-head--side">
                    <h2 class="stock-card-title">Payment Status</h2>
                </div>

                <label class="field">
                    <span>Status</span>
                    <div class="stock-custom-select" data-stock-select-root>
                        <select id="paymentStatus" name="payment_status" form="stockMovementForm" class="stock-native-select" data-stock-select-native>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                        <button type="button" class="stock-custom-select__trigger" data-stock-select-trigger aria-expanded="false">
                            <span data-stock-select-label>Paid</span>
                            <svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5"></path></svg>
                        </button>
                        <div class="stock-custom-select__menu" data-stock-select-menu hidden></div>
                    </div>
                </label>

                <div class="payment-toggle-wrap">
                    <div class="payment-toggle" id="paymentMethodToggle">
                        <button type="button" class="payment-toggle__btn is-active" data-method="cash">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v10H4z"></path><path d="M8 11h8"></path><path d="M8 9h3"></path></svg>
                            <span>Cash</span>
                        </button>
                        <button type="button" class="payment-toggle__btn" data-method="card">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M3 10h18"></path></svg>
                            <span>Card</span>
                        </button>
                    </div>
                </div>
            </section>

            <section class="section-card stock-side-card" id="stockInStatusCard">
                <div class="stock-card-head stock-card-head--side">
                    <h2 class="stock-card-title">Incoming Stock Status</h2>
                </div>
                <label class="field field--incoming-status">
                    <span class="sr-only">Status</span>
                    <div class="stock-custom-select stock-custom-select--status" data-stock-select-root>
                        <select id="incomingStatus" name="incoming_status" form="stockMovementForm" required class="stock-native-select" data-stock-select-native>
                            <option value="" selected disabled>Select incoming stock status</option>
                            <option value="order_dispatched">Order Dispatched</option>
                            <option value="in_transit">In Transit</option>
                            <option value="received">Received at Warehouse</option>
                        </select>
                        <button type="button" class="stock-custom-select__trigger" data-stock-select-trigger aria-expanded="false">
                            <span data-stock-select-label>Select incoming stock status</span>
                            <svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5"></path></svg>
                        </button>
                        <div class="stock-custom-select__menu" data-stock-select-menu hidden></div>
                    </div>
                </label>
            </section>

            <section class="section-card stock-side-card is-hidden" id="stockOutStatusCard">
                <div class="stock-card-head stock-card-head--side">
                    <h2 class="stock-card-title">Movement Status</h2>
                </div>
                <div class="status-list">
                    <label class="status-option">
                        <input type="radio" name="movement_status" value="dispatched" checked form="stockMovementForm">
                        <span class="status-option__body">
                            <span class="status-option__title">Dispatched from Warehouse</span>
                            <span class="status-option__meta">Vehicle is in transit to destination</span>
                        </span>
                    </label>
                    <label class="status-option">
                        <input type="radio" name="movement_status" value="hub" form="stockMovementForm">
                        <span class="status-option__body">
                            <span class="status-option__title">Stock Received at Hub</span>
                            <span class="status-option__meta">Logging local storage check</span>
                        </span>
                    </label>
                    <label class="status-option">
                        <input type="radio" name="movement_status" value="delivered" form="stockMovementForm">
                        <span class="status-option__body">
                            <span class="status-option__title">Delivery Confirmed</span>
                            <span class="status-option__meta">Final handover to client</span>
                        </span>
                    </label>
                </div>
            </section>

            <div class="stock-actions stock-actions--sidebar">
                <button type="reset" class="btn-outline" form="stockMovementForm">Reset</button>
                <button type="submit" class="btn-primary" id="recordMovementBtn" form="stockMovementForm">Record Movement</button>
            </div>
        </aside>
    </div>

    <section class="section-card stock-history-card">
        <div class="dashboard-panel__header">
            <div>
                <p class="eyebrow">Recent activity</p>
                <h2>Latest stock movements</h2>
                <p class="page-subtitle">A running ledger of the most recent stock in and stock out transactions.</p>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th style="text-align:center">Quantity</th>
                    <th>Status</th>
                    <th>Party</th>
                    <th>Payment</th>
                    <th>Update</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="stockHistoryTable">
                <tr>
                    <td colspan="9" class="empty-state">Loading recent movements...</td>
                </tr>
            </tbody>
        </table>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.inventraAdminStockPatchLoaded) {
        return;
    }

    window.inventraAdminStockPatchLoaded = true;

    const productSelect = document.getElementById('stockProduct');
    const productRoot = productSelect ? productSelect.closest('[data-stock-select-root]') : null;
    const productTrigger = productRoot ? productRoot.querySelector('[data-stock-select-trigger]') : null;
    const productLabel = productRoot ? productRoot.querySelector('[data-stock-select-label]') : null;
    const productMenu = productRoot ? productRoot.querySelector('[data-stock-select-menu]') : null;
    const historyTable = document.getElementById('stockHistoryTable');
    let products = [];

    function json(url) {
        return fetch(url, { cache: 'no-store' }).then(function (response) {
            return response.text().then(function (text) {
                let data = null;

                try {
                    data = text ? JSON.parse(text) : null;
                } catch (error) {
                    throw new Error('Invalid server response from ' + url);
                }

                if (!response.ok) {
                    throw new Error((data && (data.message || data.error || data.detail)) || 'Request failed.');
                }

                return data;
            });
        });
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

        return [];
    }

    function installProductDropdown() {
        if (!productSelect || !productRoot || !productTrigger || !productLabel || !productMenu) {
            return;
        }

        productSelect.style.position = 'absolute';
        productSelect.style.opacity = '0';
        productSelect.style.pointerEvents = 'none';
        productSelect.style.width = '1px';
        productSelect.style.height = '1px';

        productMenu.innerHTML = ''
            + '<div class="stock-custom-select__search">'
            + '<input type="search" data-admin-stock-product-search placeholder="Search product or SKU">'
            + '</div>'
            + '<div class="stock-custom-select__options" data-admin-stock-product-options></div>';

        const search = productMenu.querySelector('[data-admin-stock-product-search]');

        function render(query) {
            const options = productMenu.querySelector('[data-admin-stock-product-options]');
            const normalizedQuery = String(query || '').toLowerCase().trim();
            const matches = products.filter(function (product) {
                return [
                    product.name || '',
                    product.sku || ''
                ].join(' ').toLowerCase().includes(normalizedQuery);
            });

            options.innerHTML = '';

            if (matches.length === 0) {
                options.innerHTML = '<div class="stock-custom-select__empty">No products found</div>';
                return;
            }

            matches.forEach(function (product) {
                const stock = product.qty ?? product.stock ?? 0;
                const price = product.unit_price ?? product.price ?? 0;
                const button = document.createElement('button');

                button.type = 'button';
                button.className = 'stock-custom-select__option';
                button.textContent = product.name + ' (Stock: ' + stock + ')';
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    productSelect.value = String(product.id);
                    productLabel.textContent = button.textContent;

                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    if (selectedOption) {
                        selectedOption.setAttribute('data-stock', stock);
                        selectedOption.setAttribute('data-price', price);
                    }

                    productSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    productRoot.classList.remove('is-open');
                    productTrigger.setAttribute('aria-expanded', 'false');
                    productMenu.hidden = true;
                });

                options.appendChild(button);
            });
        }

        productTrigger.onclick = function (event) {
            event.preventDefault();
            event.stopPropagation();

            const willOpen = !productRoot.classList.contains('is-open');
            productRoot.classList.toggle('is-open', willOpen);
            productTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            productMenu.hidden = !willOpen;
            render(search.value);

            if (willOpen) {
                window.setTimeout(function () { search.focus(); }, 0);
            }
        };

        productMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        search.addEventListener('input', function () {
            render(search.value);
        });

        document.addEventListener('click', function (event) {
            if (!productRoot.contains(event.target)) {
                productRoot.classList.remove('is-open');
                productTrigger.setAttribute('aria-expanded', 'false');
                productMenu.hidden = true;
            }
        });

        render('');
    }

    function loadProducts() {
        return json('api/products/get_user_products.php')
            .catch(function () {
                return json('api/products/get_products.php');
            })
            .then(function (data) {
                products = normalizeProducts(data);

                if (!productSelect) {
                    return;
                }

                productSelect.innerHTML = '<option value="">Select product</option>';
                products.forEach(function (product) {
                    const stock = product.qty ?? product.stock ?? 0;
                    const price = product.unit_price ?? product.price ?? 0;
                    const option = document.createElement('option');

                    option.value = product.id;
                    option.textContent = product.name + ' (Stock: ' + stock + ')';
                    option.setAttribute('data-stock', stock);
                    option.setAttribute('data-price', price);
                    option.setAttribute('data-name', product.name || '');
                    option.setAttribute('data-sku', product.sku || '');
                    productSelect.appendChild(option);
                });

                installProductDropdown();
            });
    }

    function loadHistory() {
        if (!historyTable) {
            return Promise.resolve();
        }

        return json('api/stock/list.php')
            .catch(function () {
                return json('api/stock/user_list.php');
            })
            .then(function (data) {
                const movements = Array.isArray(data)
                    ? data
                    : (Array.isArray(data.movements) ? data.movements : []);

                if (movements.length === 0) {
                    historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">No stock movements have been recorded yet.</td></tr>';
                    return;
                }

                historyTable.innerHTML = movements.map(function (movement) {
                    const status = movement.status || movement.incoming_status || movement.movement_status || '';
                    const date = movement.created_at_label || movement.created_at || '';
                    const payment = [movement.payment_status || '', movement.payment_method || ''].filter(Boolean).join(' / ');
                    return ''
                        + '<tr>'
                        + '<td>' + escapeHtml(movement.reference || '') + '</td>'
                        + '<td>' + escapeHtml(movement.product_name || movement.name || '') + '</td>'
                        + '<td>' + escapeHtml(String(movement.movement_type || '').toUpperCase()) + '</td>'
                        + '<td style="text-align:center">' + escapeHtml(movement.quantity || '') + '</td>'
                        + '<td>' + escapeHtml(status) + '</td>'
                        + '<td>' + escapeHtml(movement.full_name || 'Not provided') + '</td>'
                        + '<td>' + escapeHtml(payment) + '</td>'
                        + '<td>' + escapeHtml(status) + '</td>'
                        + '<td>' + escapeHtml(date) + '</td>'
                        + '</tr>';
                }).join('');
            })
            .catch(function (error) {
                historyTable.innerHTML = '<tr><td colspan="9" class="empty-state">' + escapeHtml(error.message || 'Unable to load stock movement data.') + '</td></tr>';
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

    loadProducts().catch(function () {
        if (productMenu) {
            productMenu.innerHTML = '<div class="stock-custom-select__empty">Unable to load products</div>';
        }
    });
    loadHistory();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function appBase() {
        return window.location.pathname.split('/index.php')[0].replace(/\/$/, '');
    }

    function ensureMessage(form) {
        var message = document.getElementById('stockFormMessage');

        if (!message) {
            message = document.createElement('p');
            message.id = 'stockFormMessage';
            message.className = 'form-message';
            form.appendChild(message);
        }

        return message;
    }

    function findSelectedProductId(form) {
        var productInput = form.querySelector('[name="product_id"]');
        var selectedProductOption = null;

        if (productInput && productInput.value) {
            return productInput.value;
        }

        document.querySelectorAll('select').forEach(function (select) {
            var option = select.options ? select.options[select.selectedIndex] : null;

            if (selectedProductOption || !option || !option.value) {
                return;
            }

            if (select.name === 'product_id' || select.id === 'stockProduct' || (option.textContent || '').indexOf('(Stock:') !== -1) {
                selectedProductOption = option;
            }
        });

        if (!productInput) {
            productInput = document.createElement('input');
            productInput.type = 'hidden';
            productInput.name = 'product_id';
            form.appendChild(productInput);
        }

        if (selectedProductOption) {
            productInput.value = selectedProductOption.value;
        }

        return productInput.value;
    }

    function selectedType() {
        var active = document.querySelector('.stock-toggle__btn.is-active');
        return active ? active.getAttribute('data-type') || 'in' : 'in';
    }

    function selectedIncomingStatus(form) {
        var select = document.getElementById('incomingStatus');
        var input = form.querySelector('[name="incoming_status"]');

        return select ? select.value : (input ? input.value : '');
    }

    function selectedPaymentMethod(form) {
        var active = document.querySelector('#paymentMethodToggle [data-method].is-active');
        var input = form.querySelector('[name="payment_method"]');

        if (active) {
            return active.getAttribute('data-method') || 'cash';
        }

        return input ? input.value || 'cash' : 'cash';
    }

    function stockPayload(form) {
        var formData = new FormData(form);
        var type = selectedType();
        var movementStatusInput = form.querySelector('input[name="movement_status"]:checked');
        var totalInput = document.getElementById('stockTotal');

        return {
            product_id: findSelectedProductId(form),
            quantity: formData.get('quantity') || '1',
            type: type,
            movement_type: type,
            stock_type: type,
            notes: formData.get('notes') || '',
            full_name: formData.get('full_name') || '',
            party_name: formData.get('full_name') || '',
            contact: formData.get('contact') || '',
            contact_number: formData.get('contact') || '',
            amount_per_piece: formData.get('amount_per_piece') || '0',
            unit_price: formData.get('amount_per_piece') || '0',
            total_amount: totalInput ? totalInput.value : '0',
            payment_status: formData.get('payment_status') || 'paid',
            payment_method: selectedPaymentMethod(form),
            incoming_status: selectedIncomingStatus(form),
            movement_status: movementStatusInput ? movementStatusInput.value : '',
            status: type === 'in' ? selectedIncomingStatus(form) : (movementStatusInput ? movementStatusInput.value : '')
        };
    }

    function postStock(payload) {
        var endpoints = [
            '/api/stock/add.php',
            '/api/stock/save.php',
            '/api/stock/create.php',
            '/api/stock/store.php',
            '/api/stock/update.php'
        ];

        function tryEndpoint(index) {
            if (index >= endpoints.length) {
                return Promise.reject(new Error('Stock movement API endpoint was not found.'));
            }

            return fetch(appBase() + endpoints[index], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function (response) {
                if (response.status === 404) {
                    return tryEndpoint(index + 1);
                }

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
            });
        }

        return tryEndpoint(0);
    }

    function submitStockMovement(event) {
        var button = document.getElementById('recordMovementBtn');
        var form = document.getElementById('stockMovementForm');
        var message;
        var payload;

        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }

        if (!form) {
            return;
        }

        message = ensureMessage(form);
        payload = stockPayload(form);

        if (!payload.product_id) {
            message.textContent = 'Please select a product.';
            message.className = 'form-message is-error';
            return;
        }

        if (payload.type === 'in' && !payload.incoming_status) {
            message.textContent = 'Please select incoming stock status.';
            message.className = 'form-message is-error';
            return;
        }

        if (button) {
            button.type = 'button';
            button.disabled = true;
            button.textContent = 'Recording...';
        }

        message.textContent = 'Recording stock movement...';
        message.className = 'form-message';

        postStock(payload)
            .then(function (data) {
                message.textContent = data.message || 'Stock movement recorded successfully.';
                message.className = 'form-message is-success';
            })
            .catch(function (error) {
                message.textContent = error.message || 'Unable to record movement.';
                message.className = 'form-message is-error';
            })
            .finally(function () {
                if (button) {
                    button.disabled = false;
                    button.textContent = 'Record Movement';
                }
            });
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('#recordMovementBtn')) {
            submitStockMovement(event);
        }
    }, true);

    document.addEventListener('submit', function (event) {
        if (event.target && event.target.id === 'stockMovementForm') {
            submitStockMovement(event);
        }
    }, true);
});
</script>
