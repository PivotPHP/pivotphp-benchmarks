#!/usr/bin/env php
<?php

/**
 * Simple Concurrent Test for PivotPHP Core
 * 
 * Simulates concurrent request handling
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;

class SimpleConcurrentTest extends BenchmarkBase
{
    private Application $app;
    
    protected function getName(): string
    {
        return "concurrent/simple-test";
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // Simple endpoint
        $this->app->get('/concurrent/simple', function($req, $res) {
            return $res->json(['status' => 'ok', 'thread' => uniqid(), 'time' => microtime(true)]);
        });
        
        // Data endpoint with simulated latency
        $this->app->get('/concurrent/data', function($req, $res) {
            // Simulate data processing
            $data = [];
            for ($i = 0; $i < 50; $i++) {
                $data[] = [
                    'id' => $i,
                    'value' => rand(100, 999),
                    'processed' => microtime(true)
                ];
            }
            
            // Simulate network/DB latency
            usleep(100); // 0.1ms
            
            return $res->json(['data' => $data, 'count' => count($data)]);
        });
        
        // Heavy processing endpoint
        $this->app->post('/concurrent/heavy', function($req, $res) {
            $input = $req->getParsedBody();
            
            // Simulate CPU-intensive work
            $results = [];
            for ($i = 0; $i < 10; $i++) {
                $hash = md5(json_encode($input) . $i);
                $calc = 0;
                for ($j = 0; $j < 1000; $j++) {
                    $calc += sqrt($j);
                }
                $results[] = [
                    'iteration' => $i,
                    'hash' => $hash,
                    'calculation' => $calc
                ];
            }
            
            return $res->json([
                'input' => $input,
                'results' => $results,
                'processed_at' => microtime(true)
            ]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        // Not used
    }
    
    public function runConcurrentSimulation(): array
    {
        $this->setup();
        
        echo "=== PivotPHP Core Concurrent Request Simulation ===\n";
        echo "PHP " . PHP_VERSION . " | " . date('Y-m-d H:i:s') . "\n\n";
        
        $results = [];
        $concurrencyLevels = [1, 10, 50, 100];
        
        foreach ($concurrencyLevels as $concurrency) {
            echo "Simulating {$concurrency} concurrent requests:\n";
            
            $results[$concurrency] = [
                'simple' => $this->simulateConcurrent('GET', '/concurrent/simple', $concurrency, 100),
                'data' => $this->simulateConcurrent('GET', '/concurrent/data', $concurrency, 100),
                'heavy' => $this->simulateConcurrent('POST', '/concurrent/heavy', $concurrency, 50)
            ];
            
            $this->displayResults($concurrency, $results[$concurrency]);
            echo "\n";
        }
        
        // Performance comparison
        echo "=== CONCURRENCY SCALING ===\n";
        echo str_repeat("-", 60) . "\n";
        echo sprintf("%-20s %10s %10s %10s\n", "Endpoint", "1 conn", "10 conn", "100 conn");
        echo str_repeat("-", 60) . "\n";
        
        foreach (['simple', 'data', 'heavy'] as $endpoint) {
            echo sprintf(
                "%-20s %10s %10s %10s\n",
                ucfirst($endpoint),
                isset($results[1][$endpoint]) ? number_format($results[1][$endpoint]['throughput'], 2) . " r/s" : "-",
                isset($results[10][$endpoint]) ? number_format($results[10][$endpoint]['throughput'], 2) . " r/s" : "-",
                isset($results[100][$endpoint]) ? number_format($results[100][$endpoint]['throughput'], 2) . " r/s" : "-"
            );
        }
        
        return $results;
    }
    
    private function simulateConcurrent(string $method, string $endpoint, int $concurrency, int $requestsPerConnection): array
    {
        echo "  Testing {$endpoint}... ";
        
        $totalRequests = $concurrency * $requestsPerConnection;
        $allLatencies = [];
        $start = microtime(true);
        
        // Simulate concurrent execution by interleaving requests
        for ($round = 0; $round < $requestsPerConnection; $round++) {
            $roundLatencies = [];
            
            // Simulate all concurrent connections making a request
            for ($conn = 0; $conn < $concurrency; $conn++) {
                $reqStart = microtime(true);
                $this->makeRequest($method, $endpoint);
                $reqEnd = microtime(true);
                
                $latency = ($reqEnd - $reqStart) * 1000;
                $roundLatencies[] = $latency;
                $allLatencies[] = $latency;
                
                // Simulate concurrency overhead
                if ($concurrency > 1) {
                    usleep(10); // 0.01ms overhead per concurrent request
                }
            }
            
            // Progress indicator
            if ($requestsPerConnection >= 10 && $round % 10 === 0 && $round > 0) {
                echo ".";
            }
        }
        
        $end = microtime(true);
        $duration = $end - $start;
        
        echo " Done!\n";
        
        // Calculate metrics
        sort($allLatencies);
        $successCount = count($allLatencies);
        
        return [
            'concurrency' => $concurrency,
            'total_requests' => $totalRequests,
            'requests_per_connection' => $requestsPerConnection,
            'duration_s' => round($duration, 3),
            'throughput' => round($totalRequests / $duration, 2),
            'latency' => [
                'avg' => round(array_sum($allLatencies) / $successCount, 3),
                'min' => round(min($allLatencies), 3),
                'max' => round(max($allLatencies), 3),
                'p50' => $this->percentile($allLatencies, 50),
                'p95' => $this->percentile($allLatencies, 95),
                'p99' => $this->percentile($allLatencies, 99)
            ],
            'concurrency_efficiency' => $concurrency > 1 ? 
                round(($totalRequests / $duration) / ($totalRequests / $duration / $concurrency) * 100, 2) : 100
        ];
    }
    
    private function makeRequest(string $method, string $endpoint): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $endpoint;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($method === 'POST') {
            $_POST = [
                'test' => 'concurrent',
                'value' => rand(1, 100),
                'timestamp' => microtime(true)
            ];
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        } else {
            $_POST = [];
            unset($_SERVER['CONTENT_TYPE']);
        }
        
        // Suppress output
        ob_start();
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
        ob_end_clean();
    }
    
    private function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return round($values[$index] ?? 0, 3);
    }
    
    private function displayResults(int $concurrency, array $results): void
    {
        echo "\nResults for {$concurrency} concurrent connection(s):\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-15s %12s %12s %12s %12s %15s\n", 
            "Endpoint", "Throughput", "Avg Latency", "P95", "P99", "Efficiency"
        );
        echo str_repeat("-", 80) . "\n";
        
        foreach (['simple', 'data', 'heavy'] as $type) {
            $r = $results[$type];
            echo sprintf(
                "%-15s %12s %12s %12s %12s %15s\n",
                ucfirst($type),
                number_format($r['throughput'], 2) . " req/s",
                number_format($r['latency']['avg'], 2) . " ms",
                number_format($r['latency']['p95'], 2) . " ms",
                number_format($r['latency']['p99'], 2) . " ms",
                $r['concurrency_efficiency'] . "%"
            );
        }
    }
}

// Parse options
$options = getopt('', ['json', 'save']);

// Run tests
$test = new SimpleConcurrentTest($options);
$results = $test->runConcurrentSimulation();

// Save results if requested
if (isset($options['save'])) {
    $filename = __DIR__ . '/../../../results/pivotphp-core-concurrent-' . date('Y-m-d-His') . '.json';
    file_put_contents($filename, json_encode([
        'framework' => 'PivotPHP Core',
        'test' => 'Concurrent Request Simulation',
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION,
        'results' => $results
    ], JSON_PRETTY_PRINT));
    echo "\n\nResults saved to: " . basename($filename) . "\n";
}

// Output JSON if requested
if (isset($options['json'])) {
    echo "\n" . json_encode($results, JSON_PRETTY_PRINT) . "\n";
}