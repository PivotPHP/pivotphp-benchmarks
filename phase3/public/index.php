<?php

declare(strict_types=1);

/**
 * PivotPHP v1.1.4 - Phase 3 Benchmark Entry Point
 * Architectural Excellence & Performance Optimization Edition
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

// Initialize PivotPHP v1.1.4
$app = new Application();

// Health check endpoint
$app->get('/health', function(Request $req, Response $res) {
    return $res->json([
        'status' => 'healthy',
        'version' => '1.1.4',
        'edition' => 'Architectural Excellence & Performance Optimization Edition',
        'timestamp' => date('c'),
        'framework' => 'PivotPHP'
    ]);
});

// Benchmark endpoints
$app->get('/benchmark/simple', function(Request $req, Response $res) {
    return $res->json([
        'message' => 'PivotPHP v1.1.4 Simple Benchmark',
        'timestamp' => microtime(true),
        'memory' => memory_get_usage(true)
    ]);
});

// Array callable endpoint (v1.1.4 feature)
class BenchmarkController {
    public function arrayCallable(Request $req, Response $res): Response {
        return $res->json([
            'message' => 'Array Callable Support - PHP 8.4+',
            'version' => '1.1.4',
            'feature' => 'Array Callable',
            'timestamp' => microtime(true)
        ]);
    }
}

$app->get('/benchmark/array-callable', [BenchmarkController::class, 'arrayCallable']);

// JSON performance test
$app->get('/benchmark/json', function(Request $req, Response $res) {
    $data = [
        'users' => array_fill(0, 10, [
            'id' => 1,
            'name' => 'User',
            'email' => 'user@example.com'
        ]),
        'metadata' => [
            'count' => 10,
            'version' => '1.1.4'
        ]
    ];
    
    return $res->json($data);
});

// Multiple routes test
$app->get('/benchmark/route1', function(Request $req, Response $res) {
    return $res->json(['route' => 1, 'version' => '1.1.4']);
});

$app->get('/benchmark/route2', function(Request $req, Response $res) {
    return $res->json(['route' => 2, 'version' => '1.1.4']);
});

$app->get('/benchmark/route3', function(Request $req, Response $res) {
    return $res->json(['route' => 3, 'version' => '1.1.4']);
});

// Run the application
$app->run();