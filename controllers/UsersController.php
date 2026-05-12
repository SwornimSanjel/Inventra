<?php

require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../models/AccountModel.php';
require_once __DIR__ . '/../models/NotificationService.php';
require_once __DIR__ . '/../models/UserManagementModel.php';
require_once __DIR__ . '/../helpers/session.php';

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

class UsersController
{
    private AdminSession $adminSession;
    private AccountModel $accountModel;
    private NotificationService $notificationService;
    private UserManagementModel $userManagementModel;

    public function __construct()
    {
        $this->adminSession = new AdminSession();
        $this->accountModel = new AccountModel();
        $this->notificationService = new NotificationService();
        $this->userManagementModel = new UserManagementModel();
    }

    public function show(): void
    {
        $admin = $this->adminSession->requireAuthenticatedAdmin();

        $usersPageState = [
            'current_admin' => $admin,
            'users_api_base' => 'index.php?url=admin/users',
        ];

        $url = 'admin/users';
        require __DIR__ . '/../views/layout/shell.php';
    }

    public function getUsers(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'users' => $this->userManagementModel->getUsers(
                trim((string) ($_GET['role'] ?? '')),
                trim((string) ($_GET['status'] ?? ''))
            ),
        ]);
        exit;
    }

    public function createUser(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $password = $this->buildDefaultPassword($fullName);

        if ($fullName === '' || $email === '' || $username === '' || $role === '') {
            $this->jsonError('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Please enter a valid email address.');
        }

        if (!in_array(strtolower($role), ['admin', 'user', 'staff'], true)) {
            $this->jsonError('Please select a valid role.');
        }

        if ($this->userManagementModel->existsByUsernameOrEmail($username, $email)) {
            $this->jsonError('Username or email already exists.');
        }

        if ($this->accountModel->emailExistsForOtherAccount($email, 'users', 0)) {
            $this->jsonError('Email address is already used by another account.');
        }

        if ($this->accountModel->usernameExistsForOtherAccount($username, 'users', 0)) {
            $this->jsonError('Username or email already exists.');
        }

        $newUserId = $this->userManagementModel->createUser($fullName, $email, $username, $password, $role);

        try {
            $this->notificationService->notifyUserCreated($newUserId, $fullName);
        } catch (Throwable $e) {
            error_log('Failed to create new-user notifications: ' . $e->getMessage());
        }

        $emailSent = false;
        $roleLabel = strtolower($role) === 'admin' ? 'Admin' : 'Staff';
        $message = 'Staff created successfully.';

        try {
            $emailSent = $this->sendCredentialsEmail($email, $fullName, $username, $password, $roleLabel);
            if ($emailSent) {
                $message = 'Staff created successfully. Default password is ' . $password . ' and it was also sent by email.';
            }
        } catch (Throwable $e) {
            error_log('Failed to send credentials email to ' . $email . ': ' . $e->getMessage());
            $message = 'Staff created successfully. Default password is ' . $password . '.';
        }

        $this->jsonSuccess([
            'message' => $message,
            'email_sent' => $emailSent,
        ]);
    }

    public function updateUser(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));

        if ($userId <= 0 || $fullName === '' || $email === '' || $username === '' || $role === '') {
            $this->jsonError('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Please enter a valid email address.');
        }

        if (!in_array(strtolower($role), ['admin', 'user', 'staff'], true)) {
            $this->jsonError('Please select a valid role.');
        }

        if ($this->userManagementModel->existsByUsernameOrEmail($username, $email, $userId)) {
            $this->jsonError('Username or email already in use by another user.');
        }

        if ($this->accountModel->emailExistsForOtherAccount($email, 'users', $userId)) {
            $this->jsonError('Email address is already used by another account.');
        }

        if ($this->accountModel->usernameExistsForOtherAccount($username, 'users', $userId)) {
            $this->jsonError('Username is already used by another account.');
        }

        if (!$this->userManagementModel->findById($userId)) {
            $this->jsonError('User not found.');
        }

        $this->userManagementModel->updateUser($userId, $fullName, $email, $username, $role);
        $this->jsonSuccess(['message' => 'Staff updated successfully.']);
    }

    public function toggleStatus(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();

        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->jsonError('Invalid user ID.');
        }

        $newStatus = $this->userManagementModel->toggleStatus($userId);
        if ($newStatus === null) {
            $this->jsonError('User not found.');
        }

        if ($newStatus === 'active') {
            try {
                $this->notificationService->notifyRequestApprovedForUser($userId);
            } catch (Throwable $e) {
                error_log('Failed to create request-approved notification: ' . $e->getMessage());
            }
        }

        $this->jsonSuccess([
            'message' => 'Staff status updated to ' . $newStatus . '.',
            'new_status' => $newStatus,
        ]);
    }

    public function deleteUser(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();

        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->jsonError('Invalid user ID.');
        }

        if (inventra_authenticated_user_source() === 'users' && $userId === (int) inventra_authenticated_user_id()) {
            $this->jsonError('You cannot delete your own account.');
        }

        if (!$this->userManagementModel->findById($userId)) {
            $this->jsonError('User not found.');
        }

        $this->userManagementModel->deleteUser($userId);
        $this->jsonSuccess(['message' => 'Staff deleted successfully.']);
    }

    private function sendCredentialsEmail(string $toEmail, string $fullName, string $username, string $password, string $role): bool
    {
        $mailConfig = require __DIR__ . '/../config/mail.php';

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $mailConfig['port'];

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($toEmail, $fullName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Inventra Account Credentials';
        $mail->Body = "
            <h2>Welcome to Inventra</h2>
            <p>Your account has been created successfully.</p>
            <p><strong>Full name:</strong> {$fullName}</p>
            <p><strong>Username:</strong> {$username}</p>
            <p><strong>Password:</strong> {$password}</p>
            <p><strong>Role:</strong> {$role}</p>
            <p>This account uses the default first-login password format: first name in lowercase + @123.</p>
            <p>You can sign in with this password for your first login, then Inventra will require you to create a new password before accessing the system.</p>
            <p>If you prefer, you can also use the Forgot Password option with your registered email to set a new password yourself.</p>
        ";

        $mail->AltBody = "Welcome to Inventra!\nFull name: {$fullName}\nUsername: {$username}\nPassword: {$password}\nRole: {$role}\nThis account uses the default first-login password format: first name in lowercase + @123.\nUse this password for your first login. Inventra will require you to create a new password before accessing the system, or you can use Forgot Password with your registered email.";

        $mail->send();
        return true;
    }

    private function buildDefaultPassword(string $fullName): string
    {
        return inventra_build_default_password($fullName);
    }

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
        exit;
    }

    private function jsonSuccess(array $payload = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $payload));
        exit;
    }
}
