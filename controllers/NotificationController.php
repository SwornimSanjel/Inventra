<?php

require_once __DIR__ . '/../models/AccountModel.php';
require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../models/NotificationModel.php';

class NotificationController
{
    private AccountModel $accountModel;
    private AdminSession $adminSession;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        $this->accountModel = new AccountModel();
        $this->adminSession = new AdminSession($this->accountModel);
        $this->notificationModel = new NotificationModel();
    }

    public function getData(): void
    {
        $admin = $this->adminSession->requireAuthenticatedAdmin();

        if (($admin['source'] ?? 'admin') !== 'admin') {
            $this->respondJson([
                'success' => true,
                'data' => [
                    'notifications' => [],
                    'unread_count' => 0,
                ],
            ]);
        }

        $notifications = $this->notificationModel->buildNotificationViewData(
            $this->notificationModel->getNotificationsForUser((int) $admin['id'])
        );

        $this->respondJson([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $this->notificationModel->countUnreadForUser((int) $admin['id']),
            ],
        ]);
    }

    public function markAllAsRead(): void
    {
        $admin = $this->adminSession->requireAuthenticatedAdmin();

        if (($admin['source'] ?? 'admin') !== 'admin') {
            $this->respondJson([
                'success' => true,
                'message' => 'No notifications available for this account.',
                'unread_count' => 0,
            ]);
        }

        $this->notificationModel->markAllAsReadForUser((int) $admin['id']);

        $this->respondJson([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'unread_count' => 0,
        ]);
    }

    private function respondJson(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
