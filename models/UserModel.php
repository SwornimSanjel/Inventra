<?php

require_once __DIR__ . '/../config/database.php';

class UserModel
{
    private PDO $db;
    private ?array $adminColumns = null;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT id, full_name, email, role FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findForAuthentication(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $conditions = ['email = ?'];
        $params = [$identifier];

        if ($this->hasAdminColumn('username')) {
            $conditions[] = 'username = ?';
            $params[] = $identifier;
        }

        $sql = sprintf(
            'SELECT id, full_name, email, role, password_hash FROM admin WHERE %s LIMIT 1',
            implode(' OR ', $conditions)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, full_name, email, role FROM admin WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findFirstAdmin(): ?array
    {
        $stmt = $this->db->query("SELECT id, full_name, email, role FROM admin ORDER BY id ASC LIMIT 1");
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findSettingsProfileById(int $id): ?array
    {
        $sql = sprintf(
            'SELECT %s FROM admin WHERE id = ? LIMIT 1',
            $this->buildSettingsSelectList()
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->normalizeSettingsProfile($row) : null;
    }

    public function emailExistsForOtherAdmin(string $email, int $excludeId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM admin WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$email, $excludeId]);

        return (bool) $stmt->fetch();
    }

    public function updateAdminProfile(int $id, string $firstName, string $lastName, string $email, string $phone, ?string $avatarPath = null): bool
    {
        $assignments = [
            'full_name = ?',
            'email = ?',
        ];
        $params = [
            $this->combineName($firstName, $lastName),
            $email,
        ];

        if ($this->hasAdminColumn('phone')) {
            $assignments[] = 'phone = ?';
            $params[] = $phone;
        }

        if ($avatarPath !== null && $this->hasAdminColumn('avatar')) {
            $assignments[] = 'avatar = ?';
            $params[] = $avatarPath;
        }

        $params[] = $id;

        $stmt = $this->db->prepare(sprintf(
            'UPDATE admin SET %s WHERE id = ?',
            implode(', ', $assignments)
        ));

        return $stmt->execute($params);
    }

    public function updateNotificationPreferences(int $id, bool $lowStockAlerts, bool $weeklySummaryReports): bool
    {
        $assignments = [];
        $params = [];

        if ($this->hasAdminColumn('notify_low_stock')) {
            $assignments[] = 'notify_low_stock = ?';
            $params[] = $lowStockAlerts;
        }

        if ($this->hasAdminColumn('notify_weekly_summary')) {
            $assignments[] = 'notify_weekly_summary = ?';
            $params[] = $weeklySummaryReports;
        }

        if ($assignments === []) {
            return true;
        }

        $params[] = $id;

        $stmt = $this->db->prepare(sprintf(
            'UPDATE admin SET %s WHERE id = ?',
            implode(', ', $assignments)
        ));

        return $stmt->execute($params);
    }

    public function verifyPasswordById(int $id, string $plainPassword): bool
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM admin WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row || empty($row['password_hash'])) {
            return false;
        }

        return password_verify($plainPassword, (string) $row['password_hash']);
    }

    public function updatePasswordById(int $id, string $hash): bool
    {
        return $this->changePasswordById($id, $hash);
    }

    public function updatePassword(string $email, string $hash): bool
    {
        return $this->changePasswordByEmail($email, $hash);
    }

    public function ensureAdminSettingsSchema(): void
    {
        $columnDefinitions = [
            'phone' => 'ALTER TABLE admin ADD COLUMN phone VARCHAR(30)',
            'avatar' => 'ALTER TABLE admin ADD COLUMN avatar VARCHAR(255)',
            'notify_low_stock' => 'ALTER TABLE admin ADD COLUMN notify_low_stock BOOLEAN NOT NULL DEFAULT TRUE',
            'notify_weekly_summary' => 'ALTER TABLE admin ADD COLUMN notify_weekly_summary BOOLEAN NOT NULL DEFAULT TRUE',
        ];

        foreach ($columnDefinitions as $column => $sql) {
            if (!$this->hasAdminColumn($column)) {
                $this->db->exec($sql);
                $this->adminColumns = null;
            }
        }

        $this->adminColumns = null;
    }

    public function ensurePasswordHistorySchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS admin_password_history (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                admin_id INTEGER NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_admin_password_history_admin
                    FOREIGN KEY (admin_id) REFERENCES admin(id)
                    ON DELETE CASCADE
            )
        ");
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_admin_password_history_admin_id ON admin_password_history (admin_id)');
    }

    public function changePasswordById(int $id, string $newHash): bool
    {
        $this->ensurePasswordHistorySchema();

        $stmt = $this->db->prepare('SELECT password_hash FROM admin WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $this->archivePasswordHistory($id, (string) ($row['password_hash'] ?? ''));

            $update = $this->db->prepare('UPDATE admin SET password_hash = ? WHERE id = ?');
            $result = $update->execute([$newHash, $id]);

            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function changePasswordByEmail(string $email, string $newHash): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM admin WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        return $this->changePasswordById((int) $row['id'], $newHash);
    }

    public function hasAdminColumn(string $column): bool
    {
        if ($this->adminColumns === null) {
            $this->adminColumns = [];
            $candidateColumns = ['id', 'full_name', 'email', 'role', 'password_hash', 'username', 'phone', 'avatar', 'notify_low_stock', 'notify_weekly_summary'];
            foreach ($candidateColumns as $candidateColumn) {
                if (Database::columnExists('admin', $candidateColumn)) {
                    $this->adminColumns[] = $candidateColumn;
                }
            }
        }

        return in_array($column, $this->adminColumns, true);
    }

    private function buildSettingsSelectList(): string
    {
        $columns = [
            'id',
            'full_name',
            'email',
            'role',
        ];

        $columns[] = $this->hasAdminColumn('phone') ? 'phone' : "'' AS phone";
        $columns[] = $this->hasAdminColumn('avatar') ? 'avatar' : 'NULL AS avatar';
        $columns[] = $this->hasAdminColumn('notify_low_stock') ? 'notify_low_stock' : '1 AS notify_low_stock';
        $columns[] = $this->hasAdminColumn('notify_weekly_summary') ? 'notify_weekly_summary' : '1 AS notify_weekly_summary';

        return implode(', ', $columns);
    }

    private function normalizeSettingsProfile(array $row): array
    {
        [$firstName, $lastName] = $this->splitName((string) ($row['full_name'] ?? ''));

        return [
            'id' => (int) $row['id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $this->combineName($firstName, $lastName),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'role' => $this->formatRole((string) ($row['role'] ?? '')),
            'role_value' => (string) ($row['role'] ?? ''),
            'avatar' => $this->normalizeAvatarPath($row['avatar'] ?? null),
            'notify_low_stock' => (bool) ($row['notify_low_stock'] ?? true),
            'notify_weekly_summary' => (bool) ($row['notify_weekly_summary'] ?? true),
        ];
    }

    private function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');

        if ($fullName === '') {
            return ['', ''];
        }

        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        return [$firstName, $lastName];
    }

    private function combineName(string $firstName, string $lastName): string
    {
        return trim($firstName . ' ' . $lastName);
    }

    private function formatRole(string $role): string
    {
        $role = trim(str_replace(['_', '-'], ' ', strtolower($role)));
        return $role === '' ? '' : ucwords($role);
    }

    private function normalizeAvatarPath(mixed $avatar): ?string
    {
        if (!is_string($avatar) || trim($avatar) === '') {
            return null;
        }

        $avatar = str_replace('\\', '/', trim($avatar));
        $baseUrl = defined('BASE_URL') ? BASE_URL : './';

        if (strpos($avatar, 'public/') === 0) {
            return $baseUrl . ltrim($avatar, '/');
        }

        return $avatar;
    }

    private function archivePasswordHistory(int $adminId, string $currentHash): void
    {
        if ($currentHash === '') {
            return;
        }

        $insert = $this->db->prepare('
            INSERT INTO admin_password_history (admin_id, password_hash, changed_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ');
        $insert->execute([$adminId, $currentHash]);
    }
}


