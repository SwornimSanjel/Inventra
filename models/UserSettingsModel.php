<?php

require_once __DIR__ . '/AccountModel.php';
require_once __DIR__ . '/../helpers/session.php';

class UserSettingsModel
{
    private AccountModel $accountModel;

    public function __construct()
    {
        $this->accountModel = new AccountModel();
    }

    public function findSettingsProfile(array $account, bool $fresh = false): ?array
    {
        if (($account['source'] ?? '') !== 'users') {
            return null;
        }

        if ($fresh) {
            $latest = $this->accountModel->findByIdAndSource((int) ($account['id'] ?? 0), 'users');
            if ($latest === null) {
                return null;
            }

            $account = $latest;
        }

        return $this->accountModel->findSettingsProfile($account);
    }

    public function emailExistsForOtherUser(string $email, int $excludeId): bool
    {
        return $this->accountModel->emailExistsForOtherAccount($email, 'users', $excludeId);
    }

    public function updateProfile(array $account, string $firstName, string $lastName, string $email, string $phone, ?string $avatarPath = null): bool
    {
        return $this->accountModel->updateProfile($account, $firstName, $lastName, $email, $phone, $avatarPath);
    }

    public function verifyPassword(array $account, string $plainPassword): bool
    {
        return $this->accountModel->verifyPassword($account, $plainPassword);
    }

    public function updatePassword(array $account, string $hash): bool
    {
        return $this->accountModel->updatePassword($account, $hash);
    }

    public function updateNotificationPreferences(array $account, bool $lowStockAlerts, bool $weeklySummaryReports): bool
    {
        return $this->accountModel->updateNotificationPreferences($account, $lowStockAlerts, $weeklySummaryReports);
    }

    public function refreshAuthenticatedUser(array $user): void
    {
        inventra_set_authenticated_user([
            'id' => (int) $user['id'],
            'source' => 'users',
            'email' => (string) $user['email'],
            'full_name' => (string) ($user['full_name'] ?? ''),
            'role' => (string) ($user['role_value'] ?? 'user'),
        ]);
    }
}
