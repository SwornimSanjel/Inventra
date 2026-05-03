<?php
$forecastApiUrl = $forecastingPageState['forecast_api_url'] ?? 'index.php?url=admin/ai-forecasting/data';
?>

<div class="ai-forecasting-page" data-ai-forecasting-page data-api-url="<?= htmlspecialchars($forecastApiUrl) ?>">
    <div class="page-header ai-forecasting-page__header">
        <div class="ai-forecasting-page__heading">
            <p class="page-subtitle dashboard-page__intro ai-forecasting-page__subtitle">Monitor stock risks, reorder needs, demand forecasts, and inventory recommendations.</p>
        </div>
        <div class="ai-forecasting-toolbar" role="group" aria-label="Forecast range filters">
            <div class="ai-forecasting-range">
                <button type="button" class="ai-forecasting-range__btn" data-range-btn data-range="7">7d</button>
                <button type="button" class="ai-forecasting-range__btn is-active" data-range-btn data-range="14">14d</button>
                <button type="button" class="ai-forecasting-range__btn" data-range-btn data-range="30">30d</button>
            </div>
            <button type="button" class="btn-outline ai-forecasting-refresh" data-forecast-refresh>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.13-3.36L23 10"></path>
                    <path d="M20.49 15a9 9 0 0 1-14.13 3.36L1 14"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <section class="section-card ai-forecasting-empty" data-forecast-empty hidden>
        <div class="ai-forecasting-empty__icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 3v18h18"></path>
                <path d="M7 15l4-4 3 3 5-6"></path>
            </svg>
        </div>
        <h2>No forecasting data yet</h2>
        <p data-empty-message>At least 7 distinct days of stock movement data are required before the AI Forecasting dashboard can generate recommendations.</p>
    </section>

    <div class="ai-forecasting-content" data-forecast-content hidden>
        <section class="ai-forecasting-summary" data-summary-cards></section>

        <section class="ai-forecasting-main-grid">
            <article class="section-card ai-insights-card">
                <div class="ai-insights-card__header">
                    <div class="ai-insights-card__title-wrap">
                        <span class="ai-insights-card__spark">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m12 3 1.9 4.1L18 9l-4.1 1.9L12 15l-1.9-4.1L6 9l4.1-1.9L12 3Z"></path>
                                <path d="M5 16l.9 2.1L8 19l-2.1.9L5 22l-.9-2.1L2 19l2.1-.9L5 16Z"></path>
                            </svg>
                        </span>
                        <h2>AI Insights</h2>
                    </div>
                    <button type="button" class="ai-insights-card__link" data-analysis-toggle aria-expanded="false">View full analysis</button>
                </div>
                <div class="ai-insights-list" data-insights-list></div>
                <div class="ai-analysis-panel" data-analysis-panel hidden>
                    <div class="ai-analysis-panel__body" data-analysis-body></div>
                </div>
            </article>

            <article class="section-card ai-demand-card">
                <div class="dashboard-panel__header ai-demand-card__header">
                    <div>
                        <h2 data-chart-title>Demand Forecast - Next 14 Days</h2>
                    </div>
                    <p class="ai-demand-card__meta">Days remaining</p>
                </div>
                <div class="ai-demand-chart" data-demand-chart></div>
            </article>
        </section>

        <section class="section-card ai-recommendations-card">
            <div class="dashboard-panel__header ai-recommendations-card__header">
                <div class="ai-recommendations-card__title-wrap">
                    <h2>AI Inventory Recommendations</h2>
                    <span class="ai-recommendations-card__count" data-recommendation-count>0</span>
                </div>
            </div>

            <div class="ai-recommendations-toolbar">
                <label class="products-search-wrap ai-recommendations-search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" class="products-search-input" data-recommendation-search placeholder="Search recommendations...">
                </label>

                <div class="products-custom-select filter-select ai-forecasting-select" data-ai-select>
                    <input type="hidden" value="" data-ai-select-input data-status-filter>
                    <button type="button" class="products-custom-select__trigger" data-ai-select-trigger aria-expanded="false">
                        <span data-ai-select-label>All Status</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="products-custom-select__menu" data-ai-select-menu hidden>
                        <button type="button" class="products-custom-select__option is-active" data-ai-select-option data-value="">All Status</button>
                        <button type="button" class="products-custom-select__option" data-ai-select-option data-value="critical">Critical</button>
                        <button type="button" class="products-custom-select__option" data-ai-select-option data-value="warning">Warning</button>
                        <button type="button" class="products-custom-select__option" data-ai-select-option data-value="stable">Stable</button>
                        <button type="button" class="products-custom-select__option" data-ai-select-option data-value="overstock">Overstock</button>
                    </div>
                </div>

                <div class="products-custom-select filter-select ai-forecasting-select" data-ai-select data-category-select>
                    <input type="hidden" value="" data-ai-select-input data-category-filter>
                    <button type="button" class="products-custom-select__trigger" data-ai-select-trigger aria-expanded="false">
                        <span data-ai-select-label>All Categories</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="products-custom-select__menu" data-ai-select-menu hidden>
                        <button type="button" class="products-custom-select__option is-active" data-ai-select-option data-value="">All Categories</button>
                    </div>
                </div>

                <button type="button" class="btn-outline ai-recommendations-export" data-export-csv>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export CSV
                </button>
            </div>

            <div class="dashboard-table-wrap ai-recommendations-table-wrap">
                <table class="data-table ai-recommendations-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Daily Use</th>
                            <th>Run Out</th>
                            <th>AI Recommendation</th>
                            <th>Status</th>
                            <th>Threshold</th>
                        </tr>
                    </thead>
                    <tbody data-recommendation-body>
                        <tr>
                            <td colspan="7" class="empty-state">Loading AI forecasting data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="ai-recommendations-footer">
                <p class="ai-recommendations-footer__count" data-pagination-summary>Showing 0 of 0 products</p>
                <div class="ai-recommendations-pagination">
                    <button type="button" class="icon-btn" data-page-prev aria-label="Previous page">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <div class="ai-recommendations-pagination__pages" data-pagination-pages></div>
                    <button type="button" class="icon-btn" data-page-next aria-label="Next page">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>
