#!/usr/bin/env php
<?php

/**
 * Simple Realistic Benchmark - Working Version
 * Tests core functionality with realistic data patterns
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

class SimpleRealisticBenchmark
{
    private array $testData = [];
    
    public function __construct()
    {
        $this->generateTestData();
    }
    
    private function generateTestData(): void
    {
        $this->testData = [
            'users' => $this->generateUsers(100),
            'products' => $this->generateProducts(50),
            'orders' => $this->generateOrders(20)
        ];
    }
    
    private function generateUsers(int $count): array
    {
        $users = [];
        $names = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'David Brown'];
        $cities = ['New York', 'London', 'Paris', 'Tokyo', 'Sydney'];
        
        for ($i = 1; $i <= $count; $i++) {
            $users[] = [
                'id' => $i,
                'name' => $names[array_rand($names)] . ' ' . $i,
                'email' => 'user' . $i . '@example.com',
                'city' => $cities[array_rand($cities)],
                'age' => rand(18, 65),
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365))
            ];
        }
        
        return $users;
    }
    
    private function generateProducts(int $count): array
    {
        $products = [];
        $categories = ['Electronics', 'Clothing', 'Books', 'Home', 'Sports'];
        
        for ($i = 1; $i <= $count; $i++) {
            $products[] = [
                'id' => $i,
                'name' => 'Product ' . $i,
                'description' => 'This is a detailed description for product ' . $i,
                'price' => round(rand(999, 99999) / 100, 2),
                'category' => $categories[array_rand($categories)],
                'stock' => rand(0, 100),
                'rating' => round(rand(100, 500) / 100, 1)
            ];
        }
        
        return $products;
    }
    
    private function generateOrders(int $count): array
    {
        $orders = [];
        $statuses = ['pending', 'processing', 'shipped', 'delivered'];
        
        for ($i = 1; $i <= $count; $i++) {
            $orders[] = [
                'id' => $i,
                'user_id' => rand(1, 100),
                'total' => round(rand(1000, 50000) / 100, 2),
                'status' => $statuses[array_rand($statuses)],
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 30))
            ];
        }
        
        return $orders;
    }
    
    public function benchmarkJsonOperations(): array
    {
        echo "🔄 Testing JSON Operations\n";
        
        $results = [];
        
        // Test different data sizes
        $testCases = [
            'small' => array_slice($this->testData['users'], 0, 10),
            'medium' => array_slice($this->testData['products'], 0, 25),
            'large' => $this->testData['orders']
        ];
        
        foreach ($testCases as $size => $data) {
            echo "  📊 Testing {$size} dataset...\n";
            
            $iterations = 1000;
            
            // JSON encoding test
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $json = json_encode($data);
                unset($json);
            }
            $encodeTime = microtime(true) - $start;
            $encodeOps = $iterations / $encodeTime;
            
            // JSON decoding test
            $jsonString = json_encode($data);
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $decoded = json_decode($jsonString, true);
                unset($decoded);
            }
            $decodeTime = microtime(true) - $start;
            $decodeOps = $iterations / $decodeTime;
            
            $results[$size] = [
                'encode_ops_per_sec' => round($encodeOps, 2),
                'decode_ops_per_sec' => round($decodeOps, 2),
                'total_ops_per_sec' => round($encodeOps + $decodeOps, 2),
                'data_size' => count($data),
                'json_size' => strlen($jsonString)
            ];
            
            echo "    Encode: " . number_format($encodeOps, 2) . " ops/sec\n";
            echo "    Decode: " . number_format($decodeOps, 2) . " ops/sec\n";
            echo "    Total: " . number_format($encodeOps + $decodeOps, 2) . " ops/sec\n";
        }
        
        return $results;
    }
    
    public function benchmarkDataProcessing(): array
    {
        echo "\n🔄 Testing Data Processing\n";
        
        $results = [];
        
        // Array operations
        $start = microtime(true);
        $iterations = 5000;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Simulate common operations
            $filtered = array_filter($this->testData['users'], fn($u) => $u['age'] > 30);
            $mapped = array_map(fn($u) => $u['name'], $filtered);
            $sorted = array_slice($mapped, 0, 10);
            unset($filtered, $mapped, $sorted);
        }
        
        $arrayTime = microtime(true) - $start;
        $arrayOps = $iterations / $arrayTime;
        
        // String operations
        $start = microtime(true);
        $testString = str_repeat('Test string for processing. ', 100);
        
        for ($i = 0; $i < $iterations; $i++) {
            $processed = strtoupper($testString);
            $processed = str_replace('TEST', 'DEMO', $processed);
            $processed = substr($processed, 0, 500);
            unset($processed);
        }
        
        $stringTime = microtime(true) - $start;
        $stringOps = $iterations / $stringTime;
        
        $results = [
            'array_processing' => [
                'ops_per_sec' => round($arrayOps, 2),
                'duration' => round($arrayTime, 4)
            ],
            'string_processing' => [
                'ops_per_sec' => round($stringOps, 2),
                'duration' => round($stringTime, 4)
            ]
        ];
        
        echo "  Array Processing: " . number_format($arrayOps, 2) . " ops/sec\n";
        echo "  String Processing: " . number_format($stringOps, 2) . " ops/sec\n";
        
        return $results;
    }
    
    public function benchmarkMemoryUsage(): array
    {
        echo "\n🔄 Testing Memory Usage\n";
        
        $startMemory = memory_get_usage();
        $startPeak = memory_get_peak_usage();
        
        // Create large dataset
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData[] = [
                'id' => $i,
                'data' => str_repeat('Memory test data ', 50),
                'timestamp' => time(),
                'random' => rand(1, 1000000)
            ];
        }
        
        // Process data
        $processed = array_map(function($item) {
            return [
                'id' => $item['id'],
                'processed' => true,
                'length' => strlen($item['data'])
            ];
        }, $largeData);
        
        $endMemory = memory_get_usage();
        $endPeak = memory_get_peak_usage();
        
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
        $peakMemory = ($endPeak - $startPeak) / 1024 / 1024;
        
        unset($largeData, $processed);
        
        $results = [
            'memory_used_mb' => round($memoryUsed, 2),
            'peak_memory_mb' => round($peakMemory, 2),
            'items_processed' => 1000
        ];
        
        echo "  Memory Used: " . number_format($memoryUsed, 2) . " MB\n";
        echo "  Peak Memory: " . number_format($peakMemory, 2) . " MB\n";
        
        return $results;
    }
    
    public function runAllBenchmarks(): array
    {
        echo "🚀 Simple Realistic Benchmark Suite\n";
        echo "===================================\n";
        echo "Framework: PivotPHP Core v1.1.1\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        $allResults = [];
        
        $allResults['json_operations'] = $this->benchmarkJsonOperations();
        $allResults['data_processing'] = $this->benchmarkDataProcessing();
        $allResults['memory_usage'] = $this->benchmarkMemoryUsage();
        
        return $allResults;
    }
    
    public function generateSummary(array $results): array
    {
        // Calculate total JSON performance
        $totalJsonOps = 0;
        foreach ($results['json_operations'] as $result) {
            $totalJsonOps += $result['total_ops_per_sec'];
        }
        
        // Calculate total processing performance
        $totalProcessingOps = $results['data_processing']['array_processing']['ops_per_sec'] + 
                             $results['data_processing']['string_processing']['ops_per_sec'];
        
        return [
            'total_json_ops_per_sec' => round($totalJsonOps, 2),
            'total_processing_ops_per_sec' => round($totalProcessingOps, 2),
            'memory_efficiency' => $results['memory_usage']['memory_used_mb'],
            'peak_memory' => $results['memory_usage']['peak_memory_mb'],
            'framework' => 'PivotPHP Core v1.1.1',
            'test_date' => date('Y-m-d H:i:s')
        ];
    }
}

// Execute benchmark
if (php_sapi_name() === 'cli') {
    $benchmark = new SimpleRealisticBenchmark();
    $results = $benchmark->runAllBenchmarks();
    $summary = $benchmark->generateSummary($results);
    
    echo "\n🏆 Benchmark Summary:\n";
    echo "===================\n";
    echo "Total JSON Performance: " . number_format($summary['total_json_ops_per_sec'], 2) . " ops/sec\n";
    echo "Total Processing Performance: " . number_format($summary['total_processing_ops_per_sec'], 2) . " ops/sec\n";
    echo "Memory Used: " . number_format($summary['memory_efficiency'], 2) . " MB\n";
    echo "Peak Memory: " . number_format($summary['peak_memory'], 2) . " MB\n";
    
    // Save results
    $timestamp = date('Y-m-d-H-i-s');
    $filename = "/tmp/simple-realistic-benchmark-{$timestamp}.json";
    file_put_contents($filename, json_encode([
        'timestamp' => $timestamp,
        'framework' => 'PivotPHP Core v1.1.1',
        'benchmark_type' => 'simple_realistic',
        'summary' => $summary,
        'detailed_results' => $results
    ], JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to: {$filename}\n";
    echo "✅ Simple realistic benchmark completed successfully!\n";
}