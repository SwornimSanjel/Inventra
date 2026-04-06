<div class="auth-wrapper">
  <div class="auth-card">
    <h1>OTP Verified</h1>
    <p class="auth-sub">OTP has been verified successfully. You can now proceed to reset the password.</p>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="auth-links single-link">
      <a href="index.php?url=auth/forgot-password">Back to Login</a>
    </div>
  </div>
</div>