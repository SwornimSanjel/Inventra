<div class="auth-wrapper auth-login-wrapper">
    <span class="auth-watermark auth-watermark-top" aria-hidden="true">INV</span>
    <span class="auth-watermark auth-watermark-bottom" aria-hidden="true">ENTRA</span>

    <div class="auth-login-layout">
        <div class="auth-card auth-login-card">
            <div class="auth-brand">
                <div class="auth-brand-lockup">
                    <img
                        class="auth-brand-logo"
                        src="<?= BASE_URL ?>public/images/inventra-logo.png"
                        alt="Inventra logo"
                        width="32"
                        height="32"
                    >
                    <h1>Inventra</h1>
                </div>
                <p class="auth-sub auth-brand-sub">Inventory Management System</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?url=auth/login" class="auth-login-form">
                <div class="form-group">
                    <label for="identifier">Username or Email</label>
                    <input
                        type="text"
                        name="identifier"
                        id="identifier"
                        value="<?= htmlspecialchars($oldIdentifier ?? '') ?>"
                        placeholder="Enter your email"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field input-shell input-shell-right">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg class="icon-eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 3l18 18"></path>
                                <path d="M9.9 4.24A10.94 10.94 0 0112 4c5.52 0 10 3.58 11.67 8a11.83 11.83 0 01-4.39 5.94"></path>
                                <path d="M6.61 6.61A11.84 11.84 0 00.33 12C2 16.42 6.48 20 12 20a11.6 11.6 0 005.39-1.39"></path>
                                <path d="M10.73 10.73a2 2 0 002.54 2.54"></path>
                            </svg>
                            <svg class="icon-eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">Login</button>
            </form>

            <p class="auth-helper-copy">
                <a href="index.php?url=auth/forgot-password" class="auth-helper-link">Forgot your password?</a>
                Contact your administrator.
            </p>
        </div>
    </div>

    <p class="auth-page-footer">&copy; 2026 INVENTRA. INVENTORY MANAGEMENT.</p>
</div>

<script>
document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        var field = button.previousElementSibling;
        var isVisible = field.type === 'text';

        field.type = isVisible ? 'password' : 'text';
        button.classList.toggle('is-visible', !isVisible);
        button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
    });
});
</script>
