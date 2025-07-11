<?php

/**
 * API Routes for PivotPHP Full Stack
 * Demonstrates all framework capabilities
 */

use App\Controllers\HealthController;
use App\Controllers\UserController;

// Health check routes
$app->get('/', [HealthController::class, 'check']);
$app->get('/health', [HealthController::class, 'check']);
$app->get('/info', [HealthController::class, 'info']);

// API v1 routes
$app->group('/api/v1', function() use ($app) {
    // User resource routes (REST API)
    $app->get('/users', [UserController::class, 'index']);
    $app->get('/users/{id}', [UserController::class, 'show']);
    $app->post('/users', [UserController::class, 'store']);
    $app->put('/users/{id}', [UserController::class, 'update']);
    $app->patch('/users/{id}', [UserController::class, 'update']);
    $app->delete('/users/{id}', [UserController::class, 'destroy']);

    // Additional user endpoints
    $app->get('/users/{id}/posts', function($request, $id) {
        // User's posts (demonstrates relationship)
        return new Response(json_encode([
            'user_id' => $id,
            'posts' => []
        ]), 200, ['Content-Type' => 'application/json']);
    });

    // Benchmark-specific routes
    $app->get('/benchmark/routing/{param1}/{param2}', function($request, $param1, $param2) {
        return new Response(json_encode([
            'benchmark' => 'routing',
            'params' => [$param1, $param2],
            'framework' => 'pivotphp-full'
        ]), 200, ['Content-Type' => 'application/json']);
    });

    $app->post('/benchmark/json', function($request) {
        $data = $request->body();
        return new Response(json_encode([
            'benchmark' => 'json-processing',
            'received' => $data,
            'framework' => 'pivotphp-full'
        ]), 200, ['Content-Type' => 'application/json']);
    });

    $app->get('/benchmark/middleware', function($request) {
        return new Response(json_encode([
            'benchmark' => 'middleware',
            'middleware_count' => 2, // CORS + RateLimit
            'framework' => 'pivotphp-full'
        ]), 200, ['Content-Type' => 'application/json']);
    });
});

// API v2 routes (for versioning tests)
$app->group('/api/v2', function() use ($app) {
    $app->get('/users', function($request) {
        return new Response(json_encode([
            'version' => 'v2',
            'message' => 'API v2 endpoint',
            'framework' => 'pivotphp-full'
        ]), 200, ['Content-Type' => 'application/json']);
    });
});

// Async/ReactPHP demonstration routes
$app->group('/async', function() use ($app) {
    $app->get('/promise', function($request) {
        // Simulate async operation
        return new Response(json_encode([
            'async' => true,
            'promise_resolved' => true,
            'framework' => 'pivotphp-full',
            'reactphp_enabled' => true
        ]), 200, ['Content-Type' => 'application/json']);
    });

    $app->get('/stream', function($request) {
        // Simulate streaming response
        return new Response(json_encode([
            'streaming' => true,
            'chunk' => 1,
            'framework' => 'pivotphp-full'
        ]), 200, ['Content-Type' => 'application/json']);
    });
});

// ORM demonstration routes
$app->group('/orm', function() use ($app) {
    $app->get('/demo', function($request) {
        return new Response(json_encode([
            'orm' => 'cycle-orm',
            'features' => [
                'Active Record',
                'Query Builder',
                'Relationships',
                'Migrations',
                'Schema Introspection'
            ],
            'framework' => 'pivotphp-full'
        ]), 200, ['Content-Type' => 'application/json']);
    });
});

// Catch-all route for testing
$app->get('/test/{path:.*}', function($request, $path) {
    return new Response(json_encode([
        'test_route' => true,
        'path' => $path,
        'framework' => 'pivotphp-full',
        'capabilities' => [
            'core' => '1.1.0',
            'orm' => '1.0.1',
            'reactphp' => '0.1.0'
        ]
    ]), 200, ['Content-Type' => 'application/json']);
});