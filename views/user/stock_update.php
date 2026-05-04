<style>
#movementProduct,
#paymentStatus {
    appearance: auto;
    -webkit-appearance: menulist;
    cursor: pointer;
}
</style>

<div class="stock-movement-page">
    <div class="stock-movement-header">
        <div>
            <h1 class="page-title">Record Stock Movement</h1>
            <p>Log precise inventory flow and transaction details for ledger accuracy.</p>
        </div>
        <span class="stock-movement-recency" aria-label="Last recorded stock movement">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
            Last record: 2 hours ago
        </span>
    </div>

    <form class="stock-movement-form" id="stockMovementForm">
        <div class="stock-movement-grid">
            <div class="stock-movement-left">
                <section class="movement-card">
                    <div class="movement-card__header">
                        <h2>Stock Details</h2>
                        <div class="movement-type-toggle" role="group" aria-label="Stock movement type">
                            <label class="movement-type-option is-selected">
                                <input type="radio" name="movement_type" value="in" checked>
                                <span>Stock In</span>
                            </label>
                            <label class="movement-type-option">
                                <input type="radio" name="movement_type" value="out">
                                <span>Stock Out</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-grid">
                        <label class="form-field form-field--full">
                            <span>Product Name</span>
                            <select name="product_id" id="movementProduct" required>
                                <option value="">Select product</option>
                                <option value="1">Product One</option>
                                <option value="2">Product Two</option>
                                <option value="3">Product Three</option>
                            </select>
                        </label>

                        <label class="form-field" id="quantityField">
                            <span>Quantity</span>
                            <div class="quantity-stepper">
                                <button type="button" id="decreaseQty" aria-label="Decrease quantity">-</button>
                                <input type="number" name="quantity" id="movementQuantity" min="1" value="120" required>
                                <button type="button" id="increaseQty" aria-label="Increase quantity">+</button>
                            </div>
                        </label>

                        <label class="form-field stock-out-only" id="referenceSkuField">
                            <span>Reference SKU</span>
                            <input type="text" name="reference_sku" id="referenceSku" value="TSG-A6-2024-XP9">
                        </label>

                        <label class="form-field form-field--full">
                            <span>Movement Notes</span>
                            <textarea name="notes" id="movementNotes" rows="4"
                                placeholder="Add specific details about the batch condition or carrier..."></textarea>
                        </label>
                    </div>
                </section>

                <section class="movement-card">
                    <h2>Buyer / Seller Details</h2>

                    <div class="form-grid">
                        <label class="form-field">
                            <span>Full Name</span>
                            <input type="text" name="person_name" id="personName" value="Swornim Sanjel Pvt. Ltd." required>
                        </label>

                        <label class="form-field">
                            <span>Contact Number</span>
                            <input type="tel" name="contact_number" id="contactNumber" value="+977-9800000000" required>
                        </label>

                        <label class="form-field">
                            <span>Amount Per Piece (NPR)</span>
                            <input type="number" name="amount_per_piece" id="amountPerPiece" min="0" step="0.01" value="2450" required>
                        </label>

                        <label class="form-field">
                            <span>Total Amount (NPR)</span>
                            <input type="text" id="totalAmount" value="52,94,000.00" readonly>
                        </label>
                    </div>
                </section>
            </div>

            <div class="stock-movement-right">
                <section class="movement-card">
                    <h2>Payment Status</h2>

                    <label class="form-field form-field--full">
                        <span>Status</span>
                        <select name="payment_status" id="paymentStatus" required>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </label>

                    <label class="form-field form-field--full">
                        <span>Payment Method</span>
                        <select name="payment_method" id="paymentMethod" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                        </select>
                    </label>
                </section>

                <section class="movement-card">
                    <h2 id="movementStatusTitle">Incoming Stock Status</h2>

                    <label class="form-field form-field--full incoming-status-dropdown">
                        <span>Status Dropdown</span>
                        <select name="incoming_status" id="incomingStatus">
                            <option value="">Select incoming status</option>
                            <option value="Order Dispatched">Order Dispatched</option>
                            <option value="In Transit">In Transit</option>
                            <option value="Received at Warehouse">Received at Warehouse</option>
                        </select>
                    </label>

                    <div class="movement-status-options" id="movementStatusOptions" aria-label="Selected stock status"></div>
                    <strong class="selected-status" id="selectedIncomingStatus">No status selected</strong>
                </section>
            </div>
        </div>

        <div class="stock-movement-footer">
            <button class="btn-outline" type="reset" id="cancelMovement">Cancel</button>
            <button class="btn-primary" type="submit">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                Record Movement
            </button>
        </div>

        <p class="stock-movement-message" id="stockMovementMessage" role="status" aria-live="polite"></p>
    </form>
</div>
