<?php

require_once __DIR__ . '/../config/database.php';

class OTPModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function createOTP(int $userId, string $email, string $otp, string $expiresAt): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO password_resets (
                user_id,
                email,
                otp_code,
                attempts,
                is_verified,
                reset_token,
                expires_at,
                verified_at,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, 0, FALSE, NULL, ?, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([$userId, $email, $otp, $expiresAt]);

        return (int) $stmt->fetchColumn();
    }

    public function findLatestByEmail(string $email): ?array
    {
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

    public function findLatestActiveOTPByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM password_resets
            WHERE email = ?
              AND is_verified = FALSE
              AND expires_at > CURRENT_TIMESTAMP
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function incrementAttempts(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE password_resets
            SET attempts = attempts + 1, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    public function markVerified(int $id, string $resetToken): void
    {
        $stmt = $this->db->prepare("
            UPDATE password_resets
            SET is_verified = TRUE, reset_token = ?, verified_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$resetToken, $id]);
    }

    public function findVerifiedByToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets
            WHERE reset_token = ? AND is_verified = TRUE
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

