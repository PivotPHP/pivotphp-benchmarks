<?php

declare(strict_types=1);

use Bench\Controllers\CandidateController;
use Bench\Controllers\ProductController;
use Bench\Controllers\VoteController;
use Bench\Support\Cache;
use PivotPHP\Core\Core\Application;
use Predis\Client;

/**
 * Monta a aplicação da API de benchmark (PivotPHP) — mesma estrutura do pivotphp/skeleton.
 *
 * Fica em config/bootstrap/: o core carrega todo config/*.php (um nível) como configuração.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = new Application(dirname(__DIR__, 2));

// Banco PostgreSQL.
$app->singleton(PDO::class, function (): PDO {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '5432',
        getenv('DB_NAME') ?: 'voting_db'
    );

    return new PDO($dsn, getenv('DB_USER') ?: 'bench', getenv('DB_PASSWORD') ?: 'bench_secret', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
});

// Redis (cache de candidatos/resultados/dashboard).
$app->singleton(Cache::class, function (): Cache {
    try {
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port'   => 6379,
        ]);
    } catch (\Throwable) {
        $redis = null;
    }

    return new Cache($redis);
});

// Controllers.
$app->singleton(CandidateController::class, fn () => new CandidateController($app->make(PDO::class), $app->make(Cache::class)));
$app->singleton(VoteController::class, fn () => new VoteController($app->make(PDO::class), $app->make(Cache::class)));
$app->singleton(ProductController::class, fn () => new ProductController($app->make(PDO::class), $app->make(Cache::class)));

require __DIR__ . '/../../src/Routers/api.php';

return $app;
