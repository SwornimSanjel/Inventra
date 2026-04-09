<?php

require_once __DIR__ . '/../config/database.php';

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, full_name, email FROM admin WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updatePassword(string $email, string $hash): bool
    {
        $stmt = $this->db->prepare("UPDATE admin SET password_hash = ? WHERE email = ?");
        return $stmt->execute([$hash, $email]);
    }
}




