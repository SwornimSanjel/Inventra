<?php
$accountName = trim((string) ($account['full_name'] ?? inventra_authenticated_user_name() ?? 'Account User'));
$error = $error ?? '';
$success = $success ?? '';
?>

<div class="auth-flow-shell">
    <div class="auth-flow-card">
        <div class="auth-brand-wrap">
            <div class="auth-logo">IN</div>
            <div>
                <p class="auth-brand-name">Inventra</p>
                <p class="auth-brand-sub">Account Access</p>
            </div>
        </div>

        <div class="auth-flow-head">
            <h1>Signed in successfully</h1>
            <p class="auth-sub auth-flow-sub">
                <?= htmlspecialchars($accountName) ?> is logged in as a user account. The user panel is not built yet, so admin pages remain unavailable.
            </p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="auth-alert auth-alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="auth-actions-stack">
            <a class="auth-login-btn" href="index.php?url=account/password">Change Password</a>
            <a class="auth-helper-link" href="index.php?url=auth/logout">Log out</a>
        </div>
    </div>
</div>
