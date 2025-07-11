<?php

namespace App\Controllers;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class HealthController
{
    public function check(Request $request): Response
    {
        $health = [
            'status' => 'healthy',
            'framework' => 'PivotPHP Full',
            'version' => '1.1.0',
            'components' => [
                'core' => '1.1.0',
                'orm' => '1.0.1',
                'reactphp' => '0.1.0'
            ],
            'timestamp' => date('c'),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true)
            ],
            'capabilities' => [
                'async_processing' => true,
                'database_orm' => true,
                'high_performance' => true,
                'real_time' => true
            ]
        ];

        return new Response(json_encode($health), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    public function info(Request $request): Response
    {
        $info = [
            'framework' => 'PivotPHP Full Stack',
            'description' => 'Complete ecosystem with Core, ORM, and ReactPHP',
            'features' => [
                'High-performance routing',
                'Cycle ORM integration',
                'ReactPHP async capabilities',
                'Production-ready middleware',
                'Real-time processing',
                'Database abstraction',
                'Event-driven architecture'
            ],
            'benchmarks_available' => [
                'routing',
                'middleware',
                'json-api',
                'database',
                'orm-queries',
                'orm-hydration',
                'async-performance',
                'continuous-runtime',
                'concurrency',
                'memory',
                'security',
                'http-performance',
                'rest-api',
                'json-performance',
                'api-middleware'
            ],
            'php_version' => PHP_VERSION,
            'environment' => [
                'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
                'jit_enabled' => extension_loaded('opcache') && ini_get('opcache.jit'),
                'memory_limit' => ini_get('memory_limit')
            ]
        ];

        return new Response(json_encode($info, JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}