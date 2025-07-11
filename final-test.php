#!/usr/bin/env php
<?php

/**
 * Final PivotPHP Performance Test - Using Service Providers
 */

require_once __DIR__ . '/frameworks/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;

function benchmarkPivotPHP($iterations = 5000) {
    echo "Testing PivotPHP Core v1.1.0 Performance (Final Test)\n";
    echo "==================================================\n\n";
    
    try {
        // Bootstrap the application
        $app = require __DIR__ . '/frameworks/pivotphp-core/bootstrap/app.php';
        
        echo "✓ Application bootstrapped successfully\n";
        
        // Test routes
        $routes = [
            '/',
            '/users',
            '/users/42'
        ];
        
        // Warmup
        echo "Warmup: 500 iterations...";
        $warmupSuccess = 0;
        for ($i = 0; $i < 500; $i++) {
            $path = $routes[array_rand($routes)];
            $request = new Request('GET', $path, $path);
            
            try {
                $response = $app->handle($request);
                if ($response && $response->getStatusCode() < 400) {
                    $warmupSuccess++;
                }
            } catch (\Exception $e) {
                // Skip warmup errors
            }
        }
        echo " Done! ({$warmupSuccess}/500 successful)\n";
        
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
                if ($response && $response->getStatusCode() < 400) {
                    $successCount++;
                } else {
                    $errorCount++;
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
        echo "Framework: PivotPHP Core v1.1.0\n";
        echo "Total iterations: " . number_format($iterations) . "\n";
        echo "Successful requests: " . number_format($successCount) . "\n";
        echo "Failed requests: " . number_format($errorCount) . "\n";
        echo "Success rate: " . round(($successCount / $iterations) * 100, 2) . "%\n";
        echo "Total time: " . round($totalTime, 3) . " seconds\n";
        echo "Operations/sec: " . number_format(round($opsPerSecond, 2)) . "\n";
        echo "Average response time: " . round(($totalTime / $successCount) * 1000, 2) . " ms\n";
        echo "Memory usage: " . round($memoryUsed, 2) . " MB\n";
        echo "Peak memory: " . round($endMemory / 1024 / 1024, 2) . " MB\n";
        
        return [
            'framework' => 'pivotphp-core',
            'version' => '1.1.0',
            'total_iterations' => $iterations,
            'successful_requests' => $successCount,
            'failed_requests' => $errorCount,
            'success_rate' => round(($successCount / $iterations) * 100, 2),
            'total_time' => round($totalTime, 3),
            'ops_per_second' => round($opsPerSecond, 2),
            'avg_response_time_ms' => round(($totalTime / max($successCount, 1)) * 1000, 2),
            'memory_mb' => round($memoryUsed, 2),
            'peak_memory_mb' => round($endMemory / 1024 / 1024, 2)
        ];
        
    } catch (\Exception $e) {
        echo "✗ Error during bootstrap: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        return null;
    }
}

// Run benchmark
$results = benchmarkPivotPHP(3000);

if ($results) {
    // Save results
    $resultsFile = __DIR__ . '/results/pivotphp-core-final-test-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($resultsFile), 0755, true);
    file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT));
    
    echo "\nResults saved to: {$resultsFile}\n";
    
    // Performance classification
    if ($results['ops_per_second'] > 10000) {
        echo "\n🚀 Performance: EXCELLENT (>10K ops/sec)\n";
    } elseif ($results['ops_per_second'] > 5000) {
        echo "\n⚡ Performance: VERY GOOD (>5K ops/sec)\n";
    } elseif ($results['ops_per_second'] > 1000) {
        echo "\n✅ Performance: GOOD (>1K ops/sec)\n";
    } else {
        echo "\n⚠️  Performance: NEEDS OPTIMIZATION (<1K ops/sec)\n";
    }
} else {
    echo "\nBenchmark failed to complete.\n";
}