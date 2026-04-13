<?php
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventra | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <div class="app-layout">
        <?php include 'user_sidepanel.php'; ?>

        <main class="main-content">
            <?php include 'user_header.php'; ?>

            <div class="page-body">
                <h1>Overview</h1>
                <p>Welcome to your inventory management system.</p>
            </div>
        </main>
    </div>

</body>
</html>