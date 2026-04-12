<?php

require_once __DIR__ . '/UserModel.php';

class AdminSession
{
    private UserModel $userModel;

    public function __construct(?UserModel $userModel = null)
    {
        $this->userModel = $userModel ?? new UserModel();
    }

    public function requireAuthenticatedAdmin(): array
    {
        $admin = $this->resolveAuthenticatedAdmin();

        if ($admin !== null) {
            return $admin;
        }

        http_response_code(403);
        echo 'Unauthorized';
        exit;
    }

    public function resolveAuthenticatedAdmin(): ?array
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
                    $this->syncSession($admin);
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
                        $this->syncSession($admin);
                        return $admin;
                    }
                }
            }
        }

        if ($this->isLocalDevelopmentRequest()) {
            $fallback = $this->userModel->findFirstAdmin();
            if ($fallback !== null) {
                $admin = $this->userModel->findSettingsProfileById((int) $fallback['id']);
                if ($admin !== null) {
                    $this->syncSession($admin);
                    return $admin;
                }
            }
        }

        return null;
    }

    private function syncSession(array $admin): void
    {
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_email'] = (string) $admin['email'];
        $_SESSION['admin_name'] = (string) ($admin['full_name'] ?? '');
        $_SESSION['admin_avatar'] = (string) ($admin['avatar'] ?? '');
    }

    private function isLocalDevelopmentRequest(): bool
    {
        $serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)
            || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
    }
}
