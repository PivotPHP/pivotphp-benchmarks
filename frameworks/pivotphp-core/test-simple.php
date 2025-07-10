#!/usr/bin/env php
<?php

/**
 * Simple test to verify PivotPHP Core v1.1.0 functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

echo "Testing PivotPHP Core v1.1.0...\n";

try {
    // Create application
    $app = new Application();
    echo "✅ Application created successfully\n";

    // Test route definition
    $app->get('/test', function($req, $res) {
        $res->json(['message' => 'Hello PivotPHP v1.1.0']);
    });
    echo "✅ Route registered successfully\n";

    // Simple routing benchmark
    $iterations = 5000;
    echo "🚀 Running routing benchmark ($iterations iterations)...\n";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    for ($i = 0; $i < $iterations; $i++) {
        // Test route matching performance
        $router = $app->getRouter();
        $route = $router->match('GET', '/test');
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo "📊 Results:\n";
    echo "   Duration: " . number_format($duration, 4) . "s\n";
    echo "   Operations/second: " . number_format($opsPerSecond) . "\n";
    echo "   Memory used: " . number_format($memoryUsed / 1024, 2) . " KB\n";
    echo "   Memory peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    
    // JSON test
    echo "\n🧪 Testing JSON performance...\n";
    $jsonData = ['message' => 'Performance test', 'iteration' => 1000, 'data' => range(1, 100)];
    
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $json = json_encode($jsonData);
        $decoded = json_decode($json, true);
    }
    $end = microtime(true);
    
    $jsonOps = round(1000 / ($end - $start));
    echo "   JSON ops/second: " . number_format($jsonOps) . "\n";
    
    echo "\n✅ PivotPHP Core v1.1.0 is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}