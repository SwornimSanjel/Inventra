(function () {
  var page = document.querySelector('[data-ai-forecasting-page]');
  if (!page) {
    return;
  }

  var state = {
    range: 14,
    allRecommendations: [],
    filteredRecommendations: [],
    categories: [],
    page: 1,
    perPage: 5,
    summaryCards: [],
    insights: [],
    chartItems: [],
    analysisOpen: false
  };

  var apiUrl = page.getAttribute('data-api-url') || 'index.php?url=admin/ai-forecasting/data';
  var content = page.querySelector('[data-forecast-content]');
  var emptyState = page.querySelector('[data-forecast-empty]');
  var emptyMessage = page.querySelector('[data-empty-message]');
  var summaryCardsRoot = page.querySelector('[data-summary-cards]');
  var insightsRoot = page.querySelector('[data-insights-list]');
  var analysisToggle = page.querySelector('[data-analysis-toggle]');
  var analysisPanel = page.querySelector('[data-analysis-panel]');
  var analysisBody = page.querySelector('[data-analysis-body]');
  var chartRoot = page.querySelector('[data-demand-chart]');
  var chartTitle = page.querySelector('[data-chart-title]');
  var recommendationBody = page.querySelector('[data-recommendation-body]');
  var recommendationCount = page.querySelector('[data-recommendation-count]');
  var paginationSummary = page.querySelector('[data-pagination-summary]');
  var paginationPages = page.querySelector('[data-pagination-pages]');
  var prevButton = page.querySelector('[data-page-prev]');
  var nextButton = page.querySelector('[data-page-next]');
  var searchInput = page.querySelector('[data-recommendation-search]');
  var statusFilter = page.querySelector('[data-status-filter]');
  var categoryFilter = page.querySelector('[data-category-filter]');
  var categorySelect = page.querySelector('[data-category-select]');
  var refreshButton = page.querySelector('[data-forecast-refresh]');
  var exportButton = page.querySelector('[data-export-csv]');
  var rangeButtons = Array.prototype.slice.call(page.querySelectorAll('[data-range-btn]'));
  var customSelects = Array.prototype.slice.call(page.querySelectorAll('[data-ai-select]'));

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function iconMarkup(icon) {
    if (icon === 'alert') {
      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path></svg>';
    }

    if (icon === 'clock') {
      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>';
    }

    if (icon === 'trend') {
      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 17 6-6 4 4 8-8"></path><path d="M14 7h7v7"></path></svg>';
    }

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2v4"></path><path d="M18 2v4"></path><path d="M4 10h16"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect></svg>';
  }

  function insightIconMarkup(tone) {
    if (tone === 'warning') {
      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m13 2-2 2.5h3L13 2Z"></path><path d="m10.5 6-6 11h15l-6-11"></path><path d="M12 10v3"></path><path d="M12 16h.01"></path></svg>';
    }

    if (tone === 'success') {
      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="m9 12 2 2 4-4"></path></svg>';
    }

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 14 8l5 2-5 2-2 5-2-5-5-2 5-2 2-5Z"></path></svg>';
  }

  function setLoading(isLoading) {
    page.classList.toggle('ai-loading', !!isLoading);
    if (refreshButton) {
      refreshButton.disabled = !!isLoading;
    }
  }

  function updateRangeButtons() {
    rangeButtons.forEach(function (button) {
      button.classList.toggle('is-active', Number(button.getAttribute('data-range')) === state.range);
    });
  }

  function populateCategories(categories) {
    var current = categoryFilter.value;
    var options = ['<button type="button" class="products-custom-select__option' + (current === '' ? ' is-active' : '') + '" data-ai-select-option data-value="">All Categories</button>'];
    categories.forEach(function (category) {
      options.push(
        '<button type="button" class="products-custom-select__option' + (current === category ? ' is-active' : '') + '" data-ai-select-option data-value="' + escapeHtml(category) + '">' + escapeHtml(category) + '</button>'
      );
    });
    if (categorySelect) {
      var menu = categorySelect.querySelector('[data-ai-select-menu]');
      if (menu) {
        menu.innerHTML = options.join('');
        bindSelectOptions(categorySelect);
      }
    }

    categoryFilter.value = current && categories.indexOf(current) !== -1 ? current : '';
    syncCustomSelect(categorySelect, categoryFilter.value, categoryFilter.value || 'All Categories');
  }

  function closeCustomSelect(selectRoot) {
    if (!selectRoot) {
      return;
    }

    selectRoot.classList.remove('is-open');
    var trigger = selectRoot.querySelector('[data-ai-select-trigger]');
    var menu = selectRoot.querySelector('[data-ai-select-menu]');

    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }

    if (menu) {
      menu.hidden = true;
    }
  }

  function closeAllCustomSelects() {
    customSelects.forEach(closeCustomSelect);
  }

  function syncCustomSelect(selectRoot, value, fallbackLabel) {
    if (!selectRoot) {
      return;
    }

    var hiddenInput = selectRoot.querySelector('[data-ai-select-input]');
    var label = selectRoot.querySelector('[data-ai-select-label]');
    var matchedOption = null;

    if (hiddenInput) {
      hiddenInput.value = value == null ? '' : String(value);
    }

    selectRoot.querySelectorAll('[data-ai-select-option]').forEach(function (option) {
      var isMatch = option.getAttribute('data-value') === String(value);
      option.classList.toggle('is-active', isMatch);
      if (isMatch) {
        matchedOption = option;
      }
    });

    if (label) {
      label.textContent = matchedOption ? matchedOption.textContent.trim() : (fallbackLabel || 'Select option');
    }
  }

  function bindSelectOptions(selectRoot) {
    if (!selectRoot) {
      return;
    }

    var hiddenInput = selectRoot.querySelector('[data-ai-select-input]');

    selectRoot.querySelectorAll('[data-ai-select-option]').forEach(function (option) {
      option.addEventListener('click', function () {
        var value = option.getAttribute('data-value') || '';
        if (hiddenInput) {
          hiddenInput.value = value;
        }

        syncCustomSelect(selectRoot, value, option.textContent.trim());
        closeCustomSelect(selectRoot);
        applyFilters();
      });
    });
  }

  function setupCustomSelects() {
    customSelects.forEach(function (selectRoot) {
      var trigger = selectRoot.querySelector('[data-ai-select-trigger]');
      var menu = selectRoot.querySelector('[data-ai-select-menu]');
      var hiddenInput = selectRoot.querySelector('[data-ai-select-input]');

      if (!trigger || !menu || !hiddenInput) {
        return;
      }

      trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        var isOpen = selectRoot.classList.contains('is-open');
        closeAllCustomSelects();

        if (!isOpen) {
          selectRoot.classList.add('is-open');
          trigger.setAttribute('aria-expanded', 'true');
          menu.hidden = false;
        }
      });

      bindSelectOptions(selectRoot);
      syncCustomSelect(selectRoot, hiddenInput.value || '', trigger.textContent.trim());
    });

    document.addEventListener('click', function (event) {
      customSelects.forEach(function (selectRoot) {
        if (!selectRoot.contains(event.target)) {
          closeCustomSelect(selectRoot);
        }
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeAllCustomSelects();
      }
    });
  }

  function renderSummaryCards(cards) {
    summaryCardsRoot.innerHTML = cards.map(function (card) {
      return [
        '<article class="ai-summary-card ai-summary-card--' + escapeHtml(card.tone) + '">',
        '  <span class="ai-summary-card__label">' + escapeHtml(card.label) + '</span>',
        '  <strong class="ai-summary-card__value">' + escapeHtml(card.count) + '</strong>',
        '  <span class="ai-summary-card__meta">' + escapeHtml(card.meta) + '</span>',
        '  <span class="ai-summary-card__icon" aria-hidden="true">' + iconMarkup(card.icon) + '</span>',
        '</article>'
      ].join('');
    }).join('');
  }

  function renderInsights(insights) {
    insightsRoot.innerHTML = insights.map(function (insight) {
      return [
        '<article class="ai-insight-item ai-insight-item--' + escapeHtml(insight.tone) + '">',
        '  <span class="ai-insight-item__icon" aria-hidden="true">' + insightIconMarkup(insight.tone) + '</span>',
        '  <div>',
        '    <div class="ai-insight-item__title">' + escapeHtml(insight.title) + '</div>',
        '    <p class="ai-insight-item__message">' + escapeHtml(insight.message) + '</p>',
        '  </div>',
        '</article>'
      ].join('');
    }).join('');
  }

  function renderAnalysisPanel() {
    if (!analysisBody) {
      return;
    }

    var criticalItems = state.allRecommendations.filter(function (item) {
      return item.status === 'critical';
    }).slice(0, 3);

    var warningItems = state.allRecommendations.filter(function (item) {
      return item.status === 'warning';
    }).slice(0, 3);

    var overstockItems = state.allRecommendations.filter(function (item) {
      return item.status === 'overstock';
    }).slice(0, 2);

    var sections = [
      '<section class="ai-analysis-panel__item">' +
        '<h3>Forecast coverage</h3>' +
        '<p>' + escapeHtml(state.allRecommendations.length) + ' products are included in the current ' + escapeHtml(state.range) + '-day forecasting window.</p>' +
      '</section>'
    ];

    if (criticalItems.length) {
      sections.push(
        '<section class="ai-analysis-panel__item">' +
          '<h3>Critical attention</h3>' +
          '<p>' + escapeHtml(criticalItems.map(function (item) {
            return item.product_name + ' (' + item.runout_label + ')';
          }).join(', ')) + ' need the fastest replenishment review.</p>' +
        '</section>'
      );
    }

    if (warningItems.length) {
      sections.push(
        '<section class="ai-analysis-panel__item">' +
          '<h3>Demand watchlist</h3>' +
          '<p>' + escapeHtml(warningItems.map(function (item) {
            return item.product_name;
          }).join(', ')) + ' are approaching their runout window and should be monitored closely.</p>' +
        '</section>'
      );
    }

    if (overstockItems.length) {
      sections.push(
        '<section class="ai-analysis-panel__item">' +
          '<h3>Overstock review</h3>' +
          '<p>' + escapeHtml(overstockItems.map(function (item) {
            return item.product_name;
          }).join(', ')) + ' are above their threshold and may benefit from slower replenishment planning.</p>' +
        '</section>'
      );
    }

    analysisBody.innerHTML = sections.join('');
  }

  function syncAnalysisVisibility() {
    if (!analysisPanel || !analysisToggle) {
      return;
    }

    analysisPanel.hidden = !state.analysisOpen;
    analysisToggle.setAttribute('aria-expanded', state.analysisOpen ? 'true' : 'false');
    analysisToggle.textContent = state.analysisOpen ? 'Hide full analysis' : 'View full analysis';
  }

  function renderChart(items, title) {
    chartTitle.textContent = title || 'Demand Forecast - Next 14 Days';

    if (!items.length) {
      chartRoot.innerHTML = '<p class="empty-state">No forecast chart data available for the selected range.</p>';
      return;
    }

    chartRoot.innerHTML = items.map(function (item) {
      return [
        '<div class="ai-demand-chart__row">',
        '  <div>',
        '    <div class="ai-demand-chart__label">' + escapeHtml(item.product_name) + '</div>',
        '    <div class="ai-demand-chart__track">',
        '      <div class="ai-demand-chart__bar ai-demand-chart__bar--' + escapeHtml(item.status) + '" style="width:' + escapeHtml(item.bar_percent) + '%"></div>',
        '    </div>',
        '  </div>',
        '  <div class="ai-demand-chart__days ai-demand-chart__days--' + escapeHtml(item.status) + '">' + escapeHtml(item.runout_label) + '</div>',
        '</div>'
      ].join('');
    }).join('');
  }

  function applyFilters() {
    var search = (searchInput.value || '').toLowerCase().trim();
    var status = statusFilter.value;
    var category = categoryFilter.value;

    state.filteredRecommendations = state.allRecommendations.filter(function (row) {
      var matchesSearch = search === '' || [
        row.product_name,
        row.category,
        row.ai_recommendation,
        row.status_label,
        row.threshold
      ].join(' ').toLowerCase().indexOf(search) !== -1;

      var matchesStatus = status === '' || row.status === status;
      var matchesCategory = category === '' || row.category === category;

      return matchesSearch && matchesStatus && matchesCategory;
    });

    state.page = 1;
    renderRecommendationTable();
  }

  function paginatedRecommendations() {
    var total = state.filteredRecommendations.length;
    var totalPages = Math.max(1, Math.ceil(total / state.perPage));
    state.page = Math.min(state.page, totalPages);
    var start = (state.page - 1) * state.perPage;
    return {
      rows: state.filteredRecommendations.slice(start, start + state.perPage),
      total: total,
      totalPages: totalPages,
      start: start
    };
  }

  function renderRecommendationTable() {
    var pageData = paginatedRecommendations();
    var rows = pageData.rows;

    recommendationCount.textContent = String(state.allRecommendations.length);

    if (!rows.length) {
      recommendationBody.innerHTML = '<tr><td colspan="7" class="empty-state">No recommendations matched the current filters.</td></tr>';
    } else {
      recommendationBody.innerHTML = rows.map(function (row) {
        return [
          '<tr>',
          '  <td>',
          '    <div class="product-copy">',
          '      <div class="product-copy__title">' + escapeHtml(row.product_name) + '</div>',
          '      <div class="product-copy__meta">' + escapeHtml(row.category) + '</div>',
          '    </div>',
          '  </td>',
          '  <td class="ai-recommendations-table__stock">' + escapeHtml(row.current_stock) + ' units</td>',
          '  <td>' + escapeHtml(Number(row.daily_use).toFixed(1)) + '/day</td>',
          '  <td class="ai-recommendations-table__runout--' + escapeHtml(row.status) + '">' + escapeHtml(row.runout_label) + '</td>',
          '  <td>' + escapeHtml(row.ai_recommendation) + '</td>',
          '  <td><span class="ai-status-badge ai-status-badge--' + escapeHtml(row.status) + '">' + escapeHtml(row.status_label) + '</span></td>',
          '  <td>' + escapeHtml(row.threshold) + '</td>',
          '</tr>'
        ].join('');
      }).join('');
    }

    var visibleCount = rows.length;
    var startNumber = pageData.total === 0 ? 0 : pageData.start + 1;
    var endNumber = pageData.start + visibleCount;
    paginationSummary.textContent = pageData.total === 0
      ? 'Showing 0 of 0 products'
      : 'Showing ' + startNumber + '-' + endNumber + ' of ' + pageData.total + ' products';

    renderPagination(pageData.totalPages);
  }

  function renderPagination(totalPages) {
    prevButton.disabled = state.page <= 1;
    nextButton.disabled = state.page >= totalPages;

    var buttons = [];
    for (var pageNumber = 1; pageNumber <= totalPages; pageNumber++) {
      buttons.push(
        '<button type="button" class="ai-page-btn' + (pageNumber === state.page ? ' is-active' : '') + '" data-page-number="' + pageNumber + '">' + pageNumber + '</button>'
      );
    }

    paginationPages.innerHTML = buttons.join('');
  }

  function exportVisibleCsv() {
    var pageData = paginatedRecommendations();
    if (!pageData.rows.length) {
      return;
    }

    var csvRows = [
      ['Product', 'Category', 'Stock', 'Daily Use', 'Run Out', 'AI Recommendation', 'Status', 'Threshold']
    ];

    pageData.rows.forEach(function (row) {
      csvRows.push([
        row.product_name,
        row.category,
        row.current_stock,
        row.daily_use,
        row.runout_label,
        row.ai_recommendation,
        row.status_label,
        row.threshold
      ]);
    });

    var csv = csvRows.map(function (row) {
      return row.map(function (cell) {
        var value = String(cell == null ? '' : cell).replace(/"/g, '""');
        return '"' + value + '"';
      }).join(',');
    }).join('\n');

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'ai-forecasting-recommendations-' + state.range + 'd.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }

  function showEmptyState(message) {
    content.hidden = true;
    emptyState.hidden = false;
    emptyMessage.textContent = message || 'No forecasting data yet';
  }

  function showContent() {
    emptyState.hidden = true;
    content.hidden = false;
  }

  function handleResponse(data) {
    if (!data || data.status === 'empty') {
      showEmptyState(data && data.message ? data.message : 'No forecasting data yet');
      return;
    }

    state.range = Number((data.filters && data.filters.selected_range) || data.selected_range || state.range);
    state.allRecommendations = Array.isArray(data.recommendations) ? data.recommendations : [];
    state.filteredRecommendations = state.allRecommendations.slice();
    state.categories = Array.isArray(data.categories) ? data.categories : [];
    state.summaryCards = Array.isArray(data.summary_cards) ? data.summary_cards : [];
    state.insights = Array.isArray(data.insights) ? data.insights : [];
    state.chartItems = data.chart && Array.isArray(data.chart.items) ? data.chart.items : [];
    state.page = 1;

    updateRangeButtons();
    populateCategories(state.categories);
    renderSummaryCards(state.summaryCards);
    renderInsights(state.insights);
    renderAnalysisPanel();
    renderChart(state.chartItems, data.chart && data.chart.title);
    showContent();
    syncAnalysisVisibility();
    applyFilters();
  }

  function fetchDashboard() {
    setLoading(true);

    fetch(apiUrl + '&range=' + encodeURIComponent(state.range), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        handleResponse(data);
      })
      .catch(function () {
        showEmptyState('Unable to load forecasting data right now.');
      })
      .finally(function () {
        setLoading(false);
      });
  }

  rangeButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      var nextRange = Number(button.getAttribute('data-range'));
      if (!nextRange || nextRange === state.range) {
        return;
      }
      state.range = nextRange;
      updateRangeButtons();
      fetchDashboard();
    });
  });

  if (refreshButton) {
    refreshButton.addEventListener('click', function () {
      fetchDashboard();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
  }

  if (exportButton) {
    exportButton.addEventListener('click', exportVisibleCsv);
  }

  if (analysisToggle) {
    analysisToggle.addEventListener('click', function () {
      state.analysisOpen = !state.analysisOpen;
      syncAnalysisVisibility();
    });
  }

  if (prevButton) {
    prevButton.addEventListener('click', function () {
      if (state.page > 1) {
        state.page -= 1;
        renderRecommendationTable();
      }
    });
  }

  if (nextButton) {
    nextButton.addEventListener('click', function () {
      var totalPages = Math.max(1, Math.ceil(state.filteredRecommendations.length / state.perPage));
      if (state.page < totalPages) {
        state.page += 1;
        renderRecommendationTable();
      }
    });
  }

  if (paginationPages) {
    paginationPages.addEventListener('click', function (event) {
      var button = event.target.closest('[data-page-number]');
      if (!button) {
        return;
      }

      state.page = Number(button.getAttribute('data-page-number')) || 1;
      renderRecommendationTable();
    });
  }

  setupCustomSelects();
  syncAnalysisVisibility();
  fetchDashboard();
})();
