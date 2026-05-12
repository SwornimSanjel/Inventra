<?php

require_once __DIR__ . '/AccountModel.php';
require_once __DIR__ . '/../helpers/session.php';

class AdminSession
{
    private AccountModel $accountModel;
    private static bool $accountResolved = false;
    private static ?array $resolvedAccount = null;
    private static bool $adminResolved = false;
    private static ?array $resolvedAdmin = null;

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
        if (self::$accountResolved) {
            return self::$resolvedAccount;
        }

        if (!inventra_is_authenticated()) {
            self::$accountResolved = true;
            self::$resolvedAccount = null;
            return null;
        }

        $accountId = inventra_authenticated_user_id();
        $source = inventra_authenticated_user_source();

        if (is_numeric($accountId) && is_string($source) && trim($source) !== '') {
            $account = $this->accountModel->findByIdAndSource((int) $accountId, trim($source));
            if ($account !== null && !empty($account['is_active'])) {
                $this->syncSession($account);
                self::$accountResolved = true;
                self::$resolvedAccount = $account;
                $this->enforcePasswordChangeGate();
                return $account;
            }
        }

        $fallbackEmail = inventra_authenticated_user_email();
        if (is_string($fallbackEmail) && trim($fallbackEmail) !== '') {
            $account = $this->accountModel->findUniqueByEmail(trim($fallbackEmail), true);
            if ($account !== null) {
                $this->syncSession($account);
                self::$accountResolved = true;
                self::$resolvedAccount = $account;
                $this->enforcePasswordChangeGate();
                return $account;
            }
        }

        inventra_clear_authenticated_user();
        self::$accountResolved = true;
        self::$resolvedAccount = null;
        return null;
    }

    public function resolveAuthenticatedAdmin(): ?array
    {
        if (self::$adminResolved) {
            return self::$resolvedAdmin;
        }

        $account = $this->resolveAuthenticatedAccount();

        if ($account === null || ($account['role'] ?? 'user') !== 'admin') {
            self::$adminResolved = true;
            self::$resolvedAdmin = null;
            return null;
        }

        $profile = $this->accountModel->findSettingsProfile($account);
        $resolvedAdmin = $profile !== null
            ? array_merge($account, $profile)
            : $account;

        self::$adminResolved = true;
        self::$resolvedAdmin = $resolvedAdmin;

        return $resolvedAdmin;
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

        if (array_key_exists('avatar', $account)) {
            $_SESSION['admin_avatar'] = (string) ($account['avatar'] ?? '');
        }
    }

    private function enforcePasswordChangeGate(): void
    {
        if (!inventra_password_change_required() || $this->isPasswordChangeAllowedRequest()) {
            return;
        }

        $_SESSION['auth_error'] = 'Please change your default password before accessing Inventra.';

        if ($this->requestWantsJson()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Please change your default password before accessing Inventra.',
                'redirect' => 'index.php?url=' . inventra_forced_password_change_url(),
            ]);
            exit;
        }

        header('Location: index.php?url=' . inventra_forced_password_change_url());
        exit;
    }

    private function isPasswordChangeAllowedRequest(): bool
    {
        $route = trim((string) ($_GET['url'] ?? ''), '/');

        return in_array($route, [
            inventra_forced_password_change_url(),
            'auth/logout',
        ], true);
    }

    private function requestWantsJson(): bool
    {
        $requestUri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return str_contains($requestUri, '/api/')
            || str_contains($accept, 'application/json')
            || $requestedWith === 'xmlhttprequest';
    }
}
