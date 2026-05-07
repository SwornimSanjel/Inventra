<?php
$forecastApiUrl = $forecastingPageState['forecast_api_url'] ?? 'index.php?url=admin/ai-forecasting/data';
$productDetailApiUrl = $forecastingPageState['product_detail_api_url'] ?? 'index.php?url=admin/ai-forecasting/product-detail';
$generateInsightApiUrl = $forecastingPageState['generate_insight_api_url'] ?? 'index.php?url=admin/ai-forecasting/generate-insight';
?>

<div
    class="ai-forecasting-page"
    data-ai-forecasting-page
    data-api-url="<?= htmlspecialchars($forecastApiUrl) ?>"
    data-product-detail-url="<?= htmlspecialchars($productDetailApiUrl) ?>"
    data-generate-insight-url="<?= htmlspecialchars($generateInsightApiUrl) ?>"
>
    <div class="page-header ai-forecasting-page__header">
        <div class="ai-forecasting-page__heading">
            <p class="page-subtitle dashboard-page__intro ai-forecasting-page__subtitle">Monitor stock risks, reorder needs, demand forecasts, and inventory recommendations.</p>
        </div>
        <div class="ai-forecasting-toolbar" role="group" aria-label="Forecast range filters">
            <div class="ai-forecasting-range">
                <button type="button" class="ai-forecasting-range__btn is-active" data-range-btn data-range="7">7d</button>
                <button type="button" class="ai-forecasting-range__btn" data-range-btn data-range="14">14d</button>
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

    <section class="ai-forecasting-empty" data-forecast-empty hidden>
        <div class="ai-forecasting-empty__float ai-forecasting-empty__float--top" aria-hidden="true">
            <span class="ai-forecasting-empty__float-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M5 14l3-3 3 3 6-7"></path>
                    <path d="M16 7h3v3"></path>
                </svg>
            </span>
            <div class="ai-forecasting-empty__float-lines">
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="ai-forecasting-empty__float ai-forecasting-empty__float--bottom" aria-hidden="true">
            <span class="ai-forecasting-empty__float-icon ai-forecasting-empty__float-icon--warning">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 8v4"></path>
                    <path d="M12 16h.01"></path>
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path>
                </svg>
            </span>
            <div class="ai-forecasting-empty__float-lines">
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="ai-forecasting-empty__center">
            <div class="ai-forecasting-empty__icon" aria-hidden="true">
                <div class="ai-forecasting-empty__icon-grid">
                    <span class="ai-forecasting-empty__icon-square ai-forecasting-empty__icon-square--outer"></span>
                    <span class="ai-forecasting-empty__icon-square ai-forecasting-empty__icon-square--middle"></span>
                    <span class="ai-forecasting-empty__icon-square ai-forecasting-empty__icon-square--inner"></span>
                </div>
            </div>

            <h2>No forecasting data yet</h2>
            <p data-empty-message>Record at least 7 days of stock movement to generate AI predictions.</p>

            <div class="ai-forecasting-empty__actions">
                <a href="index.php?url=admin/stock-update" class="btn-primary ai-forecasting-empty__action">Go to Stock Update <span aria-hidden="true">&rarr;</span></a>
                <a href="#" class="ai-forecasting-empty__helper" data-forecast-helper-link>How AI Forecasts work</a>
            </div>
        </div>
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

        <section class="section-card ai-analysis-panel" data-analysis-panel hidden>
            <div class="ai-analysis-panel__header">
                <div>
                    <p class="eyebrow">Expanded analysis</p>
                    <h2>AI Forecasting full analysis</h2>
                    <p class="page-subtitle">Additional forecasting coverage, urgency groups, and demand watchlists for the current range.</p>
                </div>
            </div>
            <div class="ai-analysis-panel__body" data-analysis-body></div>
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

    <div class="ai-detail-overlay" data-detail-overlay hidden></div>
    <aside class="ai-detail-drawer" data-detail-drawer aria-hidden="true">
        <div class="ai-detail-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="aiDetailDrawerTitle">
            <div class="ai-detail-drawer__header">
                <div class="ai-detail-drawer__title-wrap">
                    <div class="ai-detail-drawer__title-row">
                        <h2 id="aiDetailDrawerTitle" data-detail-product-name>Product Analysis</h2>
                        <span class="ai-status-badge" data-detail-status>Pending</span>
                    </div>
                    <p data-detail-product-meta>Review predictive forecast data before generating an explanation.</p>
                </div>
                <button type="button" class="ai-detail-drawer__close" data-detail-close aria-label="Close product details">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="ai-detail-drawer__content">
                <div class="ai-detail-drawer__metrics" data-detail-primary-metrics></div>

                <section class="ai-detail-card ai-detail-graph-card">
                    <div class="ai-detail-card__header">
                        <h3>Stock level - Past 30 days</h3>
                        <p data-detail-threshold-marker>Min. Threshold: 0 units</p>
                    </div>
                    <div class="ai-detail-trend-chart" data-detail-trend-chart></div>
                </section>

                <section class="ai-detail-card">
                    <div class="ai-detail-card__header">
                        <h3>Recent movement pattern</h3>
                    </div>
                    <div class="ai-detail-movement-list" data-detail-movements></div>
                </section>

                <section class="ai-detail-card ai-detail-strategy-card">
                    <div class="ai-detail-card__header ai-detail-strategy-card__header">
                        <h3>Predictive / AI Analysis</h3>
                        <div class="ai-detail-strategy-card__chips">
                            <span class="ai-detail-chip" data-detail-confidence>Confidence: 0%</span>
                            <span class="ai-detail-chip ai-detail-chip--accent">AI Strategic Insight</span>
                        </div>
                    </div>
                    <p class="ai-detail-strategy-card__text" data-detail-strategy-text>No predictive insight loaded yet.</p>

                    <div class="ai-detail-gemini">
                        <div class="ai-detail-gemini__header">
                            <div>
                                <h4>Gemini AI Explanation</h4>
                                <p>Generate a short admin-friendly explanation from the selected predictive forecast result.</p>
                            </div>
                            <button type="button" class="btn-outline ai-detail-gemini__button" data-generate-insight disabled>Generate AI Explanation</button>
                        </div>
                        <p class="ai-gemini-insight-card__loading" data-insight-loading hidden>Generating AI explanation...</p>
                        <div class="ai-gemini-insight-card__box ai-detail-gemini__result" data-insight-box hidden>
                            <p data-insight-text></p>
                            <span class="ai-gemini-insight-card__source" data-insight-source></span>
                        </div>
                    </div>
                </section>
            </div>

            <div class="ai-detail-drawer__footer">
                <a href="index.php?url=admin/products" class="btn-outline ai-detail-drawer__secondary" data-detail-view-product>View Product</a>
                <a href="index.php?url=admin/stock-update" class="btn-outline ai-detail-drawer__secondary" data-detail-update-stock>Update Stock</a>
                <button type="button" class="btn-primary ai-detail-drawer__action" data-detail-mark-reorder>Mark for Reorder</button>
            </div>
        </div>
    </aside>
</div>
