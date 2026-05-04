<?php
require_once __DIR__ . '/icons.php';

$currentUrl = $_GET['url'] ?? '';
$base = BASE_URL;

$nav = [
    ['url' => 'admin/dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['url' => 'admin/users', 'label' => 'Users', 'icon' => 'users'],
    ['url' => 'admin/products', 'label' => 'Products', 'icon' => 'box'],
    ['url' => 'admin/stock-update', 'label' => 'Stock Update', 'icon' => 'stock-update'],
    ['url' => 'admin/ai-forecasting', 'label' => 'AI Forecasting', 'icon' => 'forecast'],
    ['url' => 'admin/settings', 'label' => 'Settings', 'icon' => 'settings'],
];
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-logo">
            <img src="<?= $base ?>public/images/inventra-logo.png"
                 alt="Inventra logo"
                 class="brand-logo-img"
                 width="32"
                 height="32"
                 style="width:32px;height:32px;object-fit:contain;display:block;">
        </span>
        <div>
            <div class="brand-name">Inventra</div>
            <div class="brand-sub">INVENTORY MANAGEMENT</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav as $item): ?>
            <?php
            $active = (
                strpos($currentUrl, $item['url']) === 0 ||
                (
                    $item['url'] === 'admin/stock-update' &&
                    (
                        strpos($currentUrl, 'admin/stock-in') === 0 ||
                        strpos($currentUrl, 'admin/stock-out') === 0
                    )
                )
            ) ? 'active' : '';
            ?>
            <a href="<?= $base ?>index.php?url=<?= $item['url'] ?>" class="nav-item <?= $active ?>">
                <?= icon($item['icon']) ?>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <a href="<?= $base ?>index.php?url=auth/logout" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </a>
    </div>
</aside>
