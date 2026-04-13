<?php
require __DIR__ . '/config/database.php';
$db = Database::connect();
$row = $db->query("SELECT id, full_name, email, password_hash FROM admin ORDER BY id ASC LIMIT 1")->fetch();
var_export($row);
