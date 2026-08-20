<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

final class Connection
{
    private static ?PDO $instance = null;
    private static bool $attempted = false;

    public static function get(array $config): ?PDO
    {
        if (self::$attempted) {
            return self::$instance;
        }
        self::$attempted = true;

        if (($config['host'] ?? '') === '' || ($config['database'] ?? '') === '') {
            return null;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'utf8mb4',
            );
            self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException) {
            self::$instance = null;
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$attempted = false;
    }
}
