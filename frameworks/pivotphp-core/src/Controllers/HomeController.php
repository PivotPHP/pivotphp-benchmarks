<?php

declare(strict_types=1);

namespace App\Controllers;

use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Request;

class HomeController
{
    public function index(Request $request, Response $response): void
    {
        $data = [
            'message' => 'Welcome to PivotPHP Validation Project',
            'version' => '1.0.0',
            'framework' => 'PivotPHP',
            'runtime' => php_sapi_name() === 'cli' ? 'ReactPHP' : 'Traditional',
            'endpoints' => [
                'api_health' => '/api/v1/health',
                'api_users' => '/api/v1/users',
                'api_posts' => '/api/v1/posts',
                'metrics' => '/metrics',
                'benchmarks' => [
                    'cpu' => '/api/v1/benchmark/cpu',
                    'memory' => '/api/v1/benchmark/memory',
                    'io' => '/api/v1/benchmark/io',
                ],
            ],
        ];
        
        $response->json($data);
    }

    public function about(Request $request, Response $response): void
    {
        $response->json([
            'name' => 'PivotPHP Validation Project',
            'description' => 'A validation and testing project for PivotPHP Core and ReactPHP integration',
            'features' => [
                'PSR-7 HTTP Messages',
                'PSR-15 Middleware',
                'Dependency Injection Container',
                'ReactPHP Event Loop',
                'Cycle ORM Integration',
                'RESTful API',
                'Performance Benchmarks',
            ],
            'documentation' => 'https://github.com/pivotphp/validation',
        ]);
    }
}