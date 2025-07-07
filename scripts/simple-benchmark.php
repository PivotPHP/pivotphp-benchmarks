#!/usr/bin/env php
<?php

/**
 * Simple comparative benchmark
 */

$frameworks = [
    'pivotphp-core' => __DIR__ . '/../frameworks/pivotphp-core/vendor/autoload.php',
    'slim4' => __DIR__ . '/../frameworks/slim4/vendor/autoload.php',
];

$results = [];

// Test 1: Simple autoload and class instantiation
echo "=== AUTOLOAD & INSTANTIATION BENCHMARK ===\n\n";

foreach ($frameworks as $name => $autoload) {
    require_once $autoload;
    
    $start = microtime(true);
    $memory_start = memory_get_usage();
    
    for ($i = 0; $i < 10000; $i++) {
        if ($name === 'pivotphp-core') {
            $app = new \PivotPHP\Core\Application();
        } elseif ($name === 'slim4') {
            $app = \Slim\Factory\AppFactory::create();
        }
    }
    
    $time = microtime(true) - $start;
    $memory = (memory_get_peak_usage() - $memory_start) / 1024 / 1024;
    
    $results[$name]['instantiation'] = [
        'time' => $time,
        'ops_per_second' => 10000 / $time,
        'memory_mb' => $memory
    ];
    
    printf("%-15s: %8.0f ops/sec | %6.2f ms total | %6.2f MB\n", 
        $name, 
        10000 / $time,
        $time * 1000,
        $memory
    );
}

// Test 2: Simple route registration
echo "\n=== ROUTE REGISTRATION BENCHMARK ===\n\n";

foreach ($frameworks as $name => $autoload) {
    $start = microtime(true);
    $memory_start = memory_get_usage();
    
    if ($name === 'pivotphp-core') {
        $app = new \PivotPHP\Core\Application();
        for ($i = 0; $i < 100; $i++) {
            $app->get("/route{$i}", function() { return ['ok' => true]; });
        }
    } elseif ($name === 'slim4') {
        $app = \Slim\Factory\AppFactory::create();
        for ($i = 0; $i < 100; $i++) {
            $app->get("/route{$i}", function($req, $res) { 
                $res->getBody()->write(json_encode(['ok' => true]));
                return $res;
            });
        }
    }
    
    $time = microtime(true) - $start;
    $memory = (memory_get_peak_usage() - $memory_start) / 1024 / 1024;
    
    $results[$name]['routes'] = [
        'time' => $time,
        'routes_per_second' => 100 / $time,
        'memory_mb' => $memory
    ];
    
    printf("%-15s: %8.0f routes/sec | %6.2f ms total | %6.2f MB\n", 
        $name, 
        100 / $time,
        $time * 1000,
        $memory
    );
}

// Summary
echo "\n=== SUMMARY ===\n\n";
echo "Performance relative to baseline (higher is better):\n\n";

// Find baseline (best performer)
$baseline_instantiation = max(array_column(array_column($results, 'instantiation'), 'ops_per_second'));
$baseline_routes = max(array_column(array_column($results, 'routes'), 'routes_per_second'));

foreach ($results as $name => $data) {
    $inst_perf = ($data['instantiation']['ops_per_second'] / $baseline_instantiation) * 100;
    $route_perf = ($data['routes']['routes_per_second'] / $baseline_routes) * 100;
    
    printf("%-15s: Instantiation: %6.1f%% | Route Registration: %6.1f%%\n", 
        $name, 
        $inst_perf,
        $route_perf
    );
}

// Save results
$timestamp = date('Y-m-d_H-i-s');
$outputFile = __DIR__ . "/../results/raw/simple_benchmark_{$timestamp}.json";
@mkdir(dirname($outputFile), 0755, true);
file_put_contents($outputFile, json_encode($results, JSON_PRETTY_PRINT));

echo "\nResults saved to: $outputFile\n";