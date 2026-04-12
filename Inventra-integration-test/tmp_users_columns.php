<?php
require 'config/database.php';
$db = Database::connect();
foreach ($db->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'], ':', $row['Type'], PHP_EOL;
}
