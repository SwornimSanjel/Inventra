<?php
header('Location: ../index.php?url=login');
exit;

// Secure session settings before starting
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,       // Set to true if using HTTPS
    'httponly'  => true,
    'samesite' => 'Strict'
]);

session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 1. Fetch user by username OR email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    // 2. Verify Password (using the hashed password from the DB)
    if ($user && password_verify($password, $user['password'])) {
        
        // 3. Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // 4. Set Session Variables
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['role']          = $user['role']; // 'admin' or 'staff'
        $_SESSION['last_activity'] = time();        // For session timeout tracking
        $_SESSION['ip_address']    = $_SERVER['REMOTE_ADDR']; // For IP validation
        $_SESSION['user_agent']    = $_SERVER['HTTP_USER_AGENT']; // For browser validation

        // 5. Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: ../Admin%20Dashboard/AdminDashboard.php");
        } else {
            header("Location: ../user_dashboard/dashboard.php");
        }
        exit();
        
    } else {
        // 6. If login fails, redirect back with error parameter
        header("Location: login.php?error=1");
        exit();
    }
} else {
    // Redirect if they try to access this script directly
    header("Location: login.php");
    exit();
}
