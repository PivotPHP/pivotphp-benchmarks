<?php

use app\controller\CandidateController;
use app\controller\VoteController;
use support\Response;
use Webman\Route;

// Helper para devolver JSON já serializado (cache) sem re-encodar.
if (!function_exists('json_http')) {
    function json_http(string $json): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], $json);
    }
}

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
