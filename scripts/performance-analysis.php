#!/usr/bin/env php
<?php

/**
 * Performance Analysis Script
 * 
 * Runs comprehensive performance tests and generates analysis report
 */

class PerformanceAnalyzer
{
    private array $benchmarks = [
        'basic' => [
            'routing/simple-route',
            'routing/route-with-params',
            'middleware/single-middleware',
            'middleware/pipeline',
            'http/json-processing'
        ],
        'concurrent' => [
            'concurrency/http-concurrent'
        ],
        'throughput' => [
            'throughput/request-throughput'
        ]
    ];
    
    private array $results = [];
    
    public function run(): void
    {
        echo "=== PivotPHP Performance Analysis ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n\n";
        
        // Run basic benchmarks
        $this->runBasicBenchmarks();
        
        // Run concurrent load tests
        $this->runConcurrentBenchmarks();
        
        // Run throughput tests
        $this->runThroughputBenchmarks();
        
        // Generate report
        $this->generateReport();
    }
    
    private function runBasicBenchmarks(): void
    {
        echo "Running basic benchmarks...\n";
        
        foreach ($this->benchmarks['basic'] as $benchmark) {
            $path = $this->getBenchmarkPath($benchmark);
            if (!file_exists($path)) {
                echo "  [SKIP] {$benchmark} - file not found\n";
                continue;
            }
            
            echo "  Running {$benchmark}...";
            $output = shell_exec("php {$path} --iterations=5000 --warmup=500 --json 2>/dev/null");
            
            if ($output) {
                $data = json_decode($output, true);
                if ($data) {
                    $this->results['basic'][$benchmark] = $data['results'];
                    echo " ✓ ({$data['results']['operations_per_second']} ops/s)\n";
                } else {
                    echo " ✗ (invalid output)\n";
                }
            } else {
                echo " ✗ (no output)\n";
            }
        }
        echo "\n";
    }
    
    private function runConcurrentBenchmarks(): void
    {
        echo "Running concurrent load tests...\n";
        
        $path = $this->getBenchmarkPath('concurrency/http-concurrent');
        if (!file_exists($path)) {
            echo "  [SKIP] Concurrent benchmark not found\n";
            return;
        }
        
        $output = shell_exec("php {$path} --json 2>/dev/null");
        if ($output) {
            $data = json_decode($output, true);
            if ($data && isset($data['results'])) {
                $this->results['concurrent'] = $data['results'];
                foreach ($data['results'] as $load => $result) {
                    echo "  Load {$load}: {$result['requests_per_second']} req/s\n";
                }
            }
        }
        echo "\n";
    }
    
    private function runThroughputBenchmarks(): void
    {
        echo "Running throughput tests...\n";
        
        $path = $this->getBenchmarkPath('throughput/request-throughput');
        if (!file_exists($path)) {
            echo "  [SKIP] Throughput benchmark not found\n";
            return;
        }
        
        $output = shell_exec("php {$path} --json 2>/dev/null");
        if ($output) {
            $data = json_decode($output, true);
            if ($data && isset($data['results'])) {
                $this->results['throughput'] = $data['results'];
                foreach ($data['results'] as $type => $typeResults) {
                    echo "  {$type} requests:\n";
                    foreach ($typeResults as $load => $result) {
                        echo "    Load {$load}: {$result['throughput']} req/s\n";
                    }
                }
            }
        }
        echo "\n";
    }
    
    private function getBenchmarkPath(string $benchmark): string
    {
        return __DIR__ . "/../benchmarks/pivotphp-core/{$benchmark}.php";
    }
    
    private function generateReport(): void
    {
        $report = [
            'framework' => 'PivotPHP Core',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
            'summary' => $this->generateSummary(),
            'details' => $this->results
        ];
        
        // Save JSON report
        $filename = 'performance-report-' . date('Y-m-d-His') . '.json';
        file_put_contents(__DIR__ . "/../results/{$filename}", json_encode($report, JSON_PRETTY_PRINT));
        
        // Display summary
        echo "=== Performance Summary ===\n\n";
        
        if (isset($this->results['basic'])) {
            echo "Basic Operations:\n";
            foreach ($this->results['basic'] as $name => $result) {
                $ops = $result['operations_per_second'] ?? 'N/A';
                $mem = $result['memory_peak_mb'] ?? 'N/A';
                echo "  {$name}: {$ops} ops/s (Memory: {$mem} MB)\n";
            }
            echo "\n";
        }
        
        if (isset($this->results['concurrent'])) {
            echo "Concurrent Load Performance:\n";
            foreach ($this->results['concurrent'] as $load => $result) {
                echo "  {$load} concurrent requests: {$result['requests_per_second']} req/s ";
                echo "(Avg latency: {$result['avg_latency_ms']} ms)\n";
            }
            echo "\n";
        }
        
        if (isset($this->results['throughput'])) {
            echo "Throughput by Request Type:\n";
            foreach ($this->results['throughput'] as $type => $loads) {
                echo "  {$type}:\n";
                foreach ($loads as $load => $result) {
                    echo "    Load {$load}: {$result['throughput']} req/s ";
                    echo "(P95: {$result['p95_latency_ms']} ms, P99: {$result['p99_latency_ms']} ms)\n";
                }
            }
            echo "\n";
        }
        
        echo "Full report saved to: {$filename}\n";
    }
    
    private function generateSummary(): array
    {
        $summary = [
            'avg_operations_per_second' => 0,
            'max_throughput' => 0,
            'min_latency_ms' => PHP_FLOAT_MAX,
            'avg_memory_mb' => 0
        ];
        
        // Calculate averages from basic benchmarks
        if (isset($this->results['basic'])) {
            $ops = array_column($this->results['basic'], 'operations_per_second');
            $summary['avg_operations_per_second'] = round(array_sum($ops) / count($ops), 2);
            
            $memory = array_column($this->results['basic'], 'memory_peak_mb');
            $summary['avg_memory_mb'] = round(array_sum($memory) / count($memory), 2);
        }
        
        // Find max throughput
        if (isset($this->results['throughput'])) {
            foreach ($this->results['throughput'] as $type => $loads) {
                foreach ($loads as $result) {
                    $summary['max_throughput'] = max($summary['max_throughput'], $result['throughput']);
                }
            }
        }
        
        // Find min latency
        if (isset($this->results['concurrent'])) {
            foreach ($this->results['concurrent'] as $result) {
                $summary['min_latency_ms'] = min($summary['min_latency_ms'], $result['avg_latency_ms']);
            }
        }
        
        return $summary;
    }
}

// Create results directory if it doesn't exist
$resultsDir = __DIR__ . '/../results';
if (!is_dir($resultsDir)) {
    mkdir($resultsDir, 0755, true);
}

// Run analysis
$analyzer = new PerformanceAnalyzer();
$analyzer->run();