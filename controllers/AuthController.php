<?php

require_once __DIR__ . '/../models/AccountModel.php';
require_once __DIR__ . '/../models/OTPModel.php';
require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../helpers/session.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class AuthController
{
    private AccountModel $accountModel;
    private OTPModel $otpModel;
    private AdminSession $adminSession;

    public function __construct()
    {
        $this->accountModel = new AccountModel();
        $this->otpModel = new OTPModel();
        $this->adminSession = new AdminSession($this->accountModel);
    }

    public function showForgotPassword(): void
    {
        $error = $_SESSION['auth_error'] ?? '';
        $success = $_SESSION['auth_success'] ?? '';
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        $view = 'forgot_password';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function showLogin(): void
    {
        inventra_auth_debug_log('show_login:start', [
            'is_authenticated' => inventra_is_authenticated(),
        ]);

        if (inventra_is_authenticated()) {
            $this->adminSession->resolveAuthenticatedAccount();
            inventra_auth_debug_log('show_login:redirect_dashboard');
            header('Location: index.php?url=' . inventra_default_authenticated_url());
            exit;
        }

        $error = $_SESSION['auth_error'] ?? '';
        $success = $_SESSION['auth_success'] ?? '';
        $oldIdentifier = $_SESSION['auth_old']['identifier'] ?? '';
        $loggedOut = isset($_GET['logout']);
        unset($_SESSION['auth_error'], $_SESSION['auth_success'], $_SESSION['auth_old']);

        if (isset($_GET['error']) && $error === '') {
            if ($_GET['error'] === 'session_expired') {
                $error = 'Your session has expired. Please log in again.';
            } elseif ($_GET['error'] === 'unauthorized') {
                $error = 'You do not have permission to access that page.';
            }
        }

        if ($loggedOut && $success === '') {
            $success = 'You have been logged out successfully.';
        }

        $view = 'login';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function login(): void
    {
        $identifier = trim((string) ($_POST['identifier'] ?? $_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        inventra_auth_debug_log('login:attempt', [
            'identifier' => $identifier,
            'session_before_regenerate' => session_id(),
        ]);

        $_SESSION['auth_old'] = ['identifier' => $identifier];

        if ($identifier === '' || $password === '') {
            inventra_auth_debug_log('login:missing_credentials');
            $_SESSION['auth_error'] = 'Please enter your email and password.';
            header('Location: index.php?url=login');
            exit;
        }

        $matchingAccounts = $this->accountModel->findAccountsByIdentifier($identifier, true);

        if (count($matchingAccounts) > 1) {
            $_SESSION['auth_error'] = 'Multiple accounts match that login. Please contact an administrator.';
            header('Location: index.php?url=login');
            exit;
        }

        $user = $matchingAccounts[0] ?? null;

        if (
            !$user ||
            empty($user['password_hash']) ||
            empty($user['is_active']) ||
            !password_verify($password, (string) $user['password_hash'])
        ) {
            inventra_auth_debug_log('login:invalid_credentials', [
                'user_found' => (bool) $user,
            ]);
            $_SESSION['auth_error'] = 'Invalid email or password. Please try again.';
            header('Location: index.php?url=login');
            exit;
        }

        session_regenerate_id(true);
        inventra_set_authenticated_user($user);

        inventra_auth_debug_log('login:success', [
            'account_id' => (int) $user['id'],
            'source' => (string) ($user['source'] ?? ''),
            'role' => (string) ($user['role'] ?? ''),
            'email' => (string) $user['email'],
            'session_after_regenerate' => session_id(),
            'is_authenticated' => inventra_is_authenticated(),
        ]);

        unset($_SESSION['auth_error'], $_SESSION['auth_success'], $_SESSION['auth_old']);

        header('Location: index.php?url=' . inventra_default_authenticated_url());
        exit;
    }

    public function sendOtp(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($email === '') {
            $_SESSION['auth_error'] = 'Please enter your email.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $matchingAccounts = $this->accountModel->findAccountsByEmail($email, true);

        if (count($matchingAccounts) > 1) {
            $_SESSION['auth_error'] = 'Multiple accounts use this email. Please contact an administrator.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $user = $matchingAccounts[0] ?? null;

        if (!$user) {
            $_SESSION['auth_error'] = 'No account found with this email.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $accountEmail = trim((string) ($user['email'] ?? ''));
        $accountName = trim((string) ($user['full_name'] ?? ''));

        if ($accountEmail === '') {
            $_SESSION['auth_error'] = 'This account does not have a valid email address saved.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $this->otpModel->createOTP((int) $user['id'], $accountEmail, $otp, $expiresAt);
        try {
            $this->sendOtpEmail($accountEmail, $accountName, $otp);
        } catch (Throwable $e) {
            $_SESSION['auth_error'] = 'Unable to send OTP email right now. Please try again.';
            error_log('OTP email failed for ' . $accountEmail . ': ' . $e->getMessage());
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $_SESSION['reset_email'] = $accountEmail;
        $_SESSION['otp_expires_at'] = $expiresAt;
        $_SESSION['auth_success'] = 'OTP sent successfully.';
        header('Location: index.php?url=auth/verify-otp');
        exit;
    }

    public function showVerifyOtp(): void
    {
        $email = $_SESSION['reset_email'] ?? '';
        $otpExpiresAt = $_SESSION['otp_expires_at'] ?? '';
        $otpExpiresAtTs = null;
        $error = $_SESSION['auth_error'] ?? '';
        $success = $_SESSION['auth_success'] ?? '';

        if ($email !== '') {
            $record = $this->otpModel->findLatestActiveOTPByEmail($email) ?? $this->otpModel->findLatestByEmail($email);
            if ($record && !empty($record['expires_at'])) {
                $otpExpiresAt = $record['expires_at'];
                $_SESSION['otp_expires_at'] = $otpExpiresAt;
            }
        }

        if ($otpExpiresAt !== '') {
            $timestamp = strtotime($otpExpiresAt);
            if ($timestamp !== false) {
                $otpExpiresAtTs = $timestamp;
            }
        }

        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        $view = 'verify_otp';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function verifyOtp(): void
    {
        $email = trim($_POST['email'] ?? ($_SESSION['reset_email'] ?? ''));
        $otp = trim($_POST['otp'] ?? '');

        if ($email === '') {
            $_SESSION['auth_error'] = 'Email missing. Please start again.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $_SESSION['auth_error'] = 'Please enter complete OTP';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        $record = $this->otpModel->findLatestActiveOTPByEmail($email);

        if (!$record) {
            $_SESSION['auth_error'] = 'No valid OTP found. Please request a new OTP.';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        if ((int) $record['attempts'] >= 5) {
            $_SESSION['auth_error'] = 'Too many attempts. Please resend OTP.';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        if ($record['otp_code'] !== $otp) {
            $this->otpModel->incrementAttempts((int) $record['id']);
            $_SESSION['auth_error'] = 'Invalid OTP. Please try again.';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        $resetToken = bin2hex(random_bytes(32));
        $this->otpModel->markVerified((int) $record['id'], $resetToken);

        unset($_SESSION['otp_expires_at']);

        $_SESSION['verified_reset_email'] = $email;
        $_SESSION['reset_token'] = $resetToken;
        header('Location: index.php?url=auth/reset-password');
        exit;
    }

    public function resendOtp(): void
    {
        $email = $_SESSION['reset_email'] ?? '';

        if ($email === '') {
            $_SESSION['auth_error'] = 'Email session missing. Please start again.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $matchingAccounts = $this->accountModel->findAccountsByEmail($email, true);

        if (count($matchingAccounts) > 1) {
            $_SESSION['auth_error'] = 'Multiple accounts use this email. Please contact an administrator.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $user = $matchingAccounts[0] ?? null;

        if (!$user) {
            $_SESSION['auth_error'] = 'No account found with this email.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $accountEmail = trim((string) ($user['email'] ?? ''));
        $accountName = trim((string) ($user['full_name'] ?? ''));

        if ($accountEmail === '') {
            $_SESSION['auth_error'] = 'This account does not have a valid email address saved.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $this->otpModel->createOTP((int) $user['id'], $accountEmail, $otp, $expiresAt);
        try {
            $this->sendOtpEmail($accountEmail, $accountName, $otp);
        } catch (Throwable $e) {
            $_SESSION['auth_error'] = 'Unable to resend OTP email right now. Please try again.';
            error_log('OTP resend email failed for ' . $accountEmail . ': ' . $e->getMessage());
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        $_SESSION['reset_email'] = $accountEmail;
        $_SESSION['otp_expires_at'] = $expiresAt;
        $_SESSION['auth_success'] = 'OTP resent successfully.';
        header('Location: index.php?url=auth/verify-otp');
        exit;
    }

    public function resetPassword(): void
    {
        $verifiedEmail = trim($_POST['email'] ?? ($_SESSION['verified_reset_email'] ?? ''));
        $resetToken = trim($_POST['reset_token'] ?? ($_SESSION['reset_token'] ?? ''));

        if ($verifiedEmail === '' && $resetToken === '') {
            $_SESSION['auth_error'] = 'Unauthorized or invalid request.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $resetRecord = null;

        if ($resetToken !== '') {
            $resetRecord = $this->otpModel->findVerifiedByToken($resetToken);
        }

        if (!$resetRecord) {
            $_SESSION['auth_error'] = 'Unauthorized or invalid request.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $verifiedEmail = $resetRecord['email'];
        $_SESSION['verified_reset_email'] = $verifiedEmail;
        $_SESSION['reset_token'] = $resetRecord['reset_token'] ?? $resetToken;

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($password === '') {
                $error = 'Password is required.';
            } elseif (!$this->isValidPassword($password)) {
                $error = 'Password must be at least 8 characters and include at least one of !, @, or #.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                $matchingAccounts = $this->accountModel->findAccountsByEmail($verifiedEmail, true);

                if (count($matchingAccounts) > 1) {
                    $error = 'Multiple accounts use this email. Please contact an administrator.';
                } elseif (!empty($matchingAccounts[0]) && $this->accountModel->updatePassword($matchingAccounts[0], $hash)) {
                    $account = $matchingAccounts[0];
                    $accountEmail = trim((string) ($account['email'] ?? $verifiedEmail));
                    $accountName = trim((string) ($account['full_name'] ?? ''));

                    $this->logPasswordReset($verifiedEmail);
                    $this->sendPasswordChangedEmail($accountEmail, $accountName);

                    unset(
                        $_SESSION['reset_email'],
                        $_SESSION['verified_reset_email'],
                        $_SESSION['otp_expires_at'],
                        $_SESSION['reset_token'],
                        $_SESSION['auth_error'],
                        $_SESSION['auth_success']
                    );

                    header('Location: index.php?url=auth/password-updated');
                    exit;
                } else {
                    $error = 'Unable to update password. Please try again.';
                }
            }
        } else {
            $error = $_SESSION['auth_error'] ?? '';
            $success = $_SESSION['auth_success'] ?? '';
            unset($_SESSION['auth_error'], $_SESSION['auth_success']);
        }

        $view = 'reset_password';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function showPasswordUpdated(): void
    {
        $view = 'password_updated';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function showAccountHome(): void
    {
        $account = $this->adminSession->requireAuthenticatedAccount();

        if (($account['role'] ?? 'user') === 'admin') {
            header('Location: index.php?url=admin/dashboard');
            exit;
        }

        header('Location: index.php?url=user/settings');
        exit;
    }

    public function changeOwnPassword(): void
    {
        $account = $this->adminSession->requireAuthenticatedAccount();

        if (($account['role'] ?? 'user') === 'admin') {
            header('Location: index.php?url=admin/settings');
            exit;
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($currentPassword === '') {
                $error = 'Current password is required.';
            } elseif (!$this->accountModel->verifyPassword($account, $currentPassword)) {
                $error = 'Current password is incorrect.';
            } elseif ($password === '') {
                $error = 'Password is required.';
            } elseif (!$this->isValidPassword($password)) {
                $error = 'Password must be at least 8 characters and include at least one of !, @, or #.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                if ($this->accountModel->updatePassword($account, $hash)) {
                    $this->sendPasswordChangedEmail((string) $account['email'], (string) ($account['full_name'] ?? ''));
                    $_SESSION['auth_success'] = 'Password updated successfully.';
                    header('Location: index.php?url=account/password');
                    exit;
                }

                $error = 'Unable to update password. Please try again.';
            }
        } else {
            $error = $_SESSION['auth_error'] ?? '';
            $success = $_SESSION['auth_success'] ?? '';
            unset($_SESSION['auth_error'], $_SESSION['auth_success']);
        }

        $view = 'account_password';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function logout(): void
    {
        inventra_clear_authenticated_user();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: index.php?url=login&logout=1');
        exit;
    }

    private function sendOtpEmail(string $toEmail, string $name, string $otp): void
    {
        $mailConfig = require __DIR__ . '/../config/mail.php';

        $mail = new PHPMailer(true);

        $mail->SMTPDebug = 0;

        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $this->resolveMailEncryption($mailConfig);
        $mail->Port = $mailConfig['port'];
        $mail->Timeout = 20;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($toEmail, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Your Inventra OTP Code';
        $mail->Body = "
            <h2>OTP Verification</h2>
            <p>Your 6-digit OTP code is:</p>
            <h1 style='letter-spacing:4px;'>{$otp}</h1>
            <p>This OTP will expire in 10 minutes.</p>
        ";

        $mail->send();
    }

    private function sendPasswordChangedEmail(string $toEmail, string $name = ''): void
    {
        try {
            $mailConfig = require __DIR__ . '/../config/mail.php';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = $this->resolveMailEncryption($mailConfig);
            $mail->Port = $mailConfig['port'];
            $mail->Timeout = 20;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($toEmail, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Your Inventra password was changed';
            $mail->Body = '
                <h2>Password Updated</h2>
                <p>Your Inventra account password was changed successfully.</p>
                <p>If this was not you, please contact support immediately.</p>
            ';
            $mail->AltBody = "Your Inventra account password was changed successfully.\nIf this was not you, please contact support immediately.";

            $mail->send();
        } catch (Throwable $e) {
            error_log('Password change email failed for ' . $toEmail . ': ' . $e->getMessage());
        }
    }

    private function resolveMailEncryption(array $mailConfig): string
    {
        $encryption = strtolower(trim((string) ($mailConfig['encryption'] ?? 'ssl')));

        return match ($encryption) {
            'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            default => PHPMailer::ENCRYPTION_SMTPS,
        };
    }

    private function isValidPassword(string $password): bool
    {
        return (bool) preg_match('/^(?=.*[!@#]).{8,}$/', $password);
    }

    private function logPasswordReset(string $email): void
    {
        error_log('Password reset completed for ' . $email . ' at ' . date('Y-m-d H:i:s'));
    }
}
