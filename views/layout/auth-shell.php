<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auth - Inventra</title>
  <?php
    $authCssPath = dirname(__DIR__, 2) . '/public/css/auth.css';
    $authCssVersion = file_exists($authCssPath) ? (string) filemtime($authCssPath) : '1';
    $sessionTimeoutJsPath = dirname(__DIR__, 2) . '/public/js/session-timeout.js';
    $sessionTimeoutJsVersion = file_exists($sessionTimeoutJsPath) ? (string) filemtime($sessionTimeoutJsPath) : '1';
  ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/auth.css?v=<?= htmlspecialchars($authCssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>

<body class="auth-view auth-view-<?= htmlspecialchars($view, ENT_QUOTES, 'UTF-8') ?>">
  <?php require __DIR__ . '/../auth/' . $view . '.php'; ?>
  <?php if (function_exists('inventra_is_authenticated') && inventra_is_authenticated() && defined('INVENTRA_SESSION_TIMEOUT_SECONDS')): ?>
    <!-- We load the shared timeout monitor on protected auth pages too, such as first-login password changes. -->
    <script>window.INVENTRA_SESSION_TIMEOUT = <?= json_encode(INVENTRA_SESSION_TIMEOUT_SECONDS) ?>;</script>
    <script src="<?= BASE_URL ?>public/js/session-timeout.js?v=<?= htmlspecialchars($sessionTimeoutJsVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php endif; ?>
</body>

</html>
