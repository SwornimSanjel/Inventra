<?php
$topbarAdmin = $topbarAdmin ?? null;
$topbarNotifications = $topbarNotifications ?? [];
$topbarUnreadCount = $topbarUnreadCount ?? 0;

$topbarName = trim((string) ($topbarAdmin['full_name'] ?? 'Admin User'));
$topbarRole = trim((string) ($topbarAdmin['role'] ?? 'System Admin'));
$topbarInitials = '';

foreach (array_slice(array_values(array_filter(explode(' ', $topbarName))), 0, 2) as $part) {
    $topbarInitials .= strtoupper(substr($part, 0, 1));
}

if ($topbarInitials === '') {
    $topbarInitials = 'AD';
}

if ($topbarRole !== '') {
    $topbarRole = ucwords(str_replace(['_', '-'], ' ', strtolower($topbarRole)));
}

$notifIcons = [
    'warning' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path></svg>',
    'check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"></path></svg>',
    'user' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"></path><circle cx="12" cy="8" r="4"></circle></svg>',
    'settings' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
    'shield' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
];
?>

<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
    </div>

    <div class="topbar-center">
        <div class="search-box">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="globalSearch" placeholder="Search anything..." autocomplete="off">
        </div>
    </div>

    <div class="topbar-right">
        <div class="notif-wrap">
            <button
                class="notif-btn"
                id="notifBtn"
                title="Notifications"
                type="button"
                aria-haspopup="true"
                aria-expanded="false"
                aria-controls="notifDropdown"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="notif-dot <?= $topbarUnreadCount > 0 ? '' : 'is-hidden' ?>" id="notifDot"></span>
            </button>

            <div class="notif-dropdown notif-popover" id="notifDropdown" data-mark-read-url="index.php?url=admin/notifications/mark-all-read">
                <div class="notif-popover__header">
                    <h3 class="notif-popover__title">Notifications</h3>
                    <button class="notif-popover__mark-all" type="button" id="notifMarkAll" <?= empty($topbarNotifications) || $topbarUnreadCount === 0 ? 'disabled' : '' ?>>Mark all as read</button>
                </div>

                <?php if (!empty($topbarNotifications)): ?>
                    <div class="notif-popover__list" id="notifList">
                        <?php foreach ($topbarNotifications as $notification): ?>
                            <article class="notif-popover__item <?= !empty($notification['is_read']) ? 'is-read' : 'is-unread' ?>" data-notification-id="<?= (int) $notification['id'] ?>">
                                <div class="notif-popover__icon <?= htmlspecialchars((string) ($notification['icon']['variant'] ?? 'info')) ?>">
                                    <?= $notifIcons[$notification['icon']['symbol'] ?? 'settings'] ?? $notifIcons['settings'] ?>
                                </div>
                                <div class="notif-popover__content">
                                    <p class="notif-popover__message">
                                        <span class="notif-popover__message-title"><?= htmlspecialchars((string) ($notification['title'] ?? $notification['message'])) ?></span>
                                        <?php if (!empty($notification['body'])): ?>
                                            <span class="notif-popover__message-body"><?= htmlspecialchars((string) $notification['body']) ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <span class="notif-popover__time"><?= htmlspecialchars((string) $notification['relative_time']) ?></span>
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

        <div class="topbar-user">
            <div>
                <span class="user-name"><?= htmlspecialchars($topbarName) ?></span>
                <span class="user-role"><?= htmlspecialchars($topbarRole !== '' ? $topbarRole : 'Admin') ?></span>
            </div>
            <div class="avatar">
                <?= htmlspecialchars($topbarInitials) ?>
            </div>
        </div>
    </div>
</header>
