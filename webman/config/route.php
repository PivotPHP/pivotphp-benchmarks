<?php

use app\controller\CandidateController;
use app\controller\VoteController;
use app\middleware\PassThroughMiddleware;
use support\Request;
use support\Response;
use Webman\Route;

// Helper para devolver JSON já serializado (cache) sem re-encodar.
if (!function_exists('json_http')) {
    function json_http(string $json): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], $json);
    }
}

// SPEC-115 — Cenários Modulares (estilo Fastify)
Route::get('/api/bare', function () {
    return json(['hello' => 'world']);
});

Route::get('/api/user/{id}', function (Request $request, $id) {
    return json(['id' => (string) $id]);
});

Route::get('/api/middleware', function () {
    return json(['hello' => 'world']);
})->middleware([
    PassThroughMiddleware::class,
    PassThroughMiddleware::class,
    PassThroughMiddleware::class,
    PassThroughMiddleware::class,
    PassThroughMiddleware::class,
]);

Route::post('/api/echo', function (Request $request) {
    $body = json_decode($request->rawBody(), true) ?? [];
    return json((array) $body);
});

// Rotas do app completo (votação)
Route::get('/api/health', function () {
    return json(['status' => 'ok']);
});

Route::get('/api/candidates', [CandidateController::class, 'index']);
Route::get('/api/candidates/{id}', [CandidateController::class, 'show']);

Route::post('/api/votes', [VoteController::class, 'store']);
Route::get('/api/votes/results', [VoteController::class, 'results']);
Route::get('/api/votes/results/{candidateId}', [VoteController::class, 'resultForCandidate']);
Route::delete('/api/votes', [VoteController::class, 'destroy']);

Route::disableDefaultRoute();
