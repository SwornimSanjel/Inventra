<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Log In</h1>
        <p class="auth-sub">Sign in to continue to your Inventra admin workspace.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?url=auth/login">
            <div class="form-group">
                <label>Email Address</label>
                <input
                    type="text"
                    name="identifier"
                    value="<?= htmlspecialchars($oldIdentifier ?? '') ?>"
                    placeholder="Enter your email"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-field">
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

            <button type="submit" class="btn-primary btn-full">Log In</button>
        </form>

        <div class="auth-links single-link">
            <a href="index.php?url=auth/forgot-password">Forgot your password?</a>
        </div>
    </div>
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
