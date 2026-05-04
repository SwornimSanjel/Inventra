<div class="dashboard-page user-dashboard-page" style="width:100%;max-width:none;">
    <div class="page-header dashboard-page__header">
        <div>
            <p style="color:#8a94a6;font-size:13px;line-height:1.5;font-weight:400;margin:0;">
                Track inventory health, activity, and low stock risks across the current system.
            </p>
        </div>
        <a class="btn-outline" href="index.php?url=user/products">View products</a>
    </div>

    <section class="dashboard-stats user-dashboard-stats" aria-label="Inventory overview">
        <article class="stat-card user-stat-card">
            <span class="stat-card__label">Total Products</span>
            <strong class="stat-card__value" id="userTotalProducts">0</strong>
            <span class="stat-card__hint">Items currently tracked</span>
        </article>

        <article class="stat-card user-stat-card">
            <span class="stat-card__label">Categories</span>
            <strong class="stat-card__value" id="userTotalCategories">0</strong>
            <span class="stat-card__hint">Catalog groups in use</span>
        </article>

        <article class="stat-card user-stat-card">
            <span class="stat-card__label">Active Users</span>
            <strong class="stat-card__value" id="userActiveUsers">0</strong>
            <span class="stat-card__hint">Team members with active access</span>
        </article>

        <article class="stat-card user-stat-card user-stat-card--alert" style="background:#fff;border-color:#ff3b3b;">
            <span class="stat-card__label">Low Stock Items</span>
            <strong class="stat-card__value" id="userLowStockItems">0</strong>
            <span class="stat-card__hint">Require attention soon</span>
        </article>
    </section>

    <section class="section-card user-alerts-panel">
        <div class="user-alerts-panel__header">
            <div class="user-alerts-panel__title">
                <div>
                    <span class="section-eyebrow">PRIORITY LIST</span>
                    <h2>System-wide low stock alerts</h2>
                    <p>Products that are below their threshold or close to running out.</p>
                </div>
            </div>
            <a class="user-alerts-panel__view-all"
                href="index.php?url=user/products"
                style="display:inline-flex;align-items:center;justify-content:center;min-width:72px;height:32px;padding:0 16px;border-radius:6px;background:#2f3744;color:#fff;font-weight:700;text-decoration:none;">
                View all
            </a>
        </div>

        <div class="dashboard-table-wrap user-alerts-table-wrap">
            <table class="data-table user-alerts-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th style="text-align:center">Stock</th>
                        <th style="text-align:center">Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="userLowStockTableBody">
                    <tr>
                        <td colspan="6" class="empty-state">Loading low stock alerts...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="user-alerts-pagination" aria-label="Low stock pagination">
            <span id="userLowStockShowing">Showing 0 of 0</span>
            <div class="user-alerts-pagination__buttons">
                <button class="user-page-btn" type="button" id="userLowStockPrev" aria-label="Previous page">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                </button>
                <button class="user-page-btn" type="button" id="userLowStockNext" aria-label="Next page">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </button>
            </div>
        </div>
    </section>
</div>
