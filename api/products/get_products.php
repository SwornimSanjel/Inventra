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
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (($account['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);

$sql = "
    SELECT
        p.id,
        p.category_id,
        p.name,
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
    $sql .= ' AND (p.name LIKE ? OR COALESCE(p.description, "") LIKE ? OR COALESCE(c.name, p.category, "") LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
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
    $computedStatus = strtolower(str_replace(' ', '_', getStockStatus(
        (int) $row['qty'],
        (int) $row['lower_limit'],
        (int) $row['upper_limit']
    )));

    if ($status !== '' && $status !== $computedStatus) {
        continue;
    }

    $row['status'] = $computedStatus;
    $data[] = $row;
}

echo json_encode($data);
