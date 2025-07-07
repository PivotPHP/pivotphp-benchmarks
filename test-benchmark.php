#!/usr/bin/env php
<?php

/**
 * Quick test to verify benchmark setup
 */

echo "Testing HelixPHP Benchmark Setup...\n\n";

// Check if vendor directory exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ Vendor directory not found. Please run 'composer install' first.\n";
    exit(1);
}

require_once __DIR__ . '/vendor/autoload.php';

// Test Timer class
try {
    $timer = new \HelixBenchmark\Timer();
    echo "✅ Timer class loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load Timer class: " . $e->getMessage() . "\n";
}

// Test BenchmarkBase class
try {
    if (class_exists('\\HelixBenchmark\\BenchmarkBase')) {
        echo "✅ BenchmarkBase class found\n";
    }
} catch (Exception $e) {
    echo "❌ Failed to load BenchmarkBase class: " . $e->getMessage() . "\n";
}

// Check if we can run a simple benchmark
echo "\nTrying to run a simple benchmark test...\n";

$benchmarkFile = __DIR__ . '/benchmarks/helix-core/routing/simple-route.php';
if (file_exists($benchmarkFile)) {
    echo "✅ Found simple-route.php benchmark\n";
    
    // Try to run it with minimal iterations
    $command = sprintf(
        'php %s --iterations=10 --warmup=5 --json 2>&1',
        escapeshellarg($benchmarkFile)
    );
    
    echo "\nRunning: $command\n";
    $output = shell_exec($command);
    
    if ($output) {
        $result = json_decode($output, true);
        if ($result && isset($result['results'])) {
            echo "\n✅ Benchmark executed successfully!\n";
            echo "Operations/second: " . number_format($result['results']['operations_per_second']) . "\n";
            echo "Memory used: " . $result['results']['memory_used_mb'] . " MB\n";
        } else {
            echo "\n❌ Failed to parse benchmark output:\n";
            echo substr($output, 0, 500) . "\n";
        }
    } else {
        echo "\n❌ No output from benchmark\n";
    }
} else {
    echo "❌ Benchmark file not found: $benchmarkFile\n";
}

echo "\nTest complete.\n";