<?php

/**
 * Simple Slim 4 API for HTTP Benchmarking
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Home route
$app->get('/', function (Request $request, Response $response, $args) {
    $payload = json_encode([
        'message' => 'Slim 4 API',
        'framework' => 'slim4',
        'version' => '4.x',
        'timestamp' => time()
    ]);

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// Health check
$app->get('/api/health', function (Request $request, Response $response, $args) {
    $payload = json_encode([
        'status' => 'healthy',
        'framework' => 'slim4',
        'version' => '4.x',
        'timestamp' => time(),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true)
    ]);

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// Users API
$app->get('/api/users', function (Request $request, Response $response, $args) {
    $users = [];
    for ($i = 1; $i <= 10; $i++) {
        $users[] = [
            'id' => $i,
            'name' => 'User ' . $i,
            'email' => 'user' . $i . '@example.com',
            'active' => $i % 2 === 0
        ];
    }
    
    $payload = json_encode([
        'data' => $users,
        'count' => count($users),
        'framework' => 'slim4'
    ]);

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// User detail
$app->get('/api/users/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    
    $payload = json_encode([
        'id' => (int)$id,
        'name' => 'User ' . $id,
        'email' => 'user' . $id . '@example.com',
        'active' => true,
        'framework' => 'slim4',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();