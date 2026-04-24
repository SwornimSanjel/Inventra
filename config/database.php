<?php
class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                "pgsql:host=db.xstqbxlnkzvfoovanaib.supabase.co;port=5432;dbname=postgres;sslmode=require",
                "postgres",
                "axJ_x_U&2R%N?k+",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        return self::$pdo;
    }

    public static function tableExists(string $table): bool
    {
        $stmt = self::connect()->prepare('
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = ?
              AND table_name = ?
            LIMIT 1
        ');
        $stmt->execute(['public', $table]);

        return (bool) $stmt->fetchColumn();
    }

    public static function columnExists(string $table, string $column): bool
    {
        $stmt = self::connect()->prepare('
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = ?
              AND table_name = ?
              AND column_name = ?
            LIMIT 1
        ');
        $stmt->execute(['public', $table, $column]);

        return (bool) $stmt->fetchColumn();
    }
}
