<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
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

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
if (!$stmt->execute([$id])) {
    echo json_encode(['success' => false, 'message' => 'Unable to delete product right now.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
