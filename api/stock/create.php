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

inventra_ensure_stock_movements_table($conn);

$payload = json_decode(file_get_contents('php://input'), true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request payload.']);
    exit;
}

$userId = (int) ($account['id'] ?? $account['user_id'] ?? $account['admin_id'] ?? 0);
$productId = (int) ($payload['product_id'] ?? 0);
$movementTypeInput = trim((string) ($payload['movement_type'] ?? ''));
$quantity = (int) ($payload['quantity'] ?? 0);
$notes = trim((string) ($payload['notes'] ?? ''));
$fullName = trim((string) ($payload['full_name'] ?? ''));
$contact = trim((string) ($payload['contact'] ?? ''));
$amountPerPiece = (float) ($payload['amount_per_piece'] ?? -1);
$paymentStatus = strtolower(trim((string) ($payload['payment_status'] ?? '')));
$paymentMethod = strtolower(trim((string) ($payload['payment_method'] ?? '')));
$incomingStatus = trim((string) ($payload['incoming_status'] ?? ''));
$movementStatus = trim((string) ($payload['movement_status'] ?? ''));

if (in_array($movementTypeInput, ['in', 'stock_in', 'Stock In'], true)) {
    $movementType = 'in';
} elseif (in_array($movementTypeInput, ['out', 'stock_out', 'Stock Out'], true)) {
    $movementType = 'out';
} else {
    echo json_encode(['success' => false, 'message' => 'Please select Stock In or Stock Out.']);
    exit;
}

$incomingStatusMap = [
    'order_dispatched' => 'Order Dispatched',
    'in_transit' => 'In Transit',
    'received' => 'Received at Warehouse',
];

if (isset($incomingStatusMap[$incomingStatus])) {
    $incomingStatus = $incomingStatusMap[$incomingStatus];
}

$allowedIncomingStatuses = [
    'Order Dispatched',
    'In Transit',
    'Received at Warehouse',
];

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a product.']);
    exit;
}

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authenticated user could not be identified.']);
    exit;
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0.']);
    exit;
}

if ($fullName === '') {
    echo json_encode(['success' => false, 'message' => 'Full name is required.']);
    exit;
}

if ($contact === '' || !preg_match('/^[0-9+\-\s()]{7,20}$/', $contact)) {
    echo json_encode(['success' => false, 'message' => 'Valid contact number is required.']);
    exit;
}

if ($amountPerPiece < 0) {
    echo json_encode(['success' => false, 'message' => 'Amount per piece must be valid.']);
    exit;
}

if (!in_array($paymentStatus, ['paid', 'unpaid'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment status.']);
    exit;
}

if (!in_array($paymentMethod, ['cash', 'card'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method.']);
    exit;
}

if ($movementType === 'in' && !in_array($incomingStatus, $allowedIncomingStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid incoming stock status.']);
    exit;
}

if ($movementType === 'out') {
    $incomingStatus = null;
}

$totalAmount = round($quantity * $amountPerPiece, 2);

$conn->beginTransaction();

try {
    $productStmt = $conn->prepare('SELECT id, name, qty FROM products WHERE id = ? LIMIT 1 FOR UPDATE');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();

    if (!$product) {
        throw new RuntimeException('Product not found.');
    }

    $currentQty = (int) $product['qty'];

    if ($movementType === 'out' && $quantity > $currentQty) {
        throw new RuntimeException('Not enough stock available for this stock out request.');
    }

    $newQty = $movementType === 'in' ? $currentQty + $quantity : $currentQty - $quantity;

    $updateStmt = $conn->prepare('UPDATE products SET qty = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $updateStmt->execute([$newQty, $productId]);

    $reference = inventra_generate_stock_reference();

    $movementStmt = $conn->prepare("
        INSERT INTO stock_movements (
            reference,
            product_id,
            user_id,
            movement_type,
            quantity,
            notes,
            full_name,
            contact,
            amount_per_piece,
            total_amount,
            payment_status,
            payment_method,
            incoming_status,
            movement_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $movementStmt->execute([
        $reference,
        $productId,
        $userId,
        $movementType,
        $quantity,
        $notes,
        $fullName,
        $contact,
        $amountPerPiece,
        $totalAmount,
        $paymentStatus,
        $paymentMethod,
        $incomingStatus,
        $movementStatus,
    ]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Stock movement recorded successfully.',
        'reference' => $reference,
        'new_stock' => $newQty,
    ]);
} catch (Throwable $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
