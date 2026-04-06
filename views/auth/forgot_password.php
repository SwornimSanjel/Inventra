<div class="auth-wrapper">
  <div class="auth-card">
    <h1>Forgot Password</h1>
    <p class="auth-sub">Enter your registered email to receive a 6-digit OTP.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php?url=auth/send-otp">
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
</div>