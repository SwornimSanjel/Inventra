<?php
$statusFilter = $_GET['status'] ?? '';
$searchTerm   = trim($_GET['search'] ?? '');

$allProducts = [
  [
    'id' => 1,
    'product_name' => 'MacBook Pro 14" M2',
    'description' => 'Apple laptop for office and development work',
    'category_name' => 'Electronics',
    'current_qty' => 4,
    'min_threshold' => 5,
    'unit_price' => 285000,
    'status' => 'low',
    'image_path' => ''
  ],
  [
    'id' => 2,
    'product_name' => 'Logitech MX Master 3S',
    'description' => 'Wireless productivity mouse',
    'category_name' => 'Accessories',
    'current_qty' => 0,
    'min_threshold' => 3,
    'unit_price' => 14500,
    'status' => 'out_of_stock',
    'image_path' => ''
  ],
];

$filteredProducts = array_filter($allProducts, function ($product) use ($statusFilter, $searchTerm) {
    $matchesStatus = true;
    $matchesSearch = true;

    if ($statusFilter !== '') {
        $matchesStatus = $product['status'] === $statusFilter;
    }

    if ($searchTerm !== '') {
        $needle = strtolower($searchTerm);
        $matchesSearch =
            strpos(strtolower($product['product_name']), $needle) !== false ||
            strpos(strtolower($product['category_name']), $needle) !== false ||
            strpos(strtolower($product['description']), $needle) !== false;
    }

    return $matchesStatus && $matchesSearch;
});
?>

<div class="page-header">
  <h1 class="page-title">Products</h1>
  <button class="btn-primary" type="button">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="12" y1="5" x2="12" y2="19"/>
      <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Add Product
  </button>
</div>

<div class="filter-bar" style="justify-content:space-between;flex-wrap:wrap;">
  <form method="GET" class="products-toolbar">
    <input type="hidden" name="url" value="admin/products">

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

    <select name="status" class="filter-select" onchange="this.form.submit()" style="min-width:170px">
      <option value="">All Status</option>
      <option value="low" <?= $statusFilter==='low' ? 'selected' : '' ?>>Low Stock</option>
      <option value="medium" <?= $statusFilter==='medium' ? 'selected' : '' ?>>Medium</option>
      <option value="adequate" <?= $statusFilter==='adequate' ? 'selected' : '' ?>>Adequate</option>
      <option value="out_of_stock" <?= $statusFilter==='out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
      <option value="overstocked" <?= $statusFilter==='overstocked' ? 'selected' : '' ?>>Overstocked</option>
    </select>

    <button type="submit" class="btn-outline">Search</button>

    <?php if ($searchTerm !== '' || $statusFilter !== ''): ?>
      <a href="index.php?url=admin/products" class="btn-outline">Clear</a>
    <?php endif; ?>

    <span class="muted products-count">
      <?= count($filteredProducts) ?> product<?= count($filteredProducts) != 1 ? 's' : '' ?>
    </span>
  </form>
</div>

<div class="section-card" style="padding:0;overflow:hidden">
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:40px">S/N</th>
        <th>PRODUCT</th>
        <th>CATEGORY</th>
        <th style="text-align:center">PRICE</th>
        <th style="text-align:center">QUANTITY</th>
        <th style="text-align:center">MIN THRESHOLD</th>
        <th>AVAILABILITY</th>
        <th style="width:90px;text-align:center">ACTIONS</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty($filteredProducts)): ?>
      <?php foreach(array_values($filteredProducts) as $i => $p):
        $s = $p['status'];
        $chips  = [
          'low' => 'chip-low',
          'medium' => 'chip-medium',
          'out_of_stock' => 'chip-out',
          'adequate' => 'chip-adequate',
          'overstocked' => 'chip-overstock'
        ];
        $labels = [
          'low' => 'LOW STOCK',
          'medium' => 'MEDIUM',
          'out_of_stock' => 'OUT OF STOCK',
          'adequate' => 'AVAILABLE',
          'overstocked' => 'OVERSTOCKED'
        ];
        $isLow = in_array($s, ['low', 'out_of_stock']);
      ?>
        <tr>
          <td class="muted"><?= $i + 1 ?>.</td>
          <td>
            <div class="product-meta">
              <div class="product-thumb">
                <?= htmlspecialchars(strtoupper(substr($p['product_name'], 0, 1))) ?>
              </div>
              <div class="product-copy">
                <div style="font-weight:600"><?= htmlspecialchars($p['product_name']) ?></div>
                <?php if (!empty($p['description'])): ?>
                  <p><?= htmlspecialchars($p['description']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($p['category_name']) ?></td>
          <td style="text-align:center">Rs. <?= number_format($p['unit_price'], 2) ?></td>
          <td style="text-align:center;font-weight:700" class="<?= $isLow ? 'qty-low' : 'qty-ok' ?>">
            <?= $p['current_qty'] ?>
          </td>
          <td style="text-align:center"><?= (int)$p['min_threshold'] ?></td>
          <td>
            <span class="chip <?= $chips[$s] ?? 'chip-adequate' ?>">
              <?= $labels[$s] ?? strtoupper($s) ?>
            </span>
          </td>
          <td style="text-align:center">
            <div class="actions-col" style="justify-content:center">
              <button class="icon-btn" title="Edit" type="button">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </button>
              <button class="icon-btn icon-btn-danger" title="Delete" type="button">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="8" class="empty-state">No products found.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>

  <div style="padding:12px 16px;border-top:1px solid var(--border);font-size:.75rem;color:var(--muted)">
    Showing <?= count($filteredProducts) ?> product<?= count($filteredProducts) != 1 ? 's' : '' ?>
  </div>
</div>