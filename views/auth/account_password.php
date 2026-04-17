<?php
$error = $error ?? '';
$success = $success ?? '';
?>

<div class="auth-flow-shell">
    <div class="auth-flow-card">
        <div class="auth-brand-wrap">
            <div class="auth-logo">IN</div>
            <div>
                <p class="auth-brand-name">Inventra</p>
                <p class="auth-brand-sub">Account Security</p>
            </div>
        </div>

        <div class="auth-flow-head">
            <h1>Change your password</h1>
            <p class="auth-sub auth-flow-sub">Update your own password for this account.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="auth-alert auth-alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?url=account/password" class="auth-login-form">
            <div class="field">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Enter current password" required>
            </div>

            <div class="field">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Use at least 8 chars and one of !, @, #" required>
            </div>

            <div class="field">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            </div>

            <button type="submit" class="auth-login-btn">Update Password</button>
        </form>

        <div class="auth-flow-links">
            <a href="index.php?url=account" class="auth-helper-link">Back to account</a>
            <a href="index.php?url=auth/logout" class="auth-helper-link">Log out</a>
        </div>
    </div>
</div>
