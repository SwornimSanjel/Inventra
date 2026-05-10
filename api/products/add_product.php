<?php
/**
 * Add Product API
 *
 * Mirrors the Inventra1 staff-products flow so authenticated staff can add
 * products from the copied staff page and still trigger low-stock alerts.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';
require_once __DIR__ . '/../../models/NotificationService.php';
require_once __DIR__ . '/../../config/db.php';

inventra_bootstrap_session();

$adminSession = new AdminSession();
$account = $adminSession->resolveAuthenticatedAccount();

if ($account === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = strtolower(trim((string) ($account['role'] ?? 'staff')));

if (!in_array($role, ['admin', 'staff'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$categoryRaw = trim((string) ($_POST['category_id'] ?? ''));
$categoryId = ctype_digit($categoryRaw) ? (int) $categoryRaw : 0;
$newCategory = trim((string) ($_POST['new_category'] ?? ''));
$newCategoryDescription = trim((string) ($_POST['new_category_description'] ?? ''));
$qtyRaw = trim((string) ($_POST['qty'] ?? ''));
$priceRaw = trim((string) ($_POST['price'] ?? ''));
$lowerRaw = trim((string) ($_POST['lower'] ?? ''));
$upperRaw = trim((string) ($_POST['upper'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$errors = [];

if ($name === '') {
    $errors['name'] = 'Product name is required.';
}

if ($categoryRaw === '' || ($categoryId <= 0 && $categoryRaw !== 'new')) {
    $errors['category_id'] = 'Category is required.';
}

if ($qtyRaw === '' || filter_var($qtyRaw, FILTER_VALIDATE_INT) === false || (int) $qtyRaw < 0) {
    $errors['qty'] = 'Quantity must be a valid whole number.';
}

if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
    $errors['price'] = 'Unit price must be a valid number.';
}

if ($lowerRaw === '' || filter_var($lowerRaw, FILTER_VALIDATE_INT) === false || (int) $lowerRaw < 0) {
    $errors['lower'] = 'Lower limit must be a valid whole number.';
}

if ($upperRaw === '' || filter_var($upperRaw, FILTER_VALIDATE_INT) === false || (int) $upperRaw < 0) {
    $errors['upper'] = 'Upper limit must be a valid whole number.';
}

if ($description === '') {
    $errors['description'] = 'Description is required.';
}

if ($errors === [] && (int) $upperRaw < (int) $lowerRaw) {
    $errors['upper'] = 'Upper limit must be greater than or equal to lower limit.';
}

if ($errors !== []) {
    echo json_encode(['success' => false, 'message' => reset($errors), 'errors' => $errors]);
    exit;
}

$qty = (int) $qtyRaw;
$price = (float) $priceRaw;
$lower = (int) $lowerRaw;
$upper = (int) $upperRaw;

if ($categoryRaw === 'new') {
    if ($newCategory === '') {
        echo json_encode(['success' => false, 'message' => 'Please enter a name for the new category.']);
        exit;
    }

    $existingCategoryStmt = $conn->prepare('SELECT id, name FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1');
    $existingCategoryStmt->execute([$newCategory]);
    $existingCategory = $existingCategoryStmt->fetch();

    if ($existingCategory) {
        $categoryId = (int) $existingCategory['id'];
        $categoryRow = ['name' => $existingCategory['name']];
    } else {
        $insertCategoryStmt = $conn->prepare('INSERT INTO categories (name, description) VALUES (?, ?) RETURNING id');

        if (!$insertCategoryStmt->execute([$newCategory, $newCategoryDescription])) {
            echo json_encode(['success' => false, 'message' => 'Unable to create the new category right now.']);
            exit;
        }

        $categoryId = (int) $insertCategoryStmt->fetchColumn();
        $categoryRow = ['name' => $newCategory];
    }
} else {
    $categoryStmt = $conn->prepare('SELECT name FROM categories WHERE id = ? LIMIT 1');
    $categoryStmt->execute([$categoryId]);
    $categoryRow = $categoryStmt->fetch();

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
    RETURNING id
");

$categoryName = $categoryRow['name'];
if (!$stmt->execute([
    $categoryId,
    $name,
    $categoryName,
    $qty,
    $price,
    $lower,
    $upper,
    $image,
    $description,
])) {
    echo json_encode(['success' => false, 'message' => 'Unable to save product right now.']);
    exit;
}

$productId = (int) $stmt->fetchColumn();

// Fire the alert in a non-blocking way so the page action still succeeds even
// if notification creation hits an edge case.
try {
    (new NotificationService())->notifyLowStockForProduct($productId);
} catch (Throwable $e) {
    error_log('Failed to create low-stock notification after product create: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Product added successfully.']);
