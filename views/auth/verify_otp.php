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

            <h2 class="auth-flow-title">Enter OTP</h2>
            <p class="auth-sub auth-flow-sub">Enter the 6-digit code sent to your registered email.</p>
            <p id="otpTimer" class="otp-message"></p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?url=auth/verify-otp" id="otpForm" class="auth-login-form">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email ?? ($_SESSION['reset_email'] ?? '')) ?>">
                <input type="hidden" name="otp" id="otpCombined">

                <div class="otp-group">
                    <input type="text" maxlength="1" class="otp-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-input" inputmode="numeric">
                    <input type="text" maxlength="1" class="otp-input" inputmode="numeric">
                </div>

                <div id="otpMessage" class="otp-message"></div>

                <button type="submit" class="btn-primary btn-full" id="verifyBtn" disabled>Verify OTP</button>
            </form>

            <div class="auth-links">
                <a href="index.php?url=auth/resend-otp" id="resendOtpLink">Resend OTP</a>
                <a href="index.php?url=login">Back to Login</a>
            </div>
        </div>

        <p class="auth-page-footer">&copy; 2026 INVENTRA. INVENTORY MANAGEMENT.</p>
    </div>
</div>

<script>
const otpInputs = document.querySelectorAll('.otp-input');
const verifyBtn = document.getElementById('verifyBtn');
const otpCombined = document.getElementById('otpCombined');
const otpMessage = document.getElementById('otpMessage');
const otpForm = document.getElementById('otpForm');
const otpTimer = document.getElementById('otpTimer');
const resendOtpLink = document.getElementById('resendOtpLink');
const otpExpiresAtTs = <?= json_encode($otpExpiresAtTs ?? null) ?>;
let otpCountdownInterval = null;

function setExpiredState(isExpired) {
    otpMessage.dataset.expired = isExpired ? 'true' : 'false';
    verifyBtn.disabled = isExpired || otpCombined.value.length !== 6;
    resendOtpLink.style.pointerEvents = 'auto';
    resendOtpLink.style.opacity = '1';
    resendOtpLink.setAttribute('aria-disabled', 'false');
}

function updateOtpState() {
    const value = Array.from(otpInputs).map(input => input.value).join('');
    otpCombined.value = value;

    if (value.length < 6) {
        verifyBtn.disabled = true;
        if (otpMessage.dataset.expired !== 'true') {
            otpMessage.textContent = 'Please enter complete OTP';
        }
    } else {
        if (otpMessage.dataset.expired !== 'true') {
            otpMessage.textContent = '';
        }
        verifyBtn.disabled = otpMessage.dataset.expired === 'true';
    }
}

otpInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
        }
        updateOtpState();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && index > 0) {
            otpInputs[index - 1].focus();
        }
    });
});

otpForm.addEventListener('submit', function (e) {
    if (otpMessage.dataset.expired === 'true') {
        e.preventDefault();
        otpMessage.textContent = 'OTP expired. Please resend OTP.';
        return;
    }

    if (otpCombined.value.length !== 6) {
        e.preventDefault();
        otpMessage.textContent = 'Please enter complete OTP';
    }
});

function startOtpCountdown() {
    if (!otpExpiresAtTs) {
        otpTimer.textContent = '';
        setExpiredState(false);
        return;
    }

    const expiryTime = otpExpiresAtTs * 1000;

    function updateTimer() {
        const now = Date.now();
        const distance = expiryTime - now;

        if (distance <= 0) {
            if (otpCountdownInterval) {
                clearInterval(otpCountdownInterval);
            }
            otpTimer.textContent = 'OTP expired. Please resend OTP.';
            otpMessage.textContent = '';
            setExpiredState(true);
            return;
        }

        setExpiredState(false);

        const minutes = Math.floor(distance / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        otpTimer.textContent = 'OTP expires in ' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
    }

    updateTimer();
    otpCountdownInterval = setInterval(updateTimer, 1000);
}

startOtpCountdown();
updateOtpState();
</script>
