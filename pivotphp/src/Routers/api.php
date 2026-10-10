<?php

declare(strict_types=1);

/**
 * Rotas da API de votação. $app é a Application montada em config/bootstrap/app.php.
 *
 * @var \PivotPHP\Core\Core\Application $app
 */

use Bench\Controllers\CandidateController;
use Bench\Controllers\ProductController;
use Bench\Controllers\VoteController;

// Grupo A — Overhead HTTP mínimo
$app->get('/api/health', function ($req, $res) {
    return $res->json(['status' => 'ok']);
});
$app->get('/api/ping', function ($req, $res) {
    return $res->send('pong');
});

// Grupo B — CRUD de Produtos com persistência relacional e agregação em cache
$app->get('/api/products', [ProductController::class, 'index']);
$app->get('/api/products/{id}', [ProductController::class, 'show']);
$app->post('/api/products', [ProductController::class, 'store']);
$app->patch('/api/products/{id}', [ProductController::class, 'update']);
$app->delete('/api/products/{id}', [ProductController::class, 'destroy']);
$app->get('/api/dashboard', [ProductController::class, 'dashboard']);

// Grupo C — Regras de negócio e concorrência (Votação)
$app->get('/api/candidates', [CandidateController::class, 'index']);
$app->get('/api/candidates/{id}', [CandidateController::class, 'show']);
$app->post('/api/votes', [VoteController::class, 'store']);
$app->get('/api/votes/results', [VoteController::class, 'results']);
$app->get('/api/votes/results/{candidateId}', [VoteController::class, 'resultForCandidate']);
$app->delete('/api/votes', [VoteController::class, 'destroy']);
