#!/usr/bin/env php
<?php

/**
 * Simple test to verify benchmarks work
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

echo "Testing PivotPHP Core benchmarks...\n\n";

// Test 1: Application creation
echo "1. Testing Application creation: ";
try {
    $app = new Application();
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}

// Test 2: Adding routes
echo "2. Testing route registration: ";
try {
    $app = new Application();
    $app->get('/test', function($request, $response) {
        return $response->json(['status' => 'ok']);
    });
    echo "✓ OK\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}

// Test 3: Request handling
echo "3. Testing request handling: ";
try {
    $app = new Application();
    $app->get('/test', function($request, $response) {
        return $response->json(['status' => 'ok']);
    });
    
    $request = new Request('GET', '/test', '/test');
    $response = $app->handle($request);
    
    if ($response instanceof Response) {
        echo "✓ OK\n";
    } else {
        echo "✗ FAIL: Invalid response type\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}

// Test 4: Simple benchmark
echo "4. Running simple benchmark (100 iterations): ";
try {
    $app = new Application();
    $app->get('/bench', function($request, $response) {
        return $response->json(['result' => 'ok']);
    });
    
    $start = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $request = new Request('GET', '/bench', '/bench');
        $response = $app->handle($request);
    }
    
    $time = microtime(true) - $start;
    $ops = 100 / $time;
    
    echo "✓ OK - " . round($ops, 2) . " ops/sec\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";