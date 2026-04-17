<?php

function inventra_auth_debug_log(string $event, array $data = []): void
{
    $logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $payload = [
        'time' => date('Y-m-d H:i:s'),
        'event' => $event,
        'session_id' => session_id(),
        'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
        'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
        'cookie' => (string) ($_SERVER['HTTP_COOKIE'] ?? ''),
        'data' => $data,
    ];

    @file_put_contents(
        $logDir . DIRECTORY_SEPARATOR . 'auth_debug.log',
        json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

function inventra_session_save_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
}

function inventra_session_cookie_name(): string
{
    return 'INVENTRA_MERGE_APP_SESSID';
}

function inventra_session_cookie_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = str_replace('/index.php', '', $scriptName);
    $basePath = rtrim($basePath, '/');

    return $basePath !== '' ? $basePath : '/';
}

function inventra_bootstrap_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $sessionSavePath = inventra_session_save_path();
    if (!is_dir($sessionSavePath)) {
        mkdir($sessionSavePath, 0775, true);
    }

    ini_set('session.save_path', $sessionSavePath);
    session_name(inventra_session_cookie_name());
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => inventra_session_cookie_path(),
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Clean up the old merged-app cookie name so stale localhost cookies stop colliding.
    if (isset($_COOKIE['INVENTRASESSID'])) {
        setcookie('INVENTRASESSID', '', time() - 42000, '/');
        setcookie('INVENTRASESSID', '', time() - 42000, inventra_session_cookie_path());
        unset($_COOKIE['INVENTRASESSID']);
    }

    session_start();
}

function inventra_set_authenticated_user(array $user): void
{
    $accountId = (int) ($user['id'] ?? 0);
    $source = (string) ($user['source'] ?? 'admin');
    $email = (string) ($user['email'] ?? '');
    $name = (string) ($user['full_name'] ?? '');
    $role = strtolower(trim((string) ($user['role'] ?? 'user'))) === 'admin' ? 'admin' : 'user';

    $_SESSION['auth'] = [
        'user_id' => $accountId,
        'source' => $source,
        'email' => $email,
        'name' => $name,
        'role' => $role,
        'logged_in' => true,
        'last_activity' => time(),
    ];

    // Legacy compatibility while the merged app still has some old checks.
    $_SESSION['user_id'] = $accountId;
    $_SESSION['user_email'] = $email;
    $_SESSION['auth_user_id'] = $accountId;
    $_SESSION['role'] = $role;
    $_SESSION['auth_source'] = $source;
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = $_SESSION['auth']['last_activity'];
    $_SESSION['ip_address'] = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $_SESSION['user_agent'] = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

    if ($role === 'admin') {
        $_SESSION['admin_id'] = $accountId;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin'] = true;
    } else {
        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_email'],
            $_SESSION['admin_name'],
            $_SESSION['admin_avatar'],
            $_SESSION['admin']
        );
    }
}

function inventra_set_authenticated_admin(array $user): void
{
    inventra_set_authenticated_user($user);
}

function inventra_clear_authenticated_user(): void
{
    unset(
        $_SESSION['auth'],
        $_SESSION['admin_id'],
        $_SESSION['admin_email'],
        $_SESSION['admin_name'],
        $_SESSION['admin_avatar'],
        $_SESSION['user_id'],
        $_SESSION['user_email'],
        $_SESSION['auth_user_id'],
        $_SESSION['auth_source'],
        $_SESSION['role'],
        $_SESSION['admin'],
        $_SESSION['logged_in'],
        $_SESSION['last_activity'],
        $_SESSION['ip_address'],
        $_SESSION['user_agent']
    );
}

function inventra_clear_authenticated_admin(): void
{
    inventra_clear_authenticated_user();
}

function inventra_authenticated_user_id(): ?int
{
    $auth = $_SESSION['auth'] ?? null;
    if (is_array($auth) && ($auth['logged_in'] ?? false) === true && is_numeric($auth['user_id'] ?? null)) {
        return (int) $auth['user_id'];
    }

    $candidates = [
        $_SESSION['user_id'] ?? null,
        $_SESSION['auth_user_id'] ?? null,
        $_SESSION['admin_id'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_numeric($candidate)) {
            return (int) $candidate;
        }
    }

    return null;
}

function inventra_authenticated_user_email(): ?string
{
    $auth = $_SESSION['auth'] ?? null;
    if (is_array($auth) && ($auth['logged_in'] ?? false) === true && is_string($auth['email'] ?? null) && trim($auth['email']) !== '') {
        return trim((string) $auth['email']);
    }

    $candidates = [
        $_SESSION['admin_email'] ?? null,
        $_SESSION['user_email'] ?? null,
        $_SESSION['email'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return null;
}

function inventra_authenticated_user_role(): ?string
{
    $auth = $_SESSION['auth'] ?? null;

    if (is_array($auth) && ($auth['logged_in'] ?? false) === true && is_string($auth['role'] ?? null)) {
        $role = strtolower(trim((string) $auth['role']));
        return $role === 'admin' ? 'admin' : 'user';
    }

    if (isset($_SESSION['role']) && is_string($_SESSION['role'])) {
        $role = strtolower(trim((string) $_SESSION['role']));
        return $role === 'admin' ? 'admin' : 'user';
    }

    return null;
}

function inventra_authenticated_user_source(): ?string
{
    $auth = $_SESSION['auth'] ?? null;

    if (is_array($auth) && ($auth['logged_in'] ?? false) === true && is_string($auth['source'] ?? null)) {
        $source = trim((string) $auth['source']);
        if ($source !== '') {
            return $source;
        }
    }

    if (isset($_SESSION['auth_source']) && is_string($_SESSION['auth_source']) && trim($_SESSION['auth_source']) !== '') {
        return trim((string) $_SESSION['auth_source']);
    }

    if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
        return 'admin';
    }

    return null;
}

function inventra_authenticated_user_name(): ?string
{
    $auth = $_SESSION['auth'] ?? null;

    if (is_array($auth) && ($auth['logged_in'] ?? false) === true && is_string($auth['name'] ?? null) && trim($auth['name']) !== '') {
        return trim((string) $auth['name']);
    }

    $candidates = [
        $_SESSION['admin_name'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return null;
}

function inventra_authenticated_admin_id(): ?int
{
    return inventra_is_admin() ? inventra_authenticated_user_id() : null;
}

function inventra_authenticated_admin_email(): ?string
{
    return inventra_is_admin() ? inventra_authenticated_user_email() : null;
}

function inventra_is_authenticated(): bool
{
    $auth = $_SESSION['auth'] ?? null;

    if (!is_array($auth) || ($auth['logged_in'] ?? false) !== true) {
        return false;
    }

    return inventra_authenticated_user_id() !== null;
}

function inventra_is_admin(): bool
{
    return inventra_is_authenticated() && inventra_authenticated_user_role() === 'admin';
}

function inventra_default_authenticated_url(): string
{
    return inventra_is_admin() ? 'admin/dashboard' : 'account';
}
