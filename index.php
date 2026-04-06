<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

define('BASE_URL', './');

$url = isset($_GET['url']) ? trim($_GET['url'], '/') : 'admin/dashboard';

if (strpos($url, 'auth/') === 0) {
    require_once __DIR__ . '/controllers/AuthController.php';
    $authController = new AuthController();

    if ($url === 'auth/forgot-password' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $authController->showForgotPassword();
        exit;
    }

    if ($url === 'auth/send-otp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $authController->sendOtp();
        exit;
    }

    if ($url === 'auth/verify-otp' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $authController->showVerifyOtp();
        exit;
    }

    if ($url === 'auth/verify-otp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $authController->verifyOtp();
        exit;
    }

    if ($url === 'auth/resend-otp') {
        $authController->resendOtp();
        exit;
    }

    if ($url === 'auth/reset-password' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $authController->showResetPassword();
        exit;
    }
}

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