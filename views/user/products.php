<?php
/**
 * User Products Page
 *
 * This matches the Inventra1 user inventory layout so the page keeps the same
 * design, filtering, pagination, and add-product flow.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/product_inventory.php';

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$searchTerm = trim((string) ($_GET['search'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

$db = Database::connect();
$categories = $db->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
$inventory = inventra_fetch_product_inventory_page($db, [
    'search' => $searchTerm,
    'status' => $statusFilter,
    'page' => $page,
    'per_page' => 10,
]);
$products = $inventory['items'];
$pagination = $inventory['pagination'];
$page = (int) $pagination['page'];
$totalPages = (int) $pagination['total_pages'];
$offset = (int) $pagination['offset'];
$selectedStatusLabel =
    $statusFilter === 'low' ? 'Low Stock' :
    ($statusFilter === 'medium' ? 'Medium' :
    ($statusFilter === 'adequate' ? 'Adequate' :
    ($statusFilter === 'out_of_stock' ? 'Out of Stock' :
    ($statusFilter === 'overstocked' ? 'Overstocked' : 'All Status'))));
?>

<div class="products-page products-page--inventory" data-add-product-endpoint="api/products/add_product.php">
    <div class="page-header products-page__header">
        <h1 class="page-title">Products</h1>
        <button class="btn-primary" type="button" id="openProductModal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Product
        </button>
    </div>

    <form method="GET" class="products-toolbar products-toolbar--user">
        <input type="hidden" name="url" value="user/products">
        <?php if ($searchTerm !== ''): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
        <?php endif; ?>

        <div class="products-custom-select filter-select products-filter-select" data-products-select data-products-select-submit>
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
    </form>

    <section class="section-card products-table-card">
        <div class="products-table-wrap">
            <table class="data-table products-data-table">
                <thead>
                    <tr>
                        <th class="products-col-sn">S/N</th>
                        <th class="products-col-name">PRODUCT<br>NAME</th>
                        <th class="products-col-category">CATEGORY</th>
                        <th class="products-col-qty">CURRENT<br>QTY</th>
                        <th class="products-col-threshold">MIN<br>THRESHOLD<br>(L/U)</th>
                        <th class="products-col-price">UNIT<br>PRICE</th>
                        <th class="products-col-status">STATUS</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <?php if ($products): ?>
                        <?php foreach ($products as $index => $product):
                            $statusClass = (string) $product['status'];
                            $statusLabel = strtoupper(str_replace('_', ' ', $statusClass));
                            $unitPrice = (float) $product['unit_price'];
                            $formattedPrice = number_format($unitPrice, abs($unitPrice - floor($unitPrice)) < 0.00001 ? 0 : 2);
                            $productImage = trim((string) ($product['image_path'] ?? ''));
                        ?>
                            <tr>
                                <td class="products-cell-sn"><?= $offset + $index + 1 ?>.</td>
                                <td class="products-cell-name">
                                    <div class="product-meta products-product-meta">
                                        <div class="product-thumb products-product-thumb">
                                            <?php if ($productImage !== ''): ?>
                                                <img src="<?= htmlspecialchars(BASE_URL . ltrim($productImage, '/')) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                                            <?php else: ?>
                                                <?= htmlspecialchars(strtoupper(substr((string) $product['product_name'], 0, 1))) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-copy">
                                            <div class="product-copy__title products-name"><?= htmlspecialchars($product['product_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="products-cell-category"><?= htmlspecialchars($product['category']) ?></td>
                                <td class="products-cell-qty products-cell-qty--<?= htmlspecialchars($statusClass) ?>">
                                    <?= (int) $product['current_qty'] ?>
                                </td>
                                <td class="products-cell-threshold">
                                    <?= htmlspecialchars($product['threshold']) ?>
                                </td>
                                <td class="products-cell-price">
                                    <span class="products-price__currency">Rs.</span>
                                    <span class="products-price__amount"><?= htmlspecialchars($formattedPrice) ?></span>
                                </td>
                                <td class="products-cell-status">
                                    <span class="chip chip-<?= htmlspecialchars($statusClass) ?> products-status-pill">
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">No products matched the current filters.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="products-pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a
                        href="index.php?url=user/products&page=<?= $i ?><?= $searchTerm ? '&search=' . urlencode($searchTerm) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?>"
                        class="products-pagination__link <?= $i === $page ? 'is-active' : '' ?>"
                    >
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
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
