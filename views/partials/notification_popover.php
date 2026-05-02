<?php
$topbarNotifications = $topbarNotifications ?? [];
$topbarUnreadCount = (int) ($topbarUnreadCount ?? 0);
$topbarNotificationBaseUrl = trim((string) ($topbarNotificationBaseUrl ?? 'index.php?url=admin/notifications'));

$notifIcons = [
    'warning' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path></svg>',
    'check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"></path></svg>',
    'user' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="8" r="4"></circle></svg>',
    'settings' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
    'shield' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
];
?>

<div class="notif-wrap">
    <button
        class="notif-btn"
        id="notifBtn"
        title="Notifications"
        type="button"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="notifDropdown"
        aria-label="<?= $topbarUnreadCount > 0 ? htmlspecialchars((string) $topbarUnreadCount) . ' unread notifications' : 'Notifications' ?>"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span class="notif-dot <?= $topbarUnreadCount > 0 ? '' : 'is-hidden' ?>" id="notifDot"></span>
    </button>

    <div
        class="notif-dropdown notif-popover"
        id="notifDropdown"
        data-fetch-url="<?= htmlspecialchars($topbarNotificationBaseUrl . '/data') ?>"
        data-mark-read-url="<?= htmlspecialchars($topbarNotificationBaseUrl . '/mark-all-read') ?>"
        data-mark-one-url="<?= htmlspecialchars($topbarNotificationBaseUrl . '/mark-read') ?>"
    >
        <div class="notif-popover__header">
            <h3 class="notif-popover__title">Notifications</h3>
            <button class="notif-popover__mark-all" type="button" id="notifMarkAll" <?= $topbarUnreadCount === 0 ? 'disabled' : '' ?>>Mark all as read</button>
        </div>

        <?php if (!empty($topbarNotifications)): ?>
            <div class="notif-popover__list" id="notifList">
                <?php foreach ($topbarNotifications as $notification): ?>
                    <?php
                    $body = trim((string) ($notification['body'] ?? $notification['message'] ?? ''));
                    $title = trim((string) ($notification['title'] ?? 'Notification'));
                    $titleLabel = $body !== '' && substr($title, -1) !== ':' ? $title . ':' : $title;
                    $isRead = !empty($notification['is_read']);
                    ?>
                    <article
                        class="notif-popover__item <?= $isRead ? 'is-read' : 'is-unread is-clickable' ?>"
                        data-notification-id="<?= (int) $notification['id'] ?>"
                        <?= $isRead ? '' : 'tabindex="0" role="button" aria-label="Mark notification as read"' ?>
                    >
                        <div class="notif-popover__icon <?= htmlspecialchars((string) ($notification['icon']['variant'] ?? 'info')) ?>">
                            <?= $notifIcons[$notification['icon']['symbol'] ?? 'settings'] ?? $notifIcons['settings'] ?>
                        </div>
                        <div class="notif-popover__content">
                            <p class="notif-popover__message">
                                <span class="notif-popover__message-title"><?= htmlspecialchars($titleLabel) ?></span>
                                <?php if ($body !== ''): ?>
                                    <span class="notif-popover__message-body"><?= htmlspecialchars($body) ?></span>
                                <?php endif; ?>
                            </p>
                            <span class="notif-popover__time"><?= htmlspecialchars((string) ($notification['relative_time'] ?? 'Just now')) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="notif-popover__empty" id="notifEmptyState">
                <div class="notif-popover__empty-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>
                <p class="notif-popover__empty-title">No notifications yet</p>
                <p class="notif-popover__empty-copy">New alerts and system activity will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
