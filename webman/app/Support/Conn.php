<?php

declare(strict_types=1);

namespace app\Support;

use PDO;
use Predis\Client;

/**
 * Conexões persistentes (um por worker) — PDO e Redis.
 */
final class Conn
{
    private static ?PDO $pdo = null;
    private static ?Cache $cache = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                getenv('DB_HOST') ?: '127.0.0.1',
                getenv('DB_PORT') ?: '5432',
                getenv('DB_NAME') ?: 'voting_db'
            );

            self::$pdo = new PDO(
                $dsn,
                getenv('DB_USER') ?: 'bench',
                getenv('DB_PASSWORD') ?: 'bench_secret',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }

        return self::$pdo;
    }

    public static function cache(): Cache
    {
        if (self::$cache === null) {
            try {
                $redis = new Client([
                    'scheme' => 'tcp',
                    'host'   => getenv('REDIS_HOST') ?: '127.0.0.1',
                    'port'   => 6379,
                ]);
            } catch (\Throwable) {
                $redis = null;
            }

            self::$cache = new Cache($redis);
        }

        return self::$cache;
    }
}
