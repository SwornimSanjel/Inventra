<!DOCTYPE html>
<html lang="en">
  
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventra</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/sidebar.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/topbar.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/dashboard.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/users.css">
</head>

<body>
  <div class="app-layout">
    <?php require __DIR__ . '/../partials/admin_sidebar.php'; ?>

    <div class="app-main">
      <?php require __DIR__ . '/../partials/topbar.php'; ?>

      <main class="app-content">
        <?php if ($url === 'admin/products') require __DIR__ . '/../admin/products.php'; ?>
      </main>
    
    </div>
  </div>

</body>
</html>

