<?php

require_once __DIR__ . '/../models/UserSettingsModel.php';
require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../models/NotificationService.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

class UserSettingsController
{
    private const PROFILE_UPLOAD_DIR = __DIR__ . '/../public/uploads/images/profile';
    private const MAX_AVATAR_BYTES = 5242880;

    private UserSettingsModel $userSettingsModel;
    private AdminSession $session;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->userSettingsModel = new UserSettingsModel();
        $this->session = new AdminSession();
        $this->notificationService = new NotificationService();
    }

    public function show(): void
    {
        $user = $this->requireAuthenticatedUser();
        $settings = $this->buildSettingsViewState($user);

        $url = 'user/settings';
        require __DIR__ . '/../views/layout/shell.php';
    }

    public function getProfileData(): void
    {
        $user = $this->requireAuthenticatedUser();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'avatar' => $user['avatar'],
                'notifications' => [
                    'low_stock_alerts' => $user['notify_low_stock'],
                    'weekly_summary_reports' => $user['notify_weekly_summary'],
                ],
            ],
        ]);
        exit;
    }

    public function updateProfile(): void
    {
        $user = $this->requireAuthenticatedUser();
        $input = $this->getInputData();

        $firstName = trim((string) ($input['first_name'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));

        $errors = [];

        if ($firstName === '') {
            $errors['first_name'] = 'First name is required.';
        }

        if ($lastName === '') {
            $errors['last_name'] = 'Last name is required.';
        }

        if ($email === '') {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif ($this->userSettingsModel->emailExistsForOtherUser($email, (int) $user['id'])) {
            $errors['email'] = 'That email address is already in use.';
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone number is required.';
        } elseif (mb_strlen($phone) > 30) {
            $errors['phone'] = 'Phone number must be 30 characters or fewer.';
        }

        $avatarPath = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $avatarResult = $this->handleAvatarUpload('avatar');
            if (!empty($avatarResult['error'])) {
                $errors['avatar'] = $avatarResult['error'];
            } elseif (!empty($avatarResult['path'])) {
                $avatarPath = $avatarResult['path'];
            }
        }

        if ($errors !== []) {
            if ($this->shouldReturnJson()) {
                $this->jsonResponse(false, ['errors' => $errors], 422);
            }

            $this->flashFormState('profile', [
                'errors' => $errors,
                'values' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                ],
                'message' => 'Please fix the highlighted profile fields.',
                'message_type' => 'error',
            ]);
            $this->redirectToSettings('profile');
        }

        $this->userSettingsModel->updateProfile($user, $firstName, $lastName, $email, $phone, $avatarPath);
        $updatedUser = $this->requireAuthenticatedUser(true);
        $this->userSettingsModel->refreshAuthenticatedUser($updatedUser);

        if ($this->shouldReturnJson()) {
            $this->jsonResponse(true, [
                'message' => 'Profile updated successfully.',
                'data' => [
                    'first_name' => $updatedUser['first_name'],
                    'last_name' => $updatedUser['last_name'],
                    'email' => $updatedUser['email'],
                    'phone' => $updatedUser['phone'],
                    'role' => $updatedUser['role'],
                    'avatar' => $updatedUser['avatar'],
                ],
            ]);
        }

        $_SESSION['user_settings_flash']['profile'] = [
            'message' => 'Profile updated successfully.',
            'message_type' => 'success',
        ];

        $this->redirectToSettings('profile');
    }

    public function updatePassword(): void
    {
        $user = $this->requireAuthenticatedUser();
        $input = $this->getInputData();

        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['confirm_password'] ?? '');

        $errors = [];

        if (trim($currentPassword) === '') {
            $errors['current_password'] = 'Current password is required.';
        } elseif (!$this->userSettingsModel->verifyPassword($user, $currentPassword)) {
            $errors['current_password'] = 'Current password is incorrect.';
        }

        if (trim($newPassword) === '') {
            $errors['new_password'] = 'New password is required.';
        } elseif (!$this->isValidSettingsPassword($newPassword)) {
            $errors['new_password'] = 'New password must be at least 8 characters and include at least one special character like !, @, #, $, or _.';
        }

        if (trim($confirmPassword) === '') {
            $errors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            if ($this->shouldReturnJson()) {
                $this->jsonResponse(false, ['errors' => $errors], 422);
            }

            $this->flashFormState('security', [
                'errors' => $errors,
                'message' => 'Please fix the highlighted password fields.',
                'message_type' => 'error',
            ]);
            $this->redirectToSettings('security');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->userSettingsModel->updatePassword($user, $hash);

        try {
            $this->notificationService->notifyPasswordChanged($user);
        } catch (Throwable $e) {
            error_log('Failed to create user password notification: ' . $e->getMessage());
        }

        $this->sendPasswordChangedEmail((string) $user['email'], (string) ($user['full_name'] ?? ''));

        if ($this->shouldReturnJson()) {
            $this->jsonResponse(true, ['message' => 'Password updated successfully.']);
        }

        $_SESSION['user_settings_flash']['security'] = [
            'message' => 'Password updated successfully.',
            'message_type' => 'success',
        ];

        $this->redirectToSettings('security');
    }

    public function updateNotifications(): void
    {
        $user = $this->requireAuthenticatedUser();
        $input = $this->getInputData();

        $lowStockAlerts = isset($input['low_stock_alerts']) && (string) $input['low_stock_alerts'] === '1';
        $weeklySummaryReports = isset($input['weekly_summary_reports']) && (string) $input['weekly_summary_reports'] === '1';

        $this->userSettingsModel->updateNotificationPreferences($user, $lowStockAlerts, $weeklySummaryReports);

        $updatedUser = $this->requireAuthenticatedUser(true);

        if ($this->shouldReturnJson()) {
            $this->jsonResponse(true, [
                'message' => 'Notification preferences saved successfully.',
                'data' => [
                    'low_stock_alerts' => (bool) $updatedUser['notify_low_stock'],
                    'weekly_summary_reports' => (bool) $updatedUser['notify_weekly_summary'],
                ],
            ]);
        }

        $_SESSION['user_settings_flash']['notifications'] = [
            'message' => 'Notification preferences saved successfully.',
            'message_type' => 'success',
        ];

        $this->redirectToSettings('notifications');
    }

    private function requireAuthenticatedUser(bool $fresh = false): array
    {
        $account = $this->session->requireAuthenticatedAccount();

        if (($account['role'] ?? 'user') !== 'user' || ($account['source'] ?? '') !== 'users') {
            $_SESSION['auth_error'] = 'You do not have permission to access that page.';
            header('Location: index.php?url=' . inventra_default_authenticated_url());
            exit;
        }

        $profile = $this->userSettingsModel->findSettingsProfile($account, $fresh);
        if ($profile === null) {
            $_SESSION['auth_error'] = 'Unable to load your account settings.';
            header('Location: index.php?url=auth/logout');
            exit;
        }

        return $profile;
    }

    private function buildSettingsViewState(array $user): array
    {
        $flash = $_SESSION['user_settings_flash'] ?? [];
        unset($_SESSION['user_settings_flash']);

        $profileFlash = $flash['profile'] ?? [];
        $securityFlash = $flash['security'] ?? [];
        $notificationsFlash = $flash['notifications'] ?? [];

        $profileValues = $profileFlash['values'] ?? [];
        $securityHasState = !empty($securityFlash['errors']) || !empty($securityFlash['message']) || (($flash['active_tab'] ?? '') === 'security');

        return [
            'profile' => [
                'first_name' => (string) ($profileValues['first_name'] ?? $user['first_name']),
                'last_name' => (string) ($profileValues['last_name'] ?? $user['last_name']),
                'email' => (string) ($profileValues['email'] ?? $user['email']),
                'phone' => (string) ($profileValues['phone'] ?? $user['phone']),
                'full_name' => (string) ($user['full_name'] ?? trim(((string) ($profileValues['first_name'] ?? $user['first_name'])) . ' ' . ((string) ($profileValues['last_name'] ?? $user['last_name'])))),
                'role' => (string) $user['role'],
                'photo' => $user['avatar'],
            ],
            'notifications' => [
                'low_stock_alerts' => isset($notificationsFlash['values']['low_stock_alerts'])
                    ? (bool) $notificationsFlash['values']['low_stock_alerts']
                    : (bool) $user['notify_low_stock'],
                'weekly_summary_reports' => isset($notificationsFlash['values']['weekly_summary_reports'])
                    ? (bool) $notificationsFlash['values']['weekly_summary_reports']
                    : (bool) $user['notify_weekly_summary'],
            ],
            'errors' => [
                'profile' => $profileFlash['errors'] ?? [],
                'security' => $securityFlash['errors'] ?? [],
                'notifications' => $notificationsFlash['errors'] ?? [],
            ],
            'messages' => [
                'profile' => $profileFlash['message'] ?? '',
                'security' => $securityFlash['message'] ?? '',
                'notifications' => $notificationsFlash['message'] ?? '',
            ],
            'message_types' => [
                'profile' => $profileFlash['message_type'] ?? '',
                'security' => $securityFlash['message_type'] ?? '',
                'notifications' => $notificationsFlash['message_type'] ?? '',
            ],
            'active_tab' => (string) ($flash['active_tab'] ?? 'profile'),
            'security_expanded' => $securityHasState,
            'current_user' => $user,
        ];
    }

    private function getInputData(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $_POST;
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $data = [];
        parse_str($raw, $data);
        return is_array($data) ? $data : [];
    }

    private function shouldReturnJson(): bool
    {
        return in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['PUT', 'PATCH'], true);
    }

    private function jsonResponse(bool $success, array $payload = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success], $payload));
        exit;
    }

    private function handleAvatarUpload(string $fieldName): array
    {
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            return ['path' => null, 'error' => null];
        }

        $file = $_FILES[$fieldName];

        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Unable to upload the selected image.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > self::MAX_AVATAR_BYTES) {
            return ['path' => null, 'error' => 'Avatar must be 5MB or smaller.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mimeType = $this->detectMimeType($tmpName);
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($allowedMimeTypes[$mimeType])) {
            return ['path' => null, 'error' => 'Only PNG, JPG, and JPEG files are allowed.'];
        }

        if (!is_dir(self::PROFILE_UPLOAD_DIR) && !mkdir(self::PROFILE_UPLOAD_DIR, 0775, true) && !is_dir(self::PROFILE_UPLOAD_DIR)) {
            return ['path' => null, 'error' => 'Unable to prepare the avatar upload directory.'];
        }

        $extension = $allowedMimeTypes[$mimeType];
        $accountId = (string) (inventra_authenticated_user_id() ?? 'user');
        $fileName = sprintf('user_%s_%s.%s', $accountId, bin2hex(random_bytes(8)), $extension);
        $destination = self::PROFILE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['path' => null, 'error' => 'Unable to save the uploaded avatar.'];
        }

        return [
            'path' => 'public/uploads/images/profile/' . $fileName,
            'error' => null,
        ];
    }

    private function detectMimeType(string $tmpName): string
    {
        if ($tmpName === '') {
            return '';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $tmpName) ?: '';
                finfo_close($finfo);
                return $mimeType;
            }
        }

        return (string) mime_content_type($tmpName);
    }

    private function flashFormState(string $section, array $state): void
    {
        $_SESSION['user_settings_flash'][$section] = $state;
    }

    private function redirectToSettings(string $activeTab): void
    {
        $_SESSION['user_settings_flash']['active_tab'] = $activeTab;
        header('Location: index.php?url=user/settings');
        exit;
    }

    private function isValidSettingsPassword(string $password): bool
    {
        return (bool) preg_match('/^(?=.*[^A-Za-z0-9]).{8,}$/', $password);
    }

    private function sendPasswordChangedEmail(string $toEmail, string $name = ''): void
    {
        if (trim($toEmail) === '') {
            return;
        }

        try {
            $mailConfig = require __DIR__ . '/../config/mail.php';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = $this->resolveMailEncryption($mailConfig);
            $mail->Port = $mailConfig['port'];
            $mail->Timeout = 20;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($toEmail, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Your Inventra password was changed';
            $mail->Body = '
                <h2>Password Updated</h2>
                <p>Your Inventra account password was changed successfully.</p>
                <p>If this was not you, please contact support immediately.</p>
            ';
            $mail->AltBody = "Your Inventra account password was changed successfully.\nIf this was not you, please contact support immediately.";

            $mail->send();
        } catch (Throwable $e) {
            error_log('Password change email failed for ' . $toEmail . ': ' . $e->getMessage());
        }
    }

    private function resolveMailEncryption(array $mailConfig): string
    {
        $encryption = strtolower(trim((string) ($mailConfig['encryption'] ?? 'ssl')));

        return match ($encryption) {
            'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            default => PHPMailer::ENCRYPTION_SMTPS,
        };
    }
}
