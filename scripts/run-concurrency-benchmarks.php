#!/usr/bin/env php
<?php

/**
 * Run HTTP concurrency benchmarks across all frameworks
 */

$frameworks = [
    'pivotphp-core' => 8081,
    'pivotphp-orm' => 8082,
    'slim4' => 8083,
    'lumen' => 8084,
    'flight' => 8085,
    'symfony' => 8086
];

$scenarios = [
    'low' => [
        'concurrency' => 10,
        'requests' => 1000,
        'description' => 'Low load (10 concurrent)'
    ],
    'normal' => [
        'concurrency' => 50,
        'requests' => 5000,
        'description' => 'Normal load (50 concurrent)'
    ],
    'high' => [
        'concurrency' => 100,
        'requests' => 10000,
        'description' => 'High load (100 concurrent)'
    ]
];

echo "=== HTTP Concurrency Benchmarks ===\n\n";

// Check if servers are running
foreach ($frameworks as $framework => $port) {
    $ch = curl_init("http://localhost:$port/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "Warning: $framework server not responding on port $port\n";
        echo "Run './scripts/start-servers.sh' first\n\n";
        exit(1);
    }
}

echo "All servers are running. Starting benchmarks...\n\n";

$allResults = [];

foreach ($frameworks as $framework => $port) {
    echo "Testing $framework...\n";
    
    $frameworkResults = [];
    
    foreach ($scenarios as $scenario => $config) {
        echo "  Running $scenario scenario...\n";
        
        // Use Apache Bench (ab) for concurrency testing
        $cmd = sprintf(
            'ab -n %d -c %d -q http://localhost:%d/users 2>&1',
            $config['requests'],
            $config['concurrency'],
            $port
        );
        
        $output = shell_exec($cmd);
        
        // Parse AB output
        $stats = parseAbOutput($output);
        
        if ($stats) {
            $frameworkResults[$scenario] = array_merge($stats, [
                'scenario' => $scenario,
                'description' => $config['description'],
                'concurrency' => $config['concurrency'],
                'total_requests' => $config['requests']
            ]);
            
            echo "    Requests/sec: " . round($stats['requests_per_second'], 2) . "\n";
            echo "    Mean latency: " . round($stats['mean_latency'], 2) . "ms\n";
        } else {
            echo "    Failed to run benchmark\n";
        }
    }
    
    $allResults[$framework] = $frameworkResults;
    echo "\n";
}

// Save results
$timestamp = date('Y-m-d_H-i-s');
$outputFile = __DIR__ . "/../results/raw/concurrency_http_{$timestamp}.json";
@mkdir(dirname($outputFile), 0755, true);
file_put_contents($outputFile, json_encode($allResults, JSON_PRETTY_PRINT));

echo "Results saved to: $outputFile\n\n";

// Display summary
displaySummary($allResults);

function parseAbOutput($output) {
    $stats = [];
    
    // Extract requests per second
    if (preg_match('/Requests per second:\s+([\d.]+)/', $output, $matches)) {
        $stats['requests_per_second'] = (float) $matches[1];
    }
    
    // Extract time per request (mean)
    if (preg_match('/Time per request:\s+([\d.]+)\s+\[ms\]\s+\(mean\)/', $output, $matches)) {
        $stats['mean_latency'] = (float) $matches[1];
    }
    
    // Extract failed requests
    if (preg_match('/Failed requests:\s+(\d+)/', $output, $matches)) {
        $stats['failed_requests'] = (int) $matches[1];
    }
    
    // Extract percentiles if available
    if (preg_match('/50%\s+(\d+)/', $output, $matches)) {
        $stats['p50'] = (int) $matches[1];
    }
    if (preg_match('/95%\s+(\d+)/', $output, $matches)) {
        $stats['p95'] = (int) $matches[1];
    }
    if (preg_match('/99%\s+(\d+)/', $output, $matches)) {
        $stats['p99'] = (int) $matches[1];
    }
    
    return empty($stats) ? null : $stats;
}

function displaySummary($results) {
    echo "=== SUMMARY ===\n\n";
    
    foreach (['low', 'normal', 'high'] as $scenario) {
        echo strtoupper($scenario) . " LOAD SCENARIO:\n";
        
        $rankings = [];
        foreach ($results as $framework => $scenarios) {
            if (isset($scenarios[$scenario]['requests_per_second'])) {
                $rankings[$framework] = $scenarios[$scenario]['requests_per_second'];
            }
        }
        
        arsort($rankings);
        
        $baseline = reset($rankings);
        $baselineFramework = key($rankings);
        
        foreach ($rankings as $framework => $rps) {
            $percentage = round(($rps / $baseline) * 100, 1);
            $marker = ($framework === $baselineFramework) ? ' (baseline)' : '';
            
            printf("  %-15s: %8.0f req/sec (%5.1f%%)%s\n", 
                $framework, 
                $rps, 
                $percentage,
                $marker
            );
        }
        
        echo "\n";
    }
}