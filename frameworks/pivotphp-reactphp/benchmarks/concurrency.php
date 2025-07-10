#!/usr/bin/env php
<?php

/**
 * PivotPHP ReactPHP Concurrency Benchmark
 * Tests async concurrent request handling capabilities
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use React\EventLoop\Loop;

function runBenchmark($name, $iterations, $concurrency, $callback) {
    echo "🚀 $name ($iterations iterations, $concurrency async concurrent)... ";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    // Use ReactPHP event loop for true async concurrency
    $loop = Loop::get();
    $completed = 0;
    $iterationsPerBatch = intval($iterations / $concurrency);
    
    for ($batch = 0; $batch < $concurrency; $batch++) {
        $loop->addTimer(0.001 * $batch, function() use ($callback, $iterationsPerBatch, &$completed) {
            for ($i = 0; $i < $iterationsPerBatch; $i++) {
                $callback();
            }
            $completed++;
        });
    }
    
    // Wait for all async operations to complete
    $loop->addPeriodicTimer(0.01, function($timer) use (&$completed, $concurrency, $loop) {
        if ($completed >= $concurrency) {
            $timer->cancel();
            $loop->stop();
        }
    });
    
    $loop->run();
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed,
        'concurrency' => $concurrency
    ];
}

function parseArgs($argv) {
    $args = ['json' => false, 'iterations' => 1000, 'warmup' => 100];
    
    foreach ($argv as $arg) {
        if ($arg === '--json') $args['json'] = true;
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 14));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "=================================================\n";
    echo " PivotPHP ReactPHP Concurrency Benchmark\n";
    echo "=================================================\n\n";
}

$results = [];

// Test 1: Concurrent Async Application Creation
$results['concurrent_async_app_creation'] = runBenchmark(
    'Concurrent Async App Creation', 
    $args['iterations'], 
    8, 
    function() {
        $app = new Application();
        $app->boot();
        return $app;
    }
);

// Test 2: Concurrent Promise-like Operations
$results['concurrent_promise_operations'] = runBenchmark(
    'Concurrent Promise Operations', 
    $args['iterations'], 
    16, 
    function() {
        // Simulate async promise-like operations
        $data = [
            'id' => rand(1, 10000),
            'status' => 'pending',
            'created_at' => microtime(true)
        ];
        
        // Simulate async processing
        $processed = array_merge($data, [
            'status' => 'resolved',
            'processed_at' => microtime(true),
            'result' => [
                'async_data' => range(1, 50),
                'computed_hash' => md5(json_encode($data)),
                'processing_time' => rand(1, 10) / 1000
            ]
        ]);
        
        return $processed;
    }
);

// Test 3: Concurrent Event Loop Operations
$loop = Loop::get();
$results['concurrent_event_loop_ops'] = runBenchmark(
    'Concurrent Event Loop Operations', 
    $args['iterations'], 
    12, 
    function() use ($loop) {
        // Simulate event loop operations
        $events = [];
        
        // Simulate timer events
        for ($i = 0; $i < 5; $i++) {
            $events[] = [
                'type' => 'timer',
                'delay' => rand(1, 100) / 1000,
                'callback' => function() { return microtime(true); }
            ];
        }
        
        // Simulate stream events
        for ($i = 0; $i < 3; $i++) {
            $events[] = [
                'type' => 'stream',
                'data' => str_repeat('x', rand(100, 1000)),
                'callback' => function($data) { return strlen($data); }
            ];
        }
        
        // Simulate signal events
        $events[] = [
            'type' => 'signal',
            'signal' => 'SIGUSR1',
            'callback' => function() { return 'signal_handled'; }
        ];
        
        return [
            'events_scheduled' => count($events),
            'total_timers' => count(array_filter($events, fn($e) => $e['type'] === 'timer')),
            'total_streams' => count(array_filter($events, fn($e) => $e['type'] === 'stream')),
            'total_signals' => count(array_filter($events, fn($e) => $e['type'] === 'signal')),
            'scheduled_at' => microtime(true)
        ];
    }
);

// Test 4: Concurrent HTTP Request Simulation
$results['concurrent_http_simulation'] = runBenchmark(
    'Concurrent HTTP Request Simulation', 
    $args['iterations'], 
    20, 
    function() {
        // Simulate async HTTP request handling
        $request = [
            'id' => uniqid('req_'),
            'method' => ['GET', 'POST', 'PUT', 'DELETE'][rand(0, 3)],
            'uri' => '/api/v1/resource/' . rand(1, 1000),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'ReactPHP-Benchmark/1.0',
                'X-Request-ID' => uniqid()
            ],
            'body' => json_encode(['data' => range(1, 20)]),
            'received_at' => microtime(true)
        ];
        
        // Simulate async request processing
        $response = [
            'request_id' => $request['id'],
            'status' => 200,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Response-Time' => rand(1, 50) . 'ms',
                'X-Async-Processing' => 'true'
            ],
            'body' => json_encode([
                'success' => true,
                'data' => array_reverse(range(1, 20)),
                'processing_info' => [
                    'method' => $request['method'],
                    'async' => true,
                    'event_loop' => 'reactphp'
                ]
            ]),
            'processed_at' => microtime(true),
            'processing_time' => microtime(true) - $request['received_at']
        ];
        
        return $response;
    }
);

// Test 5: Concurrent WebSocket Simulation
$results['concurrent_websocket_simulation'] = runBenchmark(
    'Concurrent WebSocket Simulation', 
    $args['iterations'], 
    10, 
    function() {
        // Simulate WebSocket connection and message handling
        $connection = [
            'id' => uniqid('ws_'),
            'connected_at' => microtime(true),
            'protocol' => 'ws',
            'version' => 13,
            'state' => 'open'
        ];
        
        // Simulate message exchange
        $messages = [];
        for ($i = 0; $i < rand(1, 10); $i++) {
            $message = [
                'id' => uniqid('msg_'),
                'type' => ['text', 'binary', 'ping', 'pong'][rand(0, 3)],
                'payload' => json_encode([
                    'message' => "Message $i",
                    'timestamp' => microtime(true),
                    'data' => range(1, 10)
                ]),
                'sent_at' => microtime(true)
            ];
            $messages[] = $message;
        }
        
        return [
            'connection' => $connection,
            'messages_exchanged' => count($messages),
            'total_payload_size' => array_sum(array_map(fn($m) => strlen($m['payload']), $messages)),
            'connection_duration' => microtime(true) - $connection['connected_at'],
            'avg_message_size' => count($messages) > 0 ? 
                array_sum(array_map(fn($m) => strlen($m['payload']), $messages)) / count($messages) : 0
        ];
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'concurrency',
        'framework' => 'pivotphp-reactphp',
        'version' => '0.1.0',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'runtime_type' => 'async',
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 ReactPHP Concurrency Performance Summary:\n";
    echo "===========================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-40s: %8s ops/sec (x%d async)\n", 
            $test, 
            number_format($result['ops_per_second']),
            $result['concurrency']
        );
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP ReactPHP v0.1.0\n";
    echo "⚡ Total Async Concurrent Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🔄 Runtime Type: Async/Event-driven\n";
    echo "🚀 Concurrency Status: " . (array_sum(array_column($results, 'ops_per_second')) > 8000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'concurrency',
        'framework' => 'pivotphp-reactphp',
        'version' => '0.1.0',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'runtime_type' => 'async',
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results)),
            'max_concurrency' => max(array_column($results, 'concurrency'))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-reactphp-concurrency-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ ReactPHP concurrency benchmark completed successfully!\n";
}