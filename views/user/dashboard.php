<div class="dashboard-page">
    <div class="page-header dashboard-page__header">
        <div>
            <p class="page-subtitle dashboard-page__intro">Track inventory health, activity, and low stock risk across the current system.</p>
        </div>
        <a href="index.php?url=user/products" class="btn-outline">View products</a>
    </div>

    <section class="dashboard-stats" id="dashboardStats">
        <article class="stat-card dashboard-card" data-dashboard-view="products" tabindex="0" role="button" aria-pressed="false">
            <span class="stat-card__label">Total Products</span>
            <strong class="stat-card__value" id="totalProducts">0</strong>
            <span class="stat-card__meta">Items currently tracked</span>
        </article>
        <article class="stat-card dashboard-card" data-dashboard-view="categories" tabindex="0" role="button" aria-pressed="false">
            <span class="stat-card__label">Categories</span>
            <strong class="stat-card__value" id="totalCategories">0</strong>
            <span class="stat-card__meta">Catalog groups in use</span>
        </article>
        <article class="stat-card dashboard-card" data-dashboard-view="users" tabindex="0" role="button" aria-pressed="false">
            <span class="stat-card__label">Active Users</span>
            <strong class="stat-card__value" id="activeUsers">0</strong>
            <span class="stat-card__meta">Team members with active access</span>
        </article>
        <article class="stat-card stat-card--alert dashboard-card is-active" data-dashboard-view="low_stock" tabindex="0" role="button" aria-pressed="true">
            <span class="stat-card__label">Low Stock Items</span>
            <strong class="stat-card__value" id="lowStockItems">0</strong>
            <span class="stat-card__meta">Require attention soon</span>
        </article>
    </section>

    <section class="section-card dashboard-panel">
        <div class="dashboard-panel__header">
            <div>
                <p class="eyebrow" id="dashboardPanelEyebrow">Priority list</p>
                <h2 id="dashboardPanelTitle">System-wide low stock alerts</h2>
                <p class="page-subtitle" id="dashboardPanelDescription">Products that are below their threshold or close to running out.</p>
            </div>
            <a id="viewAllBtn" href="index.php?url=user/products" class="btn-primary">View all</a>
        </div>

        <div class="dashboard-table-wrap">
            <table class="data-table">
                <thead id="dashboardTableHead">
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th style="text-align:center">Stock</th>
                        <th style="text-align:center">Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="dashboardTableBody">
                    <tr>
                        <td colspan="6" class="empty-state">Loading dashboard data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
