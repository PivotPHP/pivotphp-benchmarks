#!/usr/bin/env php
<?php

/**
 * Run all benchmarks for this framework
 */

$benchmarks = [
    'routing.php',
    'middleware.php', 
    'json-api.php'
];

$results = [];

foreach ($benchmarks as $benchmark) {
    $file = __DIR__ . '/' . $benchmark;
    if (!file_exists($file)) {
        echo "Skipping {$benchmark} - not found\n";
        continue;
    }
    
    echo "Running {$benchmark}...\n";
    
    $output = shell_exec("php {$file} --json --iterations=1000 --warmup=100");
    if ($output) {
        $result = json_decode($output, true);
        if ($result) {
            $results[] = $result;
            echo "  ✓ {$result['benchmark']}: {$result['ops_per_second']} ops/sec\n";
        }
    }
}

// Save results
$timestamp = date('Y-m-d_H-i-s');
$outputFile = dirname(__DIR__, 3) . "/results/raw/slim4_{$timestamp}.json";
@mkdir(dirname($outputFile), 0755, true);
file_put_contents($outputFile, json_encode($results, JSON_PRETTY_PRINT));

echo "\nResults saved to: {$outputFile}\n";