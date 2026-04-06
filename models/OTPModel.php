<?php
require_once __DIR__ . '/../config/database.php';

class OTPModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function createOrReplaceOTP(int $userId, string $email, string $otp, string $expiresAt): void {
        $delete = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete->execute([$email]);

        $stmt = $this->db->prepare("
            INSERT INTO password_resets (user_id, email, otp_code, attempts, is_verified, reset_token, expires_at, verified_at, created_at)
            VALUES (?, ?, ?, 0, 0, NULL, ?, NULL, NOW())
        ");
        $stmt->execute([$userId, $email, $otp, $expiresAt]);
    }

    public function findLatestByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets
            WHERE email = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function incrementAttempts(int $id): void {
        $stmt = $this->db->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function markVerified(int $id, string $resetToken): void {
        $stmt = $this->db->prepare("
            UPDATE password_resets
            SET is_verified = 1, reset_token = ?, verified_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$resetToken, $id]);
    }

    public function findVerifiedByToken(string $token): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets
            WHERE reset_token = ? AND is_verified = 1
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}