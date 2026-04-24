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

$result = $conn->query("
    SELECT
        sm.id,
        sm.reference,
        sm.movement_type,
        sm.quantity,
        COALESCE(sm.full_name, '') AS full_name,
        sm.payment_status,
        sm.payment_method,
        COALESCE(sm.incoming_status, '') AS incoming_status,
        COALESCE(sm.movement_status, '') AS movement_status,
        sm.created_at,
        p.name AS product_name
    FROM stock_movements sm
    INNER JOIN products p ON p.id = sm.product_id
    ORDER BY sm.created_at DESC, sm.id DESC
    LIMIT 8
");

$movements = [];
$lastRecordText = 'No movements recorded yet';

foreach ($result->fetchAll() as $row) {
    $timestamp = strtotime((string) $row['created_at']);
    $label = $timestamp ? date('M d, Y h:i A', $timestamp) : (string) $row['created_at'];

    if ($lastRecordText === 'No movements recorded yet' && $timestamp) {
        $lastRecordText = 'Last record: ' . date('M d, Y h:i A', $timestamp);
    }

    $movements[] = [
        'id' => (int) $row['id'],
        'reference' => $row['reference'],
        'movement_type' => $row['movement_type'],
        'quantity' => (int) $row['quantity'],
        'full_name' => $row['full_name'],
        'payment_status' => $row['payment_status'],
        'payment_method' => $row['payment_method'],
        'incoming_status' => $row['incoming_status'],
        'movement_status' => $row['movement_status'],
        'created_at_label' => $label,
        'product_name' => $row['product_name'],
    ];
}

echo json_encode([
    'success' => true,
    'last_record_text' => $lastRecordText,
    'movements' => $movements,
]);
