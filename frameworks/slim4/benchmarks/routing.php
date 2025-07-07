#!/usr/bin/env php
<?php

/**
 * Routing Benchmark for Slim 4
 * 
 * Tests route matching performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Shared\Interfaces\BenchmarkInterface;

class RoutingBenchmark implements BenchmarkInterface
{
    private $app;
    private ServerRequestFactory $requestFactory;
    
    public function __construct()
    {
        $this->requestFactory = new ServerRequestFactory();
        $this->setup();
    }
    
    private function setup(): void
    {
        $this->app = AppFactory::create();
        
        // Disable error handling for benchmarks
        $this->app->addErrorMiddleware(false, false, false);
        
        // Setup 100 routes for testing
        for ($i = 0; $i < 100; $i++) {
            $this->app->get("/users/{$i}", function ($request, $response) use ($i) {
                $response->getBody()->write(json_encode(['user_id' => $i]));
                return $response->withHeader('Content-Type', 'application/json');
            });
        }
        
        // Add parameterized routes
        $this->app->get('/users/{id}', function ($request, $response, $args) {
            $response->getBody()->write(json_encode(['user_id' => $args['id']]));
            return $response->withHeader('Content-Type', 'application/json');
        });
        
        $this->app->get('/posts/{id}/comments/{comment_id}', function ($request, $response, $args) {
            $response->getBody()->write(json_encode([
                'post_id' => $args['id'],
                'comment_id' => $args['comment_id']
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });
        
        // Add catch-all route for /api/*
        $this->app->get('/api/[{path:.*}]', function ($request, $response) {
            $response->getBody()->write(json_encode(['api' => true]));
            return $response->withHeader('Content-Type', 'application/json');
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
            'framework' => 'slim4',
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
        $request = $this->requestFactory->createServerRequest('GET', $path);
        
        // Handle request
        $response = $this->app->handle($request);
        
        // Consume response
        $response->getBody()->getContents();
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