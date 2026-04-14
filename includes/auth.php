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

require_once __DIR__ . '/../helpers/session.php';

// Start session if not already started
inventra_bootstrap_session();

// ── Session Timeout (30 minutes of inactivity) ──
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    if ($elapsed > SESSION_TIMEOUT) {
        // Session has expired — destroy and redirect
        inventra_clear_authenticated_admin();
        session_unset();
        session_destroy();
        header('Location: ../index.php?url=login&error=session_expired');
        exit();
    }
}
// Update last activity timestamp
$_SESSION['last_activity'] = time();

// ── Authentication Check ──
if (!inventra_is_authenticated()) {
    header('Location: ../index.php?url=login');
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
        header('Location: ../index.php?url=login&error=unauthorized');
        exit();
    }
}
?>
