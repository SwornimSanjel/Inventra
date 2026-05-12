<?php
declare(strict_types=1);

header('Content-Type: application/json');

function read_stock_payload(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $json = json_decode($raw, true);

    if (is_array($json)) {
        return $json;
    }

    return $_POST;
}

function first_payload_value(array $payload, array $keys, $default = '')
{
    foreach ($keys as $key) {
        if (isset($payload[$key]) && $payload[$key] !== '') {
            return $payload[$key];
        }
    }

    return $default;
}

try {
    require_once __DIR__ . '/../../config/db.php';

    $payload = read_stock_payload();
    $productId = (int) first_payload_value($payload, ['product_id', 'productId']);
    $quantity = (int) first_payload_value($payload, ['quantity', 'qty'], 0);
    $type = strtolower((string) first_payload_value($payload, ['type', 'movement_type', 'stock_type'], 'in'));
    $type = $type === 'out' ? 'out' : 'in';

    if ($productId <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request payload.']);
        exit;
    }

    $movementTable = null;
    foreach (['stock_movements', 'stock_movement', 'stock_history', 'stock_logs', 'stock_log', 'inventory_movements', 'inventory_movement', 'stock'] as $candidate) {
        $stmt = $conn->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$candidate]);
        if ($stmt->fetchColumn()) {
            $movementTable = $candidate;
            break;
        }
    }

    $conn->beginTransaction();

    $productStmt = $conn->prepare('SELECT id, COALESCE(qty, 0) AS qty FROM products WHERE id = ? FOR UPDATE');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new RuntimeException('Selected product was not found.');
    }

    $currentQty = (int) $product['qty'];
    if ($type === 'out' && $quantity > $currentQty) {
        throw new RuntimeException('Insufficient stock for this product.');
    }

    $newQty = $type === 'in' ? $currentQty + $quantity : $currentQty - $quantity;
    $updateProductStmt = $conn->prepare('UPDATE products SET qty = ? WHERE id = ?');
    $updateProductStmt->execute([$newQty, $productId]);

    if ($movementTable) {
        $columnsStmt = $conn->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $movementTable) . '`');
        $columns = $columnsStmt ? array_column($columnsStmt->fetchAll(PDO::FETCH_ASSOC), 'Field') : [];
        $hasColumn = static function (string $column) use ($columns): bool {
            return in_array($column, $columns, true);
        };

        $valuesByColumn = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'qty' => $quantity,
            'type' => $type,
            'movement_type' => $type,
            'stock_type' => $type,
            'notes' => first_payload_value($payload, ['notes'], ''),
            'full_name' => first_payload_value($payload, ['full_name', 'party_name'], ''),
            'party_name' => first_payload_value($payload, ['party_name', 'full_name'], ''),
            'contact' => first_payload_value($payload, ['contact', 'contact_number'], ''),
            'contact_number' => first_payload_value($payload, ['contact_number', 'contact'], ''),
            'amount_per_piece' => first_payload_value($payload, ['amount_per_piece', 'unit_price'], 0),
            'unit_price' => first_payload_value($payload, ['unit_price', 'amount_per_piece'], 0),
            'total_amount' => first_payload_value($payload, ['total_amount'], 0),
            'payment_status' => first_payload_value($payload, ['payment_status'], 'paid'),
            'payment_method' => first_payload_value($payload, ['payment_method'], 'cash'),
            'incoming_status' => first_payload_value($payload, ['incoming_status'], ''),
            'movement_status' => first_payload_value($payload, ['movement_status'], ''),
            'status' => first_payload_value($payload, ['status', 'incoming_status', 'movement_status'], ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $insertColumns = [];
        $insertValues = [];
        foreach ($valuesByColumn as $column => $value) {
            if ($hasColumn($column)) {
                $insertColumns[] = $column;
                $insertValues[] = $value;
            }
        }

        if ($insertColumns) {
            $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
            $quotedColumns = array_map(static function (string $column): string {
                return '`' . str_replace('`', '``', $column) . '`';
            }, $insertColumns);

            $insertSql = 'INSERT INTO `' . str_replace('`', '``', $movementTable) . '` ('
                . implode(', ', $quotedColumns)
                . ') VALUES (' . $placeholders . ')';
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->execute($insertValues);
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Stock movement recorded successfully.',
        'new_quantity' => $newQty
    ]);
} catch (Throwable $exception) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage()
    ]);
}
