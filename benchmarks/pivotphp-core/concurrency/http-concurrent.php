#!/usr/bin/env php
<?php

/**
 * HTTP Concurrent Requests Benchmark
 * 
 * Tests the performance of handling concurrent HTTP requests
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;

class HttpConcurrentBenchmark extends BenchmarkBase
{
    private Application $app;
    private array $loadLevels = [500, 1000, 1500];
    private int $currentLoad = 500;
    
    protected function getName(): string
    {
        return "concurrency/http-concurrent-{$this->currentLoad}";
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // API endpoints
        $this->app->get('/api/users', function($request, $response) {
            $users = [];
            for ($i = 1; $i <= 10; $i++) {
                $users[] = [
                    'id' => $i,
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com"
                ];
            }
            return $response->json($users);
        });
        
        $this->app->get('/api/users/:id', function($request, $response) {
            $id = $request->getAttribute('id');
            return $response->json([
                'id' => $id,
                'name' => "User {$id}",
                'email' => "user{$id}@example.com",
                'profile' => [
                    'bio' => 'Lorem ipsum dolor sit amet',
                    'joined' => date('Y-m-d', strtotime('-' . rand(1, 365) . ' days'))
                ]
            ]);
        });
        
        $this->app->post('/api/users', function($request, $response) {
            $data = $request->getParsedBody();
            return $response->json([
                'id' => rand(100, 999),
                'name' => $data['name'] ?? 'New User',
                'email' => $data['email'] ?? 'new@example.com',
                'created_at' => date('c')
            ], 201);
        });
        
        $this->app->put('/api/users/:id', function($request, $response) {
            $id = $request->getAttribute('id');
            $data = $request->getParsedBody();
            return $response->json([
                'id' => $id,
                'name' => $data['name'] ?? "Updated User {$id}",
                'email' => $data['email'] ?? "updated{$id}@example.com",
                'updated_at' => date('c')
            ]);
        });
        
        $this->app->delete('/api/users/:id', function($request, $response) {
            $id = $request->getAttribute('id');
            return $response->json(['deleted' => true, 'id' => $id]);
        });
        
        // Heavy computation endpoint
        $this->app->get('/api/compute', function($request, $response) {
            $result = 0;
            for ($i = 0; $i < 1000; $i++) {
                $result += sqrt($i) * pi();
            }
            return $response->json(['result' => $result]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        // Simulate concurrent requests
        $requests = $this->generateRequestBatch($this->currentLoad);
        
        foreach ($requests as $req) {
            $this->handleRequest($req);
        }
    }
    
    private function generateRequestBatch(int $count): array
    {
        $requests = [];
        $endpoints = [
            ['GET', '/api/users'],
            ['GET', '/api/users'],
            ['GET', '/api/users'],
            ['POST', '/api/users'],
            ['POST', '/api/users'],
            ['GET', '/api/compute'],
            ['GET', '/api/compute']
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $endpoint = $endpoints[array_rand($endpoints)];
            $requests[] = [
                'method' => $endpoint[0],
                'uri' => $endpoint[1]
            ];
        }
        
        return $requests;
    }
    
    private function handleRequest(array $req): void
    {
        $_SERVER['REQUEST_METHOD'] = $req['method'];
        $_SERVER['REQUEST_URI'] = $req['uri'];
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($req['method'] === 'POST' || $req['method'] === 'PUT') {
            $_POST = [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ];
            $_SERVER['CONTENT_TYPE'] = 'application/json';
        } else {
            $_POST = [];
        }
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
        
        // Consume response
        $response->getBody()->getContents();
    }
    
    public function runLoadTests(): array
    {
        $results = [];
        
        // Initialize app
        $this->setup();
        
        foreach ($this->loadLevels as $load) {
            $this->currentLoad = $load;
            echo "Running benchmark with {$load} concurrent requests...\n";
            
            $warmup = 10;
            $iterations = 100;
            
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
            $totalRequests = $iterations * $load;
            $requestsPerSecond = $totalRequests / $totalTime;
            $avgLatency = ($totalTime / $totalRequests) * 1000; // ms
            
            $results[$load] = [
                'load_level' => $load,
                'total_requests' => $totalRequests,
                'total_time' => round($totalTime, 2),
                'requests_per_second' => round($requestsPerSecond, 2),
                'avg_latency_ms' => round($avgLatency, 4),
                'memory_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
                'memory_peak_mb' => round($endMemory / 1024 / 1024, 2)
            ];
        }
        
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['json', 'load:']);

// Run benchmark
$benchmark = new HttpConcurrentBenchmark($options);

if (isset($options['load'])) {
    // Run specific load level
    $benchmark->currentLoad = (int)$options['load'];
    $benchmark->run();
} else {
    // Run all load levels
    $results = $benchmark->runLoadTests();
    
    if (isset($options['json'])) {
        echo json_encode([
            'benchmark' => 'concurrency/http-load-test',
            'framework' => 'pivotphp-core',
            'timestamp' => date('c'),
            'results' => $results
        ], JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "\n=== HTTP Concurrency Benchmark Results ===\n\n";
        foreach ($results as $load => $data) {
            echo "Load Level: {$load} concurrent requests\n";
            echo "Total Requests: {$data['total_requests']}\n";
            echo "Requests/sec: {$data['requests_per_second']}\n";
            echo "Avg Latency: {$data['avg_latency_ms']} ms\n";
            echo "Memory Peak: {$data['memory_peak_mb']} MB\n";
            echo "---\n\n";
        }
    }
}