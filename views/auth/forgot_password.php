<div class="auth-wrapper auth-login-wrapper">
    <span class="auth-watermark auth-watermark-top" aria-hidden="true">INV</span>
    <span class="auth-watermark auth-watermark-bottom" aria-hidden="true">ENTRA</span>

    <div class="auth-login-layout">
        <div class="auth-card auth-flow-card">
            <div class="auth-brand auth-brand-compact">
                <div class="auth-brand-lockup">
                    <img class="auth-brand-logo" src="<?= BASE_URL ?>public/images/inventra-logo.png" alt="Inventra logo" width="32" height="32">
                    <h1>Inventra</h1>
                </div>
                <p class="auth-sub auth-brand-sub">Inventory Management System</p>
            </div>

            <h2 class="auth-flow-title">Forgot Password</h2>
            <p class="auth-sub auth-flow-sub">Enter your registered email to receive a 6-digit OTP.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?url=auth/send-otp" class="auth-login-form">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>

                <button type="submit" class="btn-primary btn-full">Send OTP</button>
            </form>

            <div class="auth-links single-link">
                <a href="index.php?url=login">Back to Login</a>
            </div>
        </div>

        <p class="auth-page-footer">&copy; 2026 INVENTRA. INVENTORY MANAGEMENT.</p>
    </div>
</div>
