<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NotificationModel.php';

class NotificationService
{
    private PDO $db;
    private NotificationModel $notificationModel;

    public function __construct(?NotificationModel $notificationModel = null)
    {
        $this->db = Database::connect();
        $this->notificationModel = $notificationModel ?? new NotificationModel();
    }

    public function announceNotificationCenter(array $account): void
    {
        [$userId, $source] = $this->extractAccountRecipient($account);

        if ($userId <= 0 || $source === '') {
            return;
        }

        $this->notificationModel->createNotificationIfMissing(
            $userId,
            $source,
            'system_update',
            'System Update',
            'Your notification center is now available from the top bar.'
        );
    }

    public function notifyLogin(array $account): void
    {
        [$userId, $source] = $this->extractAccountRecipient($account);

        if ($userId <= 0 || $source === '') {
            return;
        }

        $this->notificationModel->createNotificationIfMissing(
            $userId,
            $source,
            'security_alert',
            'Security Alert',
            'A new login to your Inventra account was detected.',
            30
        );
    }

    public function notifyPasswordChanged(array $account): void
    {
        [$userId, $source] = $this->extractAccountRecipient($account);

        if ($userId <= 0 || $source === '') {
            return;
        }

        $this->notificationModel->createNotificationIfMissing(
            $userId,
            $source,
            'security_alert',
            'Security Alert',
            'Your Inventra account password was changed successfully.',
            5
        );
    }

    public function notifyUserCreated(int $userId, string $fullName): void
    {
        if ($userId <= 0) {
            return;
        }

        $displayName = trim($fullName) !== '' ? trim($fullName) : 'A new user';

        $this->notificationModel->createNotificationIfMissing(
            $userId,
            'users',
            'new_user',
            'New User',
            'Your Inventra account has been created and is ready to use.'
        );

        foreach ($this->getAdminRecipients() as $recipient) {
            $this->notificationModel->createNotificationIfMissing(
                (int) $recipient['id'],
                (string) $recipient['source'],
                'new_user',
                'New User',
                $displayName . ' has been added to the system.',
                1440
            );
        }
    }

    public function notifyRequestApprovedForUser(
        int $userId,
        string $message = 'Your Inventra account access has been approved and reactivated.'
    ): void {
        if ($userId <= 0) {
            return;
        }

        $this->notificationModel->createNotificationIfMissing(
            $userId,
            'users',
            'request_approved',
            'Request Approved',
            trim($message) !== '' ? trim($message) : 'Your request has been approved.',
            720
        );
    }

    public function notifyLowStockForProduct(int $productId): void
    {
        $product = $this->findProductById($productId);

        if ($product === null) {
            return;
        }

        $quantity = (int) $product['qty'];
        $lowerLimit = (int) $product['lower_limit'];

        if (!$this->isLowStock($quantity, $lowerLimit)) {
            return;
        }

        $message = trim((string) ($product['name'] ?? '')) . ' is below threshold (' . $quantity . ' remaining).';

        foreach ($this->getLowStockRecipients() as $recipient) {
            $this->notificationModel->createNotificationIfMissing(
                (int) $recipient['id'],
                (string) $recipient['source'],
                'low_stock',
                'Low Stock',
                $message,
                180
            );
        }
    }

    private function getAdminRecipients(): array
    {
        $recipients = [];

        $adminRows = $this->db->query("SELECT id, 'admin' AS source FROM admin ORDER BY id ASC")->fetchAll() ?: [];
        foreach ($adminRows as $row) {
            $recipients[] = [
                'id' => (int) $row['id'],
                'source' => (string) $row['source'],
            ];
        }

        if ($this->usersTableExists()) {
            $userRows = $this->db->query("
                SELECT id, 'users' AS source
                FROM users
                WHERE status = 'active'
                  AND role = 'admin'
                ORDER BY id ASC
            ")->fetchAll() ?: [];

            foreach ($userRows as $row) {
                $recipients[] = [
                    'id' => (int) $row['id'],
                    'source' => (string) $row['source'],
                ];
            }
        }

        return $recipients;
    }

    private function getLowStockRecipients(): array
    {
        $recipients = [];

        $adminWhere = $this->adminHasColumn('notify_low_stock')
            ? 'WHERE COALESCE(notify_low_stock, TRUE) = TRUE'
            : '';
        $adminRows = $this->db->query("
            SELECT id, 'admin' AS source
            FROM admin
            {$adminWhere}
            ORDER BY id ASC
        ")->fetchAll() ?: [];

        foreach ($adminRows as $row) {
            $recipients[] = [
                'id' => (int) $row['id'],
                'source' => (string) $row['source'],
            ];
        }

        if ($this->usersTableExists()) {
            $usersWhere = ["status = 'active'"];
            if ($this->usersHasColumn('notify_low_stock')) {
                $usersWhere[] = 'COALESCE(notify_low_stock, TRUE) = TRUE';
            }

            $userSql = "
                SELECT id, 'users' AS source
                FROM users
                WHERE " . implode(' AND ', $usersWhere) . "
                ORDER BY id ASC
            ";
            $userRows = $this->db->query($userSql)->fetchAll() ?: [];

            foreach ($userRows as $row) {
                $recipients[] = [
                    'id' => (int) $row['id'],
                    'source' => (string) $row['source'],
                ];
            }
        }

        return $recipients;
    }

    private function findProductById(int $productId): ?array
    {
        if ($productId <= 0 || !Database::tableExists('products')) {
            return null;
        }

        $stmt = $this->db->prepare('
            SELECT id, name, qty, lower_limit
            FROM products
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$productId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function isLowStock(int $quantity, int $lowerLimit): bool
    {
        if ($lowerLimit > 0) {
            return $quantity <= $lowerLimit;
        }

        return $quantity <= 0;
    }

    private function usersTableExists(): bool
    {
        try {
            return Database::tableExists('users');
        } catch (Throwable) {
            return false;
        }
    }

    private function adminHasColumn(string $column): bool
    {
        try {
            return Database::columnExists('admin', $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function usersHasColumn(string $column): bool
    {
        if (!$this->usersTableExists()) {
            return false;
        }

        try {
            return Database::columnExists('users', $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function extractAccountRecipient(array $account): array
    {
        $userId = (int) ($account['id'] ?? 0);
        $source = trim(strtolower((string) ($account['source'] ?? '')));

        if (!in_array($source, ['admin', 'users'], true)) {
            return [0, ''];
        }

        return [$userId, $source];
    }
}
