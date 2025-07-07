#!/usr/bin/env php
<?php

/**
 * Simple Load Test Benchmark
 * 
 * Tests performance under different load levels
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;

class SimpleLoadTest
{
    private Application $app;
    
    public function __construct()
    {
        $this->setupApplication();
    }
    
    private function setupApplication(): void
    {
        $this->app = new Application();
        
        // Simple endpoints
        $this->app->get('/status', function($request, $response) {
            return $response->json(['status' => 'ok', 'time' => microtime(true)]);
        });
        
        $this->app->get('/data', function($request, $response) {
            $data = [];
            for ($i = 0; $i < 10; $i++) {
                $data[] = ['id' => $i, 'value' => rand(100, 999)];
            }
            return $response->json($data);
        });
        
        $this->app->post('/echo', function($request, $response) {
            return $response->json($request->getParsedBody());
        });
    }
    
    public function runLoadTest(int $requestCount): array
    {
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        for ($i = 0; $i < $requestCount; $i++) {
            $this->makeRequest();
        }
        
        $end = microtime(true);
        $endMemory = memory_get_peak_usage();
        
        $duration = $end - $start;
        
        return [
            'request_count' => $requestCount,
            'duration_seconds' => round($duration, 3),
            'requests_per_second' => round($requestCount / $duration, 2),
            'avg_time_per_request_ms' => round(($duration / $requestCount) * 1000, 3),
            'memory_peak_mb' => round($endMemory / 1024 / 1024, 2)
        ];
    }
    
    private function makeRequest(): void
    {
        $endpoints = [
            ['GET', '/status'],
            ['GET', '/data'],
            ['POST', '/echo']
        ];
        
        $endpoint = $endpoints[array_rand($endpoints)];
        
        $_SERVER['REQUEST_METHOD'] = $endpoint[0];
        $_SERVER['REQUEST_URI'] = $endpoint[1];
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($endpoint[0] === 'POST') {
            $_POST = ['test' => 'data', 'timestamp' => time()];
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        } else {
            $_POST = [];
            unset($_SERVER['CONTENT_TYPE']);
        }
        
        // Disable output for benchmarking
        ob_start();
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
        ob_end_clean();
    }
}

// Run tests
$loads = [500, 1000, 1500];
$results = [];

echo "=== Simple Load Test ===\n\n";

$test = new SimpleLoadTest();

foreach ($loads as $load) {
    echo "Testing with {$load} requests... ";
    $result = $test->runLoadTest($load);
    $results[$load] = $result;
    echo "Done! ({$result['requests_per_second']} req/s)\n";
}

// Output results
if (in_array('--json', $argv)) {
    echo "\n" . json_encode([
        'benchmark' => 'concurrency/simple-load-test',
        'framework' => 'pivotphp-core',
        'timestamp' => date('c'),
        'results' => $results
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo "\n=== Results Summary ===\n\n";
    foreach ($results as $load => $data) {
        echo "Load Level: {$load} requests\n";
        echo "  Duration: {$data['duration_seconds']}s\n";
        echo "  Throughput: {$data['requests_per_second']} req/s\n";
        echo "  Avg Response Time: {$data['avg_time_per_request_ms']} ms\n";
        echo "  Memory Peak: {$data['memory_peak_mb']} MB\n\n";
    }
}