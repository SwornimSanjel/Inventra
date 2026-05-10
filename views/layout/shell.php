<!DOCTYPE html>
<html lang="en">
<?php
require_once __DIR__ . '/../../models/AccountModel.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../models/NotificationModel.php';

function asset_version(string $relativePath): string
{
  $absolutePath = dirname(__DIR__, 2) . '/' . $relativePath;
  $mtime = @filemtime($absolutePath);
  return $mtime ? '?v=' . $mtime : '';
}

$topbarAdmin = null;
$topbarNotifications = [];
$topbarUnreadCount = 0;
$topbarNotificationBaseUrl = '';
$userPanelAccount = null;
// Add a page-specific body class so the copied Inventra1 user-products styles
// can stay scoped to the matching page only.
$pageSlug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($url !== '' ? $url : 'home')), '-');
$bodyClass = 'page-' . $pageSlug;

if (strpos($url, 'admin/') === 0) {
  $topbarAccountModel = new AccountModel();
  $topbarAdminSession = new AdminSession($topbarAccountModel);
  $topbarAdmin = $topbarAdminSession->resolveAuthenticatedAdmin();

  if ($topbarAdmin !== null) {
    $topbarNotificationModel = new NotificationModel();
    $topbarNotifications = $topbarNotificationModel->buildNotificationViewData(
      $topbarNotificationModel->getNotificationsForUser(
        (int) $topbarAdmin['id'],
        (string) ($topbarAdmin['source'] ?? 'admin')
      )
    );
    $topbarUnreadCount = $topbarNotificationModel->countUnreadForUser(
      (int) $topbarAdmin['id'],
      (string) ($topbarAdmin['source'] ?? 'admin')
    );
    $topbarNotificationBaseUrl = 'index.php?url=admin/notifications';
  }
}

if (strpos($url, 'user/') === 0) {
  $userPanelSession = new AdminSession(new AccountModel());
  $resolvedUserAccount = $userPanelSession->resolveAuthenticatedAccount();

  if ($resolvedUserAccount !== null && ($resolvedUserAccount['source'] ?? '') === 'users') {
    $userPanelAccount = (new AccountModel())->findSettingsProfile($resolvedUserAccount) ?? $resolvedUserAccount;
    $topbarNotificationModel = new NotificationModel();
    $topbarNotifications = $topbarNotificationModel->buildNotificationViewData(
      $topbarNotificationModel->getNotificationsForUser(
        (int) $resolvedUserAccount['id'],
        (string) ($resolvedUserAccount['source'] ?? 'users')
      )
    );
    $topbarUnreadCount = $topbarNotificationModel->countUnreadForUser(
      (int) $resolvedUserAccount['id'],
      (string) ($resolvedUserAccount['source'] ?? 'users')
    );
    $topbarNotificationBaseUrl = 'index.php?url=user/notifications';
  }
}
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventra</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/global.css<?= asset_version('public/css/global.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/sidebar.css<?= asset_version('public/css/sidebar.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/topbar.css<?= asset_version('public/css/topbar.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/dashboard.css<?= asset_version('public/css/dashboard.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/products.css<?= asset_version('public/css/products.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/stock-update.css<?= asset_version('public/css/stock-update.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/users.css<?= asset_version('public/css/users.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/settings.css<?= asset_version('public/css/settings.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/user-settings.css<?= asset_version('public/css/user-settings.css') ?>">
  <?php if ($url === 'admin/ai-forecasting'): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/ai-forecasting.css<?= asset_version('public/css/ai-forecasting.css') ?>">
  <?php endif; ?>
</head>

<body class="<?= htmlspecialchars($bodyClass) ?>">
  <div class="app-layout">
    <?php if (strpos($url, 'admin/') === 0): ?>
      <?php require __DIR__ . '/../partials/admin_sidebar.php'; ?>
    <?php endif; ?>
    <?php if (strpos($url, 'user/') === 0): ?>
      <?php require __DIR__ . '/../user/layout/user_sidebar.php'; ?>
    <?php endif; ?>

    <div class="app-main">
      <?php if (strpos($url, 'admin/') === 0): ?>
        <?php require __DIR__ . '/../partials/topbar.php'; ?>
      <?php endif; ?>
      <?php if (strpos($url, 'user/') === 0): ?>
        <?php require __DIR__ . '/../user/layout/user_header.php'; ?>
      <?php endif; ?>

      <main class="app-content">
        <?php if ($url === 'admin/dashboard') require __DIR__ . '/../admin/dashboard.php'; ?>
        <?php if ($url === 'admin/users') require __DIR__ . '/../admin/users.php'; ?>
        <?php if ($url === 'admin/products') require __DIR__ . '/../admin/products.php'; ?>
        <?php if ($url === 'admin/stock-update') require __DIR__ . '/../admin/stock_update.php'; ?>
        <?php if ($url === 'admin/ai-forecasting') require __DIR__ . '/../admin/ai_forecasting.php'; ?>
        <?php if ($url === 'admin/settings') require __DIR__ . '/../admin/settings_page.php'; ?>
        <?php if ($url === 'user/dashboard') require __DIR__ . '/../user/dashboard.php'; ?>
        <?php if ($url === 'user/products') require __DIR__ . '/../user/products.php'; ?>
        <?php if ($url === 'user/stock-update') require __DIR__ . '/../user/stock_update.php'; ?>
        <?php if ($url === 'user/settings') require __DIR__ . '/../user/settings.php'; ?>
      </main>
    
    </div>
  </div>

  <script src="<?= BASE_URL ?>public/js/main.js<?= asset_version('public/js/main.js') ?>"></script>
  <?php if ($url === 'admin/dashboard' || $url === 'user/dashboard'): ?>
    <script src="<?= BASE_URL ?>public/js/dashboard.js<?= asset_version('public/js/dashboard.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/products'): ?>
    <script src="<?= BASE_URL ?>public/js/products.js<?= asset_version('public/js/products.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/stock-update'): ?>
    <script src="<?= BASE_URL ?>public/js/stock-update.js<?= asset_version('public/js/stock-update.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'user/stock-update'): ?>
    <script src="<?= BASE_URL ?>public/js/user-stock-movement.js<?= asset_version('public/js/user-stock-movement.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/users'): ?>
    <script src="<?= BASE_URL ?>public/js/users.js<?= asset_version('public/js/users.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/settings'): ?>
    <script src="<?= BASE_URL ?>public/js/settings.js<?= asset_version('public/js/settings.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/ai-forecasting'): ?>
    <script src="<?= BASE_URL ?>public/js/ai-forecasting.js<?= asset_version('public/js/ai-forecasting.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'user/products'): ?>
    <script src="<?= BASE_URL ?>public/js/user-products.js<?= asset_version('public/js/user-products.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'user/settings'): ?>
    <script src="<?= BASE_URL ?>public/js/user-settings.js<?= asset_version('public/js/user-settings.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'user/dashboard'): ?>
    <script src="<?= BASE_URL ?>public/js/user-dashboard.js<?= asset_version('public/js/user-dashboard.js') ?>"></script>
  <?php endif; ?>

</body>
</html>
<script>
(function () {
    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function forceWorkingStockProductSelect() {
        var pageUrl = String(window.location.href || '');
        var oldSelect = document.getElementById('stockProduct') || document.getElementById('stockProductOldBlocked');

        if (pageUrl.indexOf('stock-update') === -1 || !oldSelect) {
            return;
        }

        var existingFix = document.getElementById('stockProductNativeEmergencyFix');
        if (existingFix) {
            existingFix.style.display = 'block';
            existingFix.style.pointerEvents = 'auto';
            return;
        }

        var root = oldSelect.closest('[data-stock-select-root]') || oldSelect.parentElement;
        var nativeSelect = document.createElement('select');

        nativeSelect.id = 'stockProductNativeEmergencyFix';
        nativeSelect.name = 'product_id';
        nativeSelect.required = true;
        nativeSelect.innerHTML = '<option value="">Loading products...</option>';
        nativeSelect.style.cssText = [
            'display:block',
            'width:100%',
            'height:44px',
            'min-height:44px',
            'appearance:auto',
            'background:#f8fafc',
            'border:1px solid #cbd5e1',
            'border-radius:8px',
            'color:#111827',
            'padding:0 38px 0 12px',
            'font:inherit',
            'position:relative',
            'z-index:9999',
            'pointer-events:auto',
            'opacity:1',
            'visibility:visible'
        ].join(';');

        oldSelect.removeAttribute('name');
        oldSelect.style.display = 'none';

        if (root) {
            root.style.display = 'none';
            root.parentNode.insertBefore(nativeSelect, root);
        } else {
            oldSelect.parentNode.insertBefore(nativeSelect, oldSelect);
        }

        fetch('api/products/get_user_products.php', { cache: 'no-store' })
            .then(function (response) { return response.json(); })
            .then(function (products) {
                if (!Array.isArray(products)) {
                    products = Array.isArray(products.products) ? products.products : [];
                }

                nativeSelect.innerHTML = '<option value="">Select product</option>';
                oldSelect.innerHTML = '<option value="">Select product</option>';

                products.forEach(function (product) {
                    var stock = product.qty || product.stock || 0;
                    var price = product.unit_price || product.price || 0;
                    var text = product.name + ' (Stock: ' + stock + ')';
                    var option = document.createElement('option');

                    option.value = product.id;
                    option.textContent = text;
                    option.setAttribute('data-stock', stock);
                    option.setAttribute('data-price', price);
                    nativeSelect.appendChild(option);
                    oldSelect.appendChild(option.cloneNode(true));
                });
            })
            .catch(function () {
                nativeSelect.innerHTML = '<option value="">Unable to load products</option>';
            });

        nativeSelect.addEventListener('change', function () {
            var option = nativeSelect.options[nativeSelect.selectedIndex];
            var priceInput = document.getElementById('stockPrice');
            var quantityInput = document.getElementById('stockQuantity');
            var totalInput = document.getElementById('stockTotal');
            var price = option ? parseFloat(option.getAttribute('data-price') || '0') : 0;

            oldSelect.value = nativeSelect.value;
            oldSelect.dispatchEvent(new Event('change', { bubbles: true }));

            if (priceInput && price > 0 && parseFloat(priceInput.value || '0') === 0) {
                priceInput.value = price.toFixed(2);
            }

            if (priceInput && quantityInput && totalInput) {
                totalInput.value = (parseFloat(quantityInput.value || '0') * parseFloat(priceInput.value || '0')).toFixed(2);
            }
        });

        var form = document.getElementById('stockMovementForm');
        if (form && !form.getAttribute('data-stock-native-emergency-fix')) {
            form.setAttribute('data-stock-native-emergency-fix', 'true');
            form.addEventListener('submit', function () {
                nativeSelect.name = 'product_id';
                oldSelect.removeAttribute('name');
            }, true);
        }
    }

    onReady(function () {
        forceWorkingStockProductSelect();
        window.setTimeout(forceWorkingStockProductSelect, 300);
        window.setTimeout(forceWorkingStockProductSelect, 1000);
        window.setTimeout(forceWorkingStockProductSelect, 2000);
    });
})();
</script>

<script>
(function () {
    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function installSearchableStockProductDropdown() {
        if (String(window.location.href || '').indexOf('stock-update') === -1) {
            return;
        }

        if (document.getElementById('stockProductSearchableFix')) {
            return;
        }

        var originalSelect = document.getElementById('stockProductOldBlocked') || document.getElementById('stockProduct');
        var nativeSelect = document.getElementById('stockProductNativeEmergencyFix');
        var anchor = nativeSelect || originalSelect;

        if (!originalSelect && !nativeSelect) {
            return;
        }

        var wrapper = document.createElement('div');
        wrapper.id = 'stockProductSearchableFix';
        wrapper.className = 'stock-search-select';
        wrapper.innerHTML = ''
            + '<button type="button" class="stock-search-select__trigger" aria-expanded="false">'
            + '<span data-stock-search-label>Select product</span>'
            + '<span class="stock-search-select__chevron">⌄</span>'
            + '</button>'
            + '<div class="stock-search-select__menu" hidden>'
            + '<input type="search" class="stock-search-select__input" placeholder="Search product or SKU" autocomplete="off">'
            + '<div class="stock-search-select__list"></div>'
            + '</div>';

        anchor.parentNode.insertBefore(wrapper, anchor);

        if (nativeSelect) {
            nativeSelect.style.display = 'none';
            nativeSelect.removeAttribute('name');
        }

        if (originalSelect) {
            originalSelect.style.display = 'none';
            originalSelect.name = 'product_id';
        }

        var trigger = wrapper.querySelector('.stock-search-select__trigger');
        var label = wrapper.querySelector('[data-stock-search-label]');
        var menu = wrapper.querySelector('.stock-search-select__menu');
        var searchInput = wrapper.querySelector('.stock-search-select__input');
        var list = wrapper.querySelector('.stock-search-select__list');
        var products = [];

        function syncSelects(product) {
            var value = product ? String(product.id) : '';

            [originalSelect, nativeSelect].forEach(function (select) {
                if (!select) {
                    return;
                }

                var hasOption = Array.prototype.slice.call(select.options).some(function (option) {
                    return option.value === value;
                });

                if (!hasOption && product) {
                    var option = document.createElement('option');
                    var stock = product.qty || product.stock || 0;
                    var price = product.unit_price || product.price || 0;

                    option.value = value;
                    option.textContent = product.name + ' (Stock: ' + stock + ')';
                    option.setAttribute('data-stock', stock);
                    option.setAttribute('data-price', price);
                    option.setAttribute('data-name', product.name || '');
                    option.setAttribute('data-sku', product.sku || '');
                    select.appendChild(option);
                }

                select.value = value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        function render(query) {
            var normalizedQuery = String(query || '').toLowerCase().trim();
            var matches = products.filter(function (product) {
                return [
                    product.name || '',
                    product.sku || ''
                ].join(' ').toLowerCase().indexOf(normalizedQuery) !== -1;
            });

            list.innerHTML = '';

            if (matches.length === 0) {
                list.innerHTML = '<div class="stock-search-select__empty">No products found</div>';
                return;
            }

            matches.forEach(function (product) {
                var stock = product.qty || product.stock || 0;
                var button = document.createElement('button');

                button.type = 'button';
                button.className = 'stock-search-select__option';
                button.innerHTML = escapeHtml(product.name || 'Unnamed product') + ' <span>(Stock: ' + escapeHtml(stock) + ')</span>';
                button.addEventListener('click', function () {
                    label.textContent = (product.name || 'Unnamed product') + ' (Stock: ' + stock + ')';
                    syncSelects(product);
                    closeMenu();
                });

                list.appendChild(button);
            });
        }

        function openMenu() {
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            menu.hidden = false;
            render(searchInput.value);
            window.setTimeout(function () {
                searchInput.focus();
                searchInput.select();
            }, 0);
        }

        function closeMenu() {
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
        }

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (wrapper.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        searchInput.addEventListener('input', function () {
            render(searchInput.value);
        });

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target)) {
                closeMenu();
            }
        });

        fetch('api/products/get_user_products.php', { cache: 'no-store' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                products = Array.isArray(data) ? data : (Array.isArray(data.products) ? data.products : []);
                render('');
            })
            .catch(function () {
                list.innerHTML = '<div class="stock-search-select__empty">Unable to load products</div>';
            });
    }

    function injectSearchableStockProductStyles() {
        if (document.getElementById('stock-product-searchable-style')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'stock-product-searchable-style';
        style.textContent = [
            '.stock-search-select{position:relative;width:100%;z-index:40;}',
            '.stock-search-select__trigger{width:100%;height:44px;display:flex;align-items:center;justify-content:space-between;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#111827;padding:0 12px;font:inherit;text-align:left;cursor:pointer;}',
            '.stock-search-select__trigger:focus{outline:0;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.16);}',
            '.stock-search-select__chevron{font-size:18px;color:#64748b;}',
            '.stock-search-select__menu{position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:9999;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 18px 38px rgba(15,23,42,.16);overflow:hidden;}',
            '.stock-search-select__input{width:calc(100% - 20px);height:38px;margin:10px;border:1px solid #2563eb;border-radius:8px;padding:0 12px;font:inherit;outline:0;}',
            '.stock-search-select__list{max-height:240px;overflow:auto;padding:6px;}',
            '.stock-search-select__option{width:100%;border:0;background:#fff;border-radius:6px;padding:9px 10px;text-align:left;font:inherit;color:#111827;cursor:pointer;}',
            '.stock-search-select__option:hover,.stock-search-select__option:focus{background:#eaf2ff;outline:0;}',
            '.stock-search-select__option span{color:#475569;}',
            '.stock-search-select__empty{padding:14px;text-align:center;color:#64748b;font-size:13px;}'
        ].join('');
        document.head.appendChild(style);
    }

    ready(function () {
        injectSearchableStockProductStyles();
        installSearchableStockProductDropdown();
        window.setTimeout(installSearchableStockProductDropdown, 500);
        window.setTimeout(installSearchableStockProductDropdown, 1200);
    });
})();
</script>
