<?php
<<<<<<< HEAD
include "../../config/db.php";

$name = $_POST['name'];
$category = $_POST['category'];
$qty = $_POST['qty'];
$price = $_POST['price'];
$lower = $_POST['lower'];
$upper = $_POST['upper'];
$description = $_POST['description'];

$image = "";

if(isset($_FILES['image']['name']) && $_FILES['image']['name'] != ""){
    $image = time() . "_" . $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/".$image);
}

$sql = "INSERT INTO products 
(name,category,qty,unit_price,lower_limit,upper_limit,image,description)
VALUES
('$name','$category','$qty','$price','$lower','$upper','$image','$description')";

$conn->query($sql);

echo json_encode(["success"=>true]);
=======

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

if (!inventra_is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$categoryRaw = trim((string) ($_POST['category_id'] ?? ''));
$categoryId = ctype_digit($categoryRaw) ? (int) $categoryRaw : 0;
$newCategory = trim((string) ($_POST['new_category'] ?? ''));
$newCategoryDescription = trim((string) ($_POST['new_category_description'] ?? ''));
$qty = max(0, (int) ($_POST['qty'] ?? 0));
$price = max(0, (float) ($_POST['price'] ?? 0));
$lower = max(0, (int) ($_POST['lower'] ?? 0));
$upper = max($lower, (int) ($_POST['upper'] ?? 0));
$description = trim((string) ($_POST['description'] ?? ''));

if ($name === '' || ($categoryId <= 0 && $categoryRaw !== 'new')) {
    echo json_encode(['success' => false, 'message' => 'Product name and category are required.']);
    exit;
}

if ($categoryRaw === 'new') {
    if ($newCategory === '') {
        echo json_encode(['success' => false, 'message' => 'Please enter a name for the new category.']);
        exit;
    }

    $existingCategoryStmt = $conn->prepare('SELECT id, name FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1');
    $existingCategoryStmt->bind_param('s', $newCategory);
    $existingCategoryStmt->execute();
    $existingCategory = $existingCategoryStmt->get_result()->fetch_assoc();

    if ($existingCategory) {
        $categoryId = (int) $existingCategory['id'];
        $categoryRow = ['name' => $existingCategory['name']];
    } else {
        $insertCategoryStmt = $conn->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        $insertCategoryStmt->bind_param('ss', $newCategory, $newCategoryDescription);

        if (!$insertCategoryStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Unable to create the new category right now.']);
            exit;
        }

        $categoryId = (int) $insertCategoryStmt->insert_id;
        $categoryRow = ['name' => $newCategory];
    }
} else {
    $categoryStmt = $conn->prepare('SELECT name FROM categories WHERE id = ? LIMIT 1');
    $categoryStmt->bind_param('i', $categoryId);
    $categoryStmt->execute();
    $categoryResult = $categoryStmt->get_result();
    $categoryRow = $categoryResult->fetch_assoc();

    if (!$categoryRow) {
        echo json_encode(['success' => false, 'message' => 'Selected category was not found.']);
        exit;
    }
}

$image = '';

if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/images/products';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            echo json_encode(['success' => false, 'message' => 'Unable to prepare the product image folder.']);
            exit;
        }
    }

    $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $safeExtension = preg_match('/^[a-z0-9]+$/', $extension) ? $extension : 'jpg';
    $fileName = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExtension;
    $targetPath = $uploadDir . '/' . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        $image = 'public/uploads/images/products/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Product image could not be uploaded.']);
        exit;
    }
}

$stmt = $conn->prepare("
    INSERT INTO products (
        category_id,
        name,
        category,
        qty,
        unit_price,
        lower_limit,
        upper_limit,
        image,
        description
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$categoryName = $categoryRow['name'];
$stmt->bind_param(
    'issidiiss',
    $categoryId,
    $name,
    $categoryName,
    $qty,
    $price,
    $lower,
    $upper,
    $image,
    $description
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Unable to save product right now.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Product added successfully.']);
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
