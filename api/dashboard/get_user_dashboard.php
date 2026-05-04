<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../helpers/stock_status.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

if (!defined('BASE_URL')) {
    define('BASE_URL', './');
}

$userSession = new AdminSession();
$account = $userSession->resolveAuthenticatedAccount();

if ($account === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$role = strtolower((string) ($account['role'] ?? ''));

// This dashboard returns read-only overview data and is shared by authenticated users and admins.
if (!in_array($role, ['admin', 'user'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 4;
$offset = ($page - 1) * $limit;

$totalProducts = (int) ($conn->query('SELECT COUNT(*) AS total FROM products')->fetch()['total'] ?? 0);
$totalCategories = (int) ($conn->query('SELECT COUNT(*) AS total FROM categories')->fetch()['total'] ?? 0);
$activeUsers = (int) ($conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active'")->fetch()['total'] ?? 0);
$lowStockCount = (int) ($conn->query('SELECT COUNT(*) AS total FROM products WHERE qty < lower_limit')->fetch()['total'] ?? 0);

$lowStockSql = "
    SELECT
        p.id,
        p.name,
        COALESCE(p.qty, 0) AS qty,
        COALESCE(p.lower_limit, 0) AS lower_limit,
        COALESCE(p.upper_limit, 0) AS upper_limit,
        COALESCE(p.image, '') AS image
    FROM products p
    WHERE p.qty < p.lower_limit
    ORDER BY p.qty ASC, p.name ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($lowStockSql);
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();

$lowStock = [];
$rowNumber = $offset + 1;

foreach ($stmt->fetchAll() as $row) {
    $status = strtolower(str_replace(' ', '_', getStockStatus(
        (int) $row['qty'],
        (int) $row['lower_limit'],
        (int) $row['upper_limit']
    )));

    $image = trim((string) $row['image']);
    if ($image !== '' && !preg_match('#^https?://#i', $image) && strpos($image, BASE_URL) !== 0) {
        $image = BASE_URL . ltrim($image, '/');
    }

    $lowStock[] = [
        'id' => (int) $row['id'],
        'sku' => $rowNumber,
        'name' => $row['name'],
        'stock' => (int) $row['qty'],
        'lower_limit' => (int) $row['lower_limit'],
        'upper_limit' => (int) $row['upper_limit'],
        'threshold' => (int) $row['lower_limit'] . '/' . (int) $row['upper_limit'],
        'status' => $status,
        'image' => $image,
    ];

    $rowNumber++;
}

$totalPages = max(1, (int) ceil($lowStockCount / $limit));
$showing = min($limit, max(0, $lowStockCount - $offset));

echo json_encode([
    'summary' => [
        'total_products' => $totalProducts,
        'total_categories' => $totalCategories,
        'active_users' => $activeUsers,
        'low_stock_items' => $lowStockCount,
    ],
    'low_stock' => $lowStock,
    'pagination' => [
        'page' => $page,
        'total_pages' => $totalPages,
        'showing' => $showing,
        'total_items' => $lowStockCount,
    ],
]);
