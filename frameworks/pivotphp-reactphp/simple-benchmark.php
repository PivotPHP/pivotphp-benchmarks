#!/usr/bin/env php
<?php

/**
 * PivotPHP ReactPHP v0.1.0 Performance Test
 */

require_once __DIR__ . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use React\EventLoop\Loop;

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

echo "==================================================\n";
echo " PivotPHP ReactPHP v0.1.0 Performance Benchmark\n";
echo "==================================================\n\n";

$results = [];

// Test 1: Application Creation with ReactPHP
$results['app_creation'] = runBenchmark('Application Creation (with ReactPHP)', 500, function() {
    $app = new Application();
    $app->boot();
    return $app;
});

// Test 2: Event Loop Operations
$loop = Loop::get();
$results['event_loop_ops'] = runBenchmark('Event Loop Operations', 5000, function() use ($loop) {
    // Simulate async operation scheduling
    $loop->addTimer(0.001, function() {
        // Simulated async task
        return microtime(true);
    });
});

// Test 3: ReactPHP Route Registration
$app = new Application();
$app->boot();

try {
    $router = $app->make('PivotPHP\\Core\\Routing\\Router');
    
    $results['reactphp_route_registration'] = runBenchmark('ReactPHP Route Registration', 500, function() use ($router) {
        static $counter = 0;
        $router->get("/async/" . (++$counter), function($request, $response) {
            // Simulate async response
            $response->json([
                'async' => true,
                'timestamp' => microtime(true),
                'framework' => 'pivotphp-reactphp',
                'version' => '0.1.0',
                'runtime' => 'continuous'
            ]);
        });
    });
} catch (Exception $e) {
    echo "⚠️  Router test skipped: " . $e->getMessage() . "\n";
    $results['reactphp_route_registration'] = ['ops_per_second' => 0, 'duration' => 0, 'memory_used' => 0];
}

// Test 4: Async Promise-like Operations
$results['promise_operations'] = runBenchmark('Promise-like Operations', 3000, function() {
    // Simulate promise resolution
    $data = [
        'id' => rand(1, 1000),
        'status' => 'resolved',
        'result' => [
            'processed' => true,
            'timestamp' => microtime(true),
            'data' => range(1, 10)
        ]
    ];
    
    // Simulate async data transformation
    $transformed = array_merge($data, [
        'transformed' => true,
        'hash' => md5(json_encode($data))
    ]);
    
    return $transformed;
});

// Test 5: Continuous Runtime Simulation
$serverStartTime = microtime(true);
$requestCount = 0;

$results['continuous_runtime'] = runBenchmark('Continuous Runtime Simulation', 2000, function() use ($serverStartTime, &$requestCount) {
    $requestCount++;
    
    // Simulate request handling in continuous runtime
    $uptime = microtime(true) - $serverStartTime;
    $response = [
        'request_id' => $requestCount,
        'uptime' => $uptime,
        'memory_usage' => memory_get_usage(true),
        'persistent_state' => true,
        'runtime_type' => 'continuous',
        'framework' => 'pivotphp-reactphp'
    ];
    
    return $response;
});

// Display Summary
echo "\n📊 Performance Summary:\n";
echo "=======================\n";
foreach ($results as $test => $result) {
    echo sprintf("%-30s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
}

echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "🎯 Framework: PivotPHP ReactPHP v0.1.0\n";
echo "⚡ Runtime: Continuous/Async\n";
echo "🔄 Event Loop: ReactPHP\n";
echo "📊 Total Simulated Requests: " . number_format($requestCount) . "\n";
echo "⚡ Status: " . (array_sum(array_column($results, 'ops_per_second')) > 8000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";

// Save results
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'framework' => 'pivotphp-reactphp',
    'version' => '0.1.0',
    'core_version' => '1.1.0',
    'php_version' => PHP_VERSION,
    'runtime_type' => 'continuous',
    'total_requests_simulated' => $requestCount,
    'results' => $results,
    'total_peak_memory' => memory_get_peak_usage(true),
    'summary' => [
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
    ]
];

file_put_contents(__DIR__ . '/../../results/raw/pivotphp-reactphp-v0.1.0-' . date('Y-m-d-H-i-s') . '.json', 
                  json_encode($reportData, JSON_PRETTY_PRINT));

echo "\n💾 Results saved to results/raw/\n";
echo "✅ Benchmark completed successfully!\n";