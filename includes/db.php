<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}
?>
