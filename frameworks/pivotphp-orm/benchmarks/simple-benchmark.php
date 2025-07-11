#!/usr/bin/env php
<?php

/**
 * Simple Benchmark for PivotPHP ORM
 * 
 * Tests basic operations without complex dependencies
 */

require_once __DIR__ . '/../vendor/autoload.php';

class SimpleBenchmarkORM
{
    private array $results = [];
    
    public function run(int $iterations = 3000): array
    {
        echo "🚀 PivotPHP ORM Simple Benchmark\n";
        echo "===============================\n\n";
        
        // Test JSON operations
        $jsonResults = $this->testJsonOperations($iterations);
        $this->results['json'] = $jsonResults;
        
        // Test array operations
        $arrayResults = $this->testArrayOperations($iterations);
        $this->results['array'] = $arrayResults;
        
        // Test string operations
        $stringResults = $this->testStringOperations($iterations);
        $this->results['string'] = $stringResults;
        
        // Test memory usage
        $memoryResults = $this->testMemoryUsage();
        $this->results['memory'] = $memoryResults;
        
        $this->printResults();
        $this->saveResults();
        
        return $this->results;
    }
    
    private function testJsonOperations(int $iterations): array
    {
        echo "🔄 Testing JSON Operations\n";
        
        // Test data similar to ORM entities
        $userData = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'profile' => [
                'age' => 30,
                'city' => 'New York',
                'preferences' => ['theme' => 'dark', 'language' => 'en']
            ],
            'orders' => [
                ['id' => 1, 'total' => 99.99, 'status' => 'completed'],
                ['id' => 2, 'total' => 149.50, 'status' => 'pending']
            ]
        ];
        
        // Encode test
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            json_encode($userData);
        }
        $encodeTime = microtime(true) - $start;
        $encodeOps = $iterations / $encodeTime;
        
        // Decode test
        $jsonString = json_encode($userData);
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            json_decode($jsonString, true);
        }
        $decodeTime = microtime(true) - $start;
        $decodeOps = $iterations / $decodeTime;
        
        return [
            'encode_ops_sec' => round($encodeOps, 2),
            'decode_ops_sec' => round($decodeOps, 2),
            'total_ops_sec' => round($encodeOps + $decodeOps, 2)
        ];
    }
    
    private function testArrayOperations(int $iterations): array
    {
        echo "🔄 Testing Array Operations (ORM-like)\n";
        
        // Simulate ORM data manipulation
        $entities = [];
        for ($i = 0; $i < 100; $i++) {
            $entities[] = [
                'id' => $i,
                'name' => 'Entity ' . $i,
                'active' => $i % 2 === 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Filter operation (like WHERE clause)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            array_filter($entities, function($entity) {
                return $entity['active'] === true;
            });
        }
        $filterTime = microtime(true) - $start;
        $filterOps = $iterations / $filterTime;
        
        // Sort operation (like ORDER BY)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $sorted = $entities;
            usort($sorted, function($a, $b) {
                return $a['id'] <=> $b['id'];
            });
        }
        $sortTime = microtime(true) - $start;
        $sortOps = $iterations / $sortTime;
        
        // Map operation (like SELECT specific fields)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            array_map(function($entity) {
                return ['id' => $entity['id'], 'name' => $entity['name']];
            }, $entities);
        }
        $mapTime = microtime(true) - $start;
        $mapOps = $iterations / $mapTime;
        
        return [
            'filter_ops_sec' => round($filterOps, 2),
            'sort_ops_sec' => round($sortOps, 2),
            'map_ops_sec' => round($mapOps, 2),
            'total_ops_sec' => round($filterOps + $sortOps + $mapOps, 2)
        ];
    }
    
    private function testStringOperations(int $iterations): array
    {
        echo "🔄 Testing String Operations (SQL-like)\n";
        
        // SQL-like string operations
        $template = "SELECT * FROM users WHERE name = ? AND email = ? ORDER BY created_at DESC";
        
        // String replacement (parameter binding simulation)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            str_replace('?', "'value{$i}'", $template);
        }
        $replaceTime = microtime(true) - $start;
        $replaceOps = $iterations / $replaceTime;
        
        // String concatenation (query building)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $query = "SELECT * FROM table_{$i} WHERE id = {$i}";
        }
        $concatTime = microtime(true) - $start;
        $concatOps = $iterations / $concatTime;
        
        return [
            'replace_ops_sec' => round($replaceOps, 2),
            'concatenation_ops_sec' => round($concatOps, 2),
            'total_ops_sec' => round($replaceOps + $concatOps, 2)
        ];
    }
    
    private function testMemoryUsage(): array
    {
        echo "🔄 Testing Memory Usage\n";
        
        $startMemory = memory_get_usage();
        $startPeak = memory_get_peak_usage();
        
        // Simulate ORM entity collection
        $entities = [];
        for ($i = 0; $i < 5000; $i++) {
            $entities[] = [
                'id' => $i,
                'name' => 'Entity ' . $i,
                'description' => str_repeat('Data for entity ' . $i . '. ', 10),
                'metadata' => [
                    'created' => date('Y-m-d H:i:s'),
                    'tags' => ['tag1', 'tag2', 'tag3'],
                    'settings' => ['option1' => true, 'option2' => false]
                ]
            ];
        }
        
        $endMemory = memory_get_usage();
        $endPeak = memory_get_peak_usage();
        
        unset($entities);
        
        return [
            'current_mb' => round($endMemory / 1024 / 1024, 2),
            'peak_mb' => round($endPeak / 1024 / 1024, 2),
            'allocated_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2)
        ];
    }
    
    private function printResults(): void
    {
        echo "\n🏆 PivotPHP ORM Benchmark Results:\n";
        echo "=================================\n\n";
        
        echo "📊 JSON Operations:\n";
        echo "  Encode: " . number_format($this->results['json']['encode_ops_sec']) . " ops/sec\n";
        echo "  Decode: " . number_format($this->results['json']['decode_ops_sec']) . " ops/sec\n";
        echo "  Total: " . number_format($this->results['json']['total_ops_sec']) . " ops/sec\n\n";
        
        echo "📊 Array Operations (ORM-like):\n";
        echo "  Filter: " . number_format($this->results['array']['filter_ops_sec']) . " ops/sec\n";
        echo "  Sort: " . number_format($this->results['array']['sort_ops_sec']) . " ops/sec\n";
        echo "  Map: " . number_format($this->results['array']['map_ops_sec']) . " ops/sec\n";
        echo "  Total: " . number_format($this->results['array']['total_ops_sec']) . " ops/sec\n\n";
        
        echo "📊 String Operations (SQL-like):\n";
        echo "  Replace: " . number_format($this->results['string']['replace_ops_sec']) . " ops/sec\n";
        echo "  Concatenation: " . number_format($this->results['string']['concatenation_ops_sec']) . " ops/sec\n";
        echo "  Total: " . number_format($this->results['string']['total_ops_sec']) . " ops/sec\n\n";
        
        echo "📊 Memory Usage:\n";
        echo "  Current: " . $this->results['memory']['current_mb'] . " MB\n";
        echo "  Peak: " . $this->results['memory']['peak_mb'] . " MB\n";
        echo "  Allocated: " . $this->results['memory']['allocated_mb'] . " MB\n\n";
        
        // Calculate totals
        $totalOps = $this->results['json']['total_ops_sec'] + 
                   $this->results['array']['total_ops_sec'] + 
                   $this->results['string']['total_ops_sec'];
        
        echo "🚀 Performance Summary:\n";
        echo "======================\n";
        echo "Framework: PivotPHP ORM v1.0.1\n";
        echo "Total Operations: " . number_format($totalOps) . " ops/sec\n";
        echo "Memory Peak: " . $this->results['memory']['peak_mb'] . " MB\n";
        echo "Test Type: Core operations + ORM simulation\n";
    }
    
    private function saveResults(): void
    {
        $filename = '/tmp/pivotphp-orm-simple-benchmark-' . date('Y-m-d-H-i-s') . '.json';
        
        $data = [
            'benchmark' => 'pivotphp-orm-simple-benchmark',
            'framework' => 'pivotphp-orm',
            'version' => 'v1.0.1',
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
        $benchmark = new SimpleBenchmarkORM();
        $benchmark->run($iterations);
        echo "✅ PivotPHP ORM benchmark completed successfully!\n";
    } catch (Exception $e) {
        echo "❌ Benchmark failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}