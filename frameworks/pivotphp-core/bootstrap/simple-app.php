<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\Router;

// Create application instance
$app = new Application(__DIR__ . '/..');

// Boot the application to initialize services
$app->boot();

// Get router instance from container
$router = $app->make(Router::class);

// Register simple routes for benchmarking
$router->get('/', function ($request, $response, callable $next) {
    return new \PivotPHP\Core\Http\Response(200, [], json_encode(['status' => 'ok', 'framework' => 'pivotphp-core']));
});

$router->get('/health', function ($request, $response, callable $next) {
    return new \PivotPHP\Core\Http\Response(200, [], json_encode(['status' => 'healthy', 'timestamp' => time()]));
});

$router->get('/users', function ($request, $response, callable $next) {
    return new \PivotPHP\Core\Http\Response(200, [], json_encode(['users' => [
        ['id' => 1, 'name' => 'User 1'],
        ['id' => 2, 'name' => 'User 2'],
        ['id' => 3, 'name' => 'User 3']
    ]]));
});

$router->get('/users/:id', function ($request, $response, callable $next) {
    $args = $request->getAttribute('route');
    $id = $args ? $args->getArguments()['id'] ?? 1 : 1;
    
    return new \PivotPHP\Core\Http\Response(200, [], json_encode([
        'user' => ['id' => (int)$id, 'name' => "User {$id}"]
    ]));
});

return $app;