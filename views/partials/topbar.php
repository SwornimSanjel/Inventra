<?php
$topbarAdmin = $topbarAdmin ?? null;
$topbarNotifications = $topbarNotifications ?? [];
$topbarUnreadCount = $topbarUnreadCount ?? 0;

$topbarName = trim((string) ($topbarAdmin['full_name'] ?? 'Admin User'));
$topbarRole = trim((string) ($topbarAdmin['role'] ?? 'System Admin'));
$topbarAvatar = trim((string) ($topbarAdmin['avatar'] ?? ''));
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

if ($topbarAvatar !== '') {
    $avatarPath = parse_url($topbarAvatar, PHP_URL_PATH);
    $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';

    if (is_string($avatarPath) && $avatarPath !== '' && strpos($avatarPath, $basePath) === 0) {
        $relativeAvatarPath = ltrim(substr($avatarPath, strlen($basePath)), '/');
        $absoluteAvatarPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeAvatarPath);
        $avatarVersion = @filemtime($absoluteAvatarPath);

        if ($avatarVersion) {
            $topbarAvatar .= (strpos($topbarAvatar, '?') === false ? '?' : '&') . 'v=' . $avatarVersion;
        }
    }
}
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
        <?php require __DIR__ . '/notification_popover.php'; ?>

        <a class="topbar-user" href="index.php?url=admin/settings" aria-label="Open profile settings">
            <div>
                <span class="user-name"><?= htmlspecialchars($topbarName) ?></span>
                <span class="user-role"><?= htmlspecialchars($topbarRole !== '' ? $topbarRole : 'Admin') ?></span>
            </div>
            <div class="avatar">
                <?php if ($topbarAvatar !== ''): ?>
                    <img src="<?= htmlspecialchars($topbarAvatar) ?>" alt="<?= htmlspecialchars($topbarName) ?>">
                <?php else: ?>
                    <?= htmlspecialchars($topbarInitials) ?>
                <?php endif; ?>
            </div>
        </a>
    </div>
</header>
