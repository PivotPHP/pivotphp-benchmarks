#!/usr/bin/env php
<?php

/**
 * Routing Benchmark for PivotPHP ReactPHP
 *
 * Tests route matching performance in continuous runtime
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use Shared\Interfaces\BenchmarkInterface;

class ReactPHPRoutingBenchmark implements BenchmarkInterface
{
    private $app;

    public function __construct()
    {
        $this->setup();
    }

    private function setup(): void
    {
        // Bootstrap the application like the server does
        $this->app = require __DIR__ . '/../bootstrap/app.php';

        // The routes are already registered via RouteServiceProvider
        // Add additional test routes for benchmarking
        for ($i = 1; $i <= 100; $i++) {
            $this->app->get("/bench-route{$i}", function($req, $res) use ($i) {
                $res->json(['route' => $i, 'framework' => 'pivotphp-reactphp']);
            });
            
            $this->app->get("/bench-route{$i}/{id}", function($req, $res) use ($i) {
                $res->json(['route' => $i, 'id' => $req->param('id'), 'framework' => 'pivotphp-reactphp']);
            });
        }
    }

    public function run(int $iterations = 10000, int $warmup = 1000): array
    {
        $routes = [
            '/bench-route1',
            '/bench-route50',
            '/bench-route25/123',
            '/bench-route75/456',
            '/users/123',
            '/api/v1/users/789'
        ];

        $startMemory = memory_get_usage();
        $routeCount = count($routes);

        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $route = $routes[$i % $routeCount];
            // Simulate route matching using the application's router
            $router = $this->app->getRouter();
            $router->match('GET', $route);
        }

        // Actual benchmark
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $route = $routes[$i % $routeCount];
            $router = $this->app->getRouter();
            $router->match('GET', $route);
        }
        $end = microtime(true);

        $duration = $end - $start;
        $memoryUsed = memory_get_usage() - $startMemory;

        return [
            'framework' => 'pivotphp-reactphp',
            'benchmark' => 'routing',
            'iterations' => $iterations,
            'duration' => $duration,
            'ops_per_second' => round($iterations / $duration),
            'memory_used' => $memoryUsed,
            'memory_peak' => memory_get_peak_usage(true),
            'routes_tested' => $routeCount,
            'runtime_type' => 'continuous'
        ];
    }

    public function getName(): string
    {
        return 'ReactPHP Routing Benchmark';
    }

    public function getDescription(): string
    {
        return 'Tests route matching performance in PivotPHP ReactPHP continuous runtime';
    }
}

// CLI execution
if (isset($argv)) {
    $benchmark = new ReactPHPRoutingBenchmark();
    
    $iterations = 10000;
    $warmup = 1000;
    $jsonOutput = false;
    
    // Parse command line arguments
    for ($i = 1; $i < count($argv); $i++) {
        if (strpos($argv[$i], '--iterations=') === 0) {
            $iterations = (int) substr($argv[$i], 13);
        } elseif (strpos($argv[$i], '--warmup=') === 0) {
            $warmup = (int) substr($argv[$i], 9);
        } elseif ($argv[$i] === '--json') {
            $jsonOutput = true;
        }
    }
    
    $result = $benchmark->run($iterations, $warmup);
    
    if ($jsonOutput) {
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "ReactPHP Routing Benchmark Results:\n";
        echo "Framework: {$result['framework']}\n";
        echo "Iterations: {$result['iterations']}\n";
        echo "Duration: " . number_format($result['duration'], 4) . "s\n";
        echo "Ops/sec: " . number_format($result['ops_per_second']) . "\n";
        echo "Memory used: " . number_format($result['memory_used'] / 1024 / 1024, 2) . " MB\n";
        echo "Memory peak: " . number_format($result['memory_peak'] / 1024 / 1024, 2) . " MB\n";
        echo "Runtime: {$result['runtime_type']}\n";
    }
}