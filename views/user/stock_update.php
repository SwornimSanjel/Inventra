<?php
$stockPageProducts = [];

try {
    require_once __DIR__ . '/../../config/db.php';

    $stockHasSkuColumn = false;
    try {
        $stockSkuColumnStmt = $conn->query("SHOW COLUMNS FROM products LIKE 'sku'");
        $stockHasSkuColumn = $stockSkuColumnStmt && $stockSkuColumnStmt->fetch();
    } catch (Throwable $exception) {
        $stockHasSkuColumn = false;
    }

    $stockProductsStmt = $conn->query("
        SELECT
            id,
            name,
            " . ($stockHasSkuColumn ? "COALESCE(sku, '')" : "''") . " AS sku,
            COALESCE(qty, 0) AS qty,
            COALESCE(unit_price, 0) AS unit_price
        FROM products
        ORDER BY name ASC
    ");
    $stockPageProducts = $stockProductsStmt ? $stockProductsStmt->fetchAll() : [];
} catch (Throwable $exception) {
    $stockPageProducts = [];
}
?>
<div class="stock-page">
    <div class="page-header stock-page__header">
        <div class="stock-page__heading">
            <p class="page-subtitle stock-page__intro">Log precise inventory flow and transaction details for ledger accuracy.</p>
        </div>
    </div>

    <div class="stock-layout">
        <section class="section-card stock-form-card">
            <form id="stockMovementForm" class="stock-form">
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
                        <label class="field field--full stock-product-field">
                            <span>Product Name</span>
                            <div class="stock-custom-select" data-stock-select-root>
                                <input type="hidden" id="stockProduct" name="product_id" required>
                                <button type="button" class="stock-custom-select__trigger" data-stock-select-trigger aria-expanded="false">
                                    <span data-stock-select-label>Select product</span>
                                    <svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5"></path></svg>
                                </button>
                                <div class="stock-custom-select__menu" data-stock-select-menu hidden></div>
                            </div>
                        </label>

                        <label class="field field--quantity">
                            <span>Quantity</span>
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
                            <span>Full Name</span>
                            <input type="text" id="partyName" name="full_name" placeholder="Supplier or customer name">
                        </label>

                        <label class="field">
                            <span>Contact Number</span>
                            <input type="text" id="partyContact" name="contact" placeholder="Phone number">
                        </label>

                        <label class="field">
                            <span>Amount Per Piece</span>
                            <input type="number" id="stockPrice" name="amount_per_piece" min="0" step="0.01" value="0">
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
window.stockPageProducts = <?= json_encode(array_values(array_filter(array_map(static function ($stockPageProduct) {
    $stockProductId = (int) ($stockPageProduct['id'] ?? 0);

    if ($stockProductId <= 0) {
        return null;
    }

    return [
        'id' => $stockProductId,
        'name' => (string) ($stockPageProduct['name'] ?? 'Unnamed product'),
        'sku' => (string) ($stockPageProduct['sku'] ?? ''),
        'qty' => (int) ($stockPageProduct['qty'] ?? 0),
        'unit_price' => (float) ($stockPageProduct['unit_price'] ?? 0),
    ];
}, $stockPageProducts))), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('stockMovementForm');
    var message = document.getElementById('stockFormMessage');
    var recordButton = document.getElementById('recordMovementBtn');

    if (!form || form.getAttribute('data-stock-form-guard-ready') === 'true') {
        return;
    }

    form.setAttribute('data-stock-form-guard-ready', 'true');

    form.addEventListener('submit', function (event) {
        var formData = new FormData(form);
        var activeTypeButton = document.querySelector('.stock-toggle__btn.is-active');
        var movementType = activeTypeButton ? activeTypeButton.getAttribute('data-type') : 'in';

        if (!formData.get('product_id')) {
            event.preventDefault();
            if (message) {
                message.textContent = 'Please select a product.';
                message.className = 'form-message is-error';
            }
            return;
        }

        if (movementType === 'in' && !formData.get('incoming_status')) {
            event.preventDefault();
            if (message) {
                message.textContent = 'Please select incoming stock status.';
                message.className = 'form-message is-error';
            }
            return;
        }

        if (recordButton) {
            recordButton.disabled = true;
            recordButton.textContent = 'Recording...';
            window.setTimeout(function () {
                recordButton.disabled = false;
                recordButton.textContent = 'Record Movement';
            }, 5000);
        }
    }, true);

    form.addEventListener('reset', function () {
        window.setTimeout(function () {
            var productLabel = document.querySelector('[data-stock-select-label]');
            var productInput = form.querySelector('input[name="product_id"]');
            var incomingSelect = document.getElementById('incomingStatus');
            var incomingLabel = document.querySelector('#stockInStatusCard [data-stock-select-label]');
            var quantityInput = document.getElementById('stockQuantity');
            var totalInput = document.getElementById('stockTotal');

            if (productInput) {
                productInput.value = '';
            }

            if (productLabel) {
                productLabel.textContent = 'Select product';
            }

            if (incomingSelect) {
                incomingSelect.value = '';
            }

            if (incomingLabel) {
                incomingLabel.textContent = 'Select incoming stock status';
            }

            if (quantityInput) {
                quantityInput.value = '1';
            }

            if (totalInput) {
                totalInput.value = '0.00';
            }

            if (message) {
                message.textContent = '';
                message.className = 'form-message';
            }
        }, 0);
    });
});
</script>

<script>
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

    message = document.getElementById('stockFormMessage');
    if (!message) {
        message = document.createElement('p');
        message.id = 'stockFormMessage';
        message.className = 'form-message';
        form.appendChild(message);
    }

    (function syncVisibleProductValue() {
        var productInput = form.querySelector('[name="product_id"]') || document.getElementById('stockProduct');
        var productField = null;
        var productLabel = '';
        var matchedOption = null;

        document.querySelectorAll('label.field, .field').forEach(function (field) {
            if ((field.textContent || '').toLowerCase().indexOf('product name') !== -1) {
                productField = field;
            }
        });

        if (productInput && productInput.value) {
            return;
        }

        if (productField) {
            productLabel = (productField.querySelector('.stock-product-search__trigger span, [data-stock-select-label]') || {}).textContent || '';
            productLabel = productLabel.trim();
        }

        if (!productLabel || productLabel.toLowerCase() === 'select product') {
            return;
        }

        document.querySelectorAll('option[value]').forEach(function (option) {
            if (matchedOption || !option.value) {
                return;
            }

            if ((option.textContent || '').trim() === productLabel) {
                matchedOption = option;
            }
        });

        if (!matchedOption && window.stockPageProducts && Array.isArray(window.stockPageProducts)) {
            window.stockPageProducts.forEach(function (product) {
                var stock = product.qty || product.stock || 0;
                var label = String(product.name || product.product_name || 'Unnamed product') + ' (Stock: ' + stock + ')';

                if (!matchedOption && label === productLabel) {
                    matchedOption = { value: product.id || product.product_id || '' };
                }
            });
        }

        if (!productInput && form) {
            productInput = document.createElement('input');
            productInput.type = 'hidden';
            productInput.name = 'product_id';
            productInput.id = 'stockProduct';
            form.appendChild(productInput);
        }

        if (productInput && matchedOption && matchedOption.value) {
            productInput.value = matchedOption.value;
        }
    }());

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
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function initIncomingStatusDropdown() {
        var select = document.getElementById('incomingStatus');
        var root = select ? select.closest('[data-stock-select-root]') : null;
        var trigger = root ? root.querySelector('[data-stock-select-trigger]') : null;
        var label = root ? root.querySelector('[data-stock-select-label]') : null;
        var menu = root ? root.querySelector('[data-stock-select-menu]') : null;

        if (!select || !root || !trigger || !label || !menu || root.getAttribute('data-incoming-dropdown-ready') === 'true') {
            return;
        }

        root.setAttribute('data-incoming-dropdown-ready', 'true');
        root.classList.add('stock-custom-select--status');
        select.required = true;
        select.style.setProperty('display', 'none', 'important');

        function closeDropdown() {
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        }

        function updateLabel() {
            var selectedOption = select.options[select.selectedIndex];
            label.textContent = selectedOption && selectedOption.value ? selectedOption.textContent : 'Select incoming stock status';
        }

        function renderOptions() {
            menu.innerHTML = '';

            Array.prototype.slice.call(select.options).forEach(function (option) {
                var button = document.createElement('button');

                button.type = 'button';
                button.className = 'stock-custom-select__option';
                button.textContent = option.textContent;
                button.disabled = option.disabled || option.value === '';

                if (option.value && option.value === select.value) {
                    button.classList.add('is-selected');
                }

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (button.disabled) {
                        return;
                    }

                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateLabel();
                    closeDropdown();
                });

                menu.appendChild(button);
            });
        }

        trigger.addEventListener('click', function (event) {
            var shouldOpen = menu.hidden;

            event.preventDefault();
            event.stopPropagation();
            renderOptions();
            root.classList.toggle('is-open', shouldOpen);
            trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            menu.hidden = !shouldOpen;
        });

        select.addEventListener('change', updateLabel);
        updateLabel();
        renderOptions();

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                closeDropdown();
            }
        });
    }

    initIncomingStatusDropdown();
    window.setTimeout(initIncomingStatusDropdown, 300);
    window.setTimeout(initIncomingStatusDropdown, 1000);
});
</script>

<script>
(function () {
    function removeDuplicateProductSelects() {
        var productField = null;

        document.querySelectorAll('label.field').forEach(function (field) {
            var labelText = field.querySelector('span');

            if (labelText && labelText.textContent.trim().toLowerCase() === 'product name') {
                productField = field;
            }
        });

        if (!productField) {
            return;
        }

        productField.classList.add('stock-product-field');

        productField.querySelectorAll('select, .stock-product-native-select').forEach(function (select) {
            select.style.setProperty('display', 'none', 'important');
            select.style.setProperty('height', '0', 'important');
            select.style.setProperty('min-height', '0', 'important');
            select.style.setProperty('opacity', '0', 'important');
            select.style.setProperty('position', 'absolute', 'important');
            select.style.setProperty('left', '-9999px', 'important');
            select.remove();
        });
    }

    removeDuplicateProductSelects();
    document.addEventListener('DOMContentLoaded', removeDuplicateProductSelects);
    window.addEventListener('load', removeDuplicateProductSelects);

    var attempts = 0;
    var cleanupTimer = window.setInterval(function () {
        attempts += 1;
        removeDuplicateProductSelects();

        if (attempts >= 80) {
            window.clearInterval(cleanupTimer);
        }
    }, 100);

    if (window.MutationObserver) {
        new MutationObserver(removeDuplicateProductSelects).observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
}());
</script>

<style>
.stock-custom-select {
    position: relative;
}

#stockProductNativeFix {
    display: none !important;
}

.stock-product-field select,
.stock-product-field .stock-product-native-select {
    display: none !important;
    height: 0 !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
    position: absolute !important;
    left: -9999px !important;
}

.stock-custom-select__menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    z-index: 50;
    overflow: hidden;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.16);
}

.stock-custom-select__search {
    padding: 8px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.stock-custom-select__search input {
    width: 100%;
    min-height: 38px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 10px;
    color: #111827;
    font: inherit;
    outline: none;
}

.stock-custom-select__search input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.stock-custom-select__options {
    max-height: 260px;
    overflow-y: auto;
    padding: 4px;
}

.stock-custom-select__option,
.stock-custom-select__empty {
    width: 100%;
    min-height: 40px;
    padding: 9px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #111827;
    font: inherit;
    text-align: left;
}

.stock-custom-select__option {
    cursor: pointer;
}

.stock-custom-select__option:hover,
.stock-custom-select__option.is-selected {
    background: #eef2ff;
}

.stock-custom-select__option-meta {
    display: block;
    margin-top: 2px;
    color: #64748b;
    font-size: 12px;
}

.stock-custom-select__empty {
    color: #64748b;
    cursor: default;
}

.stock-custom-select--status [data-stock-select-menu][hidden] {
    display: none !important;
}

.stock-custom-select--status [data-stock-select-menu]:not([hidden]) {
    position: static !important;
    display: grid;
    gap: 22px;
    max-height: none;
    overflow: visible;
    margin-top: 28px;
    padding: 0;
    border: 0 !important;
    border-radius: 0;
    background: transparent !important;
    box-shadow: none !important;
}

.stock-custom-select--status .stock-custom-select__trigger {
    min-height: 38px;
}

.stock-custom-select--status .stock-custom-select__option {
    display: flex;
    align-items: center;
    gap: 18px;
    width: 100%;
    min-height: 54px;
    padding: 0 26px;
    border: 2px solid transparent;
    border-radius: 7px;
    background: #f8fafc;
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
}

.stock-custom-select--status .stock-custom-select__option::before {
    content: "";
    width: 5px;
    height: 5px;
    border-radius: 999px;
    background: #cbd5e1;
    flex: 0 0 auto;
}

.stock-custom-select--status .stock-custom-select__option::after {
    content: "✓";
    display: none;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    margin-left: auto;
    border: 2px solid #22c55e;
    border-radius: 999px;
    color: #22c55e;
    background: transparent;
    font-size: 12px;
    font-weight: 800;
    line-height: 1;
}

.stock-custom-select--status .stock-custom-select__option:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.stock-custom-select--status .stock-custom-select__option.is-selected {
    border-color: #86efac;
    background: #ecfdf3;
    color: #166534;
    font-weight: 700;
}

.stock-custom-select--status .stock-custom-select__option.is-selected::before {
    background: #22c55e;
}

.stock-custom-select--status .stock-custom-select__option.is-selected::after {
    display: flex;
}

.stock-custom-select--status .stock-custom-select__option:disabled {
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function initSearchableProductDropdown() {
        var productSelect = document.getElementById('stockProduct');
        var root = productSelect ? productSelect.closest('[data-stock-select-root]') : null;
        var trigger = root ? root.querySelector('[data-stock-select-trigger]') : null;
        var label = root ? root.querySelector('[data-stock-select-label]') : null;
        var menu = root ? root.querySelector('[data-stock-select-menu]') : null;
        var fallbackSelect = document.getElementById('stockProductNativeFix');

        if (!productSelect || !root || !trigger || !label || !menu) {
            return;
        }

        if (fallbackSelect) {
            fallbackSelect.remove();
        }

        root.classList.remove('stock-custom-select--product-native');
        root.removeAttribute('data-native-product-select');

        function normalizeProduct(product) {
            return {
                id: String(product.id || product.value || ''),
                name: String(product.name || product.text || 'Unnamed product'),
                sku: String(product.sku || ''),
                stock: product.qty || product.stock || product.datasetStock || 0,
                price: product.unit_price || product.price || product.datasetPrice || 0
            };
        }

        function getProductsFromSelect() {
            return Array.isArray(window.stockPageProducts) ? window.stockPageProducts.map(normalizeProduct) : [];
        }

        function syncSelectOptions(products) {
            var selectedValue = productSelect.value;

            if (selectedValue) {
                productSelect.value = selectedValue;
            }
        }

        function buildDropdown(products) {
            menu.innerHTML = ''
                + '<div class="stock-custom-select__search">'
                + '<input type="search" data-product-search placeholder="Search product or SKU" autocomplete="off">'
                + '</div>'
                + '<div class="stock-custom-select__options" data-product-options></div>';

            var search = menu.querySelector('[data-product-search]');
            var optionsWrap = menu.querySelector('[data-product-options]');

            function optionText(product) {
                return product.name + ' (Stock: ' + product.stock + ')';
            }

            function updateLabel() {
                var selectedProduct = products.find(function (product) {
                    return product.id === productSelect.value;
                });

                label.textContent = selectedProduct ? optionText(selectedProduct) : 'Select product';
            }

            function render(query) {
                var normalizedQuery = String(query || '').toLowerCase().trim();
                var matches = products.filter(function (product) {
                    return [
                        product.name,
                        product.sku
                    ].join(' ').toLowerCase().indexOf(normalizedQuery) !== -1;
                });

                optionsWrap.innerHTML = '';

                if (matches.length === 0) {
                    optionsWrap.innerHTML = '<div class="stock-custom-select__empty">No products found</div>';
                    return;
                }

                matches.forEach(function (product) {
                    var button = document.createElement('button');
                    var meta = document.createElement('span');

                    button.type = 'button';
                    button.className = 'stock-custom-select__option';
                    button.dataset.value = product.id;
                    button.textContent = optionText(product);

                    if (product.sku) {
                        meta.className = 'stock-custom-select__option-meta';
                        meta.textContent = 'SKU: ' + product.sku;
                        button.appendChild(meta);
                    }

                    if (productSelect.value === product.id) {
                        button.classList.add('is-selected');
                    }

                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        event.stopPropagation();

                        productSelect.value = product.id;
                        productSelect.dataset.stock = product.stock;
                        productSelect.dataset.price = product.price;
                        productSelect.dataset.name = product.name;
                        productSelect.dataset.sku = product.sku;
                        updateLabel();
                        productSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        root.classList.remove('is-open');
                        trigger.setAttribute('aria-expanded', 'false');
                        menu.hidden = true;
                    });

                    optionsWrap.appendChild(button);
                });
            }

            search.addEventListener('input', function () {
                render(search.value);
            });

            trigger.onclick = function (event) {
                var isOpen;

                event.preventDefault();
                event.stopPropagation();
                isOpen = !root.classList.contains('is-open');

                document.querySelectorAll('[data-stock-select-root].is-open').forEach(function (openRoot) {
                    var openMenu = openRoot.querySelector('[data-stock-select-menu]');
                    var openTrigger = openRoot.querySelector('[data-stock-select-trigger]');
                    openRoot.classList.remove('is-open');
                    if (openTrigger) {
                        openTrigger.setAttribute('aria-expanded', 'false');
                    }
                    if (openMenu) {
                        openMenu.hidden = true;
                    }
                });

                root.classList.toggle('is-open', isOpen);
                trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                menu.hidden = !isOpen;

                if (isOpen) {
                    search.value = '';
                    render('');
                    window.setTimeout(function () { search.focus(); }, 0);
                }
            };

            productSelect.addEventListener('change', updateLabel);
            productSelect.addEventListener('change', function () {
                var priceInput = document.getElementById('stockPrice');
                var quantityInput = document.getElementById('stockQuantity');
                var totalInput = document.getElementById('stockTotal');
                var price = parseFloat(productSelect.dataset.price || '0');

                if (priceInput && price > 0 && parseFloat(priceInput.value || '0') === 0) {
                    priceInput.value = price.toFixed(2);
                }

                if (priceInput && quantityInput && totalInput) {
                    totalInput.value = (parseFloat(quantityInput.value || '0') * parseFloat(priceInput.value || '0')).toFixed(2);
                }
            });
            updateLabel();
            render('');
        }

        function loadProducts() {
            return fetch('api/products/get_user_products.php', { cache: 'no-store' })
                .then(function (response) { return response.json(); })
                .then(function (products) {
                if (!Array.isArray(products)) {
                    products = Array.isArray(products.products) ? products.products : [];
                }

                return products.map(normalizeProduct);
            });
        }

        loadProducts()
            .catch(function () {
                return getProductsFromSelect();
            })
            .then(function (products) {
                syncSelectOptions(products);
                buildDropdown(products);
            });
    }

    initSearchableProductDropdown();

    document.addEventListener('click', function (event) {
        document.querySelectorAll('[data-stock-select-root].is-open').forEach(function (root) {
            if (root.contains(event.target)) {
                return;
            }

            var trigger = root.querySelector('[data-stock-select-trigger]');
            var menu = root.querySelector('[data-stock-select-menu]');

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
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var productField = document.getElementById('stockProduct')
        ? document.getElementById('stockProduct').closest('.field')
        : null;

    function keepOnlySearchableProductDropdown() {
        var productSelect = document.getElementById('stockProduct');
        var productRoot = productSelect ? productSelect.closest('[data-stock-select-root]') : null;

        if (!productField || !productSelect || !productRoot) {
            return;
        }

        productRoot.style.display = '';

        productField.querySelectorAll('select').forEach(function (select) {
            select.remove();
        });
    }

    keepOnlySearchableProductDropdown();
    window.setTimeout(keepOnlySearchableProductDropdown, 100);
    window.setTimeout(keepOnlySearchableProductDropdown, 500);
    window.setTimeout(keepOnlySearchableProductDropdown, 1200);

    if (productField && window.MutationObserver) {
        new MutationObserver(keepOnlySearchableProductDropdown).observe(productField, {
            childList: true,
            subtree: true
        });
    }
});
</script>
