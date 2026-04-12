<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Set New Password</h1>
        <p class="auth-sub">Your OTP was verified. Create a new password below.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?url=auth/reset-password">
            <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['verified_reset_email'] ?? '') ?>">
            <input type="hidden" name="reset_token" value="<?= htmlspecialchars($_SESSION['reset_token'] ?? '') ?>">

            <div class="form-group">
                <label>New Password</label>
                <div class="password-field">
                    <input type="password" name="password" id="password" placeholder="Enter new password" required>
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
                <div id="passwordMessage" class="field-message"></div>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <div class="password-field">
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" required>
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

            <button type="submit" class="btn-primary btn-full" id="updatePasswordBtn" disabled>Update Password</button>
        </form>

        <div class="auth-links single-link">
            <a href="index.php?url=login">Back to Login</a>
        </div>
    </div>
</div>

<script>
var resetForm = document.querySelector('form[action="index.php?url=auth/reset-password"]');
var passwordInput = document.getElementById('password');
var confirmPasswordInput = document.getElementById('confirmPassword');
var passwordMessage = document.getElementById('passwordMessage');
var confirmPasswordMessage = document.getElementById('confirmPasswordMessage');
var updatePasswordBtn = document.getElementById('updatePasswordBtn');

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

function validateResetPasswordForm() {
    var passwordError = validatePasswordValue(passwordInput.value);
    var confirmError = '';

    if (!confirmPasswordInput.value) {
        confirmError = 'Password is required';
    } else if (passwordInput.value !== confirmPasswordInput.value) {
        confirmError = 'Passwords do not match';
    }

    passwordMessage.textContent = passwordError;
    confirmPasswordMessage.textContent = confirmError;
    updatePasswordBtn.disabled = passwordError !== '' || confirmError !== '';
}

passwordInput.addEventListener('input', validateResetPasswordForm);
confirmPasswordInput.addEventListener('input', validateResetPasswordForm);

resetForm.addEventListener('submit', function (event) {
    validateResetPasswordForm();

    if (updatePasswordBtn.disabled) {
        event.preventDefault();
    }
});

validateResetPasswordForm();
</script>
