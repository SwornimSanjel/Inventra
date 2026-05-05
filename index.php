<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kathmandu');

require_once __DIR__ . '/helpers/session.php';
inventra_bootstrap_session();

define('BASE_URL', './');

$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

if (inventra_is_authenticated()) {
    require_once __DIR__ . '/models/AdminSession.php';
    (new AdminSession())->resolveAuthenticatedAccount();
}

if ($url === 'login' || strpos($url, 'auth/') === 0) {
    require_once __DIR__ . '/controllers/AuthController.php';
    $authController = new AuthController();

    if ($url === 'login' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $authController->showLogin();
        exit;
    }

    if ($url === 'auth/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $authController->login();
        exit;
    }

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

    if ($url === 'auth/account-password' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
        $authController->changeOwnPassword();
        exit;
    }

    if ($url === 'auth/logout') {
        $authController->logout();
        exit;
    }
}

if (strpos($url, 'account') === 0) {
    require_once __DIR__ . '/controllers/AuthController.php';
    $authController = new AuthController();

    if ($url === 'account/password' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
        $authController->changeOwnPassword();
        exit;
    }

    if (($url === 'account' || $url === 'account/home') && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $authController->showAccountHome();
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

if (strpos($url, 'user/settings') === 0) {
    require_once __DIR__ . '/controllers/UserSettingsController.php';
    $userSettingsController = new UserSettingsController();

    if ($url === 'user/settings/data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $userSettingsController->getProfileData();
        exit;
    }

    if ($url === 'user/settings/profile' && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)) {
        $userSettingsController->updateProfile();
        exit;
    }

    if ($url === 'user/settings/password' && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)) {
        $userSettingsController->updatePassword();
        exit;
    }

    if ($url === 'user/settings/notifications' && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)) {
        $userSettingsController->updateNotifications();
        exit;
    }

    if ($url === 'user/settings' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $userSettingsController->show();
        exit;
    }
}

if (strpos($url, 'admin/users') === 0) {
    require_once __DIR__ . '/controllers/UsersController.php';
    $usersController = new UsersController();

    if ($url === 'admin/users/data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $usersController->getUsers();
        exit;
    }

    if ($url === 'admin/users/create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $usersController->createUser();
        exit;
    }

    if ($url === 'admin/users/update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $usersController->updateUser();
        exit;
    }

    if ($url === 'admin/users/toggle-status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $usersController->toggleStatus();
        exit;
    }

    if ($url === 'admin/users/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $usersController->deleteUser();
        exit;
    }

    if ($url === 'admin/users' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $usersController->show();
        exit;
    }
}

if (strpos($url, 'admin/ai-forecasting') === 0) {
    require_once __DIR__ . '/controllers/AIForecastingController.php';
    $aiForecastingController = new AIForecastingController();

    if ($url === 'admin/ai-forecasting/data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $aiForecastingController->getForecastData();
        exit;
    }

    if ($url === 'admin/ai-forecasting/product-detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $aiForecastingController->getProductDetail();
        exit;
    }

    if ($url === 'admin/ai-forecasting/generate-insight' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $aiForecastingController->generateInsight();
        exit;
    }

    if ($url === 'admin/ai-forecasting/mark-reorder' && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
        $aiForecastingController->markReorder();
        exit;
    }

    if ($url === 'admin/ai-forecasting' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $aiForecastingController->show();
        exit;
    }
}

if (strpos($url, 'admin/notifications') === 0 || strpos($url, 'user/notifications') === 0) {
    require_once __DIR__ . '/controllers/NotificationController.php';
    $notificationController = new NotificationController();
    $notificationScope = strpos($url, 'admin/notifications') === 0 ? 'admin' : 'user';

    if ($url === $notificationScope . '/notifications/data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $notificationController->getData($notificationScope);
        exit;
    }

    if ($url === $notificationScope . '/notifications/mark-read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $notificationController->markAsRead($notificationScope);
        exit;
    }

    if ($url === $notificationScope . '/notifications/mark-all-read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $notificationController->markAllAsRead($notificationScope);
        exit;
    }
}

$allowed = [
    'account',
    'account/home',
    'account/password',
    'admin/dashboard',
    'admin/users',
    'admin/products',
    'admin/stock-update',
    'admin/ai-forecasting',
    'admin/settings',
    'user/dashboard',
    'user/products',
    'user/stock-update',
    'user/settings'
];

if ($url === '') {
    $url = inventra_is_authenticated() ? inventra_default_authenticated_url() : 'login';
}

if (!in_array($url, $allowed, true)) {
    $url = inventra_is_authenticated() ? inventra_default_authenticated_url() : 'login';
}

if ($url === 'login') {
    require_once __DIR__ . '/controllers/AuthController.php';
    (new AuthController())->showLogin();
    exit;
}

if (strpos($url, 'admin/') === 0) {
    require_once __DIR__ . '/models/AdminSession.php';

    $routeGuard = new AdminSession();
    $account = $routeGuard->resolveAuthenticatedAccount();
    if ($account === null) {
        inventra_auth_debug_log('route_guard:redirect_login', [
            'url' => $url,
            'auth_session' => $_SESSION['auth'] ?? null,
            'admin_id' => $_SESSION['admin_id'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
        ]);
        $_SESSION['auth_error'] = 'Please log in to continue.';
        header('Location: index.php?url=login');
        exit;
    }

    if (($account['role'] ?? 'user') !== 'admin') {
        $_SESSION['auth_error'] = 'You do not have permission to access that page.';
        header('Location: index.php?url=account');
        exit;
    }
}

if (strpos($url, 'user/') === 0) {
    require_once __DIR__ . '/models/AdminSession.php';

    $routeGuard = new AdminSession();
    $account = $routeGuard->resolveAuthenticatedAccount();
    if ($account === null) {
        $_SESSION['auth_error'] = 'Please log in to continue.';
        header('Location: index.php?url=login');
        exit;
    }

    if (($account['role'] ?? 'user') !== 'user') {
        $_SESSION['auth_error'] = 'You do not have permission to access that page.';
        header('Location: index.php?url=admin/dashboard');
        exit;
    }
}

require __DIR__ . '/views/layout/shell.php';
