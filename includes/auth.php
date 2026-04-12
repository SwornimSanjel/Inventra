<?php
/**
 * Session Authentication Guard
 * Include this file at the top of any protected page.
 * 
 * Usage:
 *   require_once __DIR__ . '/../includes/auth.php';
 * 
 * Optional: Pass a required role to restrict access:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   requireRole('admin');
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);

    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (expires when browser closes)
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,       // Set to true if using HTTPS
        'httponly'  => true,        // Prevent JavaScript access to session cookie
        'samesite' => 'Strict'     // Prevent CSRF via cross-site requests
    ]);

    session_start();
}

// ── Session Timeout (30 minutes of inactivity) ──
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    if ($elapsed > SESSION_TIMEOUT) {
        // Session has expired — destroy and redirect
        session_unset();
        session_destroy();
        header("Location: /Inventra0/login/login.php?error=session_expired");
        exit();
    }
}
// Update last activity timestamp
$_SESSION['last_activity'] = time();

// ── Authentication Check ──
if (!isset($_SESSION['user_id'])) {
    header("Location: /Inventra0/login/login.php");
    exit();
}

/**
 * Require a specific role to access the page.
 * Redirects to login with an error if the role doesn't match.
 *
 * @param string $role  The required role (e.g. 'admin', 'staff')
 */
function requireRole(string $role): void {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: /Inventra0/login/login.php?error=unauthorized");
        exit();
    }
}
?>
