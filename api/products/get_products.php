<?php
<<<<<<< HEAD
include "../../config/db.php";

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM products WHERE name LIKE '%$search%'";
$result = $conn->query($sql);

$data = [];

while($row = $result->fetch_assoc()){

    $qty = $row['qty'];
    $low = $row['lower_limit'];
    $up  = $row['upper_limit'];

    if($qty == 0){
        $stock = "Out of Stock";
    }
    elseif($qty < $low){
        $stock = "Low";
    }
    elseif($qty >= $low && $qty <= $up){
        $stock = "Medium";
    }
    elseif($qty > $up && $qty <= ($up*1.5)){
        $stock = "Adequate";
    }
    else{
        $stock = "Overstocked";
    }

    if($status != "" && $status != "All" && $status != $stock){
        continue;
    }

    $row['status'] = $stock;
    $data[] = $row;
}

echo json_encode($data);
=======

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../helpers/stock_status.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

if (!inventra_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);

$sql = "
    SELECT
        p.id,
        p.category_id,
        p.name,
        COALESCE(c.name, p.category, '') AS category,
        COALESCE(p.qty, 0) AS qty,
        COALESCE(p.unit_price, 0) AS unit_price,
        COALESCE(p.lower_limit, 0) AS lower_limit,
        COALESCE(p.upper_limit, 0) AS upper_limit,
        COALESCE(p.image, '') AS image,
        COALESCE(p.description, '') AS description,
        p.created_at,
        p.updated_at
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE 1=1
";

$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (p.name LIKE ? OR COALESCE(p.description, "") LIKE ? OR COALESCE(c.name, p.category, "") LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $types .= 'i';
    $params[] = $categoryId;
}

$sql .= ' ORDER BY p.name ASC';

$stmt = $conn->prepare($sql);

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $computedStatus = strtolower(str_replace(' ', '_', getStockStatus(
        (int) $row['qty'],
        (int) $row['lower_limit'],
        (int) $row['upper_limit']
    )));

    if ($status !== '' && $status !== $computedStatus) {
        continue;
    }

    $row['status'] = $computedStatus;
    $data[] = $row;
}

echo json_encode($data);
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
