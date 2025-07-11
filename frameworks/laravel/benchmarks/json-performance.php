#!/usr/bin/env php
<?php

/**
 * Laravel JSON Performance Benchmark
 * Tests JSON processing capabilities for APIs
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    $opsPerSecond = $duration > 0 ? round($iterations / $duration) : 0;
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed
    ];
}

function parseArgs($argv) {
    $args = ['json' => false, 'iterations' => 1000, 'warmup' => 100];
    
    foreach ($argv as $arg) {
        if ($arg === '--json') $args['json'] = true;
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 13));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "==================================================\n";
    echo " Laravel JSON Performance Benchmark\n";
    echo "==================================================\n\n";
}

$results = [];

// Test 1: Small JSON Objects
$results['small_json_objects'] = runBenchmark(
    'Small JSON Objects', 
    $args['iterations'], 
    function() {
        $data = [
            'id' => rand(1, 10000),
            'name' => 'Test User',
            'active' => true,
            'framework' => 'laravel'
        ];
        
        $request = Request::create('/api/test', 'POST', $data);
        $received = $request->all();
        
        return response()->json($received);
    }
);

// Test 2: Medium Arrays
$results['medium_arrays'] = runBenchmark(
    'Medium Arrays', 
    $args['iterations'], 
    function() {
        $data = [
            'users' => array_map(function($i) {
                return [
                    'id' => $i,
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'active' => $i % 2 === 0
                ];
            }, range(1, 100)),
            'meta' => [
                'total' => 100,
                'framework' => 'laravel',
                'generated_at' => now()->toISOString()
            ]
        ];
        
        $request = Request::create('/api/users', 'POST', $data);
        $received = $request->all();
        
        return response()->json([
            'processed' => count($received['users'] ?? []),
            'meta' => $received['meta'] ?? []
        ]);
    }
);

// Test 3: Large Datasets
$results['large_datasets'] = runBenchmark(
    'Large Datasets', 
    $args['iterations'], 
    function() {
        $data = [
            'records' => array_map(function($i) {
                return [
                    'id' => $i,
                    'uuid' => uniqid('record_'),
                    'data' => str_repeat("data_$i ", 20),
                    'attributes' => [
                        'field1' => "value_$i",
                        'field2' => rand(1, 1000),
                        'field3' => $i % 2 === 0,
                        'nested' => [
                            'sub1' => "sub_value_$i",
                            'sub2' => array_fill(0, 10, $i)
                        ]
                    ],
                    'timestamp' => time() + $i
                ];
            }, range(1, 1000)),
            'framework' => 'laravel'
        ];
        
        $request = Request::create('/api/bulk', 'POST', $data);
        $received = $request->all();
        
        $totalSize = strlen(json_encode($received));
        
        return response()->json([
            'records_processed' => count($received['records'] ?? []),
            'total_size_bytes' => $totalSize,
            'avg_record_size' => $totalSize / count($received['records'] ?? [1]),
            'framework' => 'laravel'
        ]);
    }
);

// Test 4: Nested Structures
$results['nested_structures'] = runBenchmark(
    'Nested Structures', 
    $args['iterations'], 
    function() {
        function createNestedData($depth = 5) {
            if ($depth <= 0) {
                return ['value' => rand(1, 1000), 'framework' => 'laravel'];
            }
            
            return [
                'level' => $depth,
                'data' => str_repeat("level_$depth ", 10),
                'nested' => createNestedData($depth - 1),
                'siblings' => array_map(function($i) use ($depth) {
                    return [
                        'sibling_id' => $i,
                        'sibling_data' => "sibling_{$depth}_$i"
                    ];
                }, range(1, 3))
            ];
        }
        
        $data = [
            'document' => createNestedData(5),
            'meta' => [
                'created' => now()->toISOString(),
                'framework' => 'laravel'
            ]
        ];
        
        $request = Request::create('/api/nested', 'POST', $data);
        $received = $request->all();
        
        function getMaxDepth($array, $depth = 0) {
            if (!is_array($array)) return $depth;
            
            $maxDepth = $depth;
            foreach ($array as $value) {
                if (is_array($value)) {
                    $maxDepth = max($maxDepth, getMaxDepth($value, $depth + 1));
                }
            }
            return $maxDepth;
        }
        
        return response()->json([
            'max_depth' => getMaxDepth($received),
            'structure_valid' => isset($received['document']['nested']),
            'framework' => 'laravel'
        ]);
    }
);

// Test 5: JSON Schema Validation
$results['json_schema_validation'] = runBenchmark(
    'JSON Schema Validation', 
    $args['iterations'], 
    function() {
        $data = [
            'user' => [
                'name' => 'Test User ' . rand(1000, 9999),
                'email' => 'test' . rand(1000, 9999) . '@example.com',
                'age' => rand(18, 80),
                'profile' => [
                    'bio' => 'This is a test bio',
                    'website' => 'https://example.com',
                    'social' => [
                        'twitter' => '@testuser',
                        'linkedin' => 'test-user'
                    ]
                ]
            ]
        ];
        
        $request = Request::create('/api/validate', 'POST', $data);
        
        $validator = Validator::make($request->all(), [
            'user.name' => 'required|string|max:255',
            'user.email' => 'required|email',
            'user.age' => 'required|integer|min:18|max:120',
            'user.profile.bio' => 'required|string',
            'user.profile.website' => 'required|url',
            'user.profile.social.twitter' => 'required|string',
            'user.profile.social.linkedin' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors(),
                'framework' => 'laravel'
            ], 422);
        }
        
        return response()->json([
            'valid' => true,
            'validated_data' => $validator->validated(),
            'framework' => 'laravel'
        ]);
    }
);

// Test 6: JSON Error Handling
$results['json_error_handling'] = runBenchmark(
    'JSON Error Handling', 
    $args['iterations'], 
    function() {
        // Simulate various JSON scenarios
        $scenarios = [
            ['valid' => true, 'data' => ['test' => 'valid']],
            ['valid' => false, 'data' => null], // Simulate malformed JSON
            ['valid' => true, 'data' => []],
            ['valid' => true, 'data' => ['large' => str_repeat('x', 1000)]]
        ];
        
        $scenario = $scenarios[array_rand($scenarios)];
        
        if (!$scenario['valid']) {
            return response()->json([
                'error' => 'Invalid JSON provided',
                'code' => 'JSON_MALFORMED',
                'framework' => 'laravel'
            ], 400);
        }
        
        $request = Request::create('/api/process', 'POST', $scenario['data']);
        $processed = $request->all();
        
        return response()->json([
            'success' => true,
            'data_received' => !empty($processed),
            'data_size' => strlen(json_encode($processed)),
            'framework' => 'laravel'
        ]);
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'json-performance',
        'framework' => 'laravel',
        'version' => '10.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'json_capabilities' => [
            'parsing',
            'generation',
            'validation',
            'error_handling',
            'nested_structures',
            'large_datasets'
        ],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second'))
    ]);
} else {
    echo "\n📊 Laravel JSON Performance Summary:\n";
    echo "====================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-30s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    echo "\n🏆 Total JSON Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "✅ Laravel JSON benchmark completed!\n";
}