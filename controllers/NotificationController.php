<?php

require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../models/NotificationModel.php';

/**
 * Handles notification read-state APIs for both admin and user panels.
 */
class NotificationController
{
    private AdminSession $session;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        $this->session = new AdminSession();
        $this->notificationModel = new NotificationModel();
    }

    public function getData(string $scope): void
    {
        $account = $this->requireNotificationAccount($scope);
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 10)));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $notifications = $this->notificationModel->buildNotificationViewData(
            $this->notificationModel->getNotificationsForUser(
                (int) $account['id'],
                (string) $account['source'],
                $limit,
                $page
            )
        );
        $total = $this->notificationModel->countNotificationsForUser((int) $account['id'], (string) $account['source']);

        $this->respondJson([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $this->notificationModel->countUnreadForUser((int) $account['id'], (string) $account['source']),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => max(1, (int) ceil($total / $limit)),
                ],
            ],
        ]);
    }

    public function markAsRead(string $scope): void
    {
        $account = $this->requireNotificationAccount($scope);
        $notificationId = (int) ($this->getInputValue('notification_id') ?? 0);

        if ($notificationId <= 0) {
            $this->respondJson([
                'success' => false,
                'message' => 'Invalid notification.',
            ], 422);
        }

        $updated = $this->notificationModel->markNotificationAsReadForUser(
            $notificationId,
            (int) $account['id'],
            (string) $account['source']
        );

        if (!$updated) {
            $this->respondJson([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $this->respondJson([
            'success' => true,
            'message' => 'Notification marked as read.',
            'unread_count' => $this->notificationModel->countUnreadForUser((int) $account['id'], (string) $account['source']),
        ]);
    }

    public function markAllAsRead(string $scope): void
    {
        $account = $this->requireNotificationAccount($scope);

        $this->notificationModel->markAllAsReadForUser((int) $account['id'], (string) $account['source']);

        $this->respondJson([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'unread_count' => 0,
        ]);
    }

    private function requireNotificationAccount(string $scope): array
    {
        $account = $this->session->resolveAuthenticatedAccount();

        if ($account === null) {
            $this->respondJson([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        if ($scope === 'admin' && (($account['role'] ?? 'user') !== 'admin')) {
            $this->respondJson([
                'success' => false,
                'message' => 'You do not have permission to access these notifications.',
            ], 403);
        }

        if ($scope === 'user' && ((($account['role'] ?? 'user') !== 'user') || (($account['source'] ?? '') !== 'users'))) {
            $this->respondJson([
                'success' => false,
                'message' => 'You do not have permission to access these notifications.',
            ], 403);
        }

        return $account;
    }

    private function getInputValue(string $key): mixed
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded) && array_key_exists($key, $decoded)) {
            return $decoded[$key];
        }

        parse_str($raw, $parsed);
        return is_array($parsed) && array_key_exists($key, $parsed) ? $parsed[$key] : null;
    }

    private function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
