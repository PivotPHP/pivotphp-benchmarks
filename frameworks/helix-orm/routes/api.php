<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Controllers\HealthController;
use App\Middleware\RateLimitMiddleware;

/** @var \Helix\Core\Application $app */

// Health check
$app->get('/', [HealthController::class, 'index']);

// Users API
$app->group('/users', function ($group) {
    $group->get('', [UserController::class, 'index']);
    $group->get('/{id:\d+}', [UserController::class, 'show']);
    $group->post('', [UserController::class, 'store']);
    $group->put('/{id:\d+}', [UserController::class, 'update']);
    $group->delete('/{id:\d+}', [UserController::class, 'destroy']);
    $group->get('/{id:\d+}/posts', [UserController::class, 'posts']);
})->addMiddleware(new RateLimitMiddleware());