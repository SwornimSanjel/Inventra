<?php
require 'config/database.php';
$db = Database::connect();
foreach (['users', 'admin'] as $table) {
    echo 'TABLE:', $table, PHP_EOL;
    foreach ($db->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'], ':', $row['Type'], PHP_EOL;
    }
}
