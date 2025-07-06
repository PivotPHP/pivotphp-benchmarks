#!/usr/bin/env php
<?php

/**
 * Simple Route Benchmark
 * 
 * Tests the performance of basic route matching and handling
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Helix\Core\Application;

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);
$iterations = isset($options['iterations']) ? (int)$options['iterations'] : 10000;
$warmup = isset($options['warmup']) ? (int)$options['warmup'] : 1000;
$jsonOutput = isset($options['json']);

// Create application instance
$app = new Application();

// Setup routes
for ($i = 0; $i < 100; $i++) {
    $app->get("/route-{$i}", function($request, $response) use ($i) {
        return $response->json(['route' => $i]);
    });
}

// Add a wildcard route
$app->get('/api/*', function($request, $response) {
    return $response->json(['api' => true]);
});

// Warmup phase
if (!$jsonOutput) {
    echo "Warming up with {$warmup} iterations...\n";
}

for ($i = 0; $i < $warmup; $i++) {
    $request = createMockRequest('GET', '/route-' . rand(0, 99));
    $app->handle($request);
}

// Benchmark phase
if (!$jsonOutput) {
    echo "Running benchmark with {$iterations} iterations...\n";
}

$startTime = microtime(true);
$startMemory = memory_get_usage();
$peakMemory = 0;

for ($i = 0; $i < $iterations; $i++) {
    $routeNum = rand(0, 99);
    $request = createMockRequest('GET', "/route-{$routeNum}");
    $response = $app->handle($request);
    
    $currentMemory = memory_get_usage();
    if ($currentMemory > $peakMemory) {
        $peakMemory = $currentMemory;
    }
}

$endTime = microtime(true);
$endMemory = memory_get_usage();

// Calculate results
$totalTime = $endTime - $startTime;
$opsPerSecond = $iterations / $totalTime;
$avgTimePerOp = ($totalTime * 1000) / $iterations; // in milliseconds
$memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // in MB
$peakMemoryMB = $peakMemory / 1024 / 1024; // in MB

// Prepare results
$results = [
    'ops_per_second' => round($opsPerSecond),
    'avg_time_ms' => round($avgTimePerOp, 4),
    'memory_used_mb' => round($memoryUsed, 2),
    'memory_peak_mb' => round($peakMemoryMB, 2),
    'total_time_seconds' => round($totalTime, 2),
    'iterations' => $iterations,
    'warmup' => $warmup,
    'routes_count' => 100
];

// Output results
if ($jsonOutput) {
    echo json_encode($results);
} else {
    echo "\nBenchmark Results:\n";
    echo "==================\n";
    echo "Operations/second: " . number_format($results['ops_per_second']) . "\n";
    echo "Avg time per op: {$results['avg_time_ms']} ms\n";
    echo "Memory used: {$results['memory_used_mb']} MB\n";
    echo "Peak memory: {$results['memory_peak_mb']} MB\n";
    echo "Total time: {$results['total_time_seconds']} seconds\n";
}

/**
 * Create a mock request object for benchmarking
 */
function createMockRequest(string $method, string $uri): object
{
    return new class($method, $uri) {
        private string $method;
        private string $uri;
        
        public function __construct(string $method, string $uri)
        {
            $this->method = $method;
            $this->uri = $uri;
        }
        
        public function getMethod(): string
        {
            return $this->method;
        }
        
        public function getUri(): object
        {
            return new class($this->uri) {
                private string $path;
                
                public function __construct(string $uri)
                {
                    $this->path = parse_url($uri, PHP_URL_PATH);
                }
                
                public function getPath(): string
                {
                    return $this->path;
                }
            };
        }
    };
}