#!/usr/bin/env php
<?php

/**
 * Simple Corrected Benchmark - PivotPHP Core
 * 
 * Simplified benchmark without configuration dependencies
 * Focuses on core performance metrics
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

class SimpleCorrectedBenchmark
{
    private array $results = [];
    
    public function run(int $iterations = 1000): array
    {
        echo "🚀 Simple Corrected Benchmark - PivotPHP Core\n";
        echo "=============================================\n\n";
        
        // Test JSON operations
        $jsonResults = $this->testJsonOperations($iterations);
        $this->results['json'] = $jsonResults;
        
        // Test string operations
        $stringResults = $this->testStringOperations($iterations);
        $this->results['string'] = $stringResults;
        
        // Test array operations
        $arrayResults = $this->testArrayOperations($iterations);
        $this->results['array'] = $arrayResults;
        
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
        
        // Small JSON test
        $smallData = ['id' => 1, 'name' => 'Test', 'active' => true];
        $smallEncode = $this->benchmarkJsonEncode($smallData, $iterations);
        $smallDecode = $this->benchmarkJsonDecode(json_encode($smallData), $iterations);
        
        // Medium JSON test
        $mediumData = [];
        for ($i = 0; $i < 100; $i++) {
            $mediumData[] = [
                'id' => $i,
                'name' => 'Item ' . $i,
                'description' => 'Description for item ' . $i,
                'price' => rand(100, 1000) / 100,
                'categories' => ['Electronics', 'Books', 'Clothing'],
                'metadata' => ['created' => date('Y-m-d'), 'updated' => date('Y-m-d H:i:s')]
            ];
        }
        $mediumEncode = $this->benchmarkJsonEncode($mediumData, $iterations / 2);
        $mediumDecode = $this->benchmarkJsonDecode(json_encode($mediumData), $iterations / 2);
        
        // Large JSON test
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData[] = [
                'id' => $i,
                'name' => 'Item ' . $i,
                'description' => str_repeat('Description for item ' . $i . '. ', 10),
                'price' => rand(100, 1000) / 100,
                'categories' => ['Electronics', 'Books', 'Clothing'],
                'metadata' => [
                    'created' => date('Y-m-d'),
                    'updated' => date('Y-m-d H:i:s'),
                    'views' => rand(1, 10000),
                    'rating' => rand(1, 5)
                ]
            ];
        }
        $largeEncode = $this->benchmarkJsonEncode($largeData, $iterations / 10);
        $largeDecode = $this->benchmarkJsonDecode(json_encode($largeData), $iterations / 10);
        
        return [
            'small' => [
                'encode' => $smallEncode,
                'decode' => $smallDecode,
                'total' => $smallEncode + $smallDecode
            ],
            'medium' => [
                'encode' => $mediumEncode,
                'decode' => $mediumDecode,
                'total' => $mediumEncode + $mediumDecode
            ],
            'large' => [
                'encode' => $largeEncode,
                'decode' => $largeDecode,
                'total' => $largeEncode + $largeDecode
            ]
        ];
    }
    
    private function benchmarkJsonEncode(array $data, int $iterations): float
    {
        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            json_encode($data);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        return $iterations / $time;
    }
    
    private function benchmarkJsonDecode(string $json, int $iterations): float
    {
        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            json_decode($json, true);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        return $iterations / $time;
    }
    
    private function testStringOperations(int $iterations): array
    {
        echo "🔄 Testing String Operations\n";
        
        $operations = [
            'concatenation' => function() {
                $str = '';
                for ($i = 0; $i < 100; $i++) {
                    $str .= 'test' . $i;
                }
                return $str;
            },
            'replacement' => function() {
                $str = 'Hello World Hello Universe Hello Everyone';
                return str_replace('Hello', 'Hi', $str);
            },
            'regex' => function() {
                $str = 'test123abc456def789';
                return preg_replace('/[0-9]/', 'X', $str);
            }
        ];
        
        $results = [];
        foreach ($operations as $name => $operation) {
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $operation();
            }
            $end = microtime(true);
            $time = $end - $start;
            $results[$name] = $iterations / $time;
        }
        
        return $results;
    }
    
    private function testArrayOperations(int $iterations): array
    {
        echo "🔄 Testing Array Operations\n";
        
        $operations = [
            'creation' => function() {
                $arr = [];
                for ($i = 0; $i < 100; $i++) {
                    $arr[] = $i;
                }
                return $arr;
            },
            'sorting' => function() {
                $arr = range(1, 100);
                shuffle($arr);
                sort($arr);
                return $arr;
            },
            'filtering' => function() {
                $arr = range(1, 100);
                return array_filter($arr, function($x) { return $x % 2 === 0; });
            }
        ];
        
        $results = [];
        foreach ($operations as $name => $operation) {
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $operation();
            }
            $end = microtime(true);
            $time = $end - $start;
            $results[$name] = $iterations / $time;
        }
        
        return $results;
    }
    
    private function testMemoryUsage(): array
    {
        echo "🔄 Testing Memory Usage\n";
        
        $startMemory = memory_get_usage();
        $startPeak = memory_get_peak_usage();
        
        // Create some data to measure memory usage
        $data = [];
        for ($i = 0; $i < 10000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => 'Item ' . $i,
                'data' => str_repeat('x', 100)
            ];
        }
        
        $endMemory = memory_get_usage();
        $endPeak = memory_get_peak_usage();
        
        unset($data);
        
        return [
            'current_mb' => round($endMemory / 1024 / 1024, 2),
            'peak_mb' => round($endPeak / 1024 / 1024, 2),
            'allocated_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2)
        ];
    }
    
    private function printResults(): void
    {
        echo "\n🏆 Benchmark Results:\n";
        echo "===================\n\n";
        
        echo "📊 JSON Operations:\n";
        echo "  Small Dataset:\n";
        echo "    Encode: " . number_format($this->results['json']['small']['encode']) . " ops/sec\n";
        echo "    Decode: " . number_format($this->results['json']['small']['decode']) . " ops/sec\n";
        echo "    Total: " . number_format($this->results['json']['small']['total']) . " ops/sec\n";
        
        echo "  Medium Dataset:\n";
        echo "    Encode: " . number_format($this->results['json']['medium']['encode']) . " ops/sec\n";
        echo "    Decode: " . number_format($this->results['json']['medium']['decode']) . " ops/sec\n";
        echo "    Total: " . number_format($this->results['json']['medium']['total']) . " ops/sec\n";
        
        echo "  Large Dataset:\n";
        echo "    Encode: " . number_format($this->results['json']['large']['encode']) . " ops/sec\n";
        echo "    Decode: " . number_format($this->results['json']['large']['decode']) . " ops/sec\n";
        echo "    Total: " . number_format($this->results['json']['large']['total']) . " ops/sec\n\n";
        
        echo "📊 String Operations:\n";
        foreach ($this->results['string'] as $operation => $opsPerSec) {
            echo "  " . ucfirst($operation) . ": " . number_format($opsPerSec) . " ops/sec\n";
        }
        echo "\n";
        
        echo "📊 Array Operations:\n";
        foreach ($this->results['array'] as $operation => $opsPerSec) {
            echo "  " . ucfirst($operation) . ": " . number_format($opsPerSec) . " ops/sec\n";
        }
        echo "\n";
        
        echo "📊 Memory Usage:\n";
        echo "  Current: " . $this->results['memory']['current_mb'] . " MB\n";
        echo "  Peak: " . $this->results['memory']['peak_mb'] . " MB\n";
        echo "  Allocated: " . $this->results['memory']['allocated_mb'] . " MB\n";
        
        // Calculate totals
        $totalJson = $this->results['json']['small']['total'] + 
                     $this->results['json']['medium']['total'] + 
                     $this->results['json']['large']['total'];
        
        $totalString = array_sum($this->results['string']);
        $totalArray = array_sum($this->results['array']);
        
        echo "\n🚀 Performance Summary:\n";
        echo "======================\n";
        echo "Total JSON Performance: " . number_format($totalJson) . " ops/sec\n";
        echo "Total String Performance: " . number_format($totalString) . " ops/sec\n";
        echo "Total Array Performance: " . number_format($totalArray) . " ops/sec\n";
        echo "Combined Performance: " . number_format($totalJson + $totalString + $totalArray) . " ops/sec\n";
    }
    
    private function saveResults(): void
    {
        $filename = '/tmp/simple-corrected-benchmark-' . date('Y-m-d-H-i-s') . '.json';
        
        $data = [
            'benchmark' => 'simple-corrected-benchmark',
            'framework' => 'pivotphp-core',
            'version' => 'v1.1.1',
            'timestamp' => date('c'),
            'results' => $this->results
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "\n💾 Results saved to: $filename\n";
    }
}

// Parse command line arguments
$args = [
    'iterations' => 1000
];

foreach ($argv as $arg) {
    if (strpos($arg, '--iterations=') === 0) {
        $args['iterations'] = (int) substr($arg, 13);
    }
}

// Run benchmark
if (php_sapi_name() === 'cli') {
    $benchmark = new SimpleCorrectedBenchmark();
    $benchmark->run($args['iterations']);
    echo "✅ Benchmark completed successfully!\n";
}