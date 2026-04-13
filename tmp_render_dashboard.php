<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_GET['url'] = 'admin/dashboard';
ob_start();
include 'index.php';
$output = ob_get_clean();
file_put_contents('tmp_dashboard_render.html', $output);
echo strlen($output);
