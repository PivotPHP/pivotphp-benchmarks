#!/usr/bin/env php
<?php

/**
 * Synchronous Load Test for Laravel Lumen
 * 
 * Tests with loads: 100, 1000, 10000
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../frameworks/lumen/bootstrap/app.php';

use PivotBenchmark\BenchmarkBase;
use Illuminate\Http\Request;

class SynchronousLoadTest extends BenchmarkBase
{
    private $app;
    private array $loadLevels = [100, 1000, 10000];
    private int $currentLoad = 100;
    
    protected function getName(): string
    {
        return "stress/synchronous-{$this->currentLoad}";
    }
    
    protected function setup(): void
    {
        // Lumen already initialized in bootstrap/app.php
        $this->app = $app ?? null;
        
        // Simple endpoint - minimal processing
        $GLOBALS['app']->router->get('/stress/simple', function () {
            return response()->json(['ok' => true, 'time' => microtime(true)]);
        });
        
        // JSON endpoint - moderate data
        $GLOBALS['app']->router->get('/stress/json', function () {
            $data = [];
            for ($i = 0; $i < 50; $i++) {
                $data[] = [
                    'id' => $i,
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com",
                    'active' => $i % 2 === 0
                ];
            }
            return response()->json(['users' => $data, 'total' => count($data)]);
        });
        
        // Complex endpoint - heavy processing
        $GLOBALS['app']->router->post('/stress/complex', function (Request $request) {
            $input = $request->all();
            
            // Simulate complex processing
            $results = [];
            for ($i = 0; $i < 20; $i++) {
                $results[] = [
                    'input' => $input,
                    'hash' => md5(json_encode($input) . $i),
                    'calculations' => [
                        'sqrt' => sqrt($i * 1000),
                        'pow' => pow($i, 2),
                        'random' => array_map(fn() => rand(1, 1000), range(1, 10))
                    ]
                ];
            }
            
            // Simulate database-like operations
            usleep(100); // 0.1ms delay
            
            return response()->json([
                'processed' => count($results),
                'results' => $results,
                'execution_time' => microtime(true)
            ]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        // Override - we'll use custom load testing
    }
    
    public function runLoadTests(): array
    {
        $results = [];
        
        // Initialize application
        $this->setup();
        
        echo "=== Laravel Lumen Synchronous Load Test ===\n";
        echo "PHP " . PHP_VERSION . " | " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->loadLevels as $load) {
            $this->currentLoad = $load;
            echo "Testing with {$load} requests:\n";
            
            $results[$load] = [
                'simple' => $this->testEndpoint('GET', '/stress/simple', $load),
                'json' => $this->testEndpoint('GET', '/stress/json', $load),
                'complex' => $this->testEndpoint('POST', '/stress/complex', $load)
            ];
            
            $this->displayResults($load, $results[$load]);
            echo "\n";
        }
        
        return $results;
    }
    
    private function testEndpoint(string $method, string $endpoint, int $requests): array
    {
        echo "  Testing {$endpoint}... ";
        
        // Warmup
        for ($i = 0; $i < min(10, $requests / 10); $i++) {
            $this->makeRequest($method, $endpoint);
        }
        
        // Actual test
        $latencies = [];
        $memoryStart = memory_get_usage(true);
        $start = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $reqStart = microtime(true);
            $this->makeRequest($method, $endpoint);
            $reqEnd = microtime(true);
            
            $latencies[] = ($reqEnd - $reqStart) * 1000; // Convert to ms
            
            // Progress indicator
            if ($requests >= 1000 && $i % 1000 === 0 && $i > 0) {
                echo ".";
            }
        }
        
        $end = microtime(true);
        $memoryEnd = memory_get_peak_usage(true);
        
        echo " Done!\n";
        
        // Calculate metrics
        $duration = $end - $start;
        sort($latencies);
        
        return [
            'requests' => $requests,
            'duration_s' => round($duration, 3),
            'throughput' => round($requests / $duration, 2),
            'latency' => [
                'avg' => round(array_sum($latencies) / count($latencies), 3),
                'min' => round(min($latencies), 3),
                'max' => round(max($latencies), 3),
                'p50' => $this->percentile($latencies, 50),
                'p95' => $this->percentile($latencies, 95),
                'p99' => $this->percentile($latencies, 99)
            ],
            'memory_mb' => round(($memoryEnd - $memoryStart) / 1024 / 1024, 2)
        ];
    }
    
    private function makeRequest(string $method, string $endpoint): void
    {
        // Set up the request environment
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $endpoint;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['PHP_SELF'] = '/index.php' . $endpoint;
        
        if ($method === 'POST') {
            $_POST = [
                'test' => 'data',
                'value' => rand(1, 100),
                'timestamp' => time()
            ];
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        } else {
            $_POST = [];
            unset($_SERVER['CONTENT_TYPE']);
        }
        
        // Create Lumen request
        $request = Request::capture();
        
        // Suppress output
        ob_start();
        $response = $GLOBALS['app']->dispatch($request);
        ob_end_clean();
    }
    
    private function percentile(array $values, int $percentile): float
    {
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return round($values[$index] ?? 0, 3);
    }
    
    private function displayResults(int $load, array $results): void
    {
        echo "\nResults for {$load} requests:\n";
        echo str_repeat("-", 70) . "\n";
        echo sprintf("%-15s %12s %12s %12s %12s\n", 
            "Endpoint", "Throughput", "Avg Latency", "P95 Latency", "Memory"
        );
        echo str_repeat("-", 70) . "\n";
        
        foreach (['simple', 'json', 'complex'] as $type) {
            $r = $results[$type];
            echo sprintf(
                "%-15s %12s %12s %12s %12s\n",
                ucfirst($type),
                number_format($r['throughput'], 2) . " req/s",
                number_format($r['latency']['avg'], 2) . " ms",
                number_format($r['latency']['p95'], 2) . " ms",
                $r['memory_mb'] . " MB"
            );
        }
    }
}

// Parse options
$options = getopt('', ['json', 'load:', 'save']);

// Run tests
$test = new SynchronousLoadTest($options);
$results = $test->runLoadTests();

// Save results if requested
if (isset($options['save'])) {
    $filename = __DIR__ . '/../../../results/lumen-stress-' . date('Y-m-d-His') . '.json';
    file_put_contents($filename, json_encode([
        'framework' => 'Laravel Lumen',
        'test' => 'Synchronous Load Test',
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION,
        'results' => $results
    ], JSON_PRETTY_PRINT));
    echo "\nResults saved to: " . basename($filename) . "\n";
}

// Output JSON if requested
if (isset($options['json'])) {
    echo "\n" . json_encode($results, JSON_PRETTY_PRINT) . "\n";
}