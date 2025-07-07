#!/usr/bin/env php
<?php

/**
 * Load Test Runner
 * 
 * Executes load tests on PivotPHP with different concurrency levels
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;

class LoadTestRunner
{
    private Application $app;
    private array $results = [];
    
    public function __construct()
    {
        $this->setupApplication();
    }
    
    private function setupApplication(): void
    {
        $this->app = new Application();
        
        // Simple JSON endpoint
        $this->app->get('/api/status', function($request, $response) {
            return $response->json([
                'status' => 'ok',
                'timestamp' => microtime(true),
                'memory' => memory_get_usage(true)
            ]);
        });
        
        // Data list endpoint
        $this->app->get('/api/items', function($request, $response) {
            $items = [];
            for ($i = 1; $i <= 20; $i++) {
                $items[] = [
                    'id' => $i,
                    'name' => "Item {$i}",
                    'value' => rand(100, 999),
                    'created' => date('c')
                ];
            }
            return $response->json(['items' => $items, 'count' => count($items)]);
        });
        
        // Echo endpoint (POST)
        $this->app->post('/api/echo', function($request, $response) {
            $data = $request->getParsedBody();
            return $response->json([
                'echo' => $data,
                'received_at' => microtime(true)
            ]);
        });
        
        // Computation endpoint
        $this->app->get('/api/compute', function($request, $response) {
            $sum = 0;
            for ($i = 1; $i <= 100; $i++) {
                $sum += sqrt($i);
            }
            return $response->json(['result' => $sum]);
        });
    }
    
    public function runTest(string $type, int $requests): array
    {
        echo "Running {$type} test with {$requests} requests...\n";
        
        // Warmup
        echo "  Warming up... ";
        for ($i = 0; $i < 100; $i++) {
            $this->makeRequest($type);
        }
        echo "Done\n";
        
        // Actual test
        echo "  Testing... ";
        $times = [];
        $start = microtime(true);
        $startMem = memory_get_usage(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $reqStart = microtime(true);
            $this->makeRequest($type);
            $reqEnd = microtime(true);
            $times[] = ($reqEnd - $reqStart) * 1000; // Convert to ms
            
            if ($i % 100 === 0) {
                echo ".";
            }
        }
        
        $end = microtime(true);
        $peakMem = memory_get_peak_usage(true);
        echo " Done\n";
        
        // Calculate metrics
        $totalTime = $end - $start;
        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);
        
        // Calculate percentiles
        sort($times);
        $p50 = $this->percentile($times, 50);
        $p95 = $this->percentile($times, 95);
        $p99 = $this->percentile($times, 99);
        
        return [
            'type' => $type,
            'requests' => $requests,
            'total_time_s' => round($totalTime, 3),
            'requests_per_second' => round($requests / $totalTime, 2),
            'latency_ms' => [
                'min' => round($minTime, 3),
                'avg' => round($avgTime, 3),
                'max' => round($maxTime, 3),
                'p50' => round($p50, 3),
                'p95' => round($p95, 3),
                'p99' => round($p99, 3)
            ],
            'memory_mb' => [
                'used' => round(($peakMem - $startMem) / 1024 / 1024, 2),
                'peak' => round($peakMem / 1024 / 1024, 2)
            ]
        ];
    }
    
    private function makeRequest(string $type): void
    {
        $endpoints = [
            'simple' => ['GET', '/api/status'],
            'data' => ['GET', '/api/items'],
            'post' => ['POST', '/api/echo'],
            'compute' => ['GET', '/api/compute']
        ];
        
        $endpoint = $endpoints[$type] ?? $endpoints['simple'];
        
        // Reset globals
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = $endpoint[0];
        $_SERVER['REQUEST_URI'] = $endpoint[1];
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($endpoint[0] === 'POST') {
            $_POST = [
                'message' => 'Test data',
                'timestamp' => time(),
                'random' => rand(1000, 9999)
            ];
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        }
        
        // Capture output to prevent display
        ob_start();
        try {
            $request = Request::createFromGlobals();
            $response = $this->app->handle($request);
        } catch (\Exception $e) {
            // Ignore
        }
        ob_end_clean();
    }
    
    private function percentile(array $values, int $percentile): float
    {
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[$index] ?? 0;
    }
    
    public function runAllTests(): void
    {
        $types = ['simple', 'data', 'post', 'compute'];
        $loads = [500, 1000, 1500];
        
        echo "\n=== PivotPHP Load Test ===\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($types as $type) {
            $this->results[$type] = [];
            foreach ($loads as $load) {
                $this->results[$type][$load] = $this->runTest($type, $load);
                echo "\n";
            }
        }
        
        $this->displayResults();
        $this->saveResults();
    }
    
    private function displayResults(): void
    {
        echo "\n=== Test Results Summary ===\n\n";
        
        foreach ($this->results as $type => $loads) {
            echo strtoupper($type) . " Endpoint Results:\n";
            echo str_repeat("-", 60) . "\n";
            
            foreach ($loads as $result) {
                echo sprintf(
                    "Load %4d: %7.2f req/s | Latency: avg %.2fms, p95 %.2fms, p99 %.2fms | Mem: %.1fMB\n",
                    $result['requests'],
                    $result['requests_per_second'],
                    $result['latency_ms']['avg'],
                    $result['latency_ms']['p95'],
                    $result['latency_ms']['p99'],
                    $result['memory_mb']['peak']
                );
            }
            echo "\n";
        }
    }
    
    private function saveResults(): void
    {
        $filename = __DIR__ . '/../results/load-test-' . date('Y-m-d-His') . '.json';
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'framework' => 'PivotPHP Core',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
            'results' => $this->results
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "Results saved to: " . basename($filename) . "\n";
    }
}

// Run tests
$runner = new LoadTestRunner();
$runner->runAllTests();