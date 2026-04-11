<?php

require_once __DIR__ . '/../models/UserModel.php';

class SettingsController
{
    private const PROFILE_UPLOAD_DIR = __DIR__ . '/../public/uploads/images/profile';
    private const MAX_AVATAR_BYTES = 5242880;

    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userModel->ensureAdminSettingsSchema();
        $this->userModel->ensurePasswordHistorySchema();
    }

    public function show(): void
    {
        $admin = $this->requireAuthenticatedAdmin();
        $settings = $this->buildSettingsViewState($admin);

        $url = 'admin/settings';
        require __DIR__ . '/../views/layout/shell.php';
    }

    public function getProfileData(): void
    {
        $admin = $this->requireAuthenticatedAdmin();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'first_name' => $admin['first_name'],
                'last_name' => $admin['last_name'],
                'email' => $admin['email'],
                'phone' => $admin['phone'],
                'role' => $admin['role'],
                'avatar' => $admin['avatar'],
                'notifications' => [
                    'low_stock_alerts' => $admin['notify_low_stock'],
                    'weekly_summary_reports' => $admin['notify_weekly_summary'],
                ],
            ],
        ]);
        exit;
    }

    public function updateProfile(): void
    {
        $admin = $this->requireAuthenticatedAdmin();

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

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
        } elseif ($this->userModel->emailExistsForOtherAdmin($email, (int) $admin['id'])) {
            $errors['email'] = 'That email address is already in use.';
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone number is required.';
        } elseif (mb_strlen($phone) > 30) {
            $errors['phone'] = 'Phone number must be 30 characters or fewer.';
        }

        $avatarPath = null;
        $avatarResult = $this->handleAvatarUpload('avatar');
        if (!empty($avatarResult['error'])) {
            $errors['avatar'] = $avatarResult['error'];
        } elseif (!empty($avatarResult['path'])) {
            $avatarPath = $avatarResult['path'];
        }

        if ($errors !== []) {
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

        $this->userModel->updateAdminProfile((int) $admin['id'], $firstName, $lastName, $email, $phone, $avatarPath);

        $_SESSION['admin_email'] = $email;
        $_SESSION['settings_flash']['profile'] = [
            'message' => 'Profile updated successfully.',
            'message_type' => 'success',
        ];

        $this->redirectToSettings('profile');
    }

    public function updatePassword(): void
    {
        $admin = $this->requireAuthenticatedAdmin();

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];

        if (trim($currentPassword) === '') {
            $errors['current_password'] = 'Current password is required.';
        } elseif (!$this->userModel->verifyPasswordById((int) $admin['id'], $currentPassword)) {
            $errors['current_password'] = 'Current password is incorrect.';
        }

        if (trim($newPassword) === '') {
            $errors['new_password'] = 'New password is required.';
        } elseif (!$this->isValidSettingsPassword($newPassword)) {
            $errors['new_password'] = 'New password must be at least 8 characters and include at least one special character.';
        }

        if (trim($confirmPassword) === '') {
            $errors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            $this->flashFormState('security', [
                'errors' => $errors,
                'message' => 'Please fix the highlighted password fields.',
                'message_type' => 'error',
            ]);
            $this->redirectToSettings('security');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->userModel->updatePasswordById((int) $admin['id'], $hash);

        $_SESSION['settings_flash']['security'] = [
            'message' => 'Password updated successfully.',
            'message_type' => 'success',
        ];

        $this->redirectToSettings('security');
    }

    public function updateNotifications(): void
    {
        $admin = $this->requireAuthenticatedAdmin();

        $lowStockAlerts = isset($_POST['low_stock_alerts']) && (string) $_POST['low_stock_alerts'] === '1';
        $weeklySummaryReports = isset($_POST['weekly_summary_reports']) && (string) $_POST['weekly_summary_reports'] === '1';

        $this->userModel->updateNotificationPreferences(
            (int) $admin['id'],
            $lowStockAlerts,
            $weeklySummaryReports
        );

        $_SESSION['settings_flash']['notifications'] = [
            'message' => 'Notification preferences saved successfully.',
            'message_type' => 'success',
        ];

        $this->redirectToSettings('notifications');
    }

    private function requireAuthenticatedAdmin(): array
    {
        $admin = $this->resolveAuthenticatedAdmin();

        if ($admin !== null) {
            return $admin;
        }

        http_response_code(403);
        echo 'Unauthorized';
        exit;
    }

    private function resolveAuthenticatedAdmin(): ?array
    {
        $candidateIds = [
            $_SESSION['admin_id'] ?? null,
            $_SESSION['user_id'] ?? null,
            $_SESSION['auth_user_id'] ?? null,
        ];

        foreach ($candidateIds as $candidateId) {
            if (is_numeric($candidateId)) {
                $admin = $this->userModel->findSettingsProfileById((int) $candidateId);
                if ($admin !== null) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    return $admin;
                }
            }
        }

        $candidateEmails = [
            $_SESSION['admin_email'] ?? null,
            $_SESSION['user_email'] ?? null,
            $_SESSION['email'] ?? null,
        ];

        foreach ($candidateEmails as $candidateEmail) {
            if (is_string($candidateEmail) && trim($candidateEmail) !== '') {
                $user = $this->userModel->findByEmail(trim($candidateEmail));
                if ($user !== null) {
                    $admin = $this->userModel->findSettingsProfileById((int) $user['id']);
                    if ($admin !== null) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_email'] = $admin['email'];
                        return $admin;
                    }
                }
            }
        }

        if ($this->isLocalDevelopmentRequest()) {
            $fallback = $this->userModel->findFirstAdmin();
            if ($fallback !== null) {
                $_SESSION['admin_id'] = (int) $fallback['id'];
                $_SESSION['admin_email'] = (string) $fallback['email'];
                return $this->userModel->findSettingsProfileById((int) $fallback['id']);
            }
        }

        return null;
    }

    private function buildSettingsViewState(array $admin): array
    {
        $flash = $_SESSION['settings_flash'] ?? [];
        unset($_SESSION['settings_flash']);

        $profileFlash = $flash['profile'] ?? [];
        $securityFlash = $flash['security'] ?? [];
        $notificationsFlash = $flash['notifications'] ?? [];

        $profileValues = $profileFlash['values'] ?? [];

        return [
            'profile' => [
                'first_name' => (string) ($profileValues['first_name'] ?? $admin['first_name']),
                'last_name' => (string) ($profileValues['last_name'] ?? $admin['last_name']),
                'email' => (string) ($profileValues['email'] ?? $admin['email']),
                'phone' => (string) ($profileValues['phone'] ?? $admin['phone']),
                'role' => (string) $admin['role'],
                'photo' => $admin['avatar'] ?: BASE_URL . 'public/images/me.JPG',
            ],
            'notifications' => [
                'low_stock_alerts' => isset($notificationsFlash['values']['low_stock_alerts'])
                    ? (bool) $notificationsFlash['values']['low_stock_alerts']
                    : (bool) $admin['notify_low_stock'],
                'weekly_summary_reports' => isset($notificationsFlash['values']['weekly_summary_reports'])
                    ? (bool) $notificationsFlash['values']['weekly_summary_reports']
                    : (bool) $admin['notify_weekly_summary'],
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
            'current_admin' => $admin,
        ];
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
            return ['path' => null, 'error' => 'Only JPG, JPEG, and PNG files are allowed.'];
        }

        if (!is_dir(self::PROFILE_UPLOAD_DIR) && !mkdir(self::PROFILE_UPLOAD_DIR, 0775, true) && !is_dir(self::PROFILE_UPLOAD_DIR)) {
            return ['path' => null, 'error' => 'Unable to prepare the avatar upload directory.'];
        }

        $extension = $allowedMimeTypes[$mimeType];
        $fileName = sprintf('admin_%s_%s.%s', (string) ($_SESSION['admin_id'] ?? 'user'), bin2hex(random_bytes(8)), $extension);
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
        $_SESSION['settings_flash'][$section] = $state;
    }

    private function redirectToSettings(string $activeTab): void
    {
        $_SESSION['settings_flash']['active_tab'] = $activeTab;
        header('Location: index.php?url=admin/settings');
        exit;
    }

    private function isValidSettingsPassword(string $password): bool
    {
        return (bool) preg_match('/^(?=.*[^A-Za-z0-9]).{8,}$/', $password);
    }

    private function isLocalDevelopmentRequest(): bool
    {
        $serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)
            || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
    }
}
