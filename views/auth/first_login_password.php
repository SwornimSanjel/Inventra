<?php require_once __DIR__ . '/../../helpers/protected_view.php'; inventra_guard_protected_view('auth/first-login-password', 'any'); ?>
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

            <h2 class="auth-flow-title">Change Default Password</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?url=auth/first-login-password" class="auth-login-form" id="firstLoginPasswordForm">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="newPassword" placeholder="Enter new password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" aria-label="Show new password">
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
                    <div id="newPasswordMessage" class="field-message"></div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" aria-label="Show confirm password">
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
                    <div id="confirmPasswordMessage" class="field-message"></div>
                </div>

                <button type="submit" class="btn-primary btn-full" id="changeDefaultPasswordBtn" disabled>Update Password</button>
            </form>

            <a href="index.php?url=auth/logout" class="btn-primary btn-full auth-back-login-btn">Back to Login</a>
        </div>

        <p class="auth-page-footer">&copy; 2026 INVENTRA. INVENTORY MANAGEMENT.</p>
    </div>
</div>

<script>
var firstLoginPasswordForm = document.getElementById('firstLoginPasswordForm');
var newPasswordInput = document.getElementById('newPassword');
var confirmPasswordInput = document.getElementById('confirmPassword');
var newPasswordMessage = document.getElementById('newPasswordMessage');
var confirmPasswordMessage = document.getElementById('confirmPasswordMessage');
var changeDefaultPasswordBtn = document.getElementById('changeDefaultPasswordBtn');

document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        var field = button.previousElementSibling;
        var isVisible = field.type === 'text';

        field.type = isVisible ? 'password' : 'text';
        button.classList.toggle('is-visible', !isVisible);
        button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
    });
});

function validatePasswordValue(value) {
    if (!value) {
        return 'Password is required';
    }

    if (value.length < 8 || !/[!@#]/.test(value)) {
        return 'Use at least 8 characters and at least one of !, @, or #';
    }

    return '';
}

function validateFirstLoginPasswordForm() {
    var newPasswordError = validatePasswordValue(newPasswordInput.value);
    var confirmPasswordError = '';

    if (!confirmPasswordInput.value) {
        confirmPasswordError = 'Password is required';
    } else if (newPasswordInput.value !== confirmPasswordInput.value) {
        confirmPasswordError = 'Passwords do not match';
    }

    newPasswordMessage.textContent = newPasswordError;
    confirmPasswordMessage.textContent = confirmPasswordError;
    changeDefaultPasswordBtn.disabled = newPasswordError !== '' || confirmPasswordError !== '';
}

newPasswordInput.addEventListener('input', validateFirstLoginPasswordForm);
confirmPasswordInput.addEventListener('input', validateFirstLoginPasswordForm);

firstLoginPasswordForm.addEventListener('submit', function (event) {
    validateFirstLoginPasswordForm();

    if (changeDefaultPasswordBtn.disabled) {
        event.preventDefault();
    }
});

validateFirstLoginPasswordForm();
</script>
