#!/usr/bin/env php
<?php

/**
 * Simple Benchmark for PivotPHP ReactPHP
 * 
 * Tests async operations and continuous runtime performance
 */

require_once __DIR__ . '/../vendor/autoload.php';

class SimpleBenchmarkReactPHP
{
    private array $results = [];
    
    public function run(int $iterations = 3000): array
    {
        echo "🚀 PivotPHP ReactPHP Simple Benchmark\n";
        echo "====================================\n\n";
        
        // Test async-like operations (simulated)
        $asyncResults = $this->testAsyncOperations($iterations);
        $this->results['async'] = $asyncResults;
        
        // Test promise-like operations
        $promiseResults = $this->testPromiseOperations($iterations);
        $this->results['promise'] = $promiseResults;
        
        // Test event loop simulation
        $eventResults = $this->testEventLoopOperations($iterations);
        $this->results['event_loop'] = $eventResults;
        
        // Test JSON operations (ReactPHP API responses)
        $jsonResults = $this->testJsonOperations($iterations);
        $this->results['json'] = $jsonResults;
        
        // Test memory usage
        $memoryResults = $this->testMemoryUsage();
        $this->results['memory'] = $memoryResults;
        
        $this->printResults();
        $this->saveResults();
        
        return $this->results;
    }
    
    private function testAsyncOperations(int $iterations): array
    {
        echo "🔄 Testing Async-like Operations\n";
        
        // Simulate async callback patterns
        $callbacks = [
            function($data) { return $data * 2; },
            function($data) { return $data + 10; },
            function($data) { return $data / 2; }
        ];
        
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $value = $i;
            foreach ($callbacks as $callback) {
                $value = $callback($value);
            }
        }
        $end = microtime(true);
        
        $time = $end - $start;
        $opsPerSec = $iterations / $time;
        
        return [
            'async_chains_ops_sec' => round($opsPerSec, 2),
            'time_seconds' => round($time, 4)
        ];
    }
    
    private function testPromiseOperations(int $iterations): array
    {
        echo "🔄 Testing Promise-like Operations\n";
        
        // Simulate promise resolution patterns
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            // Simulate promise creation and resolution
            $promise = function($resolve, $reject) use ($i) {
                if ($i % 100 !== 99) { // 99% success rate
                    return $resolve($i * 2);
                } else {
                    return $reject(new Exception('Simulated error'));
                }
            };
            
            // Simulate then/catch chain
            try {
                $promise(
                    function($value) { return $value + 1; },
                    function($error) { return 0; }
                );
            } catch (Exception $e) {
                // Handle error
            }
        }
        $end = microtime(true);
        
        $time = $end - $start;
        $opsPerSec = $iterations / $time;
        
        return [
            'promise_ops_sec' => round($opsPerSec, 2),
            'time_seconds' => round($time, 4)
        ];
    }
    
    private function testEventLoopOperations(int $iterations): array
    {
        echo "🔄 Testing Event Loop Simulation\n";
        
        // Simulate event queue processing
        $eventQueue = [];
        
        // Fill event queue
        for ($i = 0; $i < $iterations; $i++) {
            $eventQueue[] = [
                'type' => ['http_request', 'timer', 'stream'][rand(0, 2)],
                'data' => $i,
                'callback' => function($data) { return $data * 2; }
            ];
        }
        
        // Process events
        $start = microtime(true);
        $processed = 0;
        while (!empty($eventQueue)) {
            $event = array_shift($eventQueue);
            $result = $event['callback']($event['data']);
            $processed++;
        }
        $end = microtime(true);
        
        $time = $end - $start;
        $opsPerSec = $processed / $time;
        
        return [
            'event_processing_ops_sec' => round($opsPerSec, 2),
            'events_processed' => $processed,
            'time_seconds' => round($time, 4)
        ];
    }
    
    private function testJsonOperations(int $iterations): array
    {
        echo "🔄 Testing JSON Operations (API Responses)\n";
        
        // Simulate ReactPHP HTTP server response data
        $apiResponse = [
            'status' => 'success',
            'data' => [
                'users' => [],
                'metadata' => [
                    'total' => 1000,
                    'page' => 1,
                    'per_page' => 50
                ]
            ],
            'server' => [
                'name' => 'PivotPHP-ReactPHP',
                'version' => '0.1.0',
                'memory_usage' => '12MB',
                'uptime' => time()
            ]
        ];
        
        // Fill with users data
        for ($i = 0; $i < 50; $i++) {
            $apiResponse['data']['users'][] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'online' => rand(0, 1) === 1
            ];
        }
        
        // Encode test
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            json_encode($apiResponse);
        }
        $encodeTime = microtime(true) - $start;
        $encodeOps = $iterations / $encodeTime;
        
        // Decode test
        $jsonString = json_encode($apiResponse);
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            json_decode($jsonString, true);
        }
        $decodeTime = microtime(true) - $start;
        $decodeOps = $iterations / $decodeTime;
        
        return [
            'encode_ops_sec' => round($encodeOps, 2),
            'decode_ops_sec' => round($decodeOps, 2),
            'total_json_ops_sec' => round($encodeOps + $decodeOps, 2)
        ];
    }
    
    private function testMemoryUsage(): array
    {
        echo "🔄 Testing Memory Usage (Continuous Runtime)\n";
        
        $startMemory = memory_get_usage();
        $startPeak = memory_get_peak_usage();
        
        // Simulate continuous runtime data structures
        $connections = [];
        $eventHandlers = [];
        $responseBuffer = [];
        
        // Simulate 1000 concurrent connections
        for ($i = 0; $i < 1000; $i++) {
            $connections[$i] = [
                'id' => $i,
                'ip' => '192.168.1.' . ($i % 255),
                'connected_at' => time(),
                'last_activity' => time(),
                'data_buffer' => str_repeat('x', 100)
            ];
            
            $eventHandlers[$i] = function() use ($i) {
                return "Handler for connection {$i}";
            };
            
            $responseBuffer[$i] = json_encode([
                'connection_id' => $i,
                'response' => 'Hello from ReactPHP server'
            ]);
        }
        
        $endMemory = memory_get_usage();
        $endPeak = memory_get_peak_usage();
        
        // Cleanup
        unset($connections, $eventHandlers, $responseBuffer);
        
        return [
            'current_mb' => round($endMemory / 1024 / 1024, 2),
            'peak_mb' => round($endPeak / 1024 / 1024, 2),
            'allocated_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2)
        ];
    }
    
    private function printResults(): void
    {
        echo "\n🏆 PivotPHP ReactPHP Benchmark Results:\n";
        echo "=====================================\n\n";
        
        echo "📊 Async Operations:\n";
        echo "  Async Chains: " . number_format($this->results['async']['async_chains_ops_sec']) . " ops/sec\n";
        echo "  Time: " . $this->results['async']['time_seconds'] . "s\n\n";
        
        echo "📊 Promise Operations:\n";
        echo "  Promise Processing: " . number_format($this->results['promise']['promise_ops_sec']) . " ops/sec\n";
        echo "  Time: " . $this->results['promise']['time_seconds'] . "s\n\n";
        
        echo "📊 Event Loop Operations:\n";
        echo "  Event Processing: " . number_format($this->results['event_loop']['event_processing_ops_sec']) . " ops/sec\n";
        echo "  Events Processed: " . number_format($this->results['event_loop']['events_processed']) . "\n";
        echo "  Time: " . $this->results['event_loop']['time_seconds'] . "s\n\n";
        
        echo "📊 JSON Operations (API Responses):\n";
        echo "  Encode: " . number_format($this->results['json']['encode_ops_sec']) . " ops/sec\n";
        echo "  Decode: " . number_format($this->results['json']['decode_ops_sec']) . " ops/sec\n";
        echo "  Total JSON: " . number_format($this->results['json']['total_json_ops_sec']) . " ops/sec\n\n";
        
        echo "📊 Memory Usage (Continuous Runtime):\n";
        echo "  Current: " . $this->results['memory']['current_mb'] . " MB\n";
        echo "  Peak: " . $this->results['memory']['peak_mb'] . " MB\n";
        echo "  Allocated: " . $this->results['memory']['allocated_mb'] . " MB\n\n";
        
        // Calculate totals
        $totalAsync = $this->results['async']['async_chains_ops_sec'] + 
                     $this->results['promise']['promise_ops_sec'] + 
                     $this->results['event_loop']['event_processing_ops_sec'];
        
        $totalOps = $totalAsync + $this->results['json']['total_json_ops_sec'];
        
        echo "🚀 Performance Summary:\n";
        echo "======================\n";
        echo "Framework: PivotPHP ReactPHP v0.1.0\n";
        echo "Total Async Operations: " . number_format($totalAsync) . " ops/sec\n";
        echo "Total JSON Operations: " . number_format($this->results['json']['total_json_ops_sec']) . " ops/sec\n";
        echo "Combined Performance: " . number_format($totalOps) . " ops/sec\n";
        echo "Memory Peak: " . $this->results['memory']['peak_mb'] . " MB\n";
        echo "Runtime Type: Continuous (ReactPHP)\n";
    }
    
    private function saveResults(): void
    {
        $filename = '/tmp/pivotphp-reactphp-simple-benchmark-' . date('Y-m-d-H-i-s') . '.json';
        
        $data = [
            'benchmark' => 'pivotphp-reactphp-simple-benchmark',
            'framework' => 'pivotphp-reactphp',
            'version' => 'v0.1.0',
            'timestamp' => date('c'),
            'results' => $this->results
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "💾 Results saved to: $filename\n";
    }
}

// Parse command line arguments
$iterations = 3000;
foreach ($argv as $arg) {
    if (strpos($arg, '--iterations=') === 0) {
        $iterations = (int) substr($arg, 13);
    }
}

// Run benchmark
if (php_sapi_name() === 'cli') {
    try {
        $benchmark = new SimpleBenchmarkReactPHP();
        $benchmark->run($iterations);
        echo "✅ PivotPHP ReactPHP benchmark completed successfully!\n";
    } catch (Exception $e) {
        echo "❌ Benchmark failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}