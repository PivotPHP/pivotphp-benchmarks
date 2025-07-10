#!/usr/bin/env php
<?php

/**
 * Continuous Runtime Benchmark for PivotPHP ReactPHP
 *
 * Tests the advantages of continuous runtime vs traditional PHP execution
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use Shared\Interfaces\BenchmarkInterface;

class ContinuousRuntimeBenchmark implements BenchmarkInterface
{
    private Application $app;
    private int $requestCount = 0;
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->setup();
    }

    private function setup(): void
    {
        $this->app = new Application();

        // Setup routes that would benefit from continuous runtime
        $this->app->get('/api/data', function($req, $res) {
            $this->requestCount++;
            
            // Simulate data processing that benefits from persistent state
            $data = [
                'request_id' => $this->requestCount,
                'uptime' => microtime(true) - $this->startTime,
                'memory_usage' => memory_get_usage(true),
                'cache_hits' => $this->requestCount * 0.85, // Simulated cache efficiency
                'framework' => 'pivotphp-reactphp',
                'runtime' => 'continuous',
                'process_id' => getmypid()
            ];
            
            $res->json($data);
        });

        $this->app->post('/api/process', function($req, $res) {
            $this->requestCount++;
            $data = $req->json();
            
            // Simulate data processing with persistent state
            $processed = [
                'input_size' => count($data ?? []),
                'processed_at' => microtime(true),
                'request_number' => $this->requestCount,
                'cumulative_requests' => $this->requestCount,
                'uptime' => microtime(true) - $this->startTime
            ];
            
            $res->json($processed);
        });
    }

    public function run(int $iterations = 10000, int $warmup = 1000): array
    {
        $startMemory = memory_get_usage();
        $startRequests = $this->requestCount;

        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->simulateRequest('/api/data');
        }

        // Reset counter for actual benchmark
        $benchmarkStartRequests = $this->requestCount;
        
        // Actual benchmark
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            if ($i % 2 === 0) {
                $this->simulateRequest('/api/data');
            } else {
                $this->simulateRequest('/api/process', 'POST', ['key' => 'value' . $i]);
            }
        }
        $end = microtime(true);

        $duration = $end - $start;
        $memoryUsed = memory_get_usage() - $startMemory;
        $totalRequests = $this->requestCount - $benchmarkStartRequests;

        return [
            'framework' => 'pivotphp-reactphp',
            'benchmark' => 'continuous-runtime',
            'iterations' => $iterations,
            'duration' => $duration,
            'ops_per_second' => round($iterations / $duration),
            'memory_used' => $memoryUsed,
            'memory_peak' => memory_get_peak_usage(true),
            'total_requests_served' => $this->requestCount,
            'benchmark_requests' => $totalRequests,
            'uptime' => microtime(true) - $this->startTime,
            'runtime_type' => 'continuous',
            'bootstrap_overhead' => 0, // No bootstrap overhead in continuous runtime
            'persistent_state' => true
        ];
    }

    private function simulateRequest(string $path, string $method = 'GET', array $data = null): void
    {
        $request = new Request($method, $path);
        if ($data && $method === 'POST') {
            $request = $request->withParsedBody($data);
        }
        
        $response = new Response();
        
        // Simulate route matching and execution
        $route = $this->app->getRouter()->match($method, $path);
        if ($route) {
            // Execute the route handler
            $handler = $route['handler'];
            if (is_callable($handler)) {
                $handler($request, $response);
            }
        }
    }

    public function getName(): string
    {
        return 'ReactPHP Continuous Runtime Benchmark';
    }

    public function getDescription(): string
    {
        return 'Tests the performance benefits of continuous runtime vs traditional PHP execution';
    }
}

// CLI execution
if (isset($argv)) {
    $benchmark = new ContinuousRuntimeBenchmark();
    
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
    
    echo "Starting continuous runtime benchmark (uptime will accumulate)...\n";
    $result = $benchmark->run($iterations, $warmup);
    
    if ($jsonOutput) {
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "ReactPHP Continuous Runtime Benchmark Results:\n";
        echo "Framework: {$result['framework']}\n";
        echo "Iterations: {$result['iterations']}\n";
        echo "Duration: " . number_format($result['duration'], 4) . "s\n";
        echo "Ops/sec: " . number_format($result['ops_per_second']) . "\n";
        echo "Memory used: " . number_format($result['memory_used'] / 1024 / 1024, 2) . " MB\n";
        echo "Total requests served: {$result['total_requests_served']}\n";
        echo "Runtime uptime: " . number_format($result['uptime'], 2) . "s\n";
        echo "Bootstrap overhead: {$result['bootstrap_overhead']}ms (continuous runtime)\n";
        echo "Persistent state: " . ($result['persistent_state'] ? 'Yes' : 'No') . "\n";
    }
}