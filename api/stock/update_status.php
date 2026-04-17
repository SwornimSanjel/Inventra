<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../helpers/stock_movements.php';
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

inventra_ensure_stock_movements_table($conn);

$payload = json_decode(file_get_contents('php://input'), true);

$movementId = (int) ($payload['id'] ?? 0);
$status = trim((string) ($payload['status'] ?? ''));

if ($movementId <= 0 || $status === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid stock movement status update request.']);
    exit;
}

$allowedIncomingStatuses = ['order_dispatched', 'in_transit', 'received'];
$allowedOutgoingStatuses = ['dispatched', 'hub', 'delivered'];

$stmt = $conn->prepare('SELECT id, movement_type FROM stock_movements WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $movementId);
$stmt->execute();
$movement = $stmt->get_result()->fetch_assoc();

if (!$movement) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Stock movement not found.']);
    exit;
}

$movementType = (string) $movement['movement_type'];

if ($movementType === 'in') {
    if (!in_array($status, $allowedIncomingStatuses, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid incoming stock status.']);
        exit;
    }

    $updateStmt = $conn->prepare('UPDATE stock_movements SET incoming_status = ? WHERE id = ?');
    $updateStmt->bind_param('si', $status, $movementId);
    $updateStmt->execute();
} else {
    if (!in_array($status, $allowedOutgoingStatuses, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid movement status.']);
        exit;
    }

    $updateStmt = $conn->prepare('UPDATE stock_movements SET movement_status = ? WHERE id = ?');
    $updateStmt->bind_param('si', $status, $movementId);
    $updateStmt->execute();
}

echo json_encode([
    'success' => true,
    'message' => 'Stock movement status updated successfully.',
]);
