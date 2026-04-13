<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../models/NotificationModel.php';

class NotificationController
{
    private UserModel $userModel;
    private AdminSession $adminSession;
    private NotificationModel $notificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->adminSession = new AdminSession($this->userModel);
        $this->notificationModel = new NotificationModel();
        $this->notificationModel->ensureSchema();
    }

    public function getData(): void
    {
        $admin = $this->adminSession->requireAuthenticatedAdmin();

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
