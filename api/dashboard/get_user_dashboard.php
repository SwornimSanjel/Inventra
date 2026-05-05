<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../helpers/stock_status.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

$userSession = new AdminSession();
$account = $userSession->resolveAuthenticatedAccount();

if ($account === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$role = strtolower((string) ($account['role'] ?? ''));

if (!in_array($role, ['admin', 'user'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$totalProducts = (int) ($conn->query('SELECT COUNT(*) AS total FROM products')->fetch()['total'] ?? 0);
$totalCategories = (int) ($conn->query('SELECT COUNT(*) AS total FROM categories')->fetch()['total'] ?? 0);
$activeUsers = (int) ($conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active'")->fetch()['total'] ?? 0);
$lowStockCount = (int) ($conn->query('SELECT COUNT(*) AS total FROM products WHERE qty <= (lower_limit + 5)')->fetch()['total'] ?? 0);

$productsResult = $conn->query("
    SELECT
        p.id,
        p.name,
        COALESCE(c.name, p.category, 'Uncategorized') AS category_name,
        COALESCE(p.qty, 0) AS qty,
        COALESCE(p.unit_price, 0) AS unit_price,
        COALESCE(p.lower_limit, 0) AS lower_limit,
        COALESCE(p.upper_limit, 0) AS upper_limit
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.name ASC
");

$products = [];
foreach ($productsResult->fetchAll() as $row) {
    $products[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'category_name' => $row['category_name'],
        'stock' => (int) $row['qty'],
        'price' => (float) $row['unit_price'],
        'status' => strtolower(str_replace(' ', '_', getStockStatus(
            (int) $row['qty'],
            (int) $row['lower_limit'],
            (int) $row['upper_limit']
        ))),
    ];
}

$categoriesResult = $conn->query("
    SELECT
        c.id,
        c.name,
        COALESCE(c.description, '') AS description,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.description
    ORDER BY c.name ASC
");

$categories = [];
foreach ($categoriesResult->fetchAll() as $row) {
    $categories[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'product_count' => (int) $row['product_count'],
    ];
}

$usersResult = $conn->query("
    SELECT
        id,
        full_name,
        email,
        role,
        status
    FROM users
    WHERE status = 'active'
    ORDER BY full_name ASC
");

$users = [];
foreach ($usersResult->fetchAll() as $row) {
    $users[] = [
        'id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'role' => strtolower((string) ($row['role'] ?? '')) === 'admin' ? 'Admin' : 'User',
        'status' => ucfirst((string) $row['status']),
    ];
}

$lowStockStmt = $conn->prepare("
    SELECT
        p.id,
        p.name,
        COALESCE(c.name, p.category, 'Uncategorized') AS category_name,
        COALESCE(p.qty, 0) AS qty,
        COALESCE(p.lower_limit, 0) AS lower_limit,
        COALESCE(p.upper_limit, 0) AS upper_limit
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.qty <= (p.lower_limit + 5)
    ORDER BY p.qty ASC, p.name ASC
    LIMIT 5
");
$lowStockStmt->execute();

$lowStock = [];
foreach ($lowStockStmt->fetchAll() as $row) {
    $lowStock[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'category_name' => $row['category_name'],
        'stock' => (int) $row['qty'],
        'threshold' => 'L:' . (int) $row['lower_limit'] . ' / U:' . (int) $row['upper_limit'],
        'status' => strtolower(str_replace(' ', '_', getStockStatus(
            (int) $row['qty'],
            (int) $row['lower_limit'],
            (int) $row['upper_limit']
        ))),
    ];
}

echo json_encode([
    'summary' => [
        'total_products' => $totalProducts,
        'total_categories' => $totalCategories,
        'active_users' => $activeUsers,
        'low_stock_items' => $lowStockCount,
    ],
    'low_stock' => $lowStock,
    'products' => $products,
    'categories' => $categories,
    'users' => $users,
]);
