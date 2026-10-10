<?php

declare(strict_types=1);

/**
 * Rotas da API de votação. $app é a Application montada em config/bootstrap/app.php.
 *
 * @var \PivotPHP\Core\Core\Application $app
 */

use Bench\Controllers\CandidateController;
use Bench\Controllers\VoteController;

$app->get('/api/health', function ($req, $res) {
    return $res->json(['status' => 'ok']);
});

$app->get('/api/candidates', [CandidateController::class, 'index']);
$app->get('/api/candidates/{id}', [CandidateController::class, 'show']);

$app->post('/api/votes', [VoteController::class, 'store']);
$app->get('/api/votes/results', [VoteController::class, 'results']);
$app->get('/api/votes/results/{candidateId}', [VoteController::class, 'resultForCandidate']);
$app->delete('/api/votes', [VoteController::class, 'destroy']);
