#!/usr/bin/env php
<?php

/**
 * Simple PivotPHP Performance Test - Minimal Setup
 */

require_once __DIR__ . '/frameworks/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;

function runSimpleBenchmark($iterations = 3000) {
    echo "PivotPHP Core v1.1.0 Simple Performance Test\n";
    echo "===========================================\n\n";
    
    try {
        // Bootstrap the application with simple setup
        $app = require __DIR__ . '/frameworks/pivotphp-core/bootstrap/simple-app.php';
        
        echo "✓ Application bootstrapped successfully\n";
        
        // Test routes
        $routes = [
            '/',
            '/health',
            '/users',
            '/users/42'
        ];
        
        // Warmup
        echo "Warmup: 200 iterations...";
        $warmupSuccess = 0;
        for ($i = 0; $i < 200; $i++) {
            $path = $routes[array_rand($routes)];
            $request = new Request('GET', $path, $path);
            
            try {
                $response = $app->handle($request);
                if ($response && $response->getStatusCode() === 200) {
                    $warmupSuccess++;
                }
            } catch (\Exception $e) {
                // Skip warmup errors
            }
        }
        echo " Done! ({$warmupSuccess}/200 successful)\n";
        
        if ($warmupSuccess < 50) {
            echo "⚠️  Low warmup success rate. Continuing anyway...\n";
        }
        
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
                if ($response && $response->getStatusCode() === 200) {
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
        
        echo "Performance Results:\n";
        echo "===================\n";
        echo "Framework: PivotPHP Core v1.1.0\n";
        echo "Total iterations: " . number_format($iterations) . "\n";
        echo "Successful requests: " . number_format($successCount) . "\n";
        echo "Failed requests: " . number_format($errorCount) . "\n";
        echo "Success rate: " . round(($successCount / $iterations) * 100, 2) . "%\n";
        echo "Total time: " . round($totalTime, 3) . " seconds\n";
        echo "Operations/sec: " . number_format(round($opsPerSecond, 2)) . "\n";
        echo "Average response time: " . round(($totalTime / max($successCount, 1)) * 1000, 2) . " ms\n";
        echo "Memory used: " . round($memoryUsed, 2) . " MB\n";
        echo "Peak memory: " . round($endMemory / 1024 / 1024, 2) . " MB\n";
        
        // Performance classification
        echo "\nPerformance Assessment:\n";
        if ($opsPerSecond > 10000) {
            echo "🚀 EXCELLENT: >10K ops/sec - High-performance framework\n";
        } elseif ($opsPerSecond > 5000) {
            echo "⚡ VERY GOOD: >5K ops/sec - Strong performance\n";
        } elseif ($opsPerSecond > 1000) {
            echo "✅ GOOD: >1K ops/sec - Acceptable performance\n";
        } elseif ($opsPerSecond > 500) {
            echo "⚠️  MODERATE: >500 ops/sec - Room for improvement\n";
        } else {
            echo "❌ POOR: <500 ops/sec - Needs optimization\n";
        }
        
        $results = [
            'framework' => 'pivotphp-core',
            'version' => '1.1.0',
            'test_type' => 'simple_benchmark',
            'timestamp' => date('Y-m-d H:i:s'),
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
        
        // Save results
        $resultsFile = __DIR__ . '/results/pivotphp-simple-benchmark-' . date('Y-m-d-H-i-s') . '.json';
        @mkdir(dirname($resultsFile), 0755, true);
        file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT));
        
        echo "\nResults saved to: {$resultsFile}\n";
        
        return $results;
        
    } catch (\Exception $e) {
        echo "✗ Error during benchmark: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        return null;
    }
}

// Run benchmark
echo "Starting PivotPHP benchmark...\n\n";
$results = runSimpleBenchmark(2000);

if ($results) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "BENCHMARK COMPLETED SUCCESSFULLY\n";
    echo str_repeat("=", 50) . "\n";
} else {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "BENCHMARK FAILED\n";
    echo str_repeat("=", 50) . "\n";
}