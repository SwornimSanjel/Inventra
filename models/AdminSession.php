<?php

require_once __DIR__ . '/AccountModel.php';
require_once __DIR__ . '/../helpers/session.php';

class AdminSession
{
    private AccountModel $accountModel;

    public function __construct(?AccountModel $accountModel = null)
    {
        $this->accountModel = $accountModel ?? new AccountModel();
    }

    public function requireAuthenticatedAdmin(): array
    {
        $account = $this->requireAuthenticatedAccount();

        if (($account['role'] ?? 'user') === 'admin') {
            return $this->resolveAuthenticatedAdmin() ?? $account;
        }

        $_SESSION['auth_error'] = 'You do not have permission to access that page.';
        header('Location: index.php?url=' . inventra_default_authenticated_url());
        exit;
    }

    public function requireAuthenticatedAccount(): array
    {
        $account = $this->resolveAuthenticatedAccount();

        if ($account !== null) {
            return $account;
        }

        $_SESSION['auth_error'] = 'Please log in to continue.';
        header('Location: index.php?url=login');
        exit;
    }

    public function resolveAuthenticatedAccount(): ?array
    {
        if (!inventra_is_authenticated()) {
            return null;
        }

        $accountId = inventra_authenticated_user_id();
        $source = inventra_authenticated_user_source();

        if (is_numeric($accountId) && is_string($source) && trim($source) !== '') {
            $account = $this->accountModel->findByIdAndSource((int) $accountId, trim($source));
            if ($account !== null && !empty($account['is_active'])) {
                $this->syncSession($account);
                return $account;
            }
        }

        $fallbackEmail = inventra_authenticated_user_email();
        if (is_string($fallbackEmail) && trim($fallbackEmail) !== '') {
            $account = $this->accountModel->findUniqueByEmail(trim($fallbackEmail), true);
            if ($account !== null) {
                $this->syncSession($account);
                return $account;
            }
        }

        inventra_clear_authenticated_user();
        return null;
    }

    public function resolveAuthenticatedAdmin(): ?array
    {
        $account = $this->resolveAuthenticatedAccount();

        if ($account === null || ($account['role'] ?? 'user') !== 'admin') {
            return null;
        }

        $profile = $this->accountModel->findSettingsProfile($account);

        return $profile !== null
            ? array_merge($account, $profile)
            : $account;
    }

    private function syncSession(array $account): void
    {
        inventra_set_authenticated_user([
            'id' => (int) $account['id'],
            'source' => (string) ($account['source'] ?? 'admin'),
            'email' => (string) $account['email'],
            'full_name' => (string) ($account['full_name'] ?? ''),
            'role' => (string) ($account['role'] ?? 'user'),
        ]);

        $profile = $this->accountModel->findSettingsProfile($account);
        $_SESSION['admin_avatar'] = (string) ($profile['avatar'] ?? '');
    }
}
