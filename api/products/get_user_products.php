<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

$session = new AdminSession();
$account = $session->resolveAuthenticatedAccount();

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

$stmt = $conn->query("
    SELECT
        id,
        name,
        COALESCE(qty, 0) AS qty,
        COALESCE(unit_price, 0) AS unit_price
    FROM products
    ORDER BY name ASC
");

echo json_encode($stmt->fetchAll());
