#!/usr/bin/env php
<?php

/**
 * Request Throughput Benchmark
 * 
 * Measures maximum throughput under different request patterns
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;

class RequestThroughputBenchmark extends BenchmarkBase
{
    private Application $app;
    private string $testType = 'mixed'; // simple, complex, mixed
    
    protected function getName(): string
    {
        return "throughput/request-{$this->testType}";
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // Simple endpoint (minimal processing)
        $this->app->get('/simple', function($request, $response) {
            return $response->json(['status' => 'ok']);
        });
        
        // Medium complexity endpoint
        $this->app->get('/medium', function($request, $response) {
            $data = [];
            for ($i = 0; $i < 50; $i++) {
                $data[] = [
                    'id' => $i,
                    'value' => md5($i),
                    'timestamp' => microtime(true)
                ];
            }
            return $response->json($data);
        });
        
        // Complex endpoint (heavy processing)
        $this->app->get('/complex', function($request, $response) {
            $results = [];
            
            // Simulate database queries
            for ($i = 0; $i < 10; $i++) {
                $results['queries'][] = $this->simulateDbQuery();
            }
            
            // Simulate data transformation
            $results['transformed'] = array_map(function($item) {
                return [
                    'processed' => strtoupper($item['data']),
                    'hash' => hash('sha256', $item['data']),
                    'metadata' => [
                        'processed_at' => microtime(true),
                        'server' => 'pivotphp-benchmark'
                    ]
                ];
            }, $results['queries']);
            
            return $response->json($results);
        });
        
        // POST endpoint with validation
        $this->app->post('/process', function($request, $response) {
            $data = $request->getParsedBody();
            
            // Simulate validation
            $errors = [];
            if (empty($data['name'])) {
                $errors[] = 'Name is required';
            }
            if (empty($data['email'])) {
                $errors[] = 'Email is required';
            }
            
            if (!empty($errors)) {
                return $response->json(['errors' => $errors], 400);
            }
            
            // Simulate processing
            $result = [
                'id' => uniqid(),
                'processed' => true,
                'data' => $data,
                'timestamp' => date('c')
            ];
            
            return $response->json($result, 201);
        });
    }
    
    protected function runSingleIteration(): void
    {
        switch ($this->testType) {
            case 'simple':
                $this->runSimpleRequest();
                break;
            case 'complex':
                $this->runComplexRequest();
                break;
            case 'mixed':
            default:
                $this->runMixedRequests();
                break;
        }
    }
    
    private function runSimpleRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/simple';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_POST = [];
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
    
    private function runComplexRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/complex';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_POST = [];
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
    
    private function runMixedRequests(): void
    {
        $requests = [
            ['GET', '/simple', []],
            ['GET', '/medium', []],
            ['GET', '/complex', []],
            ['POST', '/process', ['name' => 'Test', 'email' => 'test@example.com']],
            ['POST', '/process', ['name' => 'Invalid']], // Will fail validation
        ];
        
        $req = $requests[array_rand($requests)];
        
        $_SERVER['REQUEST_METHOD'] = $req[0];
        $_SERVER['REQUEST_URI'] = $req[1];
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($req[0] === 'POST') {
            $_POST = $req[2];
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        } else {
            $_POST = [];
        }
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
    
    private function simulateDbQuery(): array
    {
        // Simulate database latency
        usleep(100); // 0.1ms
        
        return [
            'id' => rand(1, 1000),
            'data' => bin2hex(random_bytes(16)),
            'created_at' => microtime(true)
        ];
    }
    
    public function runThroughputTests(array $loads = [500, 1000, 1500]): array
    {
        $results = [];
        $types = ['simple', 'complex', 'mixed'];
        
        foreach ($types as $type) {
            $this->testType = $type;
            $results[$type] = [];
            
            foreach ($loads as $load) {
                echo "Testing {$type} requests with load {$load}...\n";
                
                $warmup = 10;
                $duration = 10; // Run for 10 seconds
                
                // Warmup
                for ($i = 0; $i < $warmup; $i++) {
                    $this->runSingleIteration();
                }
                
                // Benchmark
                $start = microtime(true);
                $startMemory = memory_get_usage();
                $count = 0;
                $latencies = [];
                
                while ((microtime(true) - $start) < $duration) {
                    $reqStart = microtime(true);
                    
                    // Run batch of requests
                    for ($i = 0; $i < $load; $i++) {
                        $this->runSingleIteration();
                    }
                    
                    $reqEnd = microtime(true);
                    $latencies[] = ($reqEnd - $reqStart) * 1000; // ms
                    $count += $load;
                }
                
                $end = microtime(true);
                $endMemory = memory_get_peak_usage();
                
                $totalTime = $end - $start;
                $throughput = $count / $totalTime;
                $avgLatency = array_sum($latencies) / count($latencies);
                $p95Latency = $this->calculatePercentile($latencies, 95);
                $p99Latency = $this->calculatePercentile($latencies, 99);
                
                $results[$type][$load] = [
                    'load' => $load,
                    'total_requests' => $count,
                    'duration' => round($totalTime, 2),
                    'throughput' => round($throughput, 2),
                    'avg_latency_ms' => round($avgLatency, 2),
                    'p95_latency_ms' => round($p95Latency, 2),
                    'p99_latency_ms' => round($p99Latency, 2),
                    'memory_peak_mb' => round($endMemory / 1024 / 1024, 2)
                ];
            }
        }
        
        return $results;
    }
    
    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[$index] ?? 0;
    }
}

// Parse command line arguments
$options = getopt('', ['json', 'type:', 'load:']);

// Run benchmark
$benchmark = new RequestThroughputBenchmark($options);

if (isset($options['type'])) {
    // Run specific type
    $benchmark->testType = $options['type'];
    $benchmark->run();
} else {
    // Run comprehensive throughput tests
    $loads = isset($options['load']) 
        ? [(int)$options['load']] 
        : [500, 1000, 1500];
        
    $results = $benchmark->runThroughputTests($loads);
    
    if (isset($options['json'])) {
        echo json_encode([
            'benchmark' => 'throughput/comprehensive',
            'framework' => 'pivotphp-core',
            'timestamp' => date('c'),
            'results' => $results
        ], JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "\n=== Throughput Benchmark Results ===\n\n";
        foreach ($results as $type => $typeResults) {
            echo "Request Type: " . strtoupper($type) . "\n";
            echo "=====================================\n";
            foreach ($typeResults as $load => $data) {
                echo "Load: {$load} requests/batch\n";
                echo "  Throughput: {$data['throughput']} req/s\n";
                echo "  Avg Latency: {$data['avg_latency_ms']} ms\n";
                echo "  P95 Latency: {$data['p95_latency_ms']} ms\n";
                echo "  P99 Latency: {$data['p99_latency_ms']} ms\n";
                echo "  Memory Peak: {$data['memory_peak_mb']} MB\n";
                echo "\n";
            }
        }
    }
}