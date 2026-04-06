<div class="auth-wrapper">
  <div class="auth-card">
    <h1>Enter OTP</h1>
    <p class="auth-sub">Enter the 6-digit code sent to your registered email.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?url=auth/verify-otp" id="otpForm">
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
      <a href="index.php?url=auth/resend-otp">Resend OTP</a>
      <a href="index.php?url=auth/forgot-password">Back to Login</a>
    </div>
  </div>
</div>

<script>
const otpInputs = document.querySelectorAll('.otp-input');
const verifyBtn = document.getElementById('verifyBtn');
const otpCombined = document.getElementById('otpCombined');
const otpMessage = document.getElementById('otpMessage');
const otpForm = document.getElementById('otpForm');

function updateOtpState() {
  const value = Array.from(otpInputs).map(input => input.value).join('');
  otpCombined.value = value;
  verifyBtn.disabled = value.length !== 6;

  if (value.length < 6) {
    otpMessage.textContent = 'Please enter complete OTP';
  } else {
    otpMessage.textContent = '';
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

otpForm.addEventListener('submit', function(e) {
  if (otpCombined.value.length !== 6) {
    e.preventDefault();
    otpMessage.textContent = 'Please enter complete OTP';
  }
});
</script>