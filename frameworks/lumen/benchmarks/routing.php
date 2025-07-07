#!/usr/bin/env php
<?php

/**
 * Routing Benchmark for Lumen
 * 
 * Tests route matching performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use Laravel\Lumen\Application;
use Illuminate\Http\Request;
use Shared\Interfaces\BenchmarkInterface;

class RoutingBenchmark implements BenchmarkInterface
{
    private Application $app;
    
    public function __construct()
    {
        $this->setup();
    }
    
    private function setup(): void
    {
        $this->app = new Application(dirname(__DIR__));
        
        // Setup 100 routes for testing
        for ($i = 0; $i < 100; $i++) {
            $this->app->router->get("/users/{$i}", function () use ($i) {
                return response()->json(['user_id' => $i]);
            });
        }
        
        // Add parameterized routes
        $this->app->router->get('/users/{id:[0-9]+}', function ($id) {
            return response()->json(['user_id' => $id]);
        });
        
        $this->app->router->get('/posts/{id:[0-9]+}/comments/{comment_id:[0-9]+}', function ($id, $comment_id) {
            return response()->json([
                'post_id' => $id,
                'comment_id' => $comment_id
            ]);
        });
        
        // Add wildcard route
        $this->app->router->get('/api/{path:.*}', function () {
            return response()->json(['api' => true]);
        });
    }
    
    public function run(int $iterations = 10000, int $warmup = 1000): array
    {
        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->runSingleIteration();
        }
        
        // Benchmark
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->runSingleIteration();
        }
        
        $end = microtime(true);
        $endMemory = memory_get_peak_usage();
        
        $totalTime = $end - $start;
        $opsPerSecond = $iterations / $totalTime;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
        
        return [
            'benchmark' => $this->getName(),
            'framework' => 'lumen',
            'description' => $this->getDescription(),
            'iterations' => $iterations,
            'warmup' => $warmup,
            'total_time' => $totalTime,
            'ops_per_second' => round($opsPerSecond, 2),
            'memory_mb' => round($memoryUsed, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function runSingleIteration(): void
    {
        $routes = [
            '/users/42',
            '/users/' . rand(0, 99),
            '/posts/10/comments/5',
            '/api/v1/products'
        ];
        
        $path = $routes[array_rand($routes)];
        
        // Create request
        $request = Request::create($path, 'GET');
        
        // Handle request
        $response = $this->app->handle($request);
        
        // Consume response
        $response->getContent();
    }
    
    public function getName(): string
    {
        return 'routing/match-and-dispatch';
    }
    
    public function getDescription(): string
    {
        return 'Tests route matching and dispatching performance with 100+ routes';
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['iterations:', 'warmup:', 'json']);
    
    $iterations = isset($options['iterations']) ? (int)$options['iterations'] : 10000;
    $warmup = isset($options['warmup']) ? (int)$options['warmup'] : 1000;
    
    $benchmark = new RoutingBenchmark();
    $results = $benchmark->run($iterations, $warmup);
    
    if (isset($options['json'])) {
        echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Benchmark: {$results['benchmark']}\n";
        echo "Framework: {$results['framework']}\n";
        echo "Operations/sec: {$results['ops_per_second']}\n";
        echo "Memory used: {$results['memory_mb']} MB\n";
    }
}