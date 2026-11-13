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

// SPEC-115 — Cenários Modulares (estilo Fastify)
$app->get('/api/bare', function ($req, $res) {
    return $res->json(['hello' => 'world']);
});
$app->get('/api/user/:id', function ($req, $res) {
    return $res->json(['id' => (string) $req->param('id')]);
});
$mwPass = fn ($req, $res, $next) => $next($req, $res);
$app->get('/api/middleware', function ($req, $res) {
    return $res->json(['hello' => 'world']);
}, [], $mwPass, $mwPass, $mwPass, $mwPass, $mwPass);

$app->post('/api/echo', function ($req, $res) {
    return $res->json($req->json());
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
