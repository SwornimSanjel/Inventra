<?php
declare(strict_types=1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/db.php';

    $tableCandidates = [
        'stock_movements',
        'stock_movement',
        'stock_history',
        'stock_logs',
        'stock_log',
        'inventory_movements',
        'inventory_movement',
        'stock'
    ];

    $tableName = null;
    foreach ($tableCandidates as $candidate) {
        $stmt = $conn->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$candidate]);
        if ($stmt->fetchColumn()) {
            $tableName = $candidate;
            break;
        }
    }

    if (!$tableName) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $columnsStmt = $conn->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $tableName) . '`');
    $columns = $columnsStmt ? array_column($columnsStmt->fetchAll(PDO::FETCH_ASSOC), 'Field') : [];
    $hasColumn = static function (string $column) use ($columns): bool {
        return in_array($column, $columns, true);
    };

    $productJoin = $hasColumn('product_id') ? ' LEFT JOIN products p ON p.id = s.product_id' : '';
    $productName = $hasColumn('product_id') ? 'COALESCE(p.name, "Unknown product")' : '"Unknown product"';
    $dateColumn = 's.id';
    foreach (['created_at', 'date', 'movement_date', 'updated_at'] as $candidate) {
        if ($hasColumn($candidate)) {
            $dateColumn = 's.`' . $candidate . '`';
            break;
        }
    }

    $selects = [
        $hasColumn('id') ? 's.id AS id' : 'NULL AS id',
        $productName . ' AS product',
        $hasColumn('type') ? 's.type AS type' : ($hasColumn('movement_type') ? 's.movement_type AS type' : '"" AS type'),
        $hasColumn('quantity') ? 's.quantity AS quantity' : '0 AS quantity',
        $hasColumn('status') ? 's.status AS status' : ($hasColumn('incoming_status') ? 's.incoming_status AS status' : '"" AS status'),
        $hasColumn('full_name') ? 's.full_name AS party' : ($hasColumn('party_name') ? 's.party_name AS party' : '"" AS party'),
        $hasColumn('payment_status') ? 's.payment_status AS payment' : '"" AS payment',
        $hasColumn('created_at') ? 's.created_at AS created_at' : ($hasColumn('date') ? 's.date AS created_at' : 'NULL AS created_at')
    ];

    $sql = 'SELECT ' . implode(', ', $selects)
        . ' FROM `' . str_replace('`', '``', $tableName) . '` s'
        . $productJoin
        . ' ORDER BY ' . $dateColumn . ' DESC LIMIT 20';

    $stmt = $conn->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode(['success' => true, 'data' => $rows, 'movements' => $rows]);
} catch (Throwable $exception) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'data' => [],
        'movements' => [],
        'message' => $exception->getMessage()
    ]);
}
