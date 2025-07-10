#!/usr/bin/env php
<?php

/**
 * Simple PivotPHP Core v1.1.0 Performance Test
 */

require_once __DIR__ . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

function runBenchmark($name, $iterations, $callback) {
    echo "🚀 $name ($iterations iterations)... ";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed
    ];
}

echo "==============================================\n";
echo " PivotPHP Core v1.1.0 Performance Benchmark\n";
echo "==============================================\n\n";

$results = [];

// Test 1: Application Creation
$results['app_creation'] = runBenchmark('Application Creation', 1000, function() {
    new Application();
});

// Test 2: Route Registration (using correct PivotPHP pattern)
$app = new Application();
$app->boot(); // Boot the application to initialize services

$router = $app->make('PivotPHP\\Core\\Routing\\Router');
$results['route_registration'] = runBenchmark('Route Registration', 1000, function() use ($router) {
    static $counter = 0;
    $router->get("/test" . (++$counter), function($request, $response) {
        $response->json(['test' => true]);
    });
});

// Test 3: Container Resolution
$app->bind('test', function() {
    return 'test_value';
});

$results['container_resolution'] = runBenchmark('Container Resolution', 10000, function() use ($app) {
    $app->make('test');
});

// Test 4: JSON Encoding/Decoding
$testData = [
    'message' => 'Performance test',
    'timestamp' => time(),
    'data' => range(1, 50),
    'metadata' => [
        'version' => '1.1.0',
        'framework' => 'pivotphp'
    ]
];

$results['json_operations'] = runBenchmark('JSON Operations', 10000, function() use ($testData) {
    $json = json_encode($testData);
    json_decode($json, true);
});

// Display Summary
echo "\n📊 Performance Summary:\n";
echo "=======================\n";
foreach ($results as $test => $result) {
    echo sprintf("%-20s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
}

echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "🎯 Framework: PivotPHP Core v1.1.0\n";
echo "⚡ Status: " . (array_sum(array_column($results, 'ops_per_second')) > 20000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";

// Save results
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'framework' => 'pivotphp-core',
    'version' => '1.1.0',
    'php_version' => PHP_VERSION,
    'results' => $results,
    'total_peak_memory' => memory_get_peak_usage(true),
    'summary' => [
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
    ]
];

file_put_contents(__DIR__ . '/../../results/raw/pivotphp-core-v1.1.0-' . date('Y-m-d-H-i-s') . '.json', 
                  json_encode($reportData, JSON_PRETTY_PRINT));

echo "\n💾 Results saved to results/raw/\n";
echo "✅ Benchmark completed successfully!\n";