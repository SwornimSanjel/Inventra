<?php
require __DIR__ . '/config/database.php';
$db = Database::connect();
foreach ($db->query('SHOW COLUMNS FROM admin')->fetchAll() as $row) {
    echo $row['Field'] . '|' . $row['Type'] . '|' . $row['Null'] . '|' . ($row['Default'] ?? 'NULL') . PHP_EOL;
}
