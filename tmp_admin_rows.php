<?php
require __DIR__ . '/config/database.php';
$db = Database::connect();
foreach ($db->query('SELECT * FROM admin LIMIT 2')->fetchAll() as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
