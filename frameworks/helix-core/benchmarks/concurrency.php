#!/usr/bin/env php
<?php

/**
 * Concurrency Benchmark for HelixPHP Core
 * 
 * Tests performance under concurrent load with different scenarios
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';
require_once __DIR__ . '/../../../shared/Traits/ConcurrencyTrait.php';

use Helix\Core\Application;
use Helix\Http\Psr7\Factory\ServerRequestFactory;
use Shared\Interfaces\BenchmarkInterface;
use Shared\Traits\ConcurrencyTrait;

class ConcurrencyBenchmark implements BenchmarkInterface
{
    use ConcurrencyTrait;
    
    private ServerRequestFactory $requestFactory;
    
    public function __construct()
    {
        $this->requestFactory = new ServerRequestFactory();
    }
    
    public function run(int $iterations = 1000, int $warmup = 100): array
    {
        $scenarios = [
            'low' => [
                'concurrency' => 10,
                'total_requests' => 1000,
                'description' => 'Low load - 10 concurrent connections'
            ],
            'normal' => [
                'concurrency' => 50,
                'total_requests' => 5000,
                'description' => 'Normal load - 50 concurrent connections'
            ],
            'high' => [
                'concurrency' => 100,
                'total_requests' => 10000,
                'description' => 'High load - 100 concurrent connections'
            ]
        ];
        
        $results = [];
        
        foreach ($scenarios as $scenario => $config) {
            echo "Running {$scenario} concurrency scenario...\n";
            
            // Create app instance for this scenario
            $app = $this->createApp();
            
            // Run concurrent requests
            $stats = $this->runConcurrent(
                function() use ($app) {
                    $this->makeRequest($app);
                },
                $config['concurrency'],
                $config['total_requests']
            );
            
            $results[$scenario] = array_merge($stats, [
                'scenario' => $scenario,
                'description' => $config['description']
            ]);
            
            echo "  Completed: {$stats['requests_per_second']} req/sec\n";
            echo "  Latency p95: {$stats['latency_ms']['p95']}ms\n\n";
        }
        
        return [
            'benchmark' => $this->getName(),
            'framework' => 'helix-core',
            'description' => $this->getDescription(),
            'scenarios' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function createApp(): Application
    {
        $app = new Application();
        
        // Add some middleware to simulate real app
        $app->addMiddleware(function($request, $handler) {
            $response = $handler->handle($request);
            return $response->withHeader('X-Processed', 'true');
        });
        
        // Define routes
        $app->get('/', function($request, $response) {
            return $response->json(['status' => 'ok', 'time' => microtime(true)]);
        });
        
        $app->get('/users', function($request, $response) {
            $users = [];
            for ($i = 1; $i <= 20; $i++) {
                $users[] = [
                    'id' => $i,
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com"
                ];
            }
            return $response->json(['data' => $users]);
        });
        
        $app->post('/users', function($request, $response) {
            $data = $request->getParsedBody();
            return $response->json([
                'data' => array_merge(['id' => rand(1000, 9999)], $data)
            ], 201);
        });
        
        return $app;
    }
    
    private function makeRequest(Application $app): void
    {
        $endpoints = [
            ['method' => 'GET', 'path' => '/'],
            ['method' => 'GET', 'path' => '/users'],
            ['method' => 'POST', 'path' => '/users']
        ];
        
        $endpoint = $endpoints[array_rand($endpoints)];
        
        // Create request
        $request = $this->requestFactory->createServerRequest($endpoint['method'], $endpoint['path']);
        
        if ($endpoint['method'] === 'POST') {
            $body = json_encode(['name' => 'Test User', 'email' => 'test@example.com']);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->requestFactory->createStream($body));
        }
        
        // Handle request
        $response = $app->handle($request);
        
        // Validate response
        if ($response->getStatusCode() >= 400) {
            throw new \Exception("Request failed with status: " . $response->getStatusCode());
        }
    }
    
    public function getName(): string
    {
        return 'concurrency/load-test';
    }
    
    public function getDescription(): string
    {
        return 'Tests framework performance under concurrent load (low: 10, normal: 50, high: 100 connections)';
    }
}

// Check if pcntl extension is available
if (!extension_loaded('pcntl')) {
    die("Error: pcntl extension is required for concurrency tests\n");
}

if (!extension_loaded('shmop')) {
    die("Error: shmop extension is required for concurrency tests\n");
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['scenario:', 'json']);
    
    $benchmark = new ConcurrencyBenchmark();
    $results = $benchmark->run();
    
    if (isset($options['json'])) {
        echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Benchmark: {$results['benchmark']}\n";
        echo "Framework: {$results['framework']}\n\n";
        
        foreach ($results['scenarios'] as $scenario => $stats) {
            echo "=== {$scenario} load scenario ===\n";
            echo "Concurrency: {$stats['concurrency']} connections\n";
            echo "Total requests: {$stats['total_requests']}\n";
            echo "Requests/sec: " . round($stats['requests_per_second'], 2) . "\n";
            echo "Failed requests: {$stats['failed_requests']}\n";
            echo "Latency (ms):\n";
            echo "  Min: " . round($stats['latency_ms']['min'], 2) . "\n";
            echo "  Avg: " . round($stats['latency_ms']['avg'], 2) . "\n";
            echo "  p50: " . round($stats['latency_ms']['p50'], 2) . "\n";
            echo "  p95: " . round($stats['latency_ms']['p95'], 2) . "\n";
            echo "  p99: " . round($stats['latency_ms']['p99'], 2) . "\n";
            echo "  Max: " . round($stats['latency_ms']['max'], 2) . "\n";
            echo "\n";
        }
    }
}