<?php
require_once __DIR__ . '/database.php';

try {
    $conn = Database::connect();
} catch (Throwable $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}

?>
