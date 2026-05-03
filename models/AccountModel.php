<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/UserModel.php';
require_once __DIR__ . '/UserManagementModel.php';

class AccountModel
{
    private PDO $db;
    private UserModel $adminModel;
    private UserManagementModel $userManagementModel;
    private ?array $usersColumns = null;
    private ?bool $usersTableAvailable = null;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->adminModel = new UserModel();
        $this->userManagementModel = new UserManagementModel();
    }

    public function findAccountsByIdentifier(string $identifier, bool $activeOnly = true): array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return [];
        }

        return array_merge(
            $this->findAdminAccountsByIdentifier($identifier),
            $this->findUserAccountsByIdentifier($identifier, $activeOnly)
        );
    }

    public function findUniqueForAuthentication(string $identifier): ?array
    {
        $accounts = $this->findAccountsByIdentifier($identifier, true);
        return count($accounts) === 1 ? $accounts[0] : null;
    }

    public function countAccountsByIdentifier(string $identifier, bool $activeOnly = true): int
    {
        return count($this->findAccountsByIdentifier($identifier, $activeOnly));
    }

    public function findAccountsByEmail(string $email, bool $activeOnly = true): array
    {
        $email = trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        return array_merge(
            $this->findAdminAccountsByEmail($email),
            $this->findUserAccountsByEmail($email, $activeOnly)
        );
    }

    public function findUniqueByEmail(string $email, bool $activeOnly = true): ?array
    {
        $accounts = $this->findAccountsByEmail($email, $activeOnly);
        return count($accounts) === 1 ? $accounts[0] : null;
    }

    public function countAccountsByEmail(string $email, bool $activeOnly = true): int
    {
        return count($this->findAccountsByEmail($email, $activeOnly));
    }

    public function findByIdAndSource(int $id, string $source): ?array
    {
        if ($id <= 0) {
            return null;
        }

        if ($source === 'admin') {
            $conditions = ['id = ?'];
            $params = [$id];

            if ($this->adminModel->hasAdminColumn('username')) {
                $sql = 'SELECT id, full_name, email, role, password_hash, username FROM admin WHERE id = ? LIMIT 1';
            } else {
                $sql = 'SELECT id, full_name, email, role, password_hash FROM admin WHERE id = ? LIMIT 1';
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();

            return $row ? $this->normalizeAdminAccount($row) : null;
        }

        if ($source !== 'users' || !$this->usersTableExists()) {
            return null;
        }

        $select = [
            'id',
            'full_name',
            'username',
            'email',
            'role',
            'status',
            'password',
        ];

        $stmt = $this->db->prepare(sprintf(
            'SELECT %s FROM users WHERE id = ? LIMIT 1',
            implode(', ', $select)
        ));
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->normalizeUserAccount($row) : null;
    }

    public function findSettingsProfile(array $account): ?array
    {
        $source = (string) ($account['source'] ?? '');
        $id = (int) ($account['id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        if ($source === 'admin') {
            $profile = $this->adminModel->findSettingsProfileById($id);

            if ($profile === null) {
                return null;
            }

            $profile['source'] = 'admin';
            return $profile;
        }

        if ($source !== 'users' || !$this->usersTableExists()) {
            return null;
        }

        $columns = [
            'id',
            'full_name',
            'email',
            'role',
        ];

        $columns[] = $this->usersTableHasColumn('phone') ? 'phone' : "'' AS phone";
        $columns[] = $this->usersTableHasColumn('avatar') ? 'avatar' : 'NULL AS avatar';
        $columns[] = $this->usersTableHasColumn('notify_low_stock') ? 'notify_low_stock' : '1 AS notify_low_stock';
        $columns[] = $this->usersTableHasColumn('notify_weekly_summary') ? 'notify_weekly_summary' : '1 AS notify_weekly_summary';

        $stmt = $this->db->prepare(sprintf(
            'SELECT %s FROM users WHERE id = ? LIMIT 1',
            implode(', ', $columns)
        ));
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        [$firstName, $lastName] = $this->splitName((string) ($row['full_name'] ?? ''));
        $avatar = $this->normalizeAvatarPath($row['avatar'] ?? null);

        return [
            'id' => (int) $row['id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $this->combineName($firstName, $lastName),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'role' => $this->formatRole($this->normalizeRole((string) ($row['role'] ?? 'user'))),
            'role_value' => $this->normalizeRole((string) ($row['role'] ?? 'user')),
            'avatar' => $avatar,
            'notify_low_stock' => (bool) ($row['notify_low_stock'] ?? true),
            'notify_weekly_summary' => (bool) ($row['notify_weekly_summary'] ?? true),
            'source' => 'users',
        ];
    }

    public function verifyPassword(array $account, string $plainPassword): bool
    {
        $source = (string) ($account['source'] ?? '');
        $id = (int) ($account['id'] ?? 0);

        if ($id <= 0 || $plainPassword === '') {
            return false;
        }

        if ($source === 'admin') {
            return $this->adminModel->verifyPasswordById($id, $plainPassword);
        }

        if ($source !== 'users' || !$this->usersTableExists()) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row || empty($row['password'])) {
            return false;
        }

        return password_verify($plainPassword, (string) $row['password']);
    }

    public function updatePassword(array $account, string $hash): bool
    {
        $source = (string) ($account['source'] ?? '');
        $id = (int) ($account['id'] ?? 0);

        if ($id <= 0 || $hash === '') {
            return false;
        }

        if ($source === 'admin') {
            return $this->adminModel->updatePasswordById($id, $hash);
        }

        if ($source !== 'users' || !$this->usersTableExists()) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([$hash, $id]);
    }

    public function updateProfile(array $account, string $firstName, string $lastName, string $email, string $phone, ?string $avatarPath = null): bool
    {
        $source = (string) ($account['source'] ?? '');
        $id = (int) ($account['id'] ?? 0);

        if ($id <= 0) {
            return false;
        }

        if ($source === 'admin') {
            return $this->adminModel->updateAdminProfile($id, $firstName, $lastName, $email, $phone, $avatarPath);
        }

        if ($source !== 'users' || !$this->usersTableExists()) {
            return false;
        }

        $assignments = [
            'full_name = ?',
            'email = ?',
        ];
        $params = [
            $this->combineName($firstName, $lastName),
            $email,
        ];

        if ($this->usersTableHasColumn('phone')) {
            $assignments[] = 'phone = ?';
            $params[] = $phone;
        }

        if ($avatarPath !== null && $this->usersTableHasColumn('avatar')) {
            $assignments[] = 'avatar = ?';
            $params[] = $avatarPath;
        }

        $params[] = $id;

        $stmt = $this->db->prepare(sprintf(
            'UPDATE users SET %s WHERE id = ?',
            implode(', ', $assignments)
        ));

        return $stmt->execute($params);
    }

    public function updateNotificationPreferences(array $account, bool $lowStockAlerts, bool $weeklySummaryReports): bool
    {
        $source = (string) ($account['source'] ?? '');
        $id = (int) ($account['id'] ?? 0);

        if ($id <= 0) {
            return false;
        }

        if ($source === 'admin') {
            return $this->adminModel->updateNotificationPreferences($id, $lowStockAlerts, $weeklySummaryReports);
        }

        if ($source !== 'users' || !$this->usersTableExists()) {
            return false;
        }

        $assignments = [];
        $bindings = [];

        if ($this->usersTableHasColumn('notify_low_stock')) {
            $assignments[] = 'notify_low_stock = ?';
            $bindings[] = [
                'value' => $lowStockAlerts,
                'type' => PDO::PARAM_BOOL,
            ];
        }

        if ($this->usersTableHasColumn('notify_weekly_summary')) {
            $assignments[] = 'notify_weekly_summary = ?';
            $bindings[] = [
                'value' => $weeklySummaryReports,
                'type' => PDO::PARAM_BOOL,
            ];
        }

        if ($assignments === []) {
            return true;
        }

        $stmt = $this->db->prepare(sprintf(
            'UPDATE users SET %s WHERE id = ?',
            implode(', ', $assignments)
        ));

        $position = 1;

        foreach ($bindings as $binding) {
            $stmt->bindValue($position++, $binding['value'], $binding['type']);
        }

        $stmt->bindValue($position, $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function emailExistsForOtherAccount(string $email, string $source, int $excludeId): bool
    {
        $email = trim($email);

        if ($email === '') {
            return false;
        }

        $adminParams = [$email];
        $adminSql = 'SELECT id FROM admin WHERE email = ?';
        if ($source === 'admin' && $excludeId > 0) {
            $adminSql .= ' AND id <> ?';
            $adminParams[] = $excludeId;
        }
        $adminSql .= ' LIMIT 1';

        $adminStmt = $this->db->prepare($adminSql);
        $adminStmt->execute($adminParams);
        if ($adminStmt->fetch()) {
            return true;
        }

        if (!$this->usersTableExists()) {
            return false;
        }

        $userParams = [$email];
        $userSql = 'SELECT id FROM users WHERE email = ?';
        if ($source === 'users' && $excludeId > 0) {
            $userSql .= ' AND id <> ?';
            $userParams[] = $excludeId;
        }
        $userSql .= ' LIMIT 1';

        $userStmt = $this->db->prepare($userSql);
        $userStmt->execute($userParams);

        return (bool) $userStmt->fetch();
    }

    public function usernameExistsForOtherAccount(string $username, string $source, int $excludeId): bool
    {
        $username = trim($username);

        if ($username === '' || !$this->usersTableExists()) {
            return false;
        }

        if ($this->adminModel->hasAdminColumn('username')) {
            $adminParams = [$username];
            $adminSql = 'SELECT id FROM admin WHERE username = ?';
            if ($source === 'admin' && $excludeId > 0) {
                $adminSql .= ' AND id <> ?';
                $adminParams[] = $excludeId;
            }
            $adminSql .= ' LIMIT 1';

            $adminStmt = $this->db->prepare($adminSql);
            $adminStmt->execute($adminParams);
            if ($adminStmt->fetch()) {
                return true;
            }
        }

        $userParams = [$username];
        $userSql = 'SELECT id FROM users WHERE username = ?';
        if ($source === 'users' && $excludeId > 0) {
            $userSql .= ' AND id <> ?';
            $userParams[] = $excludeId;
        }
        $userSql .= ' LIMIT 1';

        $userStmt = $this->db->prepare($userSql);
        $userStmt->execute($userParams);

        return (bool) $userStmt->fetch();
    }

    private function findAdminAccountsByIdentifier(string $identifier): array
    {
        $columns = ['id', 'full_name', 'email', 'role', 'password_hash'];
        $conditions = ['email = ?'];
        $params = [$identifier];

        if ($this->adminModel->hasAdminColumn('username')) {
            $columns[] = 'username';
            $conditions[] = 'username = ?';
            $params[] = $identifier;
        }

        $stmt = $this->db->prepare(sprintf(
            'SELECT %s FROM admin WHERE %s',
            implode(', ', $columns),
            implode(' OR ', $conditions)
        ));
        $stmt->execute($params);

        return array_map(fn(array $row) => $this->normalizeAdminAccount($row), $stmt->fetchAll() ?: []);
    }

    private function findAdminAccountsByEmail(string $email): array
    {
        $columns = ['id', 'full_name', 'email', 'role', 'password_hash'];

        if ($this->adminModel->hasAdminColumn('username')) {
            $columns[] = 'username';
        }

        $stmt = $this->db->prepare(sprintf(
            'SELECT %s FROM admin WHERE email = ?',
            implode(', ', $columns)
        ));
        $stmt->execute([$email]);

        return array_map(fn(array $row) => $this->normalizeAdminAccount($row), $stmt->fetchAll() ?: []);
    }

    private function findUserAccountsByIdentifier(string $identifier, bool $activeOnly): array
    {
        if (!$this->usersTableExists()) {
            return [];
        }

        $sql = '
            SELECT id, full_name, username, email, role, status, password
            FROM users
            WHERE (email = ? OR username = ?)
        ';
        $params = [$identifier, $identifier];

        if ($activeOnly && $this->usersTableHasColumn('status')) {
            $sql .= " AND status = 'active'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(fn(array $row) => $this->normalizeUserAccount($row), $stmt->fetchAll() ?: []);
    }

    private function findUserAccountsByEmail(string $email, bool $activeOnly): array
    {
        if (!$this->usersTableExists()) {
            return [];
        }

        $sql = '
            SELECT id, full_name, username, email, role, status, password
            FROM users
            WHERE email = ?
        ';
        $params = [$email];

        if ($activeOnly && $this->usersTableHasColumn('status')) {
            $sql .= " AND status = 'active'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(fn(array $row) => $this->normalizeUserAccount($row), $stmt->fetchAll() ?: []);
    }

    private function normalizeAdminAccount(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'source' => 'admin',
            'full_name' => (string) ($row['full_name'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'role' => $this->normalizeRole((string) ($row['role'] ?? 'admin')),
            'role_value' => $this->normalizeRole((string) ($row['role'] ?? 'admin')),
            'status' => 'active',
            'is_active' => true,
            'password_hash' => (string) ($row['password_hash'] ?? ''),
        ];
    }

    private function normalizeUserAccount(array $row): array
    {
        $status = (string) ($row['status'] ?? 'active');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'source' => 'users',
            'full_name' => (string) ($row['full_name'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'role' => $this->normalizeRole((string) ($row['role'] ?? 'user')),
            'role_value' => $this->normalizeRole((string) ($row['role'] ?? 'user')),
            'status' => $status,
            'is_active' => $status === 'active',
            'password_hash' => (string) ($row['password'] ?? ''),
        ];
    }

    private function normalizeRole(string $role): string
    {
        return strtolower(trim($role)) === 'admin' ? 'admin' : 'user';
    }

    private function usersTableExists(): bool
    {
        $this->loadUsersMetadata();
        return $this->usersTableAvailable === true;
    }

    private function usersTableHasColumn(string $column): bool
    {
        $this->loadUsersMetadata();
        if ($this->usersTableAvailable !== true) {
            return false;
        }

        return in_array($column, $this->usersColumns, true);
    }

    private function loadUsersMetadata(): void
    {
        if ($this->usersColumns !== null) {
            return;
        }

        $stmt = $this->db->prepare('
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = ?
              AND table_name = ?
        ');
        $stmt->execute(['public', 'users']);

        $this->usersColumns = array_map(
            static fn(array $row): string => (string) $row['column_name'],
            $stmt->fetchAll() ?: []
        );
        $this->usersTableAvailable = $this->usersColumns !== [];
    }

    private function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');

        if ($fullName === '') {
            return ['', ''];
        }

        $parts = explode(' ', $fullName, 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
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
}
