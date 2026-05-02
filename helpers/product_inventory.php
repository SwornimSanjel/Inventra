<?php

function inventra_product_inventory_allowed_statuses(): array
{
    return ['low', 'medium', 'adequate', 'overstocked', 'out_of_stock'];
}

function inventra_normalize_product_inventory_status(string $status): string
{
    $status = trim(strtolower($status));
    return in_array($status, inventra_product_inventory_allowed_statuses(), true) ? $status : '';
}

function inventra_product_inventory_status_case(): string
{
    return "
        CASE
            WHEN COALESCE(p.qty, 0) <= 0 THEN 'out_of_stock'
            WHEN COALESCE(p.qty, 0) <= COALESCE(p.lower_limit, 0) THEN 'low'
            WHEN COALESCE(p.qty, 0) >= COALESCE(p.upper_limit, 0) THEN 'overstocked'
            WHEN COALESCE(p.qty, 0) <= (COALESCE(p.lower_limit, 0) + ((COALESCE(p.upper_limit, 0) - COALESCE(p.lower_limit, 0)) / 2.0)) THEN 'medium'
            ELSE 'adequate'
        END
    ";
}

function inventra_fetch_product_inventory_page(PDO $db, array $options = []): array
{
    $searchTerm = trim((string) ($options['search'] ?? ''));
    $statusFilter = inventra_normalize_product_inventory_status((string) ($options['status'] ?? ''));
    $page = max(1, (int) ($options['page'] ?? 1));
    $perPage = max(1, min(100, (int) ($options['per_page'] ?? 10)));
    $statusCase = inventra_product_inventory_status_case();

    $whereSql = ' WHERE 1=1';
    $params = [];

    if ($searchTerm !== '') {
        $searchLike = '%' . $searchTerm . '%';
        $whereSql .= ' AND p.name LIKE ?';
        $params[] = $searchLike;
    }

    if ($statusFilter !== '') {
        $whereSql .= " AND ($statusCase) = ?";
        $params[] = $statusFilter;
    }

    $countSql = 'SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id' . $whereSql;
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($params);
    $totalItems = (int) $stmtCount->fetchColumn();

    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;

    $sql = "
        SELECT
            p.id,
            p.category_id,
            p.name AS product_name,
            COALESCE(p.category, c.name, '') AS category,
            COALESCE(p.description, '') AS description,
            COALESCE(p.qty, 0) AS current_qty,
            COALESCE(p.unit_price, 0) AS unit_price,
            COALESCE(p.lower_limit, 0) AS lower_limit,
            COALESCE(p.upper_limit, 0) AS upper_limit,
            COALESCE(p.image, '') AS image_path,
            p.created_at,
            p.updated_at,
            ($statusCase) AS status
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
    " . $whereSql . ' ORDER BY p.id DESC LIMIT ? OFFSET ?';

    $queryParams = $params;
    $queryParams[] = $perPage;
    $queryParams[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($queryParams);

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $lowerLimit = (int) $row['lower_limit'];
        $upperLimit = (int) $row['upper_limit'];

        $items[] = [
            'id' => (int) $row['id'],
            'category_id' => (int) $row['category_id'],
            'product_name' => (string) $row['product_name'],
            'category' => (string) $row['category'],
            'description' => (string) $row['description'],
            'current_qty' => (int) $row['current_qty'],
            'unit_price' => (float) $row['unit_price'],
            'threshold' => $lowerLimit . ' / ' . $upperLimit,
            'lower_limit' => $lowerLimit,
            'upper_limit' => $upperLimit,
            'status' => (string) $row['status'],
            'image_path' => (string) $row['image_path'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'offset' => $offset,
        ],
        'filters' => [
            'search' => $searchTerm,
            'status' => $statusFilter,
        ],
    ];
}
