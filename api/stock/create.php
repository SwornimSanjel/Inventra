<?php

<<<<<<< HEAD
error_reporting(0);
ini_set('display_errors',0);

header("Content-Type: application/json");
require_once __DIR__ . "/../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$product_id = $data['product_id'];
$movement_type = $data['movement_type'];
$quantity = (int)$data['quantity'];
$notes = $data['notes'];

$full_name = $data['full_name'];
$contact = $data['contact'];

$price = (float)$data['amount_per_piece'];
$payment_status = $data['payment_status'];
$payment_method = $data['payment_method'];

$incoming_status = $data['incoming_status'] ?? null;
$movement_status = $data['movement_status'] ?? null;

$total = $quantity * $price;

/* get product */
$stmt = $conn->prepare("SELECT qty FROM products WHERE id=?");
$stmt->bind_param("i",$product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if(!$product){
echo json_encode(["success"=>false,"message"=>"Product not found"]);
exit;
}

$current_stock = $product['qty'];

/* validate stock out */
if($movement_type == "out" && $quantity > $current_stock){
echo json_encode([
"success"=>false,
"message"=>"Not enough stock"
]);
exit;
}

/* calculate new stock */
if($movement_type == "in"){
$new_stock = $current_stock + $quantity;
}else{
$new_stock = $current_stock - $quantity;
}

/* update qty */
$update = $conn->prepare("UPDATE products SET qty=? WHERE id=?");
$update->bind_param("ii",$new_stock,$product_id);
$update->execute();

/* reference */
$reference = "STK-" . time();

/* insert movement */
$stmt = $conn->prepare("
INSERT INTO stock_movements
(reference,product_id,movement_type,quantity,notes,
full_name,contact,
amount_per_piece,total_amount,
payment_status,payment_method,
incoming_status,movement_status)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"ssisssddsssss",
$reference,
$product_id,
$movement_type,
$quantity,
$notes,
$full_name,
$contact,
$price,
$total,
$payment_status,
$payment_method,
$incoming_status,
$movement_status
);

$stmt->execute();

echo json_encode([
"success"=>true,
"message"=>"Stock updated successfully",
"new_stock"=>$new_stock,
"reference"=>$reference
]);
=======
header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../helpers/stock_movements.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

if (!inventra_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

inventra_ensure_stock_movements_table($conn);

$payload = json_decode(file_get_contents('php://input'), true);

$productId = (int) ($payload['product_id'] ?? 0);
$movementType = ($payload['movement_type'] ?? 'in') === 'out' ? 'out' : 'in';
$quantity = max(1, (int) ($payload['quantity'] ?? 1));
$notes = trim((string) ($payload['notes'] ?? ''));
$fullName = trim((string) ($payload['full_name'] ?? ''));
$contact = trim((string) ($payload['contact'] ?? ''));
$amountPerPiece = max(0, (float) ($payload['amount_per_piece'] ?? 0));
$paymentStatus = ($payload['payment_status'] ?? 'paid') === 'unpaid' ? 'unpaid' : 'paid';
$paymentMethod = ($payload['payment_method'] ?? 'cash') === 'card' ? 'card' : 'cash';
$incomingStatus = trim((string) ($payload['incoming_status'] ?? ''));
$movementStatus = trim((string) ($payload['movement_status'] ?? ''));
$totalAmount = $quantity * $amountPerPiece;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a product.']);
    exit;
}

$conn->begin_transaction();

try {
    $productStmt = $conn->prepare('SELECT id, name, qty FROM products WHERE id = ? LIMIT 1 FOR UPDATE');
    $productStmt->bind_param('i', $productId);
    $productStmt->execute();
    $product = $productStmt->get_result()->fetch_assoc();

    if (!$product) {
        throw new RuntimeException('Product not found.');
    }

    $currentQty = (int) $product['qty'];

    if ($movementType === 'out' && $quantity > $currentQty) {
        throw new RuntimeException('Not enough stock available for this stock out request.');
    }

    $newQty = $movementType === 'in' ? $currentQty + $quantity : $currentQty - $quantity;

    $updateStmt = $conn->prepare('UPDATE products SET qty = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $updateStmt->bind_param('ii', $newQty, $productId);
    $updateStmt->execute();

    $reference = inventra_generate_stock_reference();

    $movementStmt = $conn->prepare("
        INSERT INTO stock_movements (
            reference,
            product_id,
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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $movementStmt->bind_param(
        'sisisssddssss',
        $reference,
        $productId,
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
        $movementStatus
    );
    $movementStmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Stock movement recorded successfully.',
        'reference' => $reference,
        'new_stock' => $newQty,
    ]);
} catch (Throwable $exception) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
