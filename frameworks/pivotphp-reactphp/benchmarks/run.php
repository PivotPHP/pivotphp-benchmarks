#!/usr/bin/env php
<?php

/**
 * ReactPHP Benchmark Runner
 * 
 * Runs all ReactPHP-specific benchmarks
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "PivotPHP ReactPHP Benchmark Suite\n";
echo "==================================\n\n";

$benchmarks = [
    'routing.php' => 'Route matching performance',
    'http-server.php' => 'HTTP server performance (real requests)',
    'async-performance.php' => 'Async operations performance', 
    'continuous-runtime.php' => 'Continuous runtime advantages'
];

$iterations = 10000;
$warmup = 1000;
$jsonOutput = false;

// Parse command line arguments
for ($i = 1; $i < count($argv); $i++) {
    if (strpos($argv[$i], '--iterations=') === 0) {
        $iterations = (int) substr($argv[$i], 13);
    } elseif (strpos($argv[$i], '--warmup=') === 0) {
        $warmup = (int) substr($argv[$i], 9);
    } elseif ($argv[$i] === '--json') {
        $jsonOutput = true;
    }
}

$results = [];

foreach ($benchmarks as $script => $description) {
    $benchmarkPath = __DIR__ . '/' . $script;
    
    if (!file_exists($benchmarkPath)) {
        echo "SKIP: {$script} - File not found\n";
        continue;
    }
    
    echo "Running: {$description}\n";
    
    $cmd = sprintf(
        'php %s --iterations=%d --warmup=%d --json',
        escapeshellarg($benchmarkPath),
        $iterations,
        $warmup
    );
    
    $output = shell_exec($cmd);
    
    if ($output === null) {
        echo "ERROR: Failed to run {$script}\n\n";
        continue;
    }
    
    $result = json_decode(trim($output), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERROR: Invalid JSON output from {$script}\n";
        echo "Output: {$output}\n\n";
        continue;
    }
    
    $results[] = $result;
    
    if (!$jsonOutput) {
        echo "  Ops/sec: " . number_format($result['ops_per_second']) . "\n";
        echo "  Memory: " . number_format($result['memory_used'] / 1024 / 1024, 2) . " MB\n";
        if (isset($result['uptime'])) {
            echo "  Uptime: " . number_format($result['uptime'], 2) . "s\n";
        }
        echo "\n";
    }
}

if ($jsonOutput) {
    echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "ReactPHP Benchmark Summary:\n";
    echo "=========================\n";
    
    foreach ($results as $result) {
        echo "{$result['benchmark']}: " . number_format($result['ops_per_second']) . " ops/sec\n";
    }
    
    echo "\nFramework: PivotPHP ReactPHP (Continuous Runtime)\n";
    echo "Total benchmarks: " . count($results) . "\n";
}