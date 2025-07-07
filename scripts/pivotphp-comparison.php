#!/usr/bin/env php
<?php

/**
 * Compare PivotPHP Core vs PivotPHP with ORM
 */

echo "=== PivotPHP Core vs ORM Comparison ===\n\n";

$tests = [
    'pivotphp-core' => [
        'path' => __DIR__ . '/../frameworks/pivotphp-core',
        'description' => 'PivotPHP without ORM'
    ],
    'pivotphp-orm' => [
        'path' => __DIR__ . '/../frameworks/pivotphp-orm',
        'description' => 'PivotPHP with Cycle ORM'
    ]
];

$results = [];

// Test 1: Bootstrap time
echo "1. BOOTSTRAP TIME (loading framework)\n";
echo str_repeat('-', 50) . "\n";

foreach ($tests as $name => $config) {
    $start = microtime(true);
    $memStart = memory_get_usage();

    require_once $config['path'] . '/vendor/autoload.php';

    $bootstrapTime = (microtime(true) - $start) * 1000; // ms
    $memoryUsed = (memory_get_usage() - $memStart) / 1024 / 1024; // MB

    $results[$name]['bootstrap'] = [
        'time_ms' => $bootstrapTime,
        'memory_mb' => $memoryUsed
    ];

    printf("%-15s: %6.2f ms | %6.2f MB\n", $name, $bootstrapTime, $memoryUsed);
}

// Test 2: Application initialization
echo "\n2. APPLICATION INITIALIZATION\n";
echo str_repeat('-', 50) . "\n";

$iterations = 1000;

foreach ($tests as $name => $config) {
    $start = microtime(true);
    $memStart = memory_get_usage();

    for ($i = 0; $i < $iterations; $i++) {
        $app = new \PivotPHP\Core\Application();

        if ($name === 'pivotphp-orm') {
            // Simulate ORM service provider registration
            $container = $app->getContainer();
            $container->bind('orm.config', function() { return ['db' => 'test']; });
        }
    }

    $totalTime = microtime(true) - $start;
    $memoryUsed = (memory_get_peak_usage() - $memStart) / 1024 / 1024;
    $opsPerSecond = $iterations / $totalTime;

    $results[$name]['initialization'] = [
        'ops_per_second' => $opsPerSecond,
        'avg_time_ms' => ($totalTime / $iterations) * 1000,
        'memory_mb' => $memoryUsed
    ];

    printf("%-15s: %8.0f ops/sec | %6.3f ms avg | %6.2f MB\n",
        $name, $opsPerSecond, ($totalTime / $iterations) * 1000, $memoryUsed);
}

// Test 3: Route registration
echo "\n3. ROUTE REGISTRATION (100 routes)\n";
echo str_repeat('-', 50) . "\n";

$routeCount = 100;

foreach ($tests as $name => $config) {
    $app = new \PivotPHP\Core\Application();

    $start = microtime(true);
    $memStart = memory_get_usage();

    for ($i = 0; $i < $routeCount; $i++) {
        $app->get("/route{$i}", function() { return ['id' => $i]; });
    }

    $totalTime = (microtime(true) - $start) * 1000; // ms
    $memoryUsed = (memory_get_usage() - $memStart) / 1024 / 1024; // MB
    $routesPerMs = $routeCount / $totalTime;

    $results[$name]['routes'] = [
        'total_time_ms' => $totalTime,
        'routes_per_ms' => $routesPerMs,
        'memory_mb' => $memoryUsed
    ];

    printf("%-15s: %6.2f ms total | %6.2f routes/ms | %6.2f MB\n",
        $name, $totalTime, $routesPerMs, $memoryUsed);
}

// Test 4: Memory footprint with services
echo "\n4. MEMORY FOOTPRINT (with typical services)\n";
echo str_repeat('-', 50) . "\n";

foreach ($tests as $name => $config) {
    $memStart = memory_get_usage();

    $app = new \PivotPHP\Core\Application();
    $container = $app->getContainer();

    // Register typical services
    $container->bind('config', function() { return ['app' => ['name' => 'test', 'env' => 'prod']]; });
    $container->bind('logger', function() { return new stdClass(); });
    $container->bind('cache', function() { return new stdClass(); });

    if ($name === 'pivotphp-orm') {
        // Additional ORM services
        $container->bind('db.connection', function() { return new stdClass(); });
        $container->bind('orm.schema', function() { return new stdClass(); });
        $container->bind('orm.repository_manager', function() { return new stdClass(); });
    }

    // Add some routes
    for ($i = 0; $i < 20; $i++) {
        $app->get("/api/v1/resource{$i}", function() { return ['ok' => true]; });
    }

    $memoryUsed = (memory_get_usage() - $memStart) / 1024 / 1024; // MB

    $results[$name]['footprint'] = [
        'memory_mb' => $memoryUsed
    ];

    printf("%-15s: %6.2f MB\n", $name, $memoryUsed);
}

// Summary
echo "\n=== PERFORMANCE SUMMARY ===\n";
echo str_repeat('=', 50) . "\n\n";

// Calculate overhead
$coreInit = $results['pivotphp-core']['initialization']['ops_per_second'];
$ormInit = $results['pivotphp-orm']['initialization']['ops_per_second'];
$initOverhead = (($coreInit - $ormInit) / $coreInit) * 100;

$coreBootstrap = $results['pivotphp-core']['bootstrap']['time_ms'];
$ormBootstrap = $results['pivotphp-orm']['bootstrap']['time_ms'];
$bootstrapOverhead = (($ormBootstrap - $coreBootstrap) / $coreBootstrap) * 100;

$coreMemory = $results['pivotphp-core']['footprint']['memory_mb'];
$ormMemory = $results['pivotphp-orm']['footprint']['memory_mb'];
$memoryOverhead = (($ormMemory - $coreMemory) / $coreMemory) * 100;

echo "ORM Overhead:\n";
printf("  Bootstrap time:    +%.1f%% (%.2f ms vs %.2f ms)\n",
    $bootstrapOverhead, $ormBootstrap, $coreBootstrap);
printf("  Initialization:    -%.1f%% (%.0f vs %.0f ops/sec)\n",
    $initOverhead, $ormInit, $coreInit);
printf("  Memory footprint:  +%.1f%% (%.2f MB vs %.2f MB)\n",
    $memoryOverhead, $ormMemory, $coreMemory);

echo "\nConclusion:\n";
if ($memoryOverhead > 50) {
    echo "- Significant memory overhead with ORM (>{$memoryOverhead}%)\n";
} else {
    echo "- Moderate memory overhead with ORM (~{$memoryOverhead}%)\n";
}

if ($initOverhead > 20) {
    echo "- Notable performance impact on initialization\n";
} else {
    echo "- Minimal performance impact on initialization\n";
}

// Save results
$timestamp = date('Y-m-d_H-i-s');
$outputFile = __DIR__ . "/../results/raw/pivotphp_comparison_{$timestamp}.json";
@mkdir(dirname($outputFile), 0755, true);
file_put_contents($outputFile, json_encode($results, JSON_PRETTY_PRINT));

echo "\nDetailed results saved to: $outputFile\n";
