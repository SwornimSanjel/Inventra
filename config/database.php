<?php
class Database
{
    private static ?PDO $pdo = null;
    private static bool $envLoaded = false;

    private static function loadEnvFile(): void
    {
        if (self::$envLoaded) {
            return;
        }

        self::$envLoaded = true;

        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    private static function env(string $key, ?string $default = null): ?string
    {
        self::loadEnvFile();

        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        if ($value === null) {
            return $default;
        }

        $value = trim((string) $value);
        return $value === '' ? $default : $value;
    }

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $host = self::env('DB_HOST', 'db.xstqbxlnkzvfoovanaib.supabase.co');
            $port = self::env('DB_PORT', '5432');
            $dbname = self::env('DB_NAME', 'postgres');
            $username = self::env('DB_USER', 'postgres');
            $password = self::env('DB_PASSWORD', '');
            $sslmode = self::env('DB_SSLMODE', $host === '127.0.0.1' || $host === 'localhost' ? 'disable' : 'require');

            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                $host,
                $port,
                $dbname,
                $sslmode
            );

            try {
                self::$pdo = new PDO(
                    $dsn,
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                $resolutionHint = '';
                $ipv4Records = gethostbynamel($host);
                $dnsRecords = function_exists('dns_get_record')
                    ? @dns_get_record($host, DNS_A + DNS_AAAA)
                    : [];

                $hasIpv6OnlyRecord = is_array($dnsRecords)
                    && $dnsRecords !== []
                    && !array_filter($dnsRecords, static fn(array $record): bool => ($record['type'] ?? '') === 'A')
                    && (bool) array_filter($dnsRecords, static fn(array $record): bool => ($record['type'] ?? '') === 'AAAA');

                if (($ipv4Records === false || $ipv4Records === []) && $hasIpv6OnlyRecord) {
                    $resolutionHint = ' PHP can only see an IPv6 DNS record for this host. On this machine, use the Supabase transaction/session pooler hostname from the Connect page or another IPv4-reachable host.';
                }

                throw new PDOException(
                    'Database connection failed for host "' . $host . '" on port ' . $port . '. ' .
                    'Check DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASSWORD.' . $resolutionHint . ' ' .
                    'Original error: ' . $e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            }
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
