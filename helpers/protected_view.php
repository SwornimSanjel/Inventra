<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../models/AdminSession.php';

function inventra_guard_app_url(string $query = ''): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = '';

    foreach (['/views/', '/api/', '/helpers/', '/models/', '/controllers/'] as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            $basePath = substr($scriptName, 0, $position);
            break;
        }
    }

    if ($basePath === '') {
        $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');
    }

    $url = rtrim($basePath, '/') . '/index.php';

    return $query !== '' ? $url . '?' . $query : $url;
}

function inventra_guard_redirect(string $url): void
{
    if (!headers_sent()) {
        inventra_send_no_store_headers();
        header('Location: ' . $url);
    }

    exit;
}

function inventra_guard_route_url(string $route): string
{
    return inventra_guard_app_url('url=' . str_replace('%2F', '/', rawurlencode(trim($route, '/'))));
}

function inventra_guard_login_url(string $error = ''): string
{
    $query = 'url=login';

    if ($error !== '') {
        $query .= '&error=' . rawurlencode($error);
    }

    return inventra_guard_app_url($query);
}

function inventra_guard_role_allows(array $account, string $requiredRole): bool
{
    if ($requiredRole === 'any') {
        return true;
    }

    $role = strtolower(trim((string) ($account['role'] ?? inventra_authenticated_user_role() ?? 'user')));

    if ($requiredRole === 'admin') {
        return $role === 'admin';
    }

    if ($requiredRole === 'staff' || $requiredRole === 'user') {
        return $role !== 'admin';
    }

    return false;
}

function inventra_guard_protected_view(string $route, string $requiredRole = 'any'): void
{
    // Protected views are normally included by index.php after the central
    // route guard has run. This catches accidental direct requests.
    if (defined('BASE_URL')) {
        return;
    }

    inventra_bootstrap_session();

    if (inventra_check_session_timeout()) {
        inventra_guard_redirect(inventra_guard_login_url('session_expired'));
    }

    $session = new AdminSession();
    $account = $session->resolveAuthenticatedAccount();

    if ($account === null) {
        $_SESSION['auth_error'] = 'Please log in to continue.';
        inventra_guard_redirect(inventra_guard_login_url());
    }

    if (!inventra_guard_role_allows($account, $requiredRole)) {
        $_SESSION['auth_error'] = 'You do not have permission to access that page.';
        inventra_guard_redirect(inventra_guard_route_url(inventra_default_authenticated_url()));
    }

    inventra_guard_redirect(inventra_guard_route_url($route));
}
