<?php
require_once __DIR__ . '/includes/auth.php';
requireRole('admin');

define('BASE_URL', './');

$url = isset($_GET['url']) ? trim($_GET['url'], '/') : 'admin/dashboard';

$allowed = [
    'admin/dashboard',
    'admin/users',
    'admin/products',
    'admin/stock-update',
    'admin/settings'
];

if (!in_array($url, $allowed, true)) {
    $url = 'admin/dashboard';
}

require __DIR__ . '/views/layout/shell.php';