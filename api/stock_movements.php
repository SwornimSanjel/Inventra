<?php
declare(strict_types=1);

$sessionHelperPath = __DIR__ . '/../helpers/session.php';
if (is_file($sessionHelperPath)) {
    require_once $sessionHelperPath;
}

if (function_exists('inventra_bootstrap_session')) {
    inventra_bootstrap_session();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    if (isset($_COOKIE['INVENTRA_MERGE_APP_SESSID'])) {
        session_name('INVENTRA_MERGE_APP_SESSID');
    }

    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function request_input(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);

    if (is_array($json)) {
        return $json;
    }

    return $_POST;
}

function authenticated_user_id(): ?int
{
    if (function_exists('inventra_authenticated_user_id')) {
        return inventra_authenticated_user_id();
    }

    $candidateKeys = [
        'user_id',
        'id',
        'auth_user_id',
        'admin_id',
        'account_id',
        'logged_in_user_id',
        'logged_user_id',
    ];

    foreach ($candidateKeys as $key) {
        if (isset($_SESSION[$key]) && (int) $_SESSION[$key] > 0) {
            return (int) $_SESSION[$key];
        }
    }

    $sessionAccountKeys = [
        'user',
        'admin',
        'account',
        'auth',
        'auth_user',
        'userPanelAccount',
        'logged_in_user',
        'logged_user',
    ];

    foreach ($sessionAccountKeys as $accountKey) {
        if (isset($_SESSION[$accountKey]) && is_array($_SESSION[$accountKey])) {
            foreach ($candidateKeys as $key) {
                if (isset($_SESSION[$accountKey][$key]) && (int) $_SESSION[$accountKey][$key] > 0) {
                    return (int) $_SESSION[$accountKey][$key];
                }
            }
        }
    }

    return null;
}

function is_local_request(): bool
{
    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    return in_array($remoteAddress, ['127.0.0.1', '::1'], true);
}

function sanitized_session_debug(): array
{
    $debug = [];

    foreach ($_SESSION as $key => $value) {
        if (is_array($value)) {
            $debug[$key] = array_keys($value);
            continue;
        }

        $debug[$key] = gettype($value);
    }

    return $debug;
}

function db_connection()
{
    if (class_exists('Database') && method_exists('Database', 'connect')) {
        return Database::connect();
    }

    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }

    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
        return $GLOBALS['conn'];
    }

    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
        return $GLOBALS['db'];
    }

    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        return $GLOBALS['conn'];
    }

    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
        return $GLOBALS['mysqli'];
    }

    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof mysqli) {
        return $GLOBALS['db'];
    }

    json_response(500, ['success' => false, 'message' => 'Database connection is not available.']);
}

function first_existing_column($db, string $table, array $columns): ?string
{
    foreach ($columns as $column) {
        if (class_exists('Database') && method_exists('Database', 'columnExists') && Database::columnExists($table, $column)) {
            return $column;
        }

        if ($db instanceof PDO) {
            $statement = $db->prepare(
                'SELECT 1
                 FROM information_schema.columns
                 WHERE table_schema = ?
                   AND table_name = ?
                   AND column_name = ?
                 LIMIT 1'
            );
            $statement->execute(['public', $table, $column]);

            if ($statement->fetchColumn()) {
                return $column;
            }
        }
    }

    return null;
}

function required_string(array $input, string $key, array &$errors): string
{
    $value = trim((string) ($input[$key] ?? ''));

    if ($value === '') {
        $errors[$key] = 'This field is required.';
    }

    return $value;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug_session']) && is_local_request()) {
        json_response(200, [
            'success' => true,
            'session_name' => session_name(),
            'has_session_id' => session_id() !== '',
            'cookie_keys' => array_keys($_COOKIE),
            'session_keys' => sanitized_session_debug(),
            'detected_user_id' => authenticated_user_id(),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug_schema']) && is_local_request()) {
        $db = db_connection();
        $table = trim((string) $_GET['debug_schema']);

        if ($db instanceof PDO && $table !== '') {
            $statement = $db->prepare(
                'SELECT column_name, data_type, is_nullable
                 FROM information_schema.columns
                 WHERE table_schema = ?
                   AND table_name = ?
                 ORDER BY ordinal_position'
            );
            $statement->execute(['public', $table]);

            json_response(200, [
                'success' => true,
                'table' => $table,
                'columns' => $statement->fetchAll(PDO::FETCH_ASSOC),
            ]);
        }

        json_response(400, ['success' => false, 'message' => 'Unable to inspect schema.']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug_enums']) && is_local_request()) {
        $db = db_connection();

        if ($db instanceof PDO) {
            $statement = $db->query(
                "SELECT t.typname AS enum_name, e.enumlabel AS enum_value
                 FROM pg_type t
                 JOIN pg_enum e ON t.oid = e.enumtypid
                 JOIN pg_namespace n ON n.oid = t.typnamespace
                 WHERE n.nspname = 'public'
                 ORDER BY t.typname, e.enumsortorder"
            );

            json_response(200, [
                'success' => true,
                'enums' => $statement->fetchAll(PDO::FETCH_ASSOC),
            ]);
        }

        json_response(400, ['success' => false, 'message' => 'Unable to inspect enums.']);
    }

    json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$userId = authenticated_user_id();
if ($userId === null) {
    json_response(401, ['success' => false, 'message' => 'Authentication required.']);
}

$input = request_input();
$errors = [];

$stockType = required_string($input, 'stock_type', $errors);
$productId = (int) ($input['product_id'] ?? 0);
$quantity = (int) ($input['quantity'] ?? 0);
$movementNotes = trim((string) ($input['movement_notes'] ?? ''));
$incomingStatus = trim((string) ($input['incoming_status'] ?? ''));
$movementStatus = trim((string) ($input['movement_status'] ?? ''));
$fullName = required_string($input, 'full_name', $errors);
$contactNumber = required_string($input, 'contact_number', $errors);
$amountPerPiece = trim((string) ($input['amount_per_piece'] ?? ''));
$totalAmount = trim((string) ($input['total_amount'] ?? ''));
$paymentStatus = required_string($input, 'payment_status', $errors);
$paymentMethod = required_string($input, 'payment_method', $errors);

$allowedStockTypes = ['Stock In', 'Stock Out'];
$allowedIncomingStatuses = ['Order Dispatched', 'In Transit', 'Received at Warehouse'];
$allowedPaymentMethods = ['Cash', 'Card'];

if (!in_array($stockType, $allowedStockTypes, true)) {
    $errors['stock_type'] = 'Stock type must be Stock In or Stock Out.';
}

if ($productId <= 0) {
    $errors['product_id'] = 'A valid product ID is required.';
}

if ($quantity <= 0) {
    $errors['quantity'] = 'Quantity must be greater than 0.';
}

if ($stockType === 'Stock In' && !in_array($incomingStatus, $allowedIncomingStatuses, true)) {
    $errors['incoming_status'] = 'Incoming status is invalid.';
}

if ($stockType === 'Stock Out') {
    $incomingStatus = '';
}

if ($stockType === 'Stock In') {
    $movementStatus = '';
}

if ($contactNumber !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $contactNumber)) {
    $errors['contact_number'] = 'Contact number is invalid.';
}

if ($amountPerPiece === '' || !is_numeric($amountPerPiece) || (float) $amountPerPiece < 0) {
    $errors['amount_per_piece'] = 'Amount per piece must be a valid amount.';
}

if ($totalAmount === '' || !is_numeric($totalAmount) || (float) $totalAmount < 0) {
    $errors['total_amount'] = 'Total amount must be a valid amount.';
}

if (!in_array(strtolower($paymentMethod), ['cash', 'card'], true)) {
    $errors['payment_method'] = 'Payment method must be Cash or Card.';
}

if ($errors !== []) {
    json_response(422, ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors]);
}

$db = db_connection();
$movementQuantityColumn = first_existing_column($db, 'stock_movements', ['quantity', 'qty']);

if ($movementQuantityColumn === null) {
    json_response(500, [
        'success' => false,
        'message' => 'Stock movement quantity column is missing.',
    ]);
}

$amountPerPieceValue = number_format((float) $amountPerPiece, 2, '.', '');
$totalAmountValue = number_format((float) $totalAmount, 2, '.', '');
$movementTypeValue = $stockType === 'Stock In' ? 'in' : 'out';
$paymentMethodValue = strtolower($paymentMethod);
$paymentStatusValue = strtolower($paymentStatus);

try {
    if ($db instanceof PDO) {
        $db->beginTransaction();

        $productStatement = $db->prepare('SELECT id, qty FROM products WHERE id = :id FOR UPDATE');
        $productStatement->execute([':id' => $productId]);
        $product = $productStatement->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $db->rollBack();
            json_response(404, ['success' => false, 'message' => 'Product not found.']);
        }

        $currentQuantity = (int) $product['qty'];
        if ($stockType === 'Stock Out' && $quantity > $currentQuantity) {
            $db->rollBack();
            json_response(422, [
                'success' => false,
                'message' => 'Stock-out quantity cannot be greater than available stock.',
                'available_quantity' => $currentQuantity,
            ]);
        }

        $newQuantity = $stockType === 'Stock In'
            ? $currentQuantity + $quantity
            : $currentQuantity - $quantity;

        $movementStatement = $db->prepare(
            'INSERT INTO stock_movements
                (reference, product_id, movement_type, ' . $movementQuantityColumn . ', notes,
                 full_name, contact, amount_per_piece, total_amount, payment_status,
                 payment_method, incoming_status, movement_status, status_updated_by, created_at, updated_at)
             VALUES
                (:reference, :product_id, :movement_type, :quantity, :notes,
                 :full_name, :contact, :amount_per_piece, :total_amount, :payment_status,
                 :payment_method, :incoming_status, :movement_status, :status_updated_by, NOW(), NOW())
             RETURNING id'
        );

        $movementStatement->execute([
            ':reference' => 'SM-' . date('YmdHis') . '-' . random_int(1000, 9999),
            ':product_id' => $productId,
            ':movement_type' => $movementTypeValue,
            ':quantity' => $quantity,
            ':notes' => $movementNotes,
            ':full_name' => $fullName,
            ':contact' => $contactNumber,
            ':amount_per_piece' => $amountPerPieceValue,
            ':total_amount' => $totalAmountValue,
            ':payment_status' => $paymentStatusValue,
            ':payment_method' => $paymentMethodValue,
            ':incoming_status' => $incomingStatus !== '' ? $incomingStatus : null,
            ':movement_status' => $movementStatus !== '' ? $movementStatus : null,
            ':status_updated_by' => $userId,
        ]);

        $movementId = (int) $movementStatement->fetchColumn();

        $updateStatement = $db->prepare('UPDATE products SET qty = :quantity, updated_at = NOW() WHERE id = :id');
        $updateStatement->execute([
            ':quantity' => $newQuantity,
            ':id' => $productId,
        ]);

        $db->commit();
    } elseif ($db instanceof mysqli) {
        $db->begin_transaction();

        $productStatement = $db->prepare('SELECT id, qty FROM products WHERE id = ? FOR UPDATE');
        $productStatement->bind_param('i', $productId);
        $productStatement->execute();
        $productResult = $productStatement->get_result();
        $product = $productResult->fetch_assoc();
        $productStatement->close();

        if (!$product) {
            $db->rollback();
            json_response(404, ['success' => false, 'message' => 'Product not found.']);
        }

        $currentQuantity = (int) $product['qty'];
        if ($stockType === 'Stock Out' && $quantity > $currentQuantity) {
            $db->rollback();
            json_response(422, [
                'success' => false,
                'message' => 'Stock-out quantity cannot be greater than available stock.',
                'available_quantity' => $currentQuantity,
            ]);
        }

        $newQuantity = $stockType === 'Stock In'
            ? $currentQuantity + $quantity
            : $currentQuantity - $quantity;

        $incomingStatusValue = $incomingStatus !== '' ? $incomingStatus : null;
        $movementStatement = $db->prepare(
            'INSERT INTO stock_movements
                (stock_type, product_id, ' . $movementQuantityColumn . ', movement_notes, user_id, incoming_status,
                 full_name, contact_number, amount_per_piece, total_amount, payment_status,
                 payment_method, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $movementStatement->bind_param(
            'siisisssddss',
            $stockType,
            $productId,
            $quantity,
            $movementNotes,
            $userId,
            $incomingStatusValue,
            $fullName,
            $contactNumber,
            $amountPerPieceValue,
            $totalAmountValue,
            $paymentStatus,
            $paymentMethod
        );
        $movementStatement->execute();
        $movementId = (int) $db->insert_id;
        $movementStatement->close();

        $updateStatement = $db->prepare('UPDATE products SET qty = ?, updated_at = NOW() WHERE id = ?');
        $updateStatement->bind_param('ii', $newQuantity, $productId);
        $updateStatement->execute();
        $updateStatement->close();

        $db->commit();
    } else {
        json_response(500, ['success' => false, 'message' => 'Unsupported database connection.']);
    }

    json_response(201, [
        'success' => true,
        'message' => 'Stock movement recorded successfully.',
        'movement_id' => $movementId,
        'product_id' => $productId,
        'quantity' => $newQuantity,
    ]);
} catch (Throwable $exception) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    if ($db instanceof mysqli) {
        $db->rollback();
    }

    $payload = ['success' => false, 'message' => 'Unable to record stock movement.'];

    if (is_local_request()) {
        $payload['debug_error'] = $exception->getMessage();
    }

    json_response(500, $payload);
}
