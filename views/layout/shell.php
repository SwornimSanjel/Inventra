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
$userPanelAccount = null;

if (strpos($url, 'admin/') === 0) {
  $topbarAccountModel = new AccountModel();
  $topbarAdminSession = new AdminSession($topbarAccountModel);
  $topbarAdmin = $topbarAdminSession->resolveAuthenticatedAdmin();

  if ($topbarAdmin !== null && (($topbarAdmin['source'] ?? 'admin') === 'admin')) {
    $topbarNotificationModel = new NotificationModel();
    $topbarNotifications = $topbarNotificationModel->buildNotificationViewData(
      $topbarNotificationModel->getNotificationsForUser((int) $topbarAdmin['id'])
    );
    $topbarUnreadCount = $topbarNotificationModel->countUnreadForUser((int) $topbarAdmin['id']);
  }
}

if (strpos($url, 'user/') === 0) {
  $userPanelSession = new AdminSession(new AccountModel());
  $resolvedUserAccount = $userPanelSession->resolveAuthenticatedAccount();

  if ($resolvedUserAccount !== null && ($resolvedUserAccount['source'] ?? '') === 'users') {
    $userPanelAccount = (new AccountModel())->findSettingsProfile($resolvedUserAccount) ?? $resolvedUserAccount;
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
</head>

<body>
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
        <?php if ($url === 'admin/settings') require __DIR__ . '/../admin/settings_page.php'; ?>
        <?php if ($url === 'user/dashboard') require __DIR__ . '/../user/dashboard.php'; ?>
        <?php if ($url === 'user/products') require __DIR__ . '/../user/products.php'; ?>
        <?php if ($url === 'user/stock-update') require __DIR__ . '/../user/stock_update.php'; ?>
        <?php if ($url === 'user/settings') require __DIR__ . '/../user/settings.php'; ?>
      </main>
    
    </div>
  </div>

  <script src="<?= BASE_URL ?>public/js/main.js<?= asset_version('public/js/main.js') ?>"></script>
  <?php if ($url === 'admin/dashboard'): ?>
    <script src="<?= BASE_URL ?>public/js/dashboard.js<?= asset_version('public/js/dashboard.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/products'): ?>
    <script src="<?= BASE_URL ?>public/js/products.js<?= asset_version('public/js/products.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/stock-update'): ?>
    <script src="<?= BASE_URL ?>public/js/stock-update.js<?= asset_version('public/js/stock-update.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/users'): ?>
    <script src="<?= BASE_URL ?>public/js/users.js<?= asset_version('public/js/users.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'admin/settings'): ?>
    <script src="<?= BASE_URL ?>public/js/settings.js<?= asset_version('public/js/settings.js') ?>"></script>
  <?php endif; ?>
  <?php if ($url === 'user/settings'): ?>
    <script src="<?= BASE_URL ?>public/js/user-settings.js<?= asset_version('public/js/user-settings.js') ?>"></script>
  <?php endif; ?>

</body>
</html>
