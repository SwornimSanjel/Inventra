<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/stock_status.php';

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$searchTerm = trim((string) ($_GET['search'] ?? ''));
$categoryFilter = (int) ($_GET['category_id'] ?? 0);

$db = Database::connect();
$categories = $db->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
$categoryOptionsById = [];
foreach ($categories as $categoryRow) {
    $categoryOptionsById[(int) $categoryRow['id']] = (string) $categoryRow['name'];
}
$selectedCategoryLabel = $categoryFilter > 0 && isset($categoryOptionsById[$categoryFilter])
    ? $categoryOptionsById[$categoryFilter]
    : 'All Categories';
$selectedStatusLabel =
    $statusFilter === 'low' ? 'Low Stock' :
    ($statusFilter === 'medium' ? 'Medium' :
    ($statusFilter === 'adequate' ? 'Adequate' :
    ($statusFilter === 'out_of_stock' ? 'Out of Stock' :
    ($statusFilter === 'overstocked' ? 'Overstocked' : 'All Status'))));

$sql = "
    SELECT
        p.id,
        p.category_id,
        p.name,
        COALESCE(p.category, c.name, '') AS category_name,
        COALESCE(p.description, '') AS description,
        COALESCE(p.qty, 0) AS qty,
        COALESCE(p.unit_price, 0) AS unit_price,
        COALESCE(p.lower_limit, 0) AS lower_limit,
        COALESCE(p.upper_limit, 0) AS upper_limit,
        COALESCE(p.image, '') AS image,
        p.created_at,
        p.updated_at
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE 1=1
";
$params = [];

if ($searchTerm !== '') {
    $searchLike = '%' . $searchTerm . '%';
    $sql .= ' AND (p.name LIKE ? OR COALESCE(p.description, "") LIKE ? OR COALESCE(c.name, p.category, "") LIKE ?)';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}

if ($categoryFilter > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryFilter;
}

$sql .= ' ORDER BY p.id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = [];

foreach ($stmt->fetchAll() as $product) {
    $status = strtolower(str_replace(' ', '_', getStockStatus(
        (int) $product['qty'],
        (int) $product['lower_limit'],
        (int) $product['upper_limit']
    )));

    if ($statusFilter !== '' && $status !== $statusFilter) {
        continue;
    }

    $product['status'] = $status;
    $products[] = $product;
}
?>

<div class="products-page" data-add-product-endpoint="api/products/add_product.php">
    <div class="page-header">
        <div>
            <p class="page-subtitle dashboard-page__intro">Manage product records, thresholds, pricing, and category mapping using the current database schema.</p>
        </div>
        <button class="btn-primary" type="button" id="openProductModal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Product
        </button>
    </div>

    <section class="section-card">
        <form method="GET" class="products-toolbar">
            <input type="hidden" name="url" value="user/products">

            <div class="products-search-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    placeholder="Search products..."
                    class="products-search-input"
                >
            </div>

            <div class="products-custom-select filter-select" data-products-select>
                <input type="hidden" name="category_id" value="<?= $categoryFilter ?>" data-products-select-input>
                <button type="button" class="products-custom-select__trigger" data-products-select-trigger aria-expanded="false">
                    <span data-products-select-label><?= htmlspecialchars($selectedCategoryLabel) ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="products-custom-select__menu" data-products-select-menu hidden>
                    <button type="button" class="products-custom-select__option <?= $categoryFilter === 0 ? 'is-active' : '' ?>" data-products-select-option data-value="0">All Categories</button>
                    <?php foreach ($categories as $category): ?>
                        <button type="button" class="products-custom-select__option <?= $categoryFilter === (int) $category['id'] ? 'is-active' : '' ?>" data-products-select-option data-value="<?= (int) $category['id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="products-custom-select filter-select" data-products-select>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>" data-products-select-input>
                <button type="button" class="products-custom-select__trigger" data-products-select-trigger aria-expanded="false">
                    <span data-products-select-label><?= htmlspecialchars($selectedStatusLabel) ?></span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="products-custom-select__menu" data-products-select-menu hidden>
                    <button type="button" class="products-custom-select__option <?= $statusFilter === '' ? 'is-active' : '' ?>" data-products-select-option data-value="">All Status</button>
                    <button type="button" class="products-custom-select__option <?= $statusFilter === 'low' ? 'is-active' : '' ?>" data-products-select-option data-value="low">Low Stock</button>
                    <button type="button" class="products-custom-select__option <?= $statusFilter === 'medium' ? 'is-active' : '' ?>" data-products-select-option data-value="medium">Medium</button>
                    <button type="button" class="products-custom-select__option <?= $statusFilter === 'adequate' ? 'is-active' : '' ?>" data-products-select-option data-value="adequate">Adequate</button>
                    <button type="button" class="products-custom-select__option <?= $statusFilter === 'out_of_stock' ? 'is-active' : '' ?>" data-products-select-option data-value="out_of_stock">Out of Stock</button>
                    <button type="button" class="products-custom-select__option <?= $statusFilter === 'overstocked' ? 'is-active' : '' ?>" data-products-select-option data-value="overstocked">Overstocked</button>
                </div>
            </div>

            <button type="submit" class="btn-outline">Apply</button>

            <?php if ($searchTerm !== '' || $statusFilter !== '' || $categoryFilter > 0): ?>
                <a href="index.php?url=user/products" class="btn-outline">Clear</a>
            <?php endif; ?>

            <span class="muted products-count">
                <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?>
            </span>
        </form>
    </section>

    <section class="section-card products-table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:52px">S/N</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th style="text-align:center">Quantity</th>
                    <th style="text-align:center">Threshold</th>
                    <th style="text-align:center">Price</th>
                    <th>Status</th>
                    <th style="width:110px;text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody id="productsTableBody">
                <?php if ($products): ?>
                    <?php foreach ($products as $index => $product):
                        $statusClass = $product['status'];
                        $statusLabel = strtoupper(str_replace('_', ' ', $statusClass));
                    ?>
                        <tr>
                            <td class="muted"><?= $index + 1 ?></td>
                            <td>
                                <div class="product-meta">
                                    <div class="product-thumb">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?= htmlspecialchars(BASE_URL . ltrim((string) $product['image'], '/')) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php else: ?>
                                            <?= htmlspecialchars(strtoupper(substr($product['name'], 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-copy">
                                        <div class="product-copy__title"><?= htmlspecialchars($product['name']) ?></div>
                                        <p><?= htmlspecialchars($product['description'] !== '' ? $product['description'] : 'No description added yet.') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td style="text-align:center" class="<?= in_array($statusClass, ['low', 'out_of_stock'], true) ? 'qty-low' : 'qty-ok' ?>">
                                <?= (int) $product['qty'] ?>
                            </td>
                            <td style="text-align:center">L: <?= (int) $product['lower_limit'] ?> / U: <?= (int) $product['upper_limit'] ?></td>
                            <td style="text-align:center">Rs. <?= number_format((float) $product['unit_price'], 2) ?></td>
                            <td>
                                <span class="chip chip-<?= htmlspecialchars($statusClass) ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions-col products-actions">
                                    <span class="muted" aria-label="Actions not available">-</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">No products matched the current filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<div class="modal-overlay" id="productModal" hidden>
    <div class="modal-card">
        <div class="modal-card__header">
            <div>
                <h2 id="productModalTitle">Add Product</h2>
                <p class="page-subtitle">Save products against the current `products` and `categories` tables.</p>
            </div>
            <button type="button" class="icon-btn" id="closeProductModal" aria-label="Close product form">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <form id="productForm" class="modal-form" enctype="multipart/form-data">
            <input type="hidden" name="id" id="productId">

            <div class="form-grid">
                <label class="field">
                    <span>Product Name</span>
                    <input type="text" name="name" id="productName" required>
                </label>

                <label class="field">
                    <span>Category</span>
                    <div class="products-custom-select products-custom-select--modal" data-products-select id="productCategorySelect">
                        <input type="hidden" name="category_id" id="productCategory" value="" data-products-select-input>
                        <button type="button" class="products-custom-select__trigger products-custom-select__trigger--modal" data-products-select-trigger aria-expanded="false">
                            <span data-products-select-label>Select category</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="products-custom-select__menu" data-products-select-menu hidden>
                            <button type="button" class="products-custom-select__option products-custom-select__option--accent" data-products-select-option data-value="new">
                                + Add new category
                            </button>
                            <?php foreach ($categories as $category): ?>
                                <button type="button" class="products-custom-select__option" data-products-select-option data-value="<?= (int) $category['id'] ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="product-new-category" id="productNewCategoryWrap" hidden>
                        <input type="text" name="new_category" id="productNewCategory" placeholder="Enter new category name">
                        <textarea name="new_category_description" id="productNewCategoryDescription" rows="2" placeholder="Optional category description"></textarea>
                    </div>
                </label>

                <label class="field">
                    <span>Quantity</span>
                    <input type="number" name="qty" id="productQty" min="0" required>
                </label>

                <label class="field">
                    <span>Unit Price</span>
                    <input type="number" name="price" id="productPrice" min="0" step="0.01" required>
                </label>

                <label class="field">
                    <span>Lower Limit</span>
                    <input type="number" name="lower" id="productLower" min="0" required>
                </label>

                <label class="field">
                    <span>Upper Limit</span>
                    <input type="number" name="upper" id="productUpper" min="0" required>
                </label>

                <label class="field field--full">
                    <span>Description</span>
                    <textarea name="description" id="productDescription" rows="4" placeholder="Add a short description"></textarea>
                </label>

                <label class="field field--full">
                    <span>Image</span>
                    <input type="file" name="image" id="productImage" accept="image/*" hidden>
                    <div class="product-upload">
                        <button type="button" class="btn-outline product-upload__button" id="productImageTrigger">Choose File</button>
                        <span class="product-upload__name" id="productImageName">No file chosen</span>
                    </div>
                </label>
            </div>

            <div class="modal-card__footer">
                <p class="form-message" id="productFormMessage" aria-live="polite"></p>
                <div class="modal-card__actions">
                    <button type="button" class="btn-outline" id="productModalCancel">Cancel</button>
                    <button type="submit" class="btn-primary" id="productSubmitBtn">Save Product</button>
                </div>
            </div>
        </form>
    </div>
</div>
