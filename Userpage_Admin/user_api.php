<?php
/**
 * User Management API
 * Handles AJAX requests for user CRUD operations and email notifications.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'fetch':
        fetchUsers();
        break;
    case 'create':
        createUser();
        break;
    case 'toggle_status':
        toggleStatus();
        break;
    case 'delete':
        deleteUser();
        break;
    case 'update':
        updateUser();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Fetch all users with optional filters
 */
function fetchUsers() {
    global $pdo;
    
    $role   = $_GET['role']   ?? '';
    $status = $_GET['status'] ?? '';
    
    $sql    = "SELECT id, full_name, username, email, role, status, created_at FROM users WHERE 1=1";
    $params = [];
    
    if ($role && $role !== 'all') {
        // Map UI values to DB values
        $dbRole = ($role === 'user') ? 'staff' : $role;
        $sql .= " AND role = ?";
        $params[] = $dbRole;
    }
    
    if ($status && $status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map DB role 'staff' to UI display 'User'
    foreach ($users as &$user) {
        $user['display_role'] = ($user['role'] === 'staff') ? 'User' : 'Admin';
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
}

/**
 * Create a new user, hash password, save to DB, and send email
 */
function createUser() {
    global $pdo, $email_config;
    
    $fullName  = trim($_POST['full_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $username  = trim($_POST['username']   ?? '');
    $role      = $_POST['role']            ?? 'User';
    $password  = $_POST['password']        ?? '';
    
    // Validate required fields
    if (!$fullName || !$email || !$username || !$password) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Map UI role to DB role
    $dbRole = (strtolower($role) === 'admin') ? 'admin' : 'staff';
    
    // Check for duplicate username or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        return;
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, username, email, password, role, status, created_at) 
         VALUES (?, ?, ?, ?, ?, 'active', NOW())"
    );
    
    try {
        $stmt->execute([$fullName, $username, $email, $hashedPassword, $dbRole]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()]);
        return;
    }
    
    // Attempt to send email with credentials
    $emailSent = false;
    $emailError = '';
    
    try {
        $emailSent = sendCredentialsEmail($email, $fullName, $username, $password, $role);
    } catch (Exception $e) {
        $emailError = $e->getMessage();
    }
    
    $message = $emailSent 
        ? 'Credentials Sent Successfully to Email' 
        : 'User created successfully. Email could not be sent: ' . $emailError;
    
    echo json_encode([
        'success'    => true, 
        'message'    => $message,
        'email_sent' => $emailSent
    ]);
}

/**
 * Toggle user active/inactive status
 */
function toggleStatus() {
    global $pdo;
    
    $userId = intval($_POST['user_id'] ?? 0);
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Get current status
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $userId]);
    
    echo json_encode([
        'success'    => true, 
        'message'    => 'User status updated to ' . $newStatus,
        'new_status' => $newStatus
    ]);
}

/**
 * Delete a user
 */
function deleteUser() {
    global $pdo;
    
    $userId = intval($_POST['user_id'] ?? 0);
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Don't allow deleting yourself
    if ($userId == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
}

/**
 * Update user details
 */
function updateUser() {
    global $pdo;
    
    $userId   = intval($_POST['user_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email']     ?? '');
    $username = trim($_POST['username']  ?? '');
    $role     = $_POST['role']           ?? 'User';
    
    if (!$userId || !$fullName || !$email || !$username) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    $dbRole = (strtolower($role) === 'admin') ? 'admin' : 'staff';
    
    // Check for duplicate username/email (excluding current user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already in use by another user']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->execute([$fullName, $username, $email, $dbRole, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
}

/**
 * Send credentials email using PHPMailer
 */
function sendCredentialsEmail(string $toEmail, string $fullName, string $username, string $password, string $role): bool {
    global $email_config;
    
    $mail = new PHPMailer(true);
    
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = $email_config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $email_config['smtp_user'];
    $mail->Password   = $email_config['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $email_config['smtp_port'];
    
    // Email headers
    $mail->setFrom($email_config['from_email'], $email_config['from_name']);
    $mail->addAddress($toEmail, $fullName);
    
    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Your Inventra Account Credentials';
    $mail->Body    = "
    <div style='font-family: Inter, Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 40px 0;'>
        <div style='background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; box-shadow: 0 4px 24px rgba(0,0,0,0.06);'>
            <div style='text-align: center; margin-bottom: 32px;'>
                <h1 style='font-size: 28px; font-weight: 800; color: #0f172a; margin: 0 0 8px;'>Welcome to Inventra</h1>
                <p style='color: #64748b; font-size: 14px; margin: 0;'>Your account has been created successfully</p>
            </div>
            
            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;'>Full Name</td>
                        <td style='padding: 8px 0; color: #0f172a; font-size: 14px; font-weight: 600; text-align: right;'>{$fullName}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; border-top: 1px solid #e2e8f0;'>Username</td>
                        <td style='padding: 8px 0; color: #0f172a; font-size: 14px; font-weight: 600; text-align: right; border-top: 1px solid #e2e8f0;'>{$username}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; border-top: 1px solid #e2e8f0;'>Password</td>
                        <td style='padding: 8px 0; color: #0f172a; font-size: 14px; font-weight: 600; text-align: right; border-top: 1px solid #e2e8f0; font-family: monospace; letter-spacing: 1px;'>{$password}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; border-top: 1px solid #e2e8f0;'>Role</td>
                        <td style='padding: 8px 0; color: #0f172a; font-size: 14px; font-weight: 600; text-align: right; border-top: 1px solid #e2e8f0;'>{$role}</td>
                    </tr>
                </table>
            </div>
            
            <div style='background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 24px;'>
                <p style='margin: 0; font-size: 13px; color: #92400e;'>
                    ⚠️ <strong>Important:</strong> Please change your password after your first login for security purposes.
                </p>
            </div>
            
            <p style='color: #94a3b8; font-size: 12px; text-align: center; margin: 0;'>
                © 2026 INVENTRA. EDITORIAL INVENTORY MANAGEMENT.
            </p>
        </div>
    </div>";
    
    $mail->AltBody = "Welcome to Inventra!\n\nYour account has been created:\nFull Name: {$fullName}\nUsername: {$username}\nPassword: {$password}\nRole: {$role}\n\nPlease change your password after your first login.";
    
    $mail->send();
    return true;
}
?>
