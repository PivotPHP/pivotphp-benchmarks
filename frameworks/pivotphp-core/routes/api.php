<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Controllers\HealthController;

/** @var \PivotPHP\Core\Routing\Router $router */

// Health check
$router->get('/', function($request, $response, callable $next) {
    $health = new HealthController();
    return $health->index($request);
});

// Users API
$router->get('/users', function($request, $response, callable $next) {
    $controller = new UserController();
    return $controller->index($request);
});

$router->get('/users/:id', function($request, $response, callable $next) {
    $controller = new UserController();
    return $controller->show($request, $request->getAttribute('route')->getArguments());
});

$router->post('/users', function($request, $response, callable $next) {
    $controller = new UserController();
    return $controller->store($request);
});

$router->put('/users/:id', function($request, $response, callable $next) {
    $controller = new UserController();
    return $controller->update($request, $request->getAttribute('route')->getArguments());
});

$router->delete('/users/:id', function($request, $response, callable $next) {
    $controller = new UserController();
    return $controller->destroy($request, $request->getAttribute('route')->getArguments());
});

$router->get('/users/:id/posts', function($request, $response, callable $next) {
    $controller = new UserController();
    return $controller->posts($request, $request->getAttribute('route')->getArguments());
});