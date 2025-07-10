#!/usr/bin/env php
<?php

/**
 * Async Performance Benchmark for PivotPHP ReactPHP
 *
 * Tests async capabilities and continuous runtime performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use React\EventLoop\Loop;
use React\Promise\Promise;
use Shared\Interfaces\BenchmarkInterface;

class AsyncPerformanceBenchmark implements BenchmarkInterface
{
    private $eventLoop;
    private array $results = [];

    public function __construct()
    {
        $this->eventLoop = Loop::get();
    }

    public function run(int $iterations = 10000, int $warmup = 1000): array
    {
        $startMemory = memory_get_usage();
        
        // Warmup with async operations
        $this->runAsyncOperations($warmup);
        
        // Reset results
        $this->results = [];
        
        // Actual benchmark
        $start = microtime(true);
        $this->runAsyncOperations($iterations);
        $end = microtime(true);

        $duration = $end - $start;
        $memoryUsed = memory_get_usage() - $startMemory;

        return [
            'framework' => 'pivotphp-reactphp',
            'benchmark' => 'async-performance',
            'iterations' => $iterations,
            'duration' => $duration,
            'ops_per_second' => round($iterations / $duration),
            'memory_used' => $memoryUsed,
            'memory_peak' => memory_get_peak_usage(true),
            'async_operations' => count($this->results),
            'runtime_type' => 'continuous',
            'event_loop' => 'ReactPHP'
        ];
    }

    private function runAsyncOperations(int $count): void
    {
        $promises = [];
        
        for ($i = 0; $i < $count; $i++) {
            $promises[] = $this->createAsyncOperation($i);
        }
        
        // Wait for all promises to resolve
        $this->eventLoop->addTimer(0.001, function() use ($promises) {
            foreach ($promises as $promise) {
                $promise->then(function($result) {
                    $this->results[] = $result;
                });
            }
        });
        
        // Run event loop briefly to process promises
        $this->eventLoop->addTimer(0.01, function() {
            $this->eventLoop->stop();
        });
        
        $this->eventLoop->run();
    }

    private function createAsyncOperation(int $id): Promise
    {
        return new Promise(function($resolve) use ($id) {
            // Simulate async operation (JSON processing, data transformation)
            $data = [
                'id' => $id,
                'timestamp' => microtime(true),
                'data' => str_repeat('x', 100), // Some data to process
                'hash' => md5((string)$id)
            ];
            
            // Resolve immediately for benchmark purposes
            $resolve($data);
        });
    }

    public function getName(): string
    {
        return 'ReactPHP Async Performance Benchmark';
    }

    public function getDescription(): string
    {
        return 'Tests async operation performance in PivotPHP ReactPHP continuous runtime';
    }
}

// CLI execution
if (isset($argv)) {
    $benchmark = new AsyncPerformanceBenchmark();
    
    $iterations = 5000; // Lower for async operations
    $warmup = 500;
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
    
    $result = $benchmark->run($iterations, $warmup);
    
    if ($jsonOutput) {
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "ReactPHP Async Performance Benchmark Results:\n";
        echo "Framework: {$result['framework']}\n";
        echo "Iterations: {$result['iterations']}\n";
        echo "Duration: " . number_format($result['duration'], 4) . "s\n";
        echo "Ops/sec: " . number_format($result['ops_per_second']) . "\n";
        echo "Memory used: " . number_format($result['memory_used'] / 1024 / 1024, 2) . " MB\n";
        echo "Async operations: {$result['async_operations']}\n";
        echo "Runtime: {$result['runtime_type']}\n";
    }
}