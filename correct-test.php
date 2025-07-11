#!/usr/bin/env php
<?php

/**
 * Correct PivotPHP Performance Test - Based on pivotphp-validation reference
 */

require_once __DIR__ . '/frameworks/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Routing\Router;

function benchmarkRoutingCorrect($iterations = 10000) {
    echo "Testing PivotPHP Core v1.1.0 Performance (Corrected Implementation)\n";
    echo "===================================================================\n\n";
    
    // Create application with base path
    $app = new Application(__DIR__ . '/frameworks/pivotphp-core');
    
    // Boot the application to initialize services
    $app->boot();
    
    // Get router instance from container
    $router = $app->make(Router::class);
    
    // Add simple routes using correct syntax
    $router->get('/', function($request, $response, callable $next) {
        return new \PivotPHP\Core\Http\Response(200, [], json_encode(['status' => 'ok']));
    });
    
    $router->get('/users/:id', function($request, $response, callable $next) {
        $args = $request->getAttribute('route')->getArguments();
        return new \PivotPHP\Core\Http\Response(200, [], json_encode(['user_id' => $args['id']]));
    });
    
    $router->get('/posts/:id/comments/:comment_id', function($request, $response, callable $next) {
        $args = $request->getAttribute('route')->getArguments();
        return new \PivotPHP\Core\Http\Response(200, [], json_encode([
            'post_id' => $args['id'],
            'comment_id' => $args['comment_id']
        ]));
    });
    
    // Test routes
    $routes = [
        '/',
        '/users/42',
        '/posts/10/comments/5'
    ];
    
    // Warmup
    echo "Warmup: 1000 iterations...";
    for ($i = 0; $i < 1000; $i++) {
        $path = $routes[array_rand($routes)];
        $request = new Request('GET', $path, $path);
        
        try {
            $response = $app->handle($request);
            if ($response) {
                $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            // Ignore errors during warmup
        }
    }
    echo " Done!\n";
    
    // Benchmark
    echo "Benchmark: {$iterations} iterations...";
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    $successCount = 0;
    $errorCount = 0;
    
    for ($i = 0; $i < $iterations; $i++) {
        $path = $routes[array_rand($routes)];
        $request = new Request('GET', $path, $path);
        
        try {
            $response = $app->handle($request);
            if ($response) {
                $response->getBody()->getContents();
                $successCount++;
            }
        } catch (\Exception $e) {
            $errorCount++;
        }
    }
    
    $endTime = microtime(true);
    $endMemory = memory_get_peak_usage();
    echo " Done!\n\n";
    
    $totalTime = $endTime - $startTime;
    $opsPerSecond = $successCount > 0 ? $successCount / $totalTime : 0;
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
    
    echo "Results:\n";
    echo "--------\n";
    echo "Framework: PivotPHP Core v1.1.0 (Corrected)\n";
    echo "Total iterations: " . number_format($iterations) . "\n";
    echo "Successful requests: " . number_format($successCount) . "\n";
    echo "Failed requests: " . number_format($errorCount) . "\n";
    echo "Success rate: " . round(($successCount / $iterations) * 100, 2) . "%\n";
    echo "Total time: " . round($totalTime, 3) . " seconds\n";
    echo "Operations/sec: " . number_format(round($opsPerSecond, 2)) . "\n";
    echo "Memory usage: " . round($memoryUsed, 2) . " MB\n";
    echo "Peak memory: " . round($endMemory / 1024 / 1024, 2) . " MB\n";
    
    return [
        'framework' => 'pivotphp-core',
        'version' => '1.1.0-corrected',
        'total_iterations' => $iterations,
        'successful_requests' => $successCount,
        'failed_requests' => $errorCount,
        'success_rate' => round(($successCount / $iterations) * 100, 2),
        'total_time' => $totalTime,
        'ops_per_second' => round($opsPerSecond, 2),
        'memory_mb' => round($memoryUsed, 2),
        'peak_memory_mb' => round($endMemory / 1024 / 1024, 2)
    ];
}

// Run benchmark
try {
    $results = benchmarkRoutingCorrect(5000);
    
    // Save results
    $resultsFile = __DIR__ . '/results/pivotphp-core-corrected-test-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($resultsFile), 0755, true);
    file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT));
    
    echo "\nResults saved to: {$resultsFile}\n";
    
} catch (\Exception $e) {
    echo "\nError running benchmark: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}