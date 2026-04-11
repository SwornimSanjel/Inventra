<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kathmandu');

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

    if ($url === 'auth/reset-password' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
        $authController->resetPassword();
        exit;
    }

    if ($url === 'auth/password-updated' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $authController->showPasswordUpdated();
        exit;
    }
}

if (strpos($url, 'admin/settings') === 0) {
    require_once __DIR__ . '/controllers/SettingsController.php';
    $settingsController = new SettingsController();

    if ($url === 'admin/settings/data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $settingsController->getProfileData();
        exit;
    }

    if ($url === 'admin/settings/profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $settingsController->updateProfile();
        exit;
    }

    if ($url === 'admin/settings/password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $settingsController->updatePassword();
        exit;
    }

    if ($url === 'admin/settings/notifications' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $settingsController->updateNotifications();
        exit;
    }

    if ($url === 'admin/settings' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $settingsController->show();
        exit;
    }
}

if (strpos($url, 'admin/notifications') === 0) {
    require_once __DIR__ . '/controllers/NotificationController.php';
    $notificationController = new NotificationController();

    if ($url === 'admin/notifications/data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $notificationController->getData();
        exit;
    }

    if ($url === 'admin/notifications/mark-all-read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $notificationController->markAllAsRead();
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
