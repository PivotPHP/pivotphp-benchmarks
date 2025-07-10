#!/usr/bin/env php
<?php

/**
 * PivotPHP Core Memory Usage Benchmark
 * Tests memory consumption and optimization
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

function runMemoryBenchmark($name, $iterations, $callback) {
    echo "🚀 $name ($iterations iterations)... ";
    
    // Force garbage collection before test
    gc_collect_cycles();
    
    $startMemory = memory_get_usage(true);
    $startPeakMemory = memory_get_peak_usage(true);
    $start = microtime(true);
    
    $results = [];
    for ($i = 0; $i < $iterations; $i++) {
        $results[] = $callback();
        
        // Periodic memory measurement
        if ($i % 100 === 0) {
            $currentMemory = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);
        }
    }
    
    $end = microtime(true);
    $endMemory = memory_get_usage(true);
    $endPeakMemory = memory_get_peak_usage(true);
    
    // Force garbage collection after test
    unset($results);
    gc_collect_cycles();
    $finalMemory = memory_get_usage(true);
    
    $duration = $end - $start;
    $memoryUsed = $endMemory - $startMemory;
    $peakMemoryIncrease = $endPeakMemory - $startPeakMemory;
    $memoryLeaked = $finalMemory - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec, ";
    echo number_format($memoryUsed / 1024) . " KB used, ";
    echo number_format($peakMemoryIncrease / 1024) . " KB peak\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed,
        'peak_memory_increase' => $peakMemoryIncrease,
        'memory_leaked' => $memoryLeaked,
        'memory_efficiency' => $iterations / ($memoryUsed / 1024), // ops per KB
        'gc_cycles_before' => gc_status()['runs'] ?? 0
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
    echo "===========================================\n";
    echo " PivotPHP Core Memory Benchmark\n";
    echo "===========================================\n\n";
}

// Initial memory baseline
$initialMemory = memory_get_usage(true);
$initialPeakMemory = memory_get_peak_usage(true);

$results = [];

// Test 1: Application Creation Memory Usage
$results['app_creation_memory'] = runMemoryBenchmark(
    'Application Creation Memory', 
    $args['iterations'], 
    function() {
        $app = new Application();
        $app->boot();
        return $app;
    }
);

// Test 2: Large Object Creation
$results['large_object_creation'] = runMemoryBenchmark(
    'Large Object Creation', 
    $args['iterations'] / 2, 
    function() {
        // Create large objects to test memory management
        $largeObject = [
            'id' => uniqid(),
            'timestamp' => microtime(true),
            'data' => range(1, 1000),
            'metadata' => [
                'description' => str_repeat('Large metadata content. ', 100),
                'tags' => array_map(fn($i) => "tag_$i", range(1, 100)),
                'nested' => [
                    'level1' => [
                        'level2' => [
                            'level3' => array_fill(0, 50, 'deep_data')
                        ]
                    ]
                ]
            ],
            'binary_data' => str_repeat('x', 10240), // 10KB of data
            'serialized_data' => serialize(range(1, 500))
        ];
        
        return $largeObject;
    }
);

// Test 3: String Concatenation Memory
$results['string_concatenation_memory'] = runMemoryBenchmark(
    'String Concatenation Memory', 
    $args['iterations'], 
    function() {
        $result = '';
        for ($i = 0; $i < 100; $i++) {
            $result .= "String part $i with some content. ";
        }
        return $result;
    }
);

// Test 4: Array Operations Memory
$results['array_operations_memory'] = runMemoryBenchmark(
    'Array Operations Memory', 
    $args['iterations'], 
    function() {
        $array = range(1, 500);
        
        // Array manipulations
        $mapped = array_map(fn($x) => $x * 2, $array);
        $filtered = array_filter($mapped, fn($x) => $x % 4 === 0);
        $reduced = array_reduce($filtered, fn($carry, $item) => $carry + $item, 0);
        
        return [
            'original_count' => count($array),
            'mapped_count' => count($mapped),
            'filtered_count' => count($filtered),
            'reduced_value' => $reduced
        ];
    }
);

// Test 5: JSON Processing Memory
$results['json_processing_memory'] = runMemoryBenchmark(
    'JSON Processing Memory', 
    $args['iterations'], 
    function() {
        $data = [
            'users' => array_map(function($i) {
                return [
                    'id' => $i,
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'profile' => [
                        'bio' => str_repeat("Bio content for user $i. ", 20),
                        'preferences' => [
                            'theme' => 'dark',
                            'language' => 'en',
                            'notifications' => true
                        ],
                        'activity' => array_map(fn($j) => [
                            'action' => "action_$j",
                            'timestamp' => time() - rand(0, 86400),
                            'data' => range(1, 10)
                        ], range(1, 5))
                    ]
                ];
            }, range(1, 50)),
            'metadata' => [
                'total' => 50,
                'generated_at' => date('Y-m-d H:i:s'),
                'version' => '1.1.0'
            ]
        ];
        
        $json = json_encode($data);
        $decoded = json_decode($json, true);
        
        return [
            'json_size' => strlen($json),
            'decoded_users' => count($decoded['users']),
            'total_activities' => array_sum(array_map(fn($user) => count($user['profile']['activity']), $decoded['users']))
        ];
    }
);

// Test 6: Container Resolution Memory
$app = new Application();
$app->boot();

// Bind various services
$app->bind('service1', function() {
    return ['data' => range(1, 100), 'timestamp' => microtime(true)];
});

$app->bind('service2', function() {
    return (object) ['value' => str_repeat('test data ', 50)];
});

$app->singleton('singleton_service', function() {
    return ['heavy_data' => array_fill(0, 200, 'singleton_data')];
});

$results['container_resolution_memory'] = runMemoryBenchmark(
    'Container Resolution Memory', 
    $args['iterations'], 
    function() use ($app) {
        $services = [
            $app->make('service1'),
            $app->make('service2'),
            $app->make('singleton_service')
        ];
        
        return count($services);
    }
);

// Test 7: Router Memory Usage
$router = $app->make('PivotPHP\\Core\\Routing\\Router');

$results['router_memory_usage'] = runMemoryBenchmark(
    'Router Memory Usage', 
    $args['iterations'] / 4, 
    function() use ($router) {
        static $counter = 0;
        $counter++;
        
        // Register route with closure
        $router->get("/memory/test/$counter", function($request, $response) use ($counter) {
            $data = [
                'route_id' => $counter,
                'timestamp' => microtime(true),
                'payload' => range(1, 50)
            ];
            return $response->json($data);
        });
        
        return $counter;
    }
);

// Test 8: Memory Leak Detection
$results['memory_leak_detection'] = runMemoryBenchmark(
    'Memory Leak Detection', 
    $args['iterations'] / 4, 
    function() {
        // Create objects with potential circular references
        $obj1 = new stdClass();
        $obj2 = new stdClass();
        
        $obj1->data = range(1, 100);
        $obj2->data = range(101, 200);
        
        // Create circular reference
        $obj1->ref = $obj2;
        $obj2->ref = $obj1;
        
        // Process data
        $processed = array_merge($obj1->data, $obj2->data);
        
        // Break circular reference
        unset($obj1->ref);
        unset($obj2->ref);
        
        return count($processed);
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'memory',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'memory_baseline' => [
            'initial_memory' => $initialMemory,
            'initial_peak' => $initialPeakMemory,
            'final_memory' => memory_get_usage(true),
            'final_peak' => memory_get_peak_usage(true)
        ],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'total_memory_used' => array_sum(array_column($results, 'memory_used')),
        'avg_memory_efficiency' => array_sum(array_column($results, 'memory_efficiency')) / count($results),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 Memory Performance Summary:\n";
    echo "==============================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec | %6s KB | %4.1f ops/KB\n", 
            $test, 
            number_format($result['ops_per_second']),
            number_format($result['memory_used'] / 1024, 1),
            $result['memory_efficiency']
        );
    }
    
    $finalMemory = memory_get_usage(true);
    $finalPeakMemory = memory_get_peak_usage(true);
    $totalMemoryUsed = array_sum(array_column($results, 'memory_used'));
    $totalMemoryLeaked = array_sum(array_column($results, 'memory_leaked'));
    $avgMemoryEfficiency = array_sum(array_column($results, 'memory_efficiency')) / count($results);
    
    echo "\n🏆 Memory Statistics:\n";
    echo "====================\n";
    echo "Initial Memory:     " . number_format($initialMemory / 1024 / 1024, 2) . " MB\n";
    echo "Final Memory:       " . number_format($finalMemory / 1024 / 1024, 2) . " MB\n";
    echo "Peak Memory:        " . number_format($finalPeakMemory / 1024 / 1024, 2) . " MB\n";
    echo "Total Memory Used:  " . number_format($totalMemoryUsed / 1024, 1) . " KB\n";
    echo "Memory Leaked:      " . number_format($totalMemoryLeaked / 1024, 1) . " KB\n";
    echo "Avg Efficiency:     " . number_format($avgMemoryEfficiency, 1) . " ops/KB\n";
    
    echo "\n🎯 Framework: PivotPHP Core v1.1.0\n";
    echo "⚡ Total Memory Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "💾 Memory Status: " . ($totalMemoryLeaked < 1024000 ? "EFFICIENT ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'memory',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'memory_baseline' => [
            'initial_memory' => $initialMemory,
            'initial_peak' => $initialPeakMemory,
            'final_memory' => $finalMemory,
            'final_peak' => $finalPeakMemory
        ],
        'results' => $results,
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'total_memory_used' => $totalMemoryUsed,
            'total_memory_leaked' => $totalMemoryLeaked,
            'avg_memory_efficiency' => $avgMemoryEfficiency
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-core-memory-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ Memory benchmark completed successfully!\n";
}