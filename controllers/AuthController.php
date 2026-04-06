<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/OTPModel.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class AuthController {
    private UserModel $userModel;
    private OTPModel $otpModel;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->otpModel = new OTPModel();
    }

    public function showForgotPassword(): void {
        $error = $_SESSION['auth_error'] ?? '';
        $success = $_SESSION['auth_success'] ?? '';
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        $view = 'forgot_password';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function sendOtp(): void {
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

        $this->otpModel->createOrReplaceOTP((int)$user['id'], $email, $otp, $expiresAt);
        $this->sendOtpEmail($email, $user['full_name'], $otp);

        $_SESSION['reset_email'] = $email;
        $_SESSION['auth_success'] = 'OTP sent successfully.';
        header('Location: index.php?url=auth/verify-otp');
        exit;
    }

    public function showVerifyOtp(): void {
        $email = $_SESSION['reset_email'] ?? '';
        $error = $_SESSION['auth_error'] ?? '';
        $success = $_SESSION['auth_success'] ?? '';

        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        $view = 'verify_otp';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    public function verifyOtp(): void {
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

        if ((int)$record['attempts'] >= 5) {
            $_SESSION['auth_error'] = 'Too many attempts. Please resend OTP.';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        if (strtotime($record['expires_at']) < time()) {
            $_SESSION['auth_error'] = 'OTP expired';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        if ($record['otp_code'] !== $otp) {
            $this->otpModel->incrementAttempts((int)$record['id']);
            $_SESSION['auth_error'] = 'Invalid OTP. Please try again.';
            header('Location: index.php?url=auth/verify-otp');
            exit;
        }

        $resetToken = bin2hex(random_bytes(32));
        $this->otpModel->markVerified((int)$record['id'], $resetToken);

        $_SESSION['reset_token'] = $resetToken;
        $_SESSION['auth_success'] = 'OTP verified';
        header('Location: index.php?url=auth/reset-password');
        exit;
    }

    public function resendOtp(): void {
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

        $this->otpModel->createOrReplaceOTP((int)$user['id'], $email, $otp, $expiresAt);
        $this->sendOtpEmail($email, $user['full_name'], $otp);

        $_SESSION['auth_success'] = 'OTP resent successfully.';
        header('Location: index.php?url=auth/verify-otp');
        exit;
    }

    public function showResetPassword(): void {
        $resetToken = $_SESSION['reset_token'] ?? '';

        if ($resetToken === '') {
            $_SESSION['auth_error'] = 'Unauthorized password reset access.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $record = $this->otpModel->findVerifiedByToken($resetToken);

        if (!$record) {
            $_SESSION['auth_error'] = 'Reset session expired or invalid.';
            header('Location: index.php?url=auth/forgot-password');
            exit;
        }

        $error = $_SESSION['auth_error'] ?? '';
        $success = $_SESSION['auth_success'] ?? '';

        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        $view = 'reset_password';
        require __DIR__ . '/../views/layout/auth-shell.php';
    }

    private function sendOtpEmail(string $toEmail, string $name, string $otp): void {
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
}