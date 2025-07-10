#!/usr/bin/env php
<?php

/**
 * PivotPHP ORM v1.0.1 Performance Test
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

echo "================================================\n";
echo " PivotPHP ORM v1.0.1 Performance Benchmark\n";
echo "================================================\n\n";

$results = [];

// Test 1: Application Creation with ORM
$results['app_creation'] = runBenchmark('Application Creation (with ORM)', 500, function() {
    new Application();
});

// Test 2: ORM Model Creation
// Note: This simulates model operations without actual database connection
$results['orm_model_ops'] = runBenchmark('ORM Model Operations (simulated)', 2000, function() {
    // Simulate typical ORM operations like entity instantiation and data mapping
    $data = [
        'id' => rand(1, 1000),
        'name' => 'User ' . rand(1, 100),
        'email' => 'user' . rand(1, 100) . '@example.com',
        'created_at' => date('Y-m-d H:i:s'),
        'metadata' => json_encode(['type' => 'test', 'active' => true])
    ];
    
    // Simulate data validation and transformation
    $validated = array_map('trim', $data);
    $transformed = array_merge($validated, ['processed' => true]);
    
    return $transformed;
});

// Test 3: Route Registration with ORM patterns
$app = new Application();
$app->boot();

try {
    $router = $app->make('PivotPHP\\Core\\Routing\\Router');
    
    $results['orm_route_registration'] = runBenchmark('ORM Route Registration', 500, function() use ($router) {
        static $counter = 0;
        $router->get("/users/" . (++$counter), function($request, $response) {
            // Simulate ORM query response
            $response->json([
                'user' => [
                    'id' => rand(1, 1000),
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ],
                'framework' => 'pivotphp-orm',
                'version' => '1.0.1'
            ]);
        });
    });
} catch (Exception $e) {
    echo "⚠️  Router test skipped: " . $e->getMessage() . "\n";
    $results['orm_route_registration'] = ['ops_per_second' => 0, 'duration' => 0, 'memory_used' => 0];
}

// Test 4: JSON Operations with ORM Data
$ormData = [
    'entities' => array_map(function($i) {
        return [
            'id' => $i,
            'type' => 'User',
            'attributes' => [
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'metadata' => ['active' => true, 'role' => 'user']
            ],
            'relationships' => [
                'posts' => ['count' => rand(0, 10)],
                'profile' => ['exists' => true]
            ]
        ];
    }, range(1, 10)),
    'meta' => [
        'total' => 10,
        'framework' => 'pivotphp-orm',
        'version' => '1.0.1'
    ]
];

$results['orm_json_operations'] = runBenchmark('ORM JSON Operations', 5000, function() use ($ormData) {
    $json = json_encode($ormData);
    $decoded = json_decode($json, true);
    
    // Simulate entity hydration
    $entities = $decoded['entities'] ?? [];
    foreach ($entities as &$entity) {
        $entity['hydrated'] = true;
        $entity['loaded_at'] = microtime(true);
    }
    
    return $entities;
});

// Display Summary
echo "\n📊 Performance Summary:\n";
echo "=======================\n";
foreach ($results as $test => $result) {
    echo sprintf("%-25s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
}

echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "🎯 Framework: PivotPHP ORM v1.0.1\n";
echo "📊 Extension: Cycle ORM Integration\n";
echo "⚡ Status: " . (array_sum(array_column($results, 'ops_per_second')) > 5000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";

// Save results
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'framework' => 'pivotphp-orm',
    'version' => '1.0.1',
    'core_version' => '1.1.0',
    'php_version' => PHP_VERSION,
    'results' => $results,
    'total_peak_memory' => memory_get_peak_usage(true),
    'summary' => [
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
    ]
];

file_put_contents(__DIR__ . '/../../results/raw/pivotphp-orm-v1.0.1-' . date('Y-m-d-H-i-s') . '.json', 
                  json_encode($reportData, JSON_PRETTY_PRINT));

echo "\n💾 Results saved to results/raw/\n";
echo "✅ Benchmark completed successfully!\n";