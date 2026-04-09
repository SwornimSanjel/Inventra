<?php
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
        
        // 3. Set Session Variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // 'admin' or 'staff'

        // 4. Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: ../Admin%20Dashboard/index.html");
        } else {
            header("Location: ../userdashboard/index.html");
        }
        exit();
        
    } else {
        // 5. If login fails, redirect back with error parameter
        header("Location: login.php?error=1");
        exit();
    }
} else {
    // Redirect if they try to access this script directly
    header("Location: login.php");
    exit();
}