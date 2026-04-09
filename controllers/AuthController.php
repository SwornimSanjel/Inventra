<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/OTPModel.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class AuthController
{
    private UserModel $userModel;
    private OTPModel $otpModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->otpModel = new OTPModel();
    }

    public function showForgotPassword(): void
    {
        $error = $_SESSION['auth_error'] ?? '';
        $success = $_SESSION['auth_success'] ?? '';
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        $view = 'forgot_password';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function sendOtp(): void
    {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $_SESSION['auth_error'] = 'Please enter your email.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $_SESSION['auth_error'] = 'No account found with this email.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $this->otpModel->createOrReplaceOTP((int) $user['id'], $email, $otp, $expiresAt);
        $this->sendOtpEmail($email, $user['full_name'], $otp);

        $_SESSION['reset_email'] = $email;
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
            $record = $this->otpModel->findLatestByEmail($email);
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

        $record = $this->otpModel->findLatestByEmail($email);

        if (!$record) {
            $_SESSION['auth_error'] = 'Invalid OTP';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        if ((int) $record['attempts'] >= 5) {
            $_SESSION['auth_error'] = 'Too many attempts. Please resend OTP.';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        if (strtotime($record['expires_at']) < time()) {
            $_SESSION['auth_error'] = 'OTP expired. Please resend OTP.';
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

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $_SESSION['auth_error'] = 'No account found with this email.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $this->otpModel->createOrReplaceOTP((int) $user['id'], $email, $otp, $expiresAt);
        $this->sendOtpEmail($email, $user['full_name'], $otp);

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

        if (!$resetRecord && $verifiedEmail !== '') {
            $latestRecord = $this->otpModel->findLatestByEmail($verifiedEmail);
            if ($latestRecord && (int) $latestRecord['is_verified'] === 1) {
                $resetRecord = $latestRecord;
            }
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
                $error = 'Password must be at least 8 characters and include 1 number and 1 special character.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                if ($this->userModel->updatePassword($verifiedEmail, $hash)) {
                    $this->otpModel->deleteByEmail($verifiedEmail);
                    $this->logPasswordReset($verifiedEmail);
                    $this->sendPasswordChangedEmail($verifiedEmail);

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
                }

                $error = 'Unable to update password. Please try again.';
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

    private function sendOtpEmail(string $toEmail, string $name, string $otp): void
    {
        $mailConfig = require __DIR__ . '/../config/mail.php';

        $mail = new PHPMailer(true);

        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';

        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $mailConfig['port'];

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

    private function sendPasswordChangedEmail(string $toEmail): void
    {
        try {
            $mailConfig = require __DIR__ . '/../config/mail.php';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $mailConfig['port'];

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = 'Your Inventra password was changed';
            $mail->Body = '
                <h2>Password Updated</h2>
                <p>Your Inventra account password was changed successfully.</p>
                <p>If this was not you, please contact support immediately.</p>
            ';

            $mail->send();
        } catch (Throwable $e) {
            error_log('Password change email failed for ' . $toEmail . ': ' . $e->getMessage());
        }
    }

    private function isValidPassword(string $password): bool
    {
        return (bool) preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password);
    }

    private function logPasswordReset(string $email): void
    {
        error_log('Password reset completed for ' . $email . ' at ' . date('Y-m-d H:i:s'));
    }
}
