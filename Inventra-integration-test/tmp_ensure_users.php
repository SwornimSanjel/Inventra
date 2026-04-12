<?php
require 'models/UserManagementModel.php';
$model = new UserManagementModel();
$model->ensureSchema();
require 'config/database.php';
$db = Database::connect();
foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
    echo $row[0], PHP_EOL;
}
