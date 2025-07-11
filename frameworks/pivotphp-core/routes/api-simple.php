<?php

/**
 * Simple API Routes for HTTP Benchmarking
 */

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

return function ($app) {
    // Home route
    $app->get('/', function (Request $req, Response $res) {
        return $res->json([
            'message' => 'PivotPHP Core API',
            'framework' => 'pivotphp-core',
            'version' => '1.1.1',
            'timestamp' => time()
        ]);
    });

    // Health check
    $app->get('/api/health', function (Request $req, Response $res) {
        return $res->json([
            'status' => 'healthy',
            'framework' => 'pivotphp-core',
            'version' => '1.1.1',
            'timestamp' => time(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
    });

    // Users API
    $app->get('/api/users', function (Request $req, Response $res) {
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $users[] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'active' => $i % 2 === 0
            ];
        }
        
        return $res->json([
            'data' => $users,
            'count' => count($users),
            'framework' => 'pivotphp-core'
        ]);
    });

    // User detail
    $app->get('/api/users/{id}', function (Request $req, Response $res) {
        $id = $req->param('id');
        
        return $res->json([
            'id' => (int)$id,
            'name' => 'User ' . $id,
            'email' => 'user' . $id . '@example.com',
            'active' => true,
            'framework' => 'pivotphp-core',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    });
};