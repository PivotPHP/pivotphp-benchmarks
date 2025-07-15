<?php

declare(strict_types=1);

/**
 * Slim 4 - Phase 3 Benchmark Entry Point
 * Cross-framework comparison vs PivotPHP v1.1.4
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Initialize Slim 4
$app = AppFactory::create();

// Health check endpoint
$app->get('/health', function (Request $request, Response $response) {
    $data = [
        'status' => 'healthy',
        'version' => '4.14',
        'framework' => 'Slim 4',
        'timestamp' => date('c')
    ];
    
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Benchmark endpoints
$app->get('/benchmark/simple', function (Request $request, Response $response) {
    $data = [
        'message' => 'Slim 4 Simple Benchmark',
        'timestamp' => microtime(true),
        'memory' => memory_get_usage(true)
    ];
    
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Array callable endpoint (Slim 4 approach)
class BenchmarkController {
    public function arrayCallable(Request $request, Response $response): Response {
        $data = [
            'message' => 'Slim 4 Array Callable',
            'version' => '4.14',
            'feature' => 'Array Callable',
            'timestamp' => microtime(true)
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

$app->get('/benchmark/array-callable', [BenchmarkController::class, 'arrayCallable']);

// JSON performance test
$app->get('/benchmark/json', function (Request $request, Response $response) {
    $data = [
        'users' => array_fill(0, 10, [
            'id' => 1,
            'name' => 'User',
            'email' => 'user@example.com'
        ]),
        'metadata' => [
            'count' => 10,
            'version' => '4.14'
        ]
    ];
    
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Multiple routes test
$app->get('/benchmark/route1', function (Request $request, Response $response) {
    $data = ['route' => 1, 'version' => '4.14'];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/benchmark/route2', function (Request $request, Response $response) {
    $data = ['route' => 2, 'version' => '4.14'];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/benchmark/route3', function (Request $request, Response $response) {
    $data = ['route' => 3, 'version' => '4.14'];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Run the application
$app->run();