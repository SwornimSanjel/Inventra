<?php

require_once __DIR__ . '/UserModel.php';
require_once __DIR__ . '/../helpers/session.php';

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

        $_SESSION['auth_error'] = 'Please log in to continue.';
        header('Location: index.php?url=login');
        exit;
    }

    public function resolveAuthenticatedAdmin(): ?array
    {
        if (!inventra_is_authenticated()) {
            return null;
        }

        $candidateIds = [inventra_authenticated_admin_id()];

        foreach ($candidateIds as $candidateId) {
            if (is_numeric($candidateId)) {
                $admin = $this->userModel->findSettingsProfileById((int) $candidateId);
                if ($admin !== null) {
                    $this->syncSession($admin);
                    return $admin;
                }
            }
        }

        $candidateEmails = [inventra_authenticated_admin_email()];

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

        return null;
    }

    private function syncSession(array $admin): void
    {
        inventra_set_authenticated_admin([
            'id' => (int) $admin['id'],
            'email' => (string) $admin['email'],
            'full_name' => (string) ($admin['full_name'] ?? ''),
            'role' => (string) ($admin['role_value'] ?? $admin['role'] ?? 'admin'),
        ]);
        $_SESSION['admin_avatar'] = (string) ($admin['avatar'] ?? '');
    }
}
