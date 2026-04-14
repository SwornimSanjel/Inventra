<?php
<<<<<<< HEAD
include "../../config/db.php";

$id = $_POST['id'];

$conn->query("DELETE FROM products WHERE id=$id");

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

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
$stmt->bind_param('i', $id);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Unable to delete product right now.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
>>>>>>> a797a55778273531d31c9c9dc672c4d4fa66ebad
