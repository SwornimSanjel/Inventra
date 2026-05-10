<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../helpers/stock_status.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

try {
    $adminSession = new AdminSession();
    $account = $adminSession->resolveAuthenticatedAccount();

    if ($account === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $role = strtolower(trim((string) ($account['role'] ?? 'staff')));

    if (!in_array($role, ['admin', 'staff'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $panelBase = $role === 'admin' ? 'admin' : 'user';
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $viewAll = isset($_GET['view_all']);
    $limit = 5;
    $offset = ($page - 1) * $limit;

    $totalProducts = (int) ($conn->query('SELECT COUNT(*) AS total FROM products')->fetch()['total'] ?? 0);
    $totalCategories = (int) ($conn->query('SELECT COUNT(*) AS total FROM categories')->fetch()['total'] ?? 0);
    $activeUsers = (int) ($conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active'")->fetch()['total'] ?? 0);
    $lowStockCount = (int) ($conn->query('SELECT COUNT(*) AS total FROM products WHERE COALESCE(qty, 0) <= (COALESCE(lower_limit, 0) + 5)')->fetch()['total'] ?? 0);

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
            'role' => strtolower((string) ($row['role'] ?? '')) === 'admin' ? 'Admin' : 'Staff',
            'status' => ucfirst((string) $row['status']),
        ];
    }

    $lowStockSql = "
        SELECT
            p.id,
            p.name,
            COALESCE(c.name, p.category, 'Uncategorized') AS category_name,
            COALESCE(p.qty, 0) AS qty,
            COALESCE(p.lower_limit, 0) AS lower_limit,
            COALESCE(p.upper_limit, 0) AS upper_limit
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE COALESCE(p.qty, 0) <= (COALESCE(p.lower_limit, 0) + 5)
        ORDER BY p.qty ASC, p.name ASC
    ";

    if (!$viewAll) {
        $lowStockSql .= ' LIMIT ? OFFSET ?';
        $stmt = $conn->prepare($lowStockSql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt;
    } else {
        $result = $conn->query($lowStockSql);
    }

    $lowStock = [];
    foreach ($result->fetchAll() as $row) {
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

    $totalPages = $viewAll ? 1 : max(1, (int) ceil($lowStockCount / $limit));

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
        'pagination' => [
            'page' => $page,
            'total_pages' => $totalPages,
        ],
        'panel_base' => $panelBase,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Dashboard data could not be loaded.',
        'message' => $exception->getMessage(),
    ]);
}
