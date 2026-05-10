<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../helpers/stock_status.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

$adminSession = new AdminSession();
$account = $adminSession->resolveAuthenticatedAccount();

if ($account === null) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);

    exit;
}

$role = strtolower(trim((string) ($account['role'] ?? 'staff')));

if (!in_array($role, ['admin', 'staff'], true)) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);

    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);

$skuColumn = null;

try {
    $skuColumnCheck = $conn->query("SHOW COLUMNS FROM products LIKE 'sku'");

    if ($skuColumnCheck && $skuColumnCheck->fetch()) {
        $skuColumn = 'sku';
    }
} catch (Throwable $exception) {
    $skuColumn = null;
}

$skuSelect = $skuColumn !== null
    ? 'COALESCE(p.`' . $skuColumn . '`, "") AS sku,'
    : '"" AS sku,';

$sql = "
    SELECT
        p.id,
        p.category_id,
        p.name,
        {$skuSelect}
        COALESCE(c.name, p.category, '') AS category,
        COALESCE(p.qty, 0) AS qty,
        COALESCE(p.unit_price, 0) AS unit_price,
        COALESCE(p.lower_limit, 0) AS lower_limit,
        COALESCE(p.upper_limit, 0) AS upper_limit,
        COALESCE(p.image, '') AS image,
        COALESCE(p.description, '') AS description,
        p.created_at,
        p.updated_at
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $searchConditions = [
        'p.name LIKE ?',
        'COALESCE(p.description, "") LIKE ?',
        'COALESCE(c.name, p.category, "") LIKE ?'
    ];

    $like = '%' . $search . '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    if ($skuColumn !== null) {
        $searchConditions[] = 'COALESCE(p.`' . $skuColumn . '`, "") LIKE ?';
        $params[] = $like;
    }

    $sql .= ' AND (' . implode(' OR ', $searchConditions) . ')';
}

if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}

$sql .= ' ORDER BY p.name ASC';

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$result = $stmt;
$data = [];

while ($row = $result->fetch()) {
    $computedStatus = strtolower(str_replace(
        ' ',
        '_',
        getStockStatus(
            (int) $row['qty'],
            (int) $row['lower_limit'],
            (int) $row['upper_limit']
        )
    ));

    if ($status !== '' && $status !== $computedStatus) {
        continue;
    }

    $row['status'] = $computedStatus;

    $data[] = $row;
}

echo json_encode($data);