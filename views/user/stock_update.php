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
                <label class="field field--incoming-status">
                    <span class="sr-only">Movement Status</span>
                    <div class="stock-custom-select stock-custom-select--status" data-stock-select-root>
                        <select id="movementStatus" name="movement_status" form="stockMovementForm" class="stock-native-select" data-stock-select-native>
                            <option value="" selected disabled>Select movement status</option>
                            <option value="dispatched">Dispatched from Warehouse</option>
                            <option value="hub">Stock Received at Hub</option>
                            <option value="delivered">Delivery Confirmed</option>
                        </select>
                        <button type="button" class="stock-custom-select__trigger" data-stock-select-trigger aria-expanded="false">
                            <span data-stock-select-label>Select movement status</span>
                            <svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1l5 5 5-5"></path></svg>
                        </button>
                        <div class="stock-custom-select__menu" data-stock-select-menu hidden></div>
                    </div>
                </label>
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
