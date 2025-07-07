#!/usr/bin/env php
<?php

/**
 * Simple Working Benchmark for PivotPHP Core
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

// Disable output buffering
ob_end_clean();

$iterations = 1000;

// Test 1: Routing performance
echo "Running Routing Benchmark...\n";

$app = new Application();

// Add multiple routes
for ($i = 0; $i < 50; $i++) {
    $app->get("/route{$i}", function($request, $response) use ($i) {
        return $response->withJson(['route' => $i]);
    });
}

$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $route = '/route' . ($i % 50);
    $request = new Request('GET', $route, $route);
    
    // Suppress output
    ob_start();
    $response = $app->handle($request);
    ob_end_clean();
}

$time = microtime(true) - $start;
$opsPerSec = $iterations / $time;

$results = [
    'benchmark' => 'routing',
    'framework' => 'pivotphp-core',
    'iterations' => $iterations,
    'total_time' => $time,
    'ops_per_second' => round($opsPerSec, 2)
];

echo json_encode($results, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: JSON API performance
echo "Running JSON API Benchmark...\n";

$app2 = new Application();
$app2->post('/api/data', function($request, $response) {
    $data = json_decode($request->getBody()->getContents(), true);
    return $response->withJson([
        'received' => count($data ?? []),
        'timestamp' => time()
    ]);
});

$jsonData = json_encode(range(1, 100));
$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $request = new Request('POST', '/api/data', '/api/data');
    $request->getBody()->write($jsonData);
    
    ob_start();
    $response = $app2->handle($request);
    ob_end_clean();
}

$time = microtime(true) - $start;
$opsPerSec = $iterations / $time;

$results2 = [
    'benchmark' => 'json-api',
    'framework' => 'pivotphp-core',
    'iterations' => $iterations,
    'total_time' => $time,
    'ops_per_second' => round($opsPerSec, 2)
];

echo json_encode($results2, JSON_PRETTY_PRINT) . "\n\n";

// Save combined results
$allResults = [$results, $results2];
$timestamp = date('Y-m-d_H-i-s');
$outputFile = dirname(__DIR__, 3) . "/results/raw/pivotphp-core-simple_{$timestamp}.json";
@mkdir(dirname($outputFile), 0755, true);
file_put_contents($outputFile, json_encode($allResults, JSON_PRETTY_PRINT));

echo "Results saved to: {$outputFile}\n";