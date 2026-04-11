<?php

require_once __DIR__ . '/../config/database.php';

class NotificationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                message VARCHAR(255) NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notifications_user_id_created_at (user_id, created_at),
                INDEX idx_notifications_user_id_is_read (user_id, is_read),
                CONSTRAINT fk_notifications_admin
                    FOREIGN KEY (user_id) REFERENCES admin(id)
                    ON DELETE CASCADE
            )
        ");
    }

    public function getNotificationsForUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, $limit);

        $stmt = $this->db->prepare("
            SELECT id, user_id, type, message, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchAll() ?: [];
    }

    public function countUnreadForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    public function markAllAsReadForUser(int $userId): int
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);

        return $stmt->rowCount();
    }

    public function createNotification(int $userId, string $type, string $message, bool $isRead = false, ?string $createdAt = null): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO notifications (user_id, type, message, is_read, created_at)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId,
            $type,
            $message,
            $isRead ? 1 : 0,
            $createdAt ?: date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function buildNotificationViewData(array $rows): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            $parts = $this->splitMessage((string) $row['message']);
            $mapped[] = [
                'id' => (int) $row['id'],
                'type' => (string) $row['type'],
                'message' => (string) $row['message'],
                'title' => $parts['title'],
                'body' => $parts['body'],
                'is_read' => (bool) $row['is_read'],
                'created_at' => (string) $row['created_at'],
                'relative_time' => $this->formatRelativeTime((string) $row['created_at']),
                'icon' => $this->iconForType((string) $row['type']),
            ];
        }

        return $mapped;
    }

    private function formatRelativeTime(string $createdAt): string
    {
        $timestamp = strtotime($createdAt);

        if ($timestamp === false) {
            return 'Just now';
        }

        $diff = max(0, time() - $timestamp);

        if ($diff < 60) {
            return 'Just now';
        }

        $minutes = (int) floor($diff / 60);
        if ($minutes < 60) {
            return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
        }

        $hours = (int) floor($minutes / 60);
        if ($hours < 24) {
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        $days = (int) floor($hours / 24);
        if ($days === 1) {
            return 'Yesterday at ' . date('g:i A', $timestamp);
        }

        if ($days < 7) {
            return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
        }

        $weeks = (int) floor($days / 7);
        if ($weeks < 5) {
            return $weeks . ' week' . ($weeks === 1 ? '' : 's') . ' ago';
        }

        return date('M j, Y', $timestamp);
    }

    private function iconForType(string $type): array
    {
        $map = [
            'low_stock' => ['variant' => 'warning', 'symbol' => 'warning'],
            'request_approved' => ['variant' => 'success', 'symbol' => 'check'],
            'new_user' => ['variant' => 'user', 'symbol' => 'user'],
            'system_update' => ['variant' => 'settings', 'symbol' => 'settings'],
            'security_alert' => ['variant' => 'shield', 'symbol' => 'shield'],
        ];

        return $map[$type] ?? ['variant' => 'info', 'symbol' => 'settings'];
    }

    private function splitMessage(string $message): array
    {
        $parts = explode(':', $message, 2);

        if (count($parts) === 2) {
            return [
                'title' => trim($parts[0]) . ':',
                'body' => trim($parts[1]),
            ];
        }

        return [
            'title' => $message,
            'body' => '',
        ];
    }
}
