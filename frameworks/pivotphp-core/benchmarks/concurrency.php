#!/usr/bin/env php
<?php

/**
 * PivotPHP Core Concurrency Benchmark
 * Tests concurrent request handling capabilities
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

function runBenchmark($name, $iterations, $concurrency, $callback) {
    echo "🚀 $name ($iterations iterations, $concurrency concurrent)... ";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    // Simulate concurrent execution using process forks
    $pids = [];
    $iterationsPerProcess = intval($iterations / $concurrency);
    
    for ($proc = 0; $proc < $concurrency; $proc++) {
        $pid = pcntl_fork();
        if ($pid == 0) {
            // Child process
            for ($i = 0; $i < $iterationsPerProcess; $i++) {
                $callback();
            }
            exit(0);
        } else {
            $pids[] = $pid;
        }
    }
    
    // Wait for all child processes
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed,
        'concurrency' => $concurrency
    ];
}

function parseArgs($argv) {
    $args = ['json' => false, 'iterations' => 1000, 'warmup' => 100];
    
    foreach ($argv as $arg) {
        if ($arg === '--json') $args['json'] = true;
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 14));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "==============================================\n";
    echo " PivotPHP Core Concurrency Benchmark\n";
    echo "==============================================\n\n";
}

$results = [];

// Test 1: Concurrent Application Creation
$results['concurrent_app_creation'] = runBenchmark(
    'Concurrent Application Creation', 
    $args['iterations'], 
    4, 
    function() {
        $app = new Application();
        $app->boot();
        return $app;
    }
);

// Test 2: Concurrent Route Registration
$app = new Application();
$app->boot();
$router = $app->make('PivotPHP\\Core\\Routing\\Router');

$results['concurrent_route_registration'] = runBenchmark(
    'Concurrent Route Registration', 
    $args['iterations'], 
    8, 
    function() use ($router) {
        static $counter = 0;
        $counter++;
        $router->get("/concurrent/$counter", function($request, $response) {
            $response->json(['concurrent' => true, 'id' => $counter]);
        });
    }
);

// Test 3: Concurrent Container Resolution
$app->bind('test_service', function() {
    return ['timestamp' => microtime(true), 'data' => range(1, 100)];
});

$results['concurrent_container_resolution'] = runBenchmark(
    'Concurrent Container Resolution', 
    $args['iterations'], 
    16, 
    function() use ($app) {
        $service = $app->make('test_service');
        return $service;
    }
);

// Test 4: Concurrent JSON Processing
$testData = [
    'request_id' => uniqid(),
    'timestamp' => time(),
    'data' => range(1, 500),
    'metadata' => [
        'version' => '1.1.0',
        'framework' => 'pivotphp-core',
        'benchmark' => 'concurrency'
    ]
];

$results['concurrent_json_processing'] = runBenchmark(
    'Concurrent JSON Processing', 
    $args['iterations'], 
    12, 
    function() use ($testData) {
        $json = json_encode($testData);
        $decoded = json_decode($json, true);
        
        // Simulate processing
        $processed = array_merge($decoded, [
            'processed_at' => microtime(true),
            'hash' => md5($json)
        ]);
        
        return $processed;
    }
);

// Test 5: Concurrent Middleware Simulation
$results['concurrent_middleware_processing'] = runBenchmark(
    'Concurrent Middleware Processing', 
    $args['iterations'], 
    6, 
    function() {
        // Simulate middleware chain processing
        $request = ['method' => 'GET', 'uri' => '/test', 'headers' => []];
        
        // Simulate auth middleware
        $request['user'] = ['id' => rand(1, 1000), 'role' => 'user'];
        
        // Simulate CORS middleware
        $request['cors'] = true;
        
        // Simulate rate limiting
        $request['rate_limit'] = ['remaining' => 100, 'reset' => time() + 3600];
        
        // Simulate validation
        $request['validated'] = true;
        
        return $request;
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'concurrency',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 Concurrency Performance Summary:\n";
    echo "===================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec (x%d concurrent)\n", 
            $test, 
            number_format($result['ops_per_second']),
            $result['concurrency']
        );
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP Core v1.1.0\n";
    echo "⚡ Total Concurrent Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🔄 Concurrency Status: " . (array_sum(array_column($results, 'ops_per_second')) > 5000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'concurrency',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results)),
            'max_concurrency' => max(array_column($results, 'concurrency'))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-core-concurrency-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ Concurrency benchmark completed successfully!\n";
}