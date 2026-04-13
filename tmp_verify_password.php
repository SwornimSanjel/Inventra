<?php
require __DIR__ . '/config/database.php';
$db = Database::connect();
$row = $db->query("SELECT id, email, password_hash FROM admin ORDER BY id ASC LIMIT 1")->fetch();
$passwords = ['swornim@123','anmol@123'];
foreach ($passwords as $password) {
    echo $password . ':' . (password_verify($password, $row['password_hash']) ? 'MATCH' : 'NO') . PHP_EOL;
}
