<?php

declare(strict_types=1);

use Bench\Controllers\CandidateController;
use Bench\Controllers\VoteController;
use Bench\Support\Cache;
use PivotPHP\Core\Core\Application;
use Predis\Client;

/**
 * Cria a aplicação da API de votação (PivotPHP).
 */
function createApp(): Application
{
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

    // Redis (cache de candidatos/resultados).
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

    registerRoutes($app);

    return $app;
}

function registerRoutes(Application $app): void
{
    $app->get('/api/health', function ($req, $res) {
        return $res->json(['status' => 'ok']);
    });

    $app->get('/api/candidates', [CandidateController::class, 'index']);
    $app->get('/api/candidates/{id}', [CandidateController::class, 'show']);

    $app->post('/api/votes', [VoteController::class, 'store']);
    $app->get('/api/votes/results', [VoteController::class, 'results']);
    $app->get('/api/votes/results/{candidateId}', [VoteController::class, 'resultForCandidate']);
    $app->delete('/api/votes', [VoteController::class, 'destroy']);
}
