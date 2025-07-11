#!/usr/bin/env php
<?php

/**
 * Clean PivotPHP Performance Test
 */

require_once __DIR__ . '/frameworks/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;

function benchmarkRouting($iterations = 10000) {
    echo "Testing PivotPHP Core v1.1.0 Performance\n";
    echo "========================================\n\n";
    
    // Create application
    $app = new Application();
    
    // Add simple routes
    $app->get('/', function($request, $response) {
        return $response->json(['status' => 'ok']);
    });
    
    $app->get('/users/{id}', function($request, $response, $args) {
        return $response->json(['user_id' => $args['id']]);
    });
    
    $app->get('/posts/{id}/comments/{comment_id}', function($request, $response, $args) {
        return $response->json([
            'post_id' => $args['id'],
            'comment_id' => $args['comment_id']
        ]);
    });
    
    // Test routes
    $routes = [
        '/'
    ];
    
    // Warmup
    echo "Warmup: 1000 iterations...";
    for ($i = 0; $i < 1000; $i++) {
        $path = $routes[array_rand($routes)];
        $request = new Request('GET', $path, $path);
        $response = $app->handle($request);
        $response->getBody()->getContents();
    }
    echo " Done!\n";
    
    // Benchmark
    echo "Benchmark: {$iterations} iterations...";
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    for ($i = 0; $i < $iterations; $i++) {
        $path = $routes[array_rand($routes)];
        $request = new Request('GET', $path, $path);
        $response = $app->handle($request);
        $response->getBody()->getContents();
    }
    
    $endTime = microtime(true);
    $endMemory = memory_get_peak_usage();
    echo " Done!\n\n";
    
    $totalTime = $endTime - $startTime;
    $opsPerSecond = $iterations / $totalTime;
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
    
    echo "Results:\n";
    echo "--------\n";
    echo "Framework: PivotPHP Core v1.1.0\n";
    echo "Iterations: " . number_format($iterations) . "\n";
    echo "Total time: " . round($totalTime, 3) . " seconds\n";
    echo "Operations/sec: " . number_format(round($opsPerSecond, 2)) . "\n";
    echo "Memory usage: " . round($memoryUsed, 2) . " MB\n";
    echo "Peak memory: " . round($endMemory / 1024 / 1024, 2) . " MB\n";
    
    return [
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'iterations' => $iterations,
        'total_time' => $totalTime,
        'ops_per_second' => round($opsPerSecond, 2),
        'memory_mb' => round($memoryUsed, 2),
        'peak_memory_mb' => round($endMemory / 1024 / 1024, 2)
    ];
}

// Run benchmark
$results = benchmarkRouting(10000);

// Save results
$resultsFile = __DIR__ . '/results/pivotphp-core-clean-test-' . date('Y-m-d-H-i-s') . '.json';
@mkdir(dirname($resultsFile), 0755, true);
file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT));

echo "\nResults saved to: {$resultsFile}\n";