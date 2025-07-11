<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Controllers\UserController;
use App\Middleware\RateLimitMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Health check
    $app->get('/', HealthController::class . ':index');
    
    // API Health check
    $app->get('/api/health', HealthController::class . ':health');
    
    // Users API with rate limiting
    $app->group('/api/users', function (RouteCollectorProxy $group) {
        $group->get('', UserController::class . ':index');
        $group->get('/{id:[0-9]+}', UserController::class . ':show');
        $group->post('', UserController::class . ':store');
        $group->put('/{id:[0-9]+}', UserController::class . ':update');
        $group->delete('/{id:[0-9]+}', UserController::class . ':destroy');
        $group->get('/{id:[0-9]+}/posts', UserController::class . ':posts');
    })->add(new RateLimitMiddleware());
};