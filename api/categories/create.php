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

$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Category name is required.']);
    exit;
}

$existingStmt = $conn->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1');
$existingStmt->bind_param('s', $name);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'That category already exists.']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
$stmt->bind_param('ss', $name, $description);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Unable to create category right now.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Category added successfully.',
    'category_id' => (int) $stmt->insert_id,
]);
