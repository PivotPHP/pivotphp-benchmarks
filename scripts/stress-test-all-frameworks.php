#!/usr/bin/env php
<?php

/**
 * Comprehensive Stress Test for All Frameworks
 * 
 * Tests synchronous performance with loads: 100, 1000, 10000 requests
 * Measures concurrent performance and stress resistance
 */

require_once __DIR__ . '/../vendor/autoload.php';

class FrameworkStressTest
{
    private array $frameworks = [
        'pivotphp-core' => 'PivotPHP Core',
        'pivotphp-orm' => 'PivotPHP + ORM',
        'slim4' => 'Slim Framework 4',
        'lumen' => 'Laravel Lumen',
        'flight' => 'Flight Framework'
    ];
    
    private array $loadLevels = [100, 1000, 10000];
    
    private array $endpoints = [
        'simple' => '/api/status',
        'json' => '/api/users',
        'complex' => '/api/users/1'
    ];
    
    private array $results = [];
    
    public function run(): void
    {
        echo "=== Framework Comprehensive Stress Test ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n\n";
        
        // Ensure all frameworks are running
        $this->checkFrameworks();
        
        // Run synchronous stress tests
        echo "\n== SYNCHRONOUS STRESS TESTS ==\n";
        $this->runSynchronousTests();
        
        // Run concurrent stress tests
        echo "\n== CONCURRENT STRESS TESTS ==\n";
        $this->runConcurrentTests();
        
        // Generate comprehensive report
        $this->generateReport();
    }
    
    private function checkFrameworks(): void
    {
        echo "Checking framework availability...\n";
        
        foreach ($this->frameworks as $key => $name) {
            $port = $this->getFrameworkPort($key);
            $url = "http://localhost:{$port}/api/status";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 || $httpCode === 204) {
                echo "  ✓ {$name} (port {$port}): OK\n";
            } else {
                echo "  ✗ {$name} (port {$port}): NOT RESPONDING\n";
            }
        }
    }
    
    private function runSynchronousTests(): void
    {
        foreach ($this->frameworks as $framework => $name) {
            echo "\nTesting {$name}...\n";
            $this->results[$framework]['synchronous'] = [];
            
            foreach ($this->loadLevels as $load) {
                echo "  Load level: {$load} requests\n";
                
                foreach ($this->endpoints as $type => $endpoint) {
                    $result = $this->runSynchronousTest($framework, $endpoint, $load);
                    $this->results[$framework]['synchronous'][$load][$type] = $result;
                    
                    echo sprintf(
                        "    %s: %.2f req/s, %.2fms avg, %.2fms p95\n",
                        ucfirst($type),
                        $result['throughput'],
                        $result['avg_latency'],
                        $result['p95_latency']
                    );
                }
            }
        }
    }
    
    private function runSynchronousTest(string $framework, string $endpoint, int $requests): array
    {
        $port = $this->getFrameworkPort($framework);
        $url = "http://localhost:{$port}{$endpoint}";
        
        // Warmup
        for ($i = 0; $i < 10; $i++) {
            $this->makeRequest($url);
        }
        
        // Actual test
        $latencies = [];
        $errors = 0;
        $start = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $reqStart = microtime(true);
            $success = $this->makeRequest($url);
            $reqEnd = microtime(true);
            
            if ($success) {
                $latencies[] = ($reqEnd - $reqStart) * 1000; // Convert to ms
            } else {
                $errors++;
            }
            
            // Progress indicator for long tests
            if ($requests >= 1000 && $i % 1000 === 0 && $i > 0) {
                echo ".";
            }
        }
        
        $end = microtime(true);
        $duration = $end - $start;
        
        // Calculate metrics
        sort($latencies);
        $successCount = count($latencies);
        
        return [
            'requests' => $requests,
            'successful' => $successCount,
            'errors' => $errors,
            'duration' => round($duration, 3),
            'throughput' => $successCount > 0 ? round($successCount / $duration, 2) : 0,
            'avg_latency' => $successCount > 0 ? round(array_sum($latencies) / $successCount, 3) : 0,
            'min_latency' => $successCount > 0 ? round(min($latencies), 3) : 0,
            'max_latency' => $successCount > 0 ? round(max($latencies), 3) : 0,
            'p50_latency' => $this->percentile($latencies, 50),
            'p95_latency' => $this->percentile($latencies, 95),
            'p99_latency' => $this->percentile($latencies, 99)
        ];
    }
    
    private function runConcurrentTests(): void
    {
        foreach ($this->frameworks as $framework => $name) {
            echo "\nConcurrent testing {$name}...\n";
            $this->results[$framework]['concurrent'] = [];
            
            // Test different concurrency levels
            $concurrencyLevels = [10, 50, 100];
            
            foreach ($concurrencyLevels as $concurrency) {
                echo "  Concurrency level: {$concurrency}\n";
                
                foreach ($this->endpoints as $type => $endpoint) {
                    $result = $this->runConcurrentTest($framework, $endpoint, 1000, $concurrency);
                    $this->results[$framework]['concurrent'][$concurrency][$type] = $result;
                    
                    echo sprintf(
                        "    %s: %.2f req/s, %.2fms avg latency\n",
                        ucfirst($type),
                        $result['throughput'],
                        $result['avg_latency']
                    );
                }
            }
        }
    }
    
    private function runConcurrentTest(string $framework, string $endpoint, int $totalRequests, int $concurrency): array
    {
        $port = $this->getFrameworkPort($framework);
        $url = "http://localhost:{$port}{$endpoint}";
        
        // Use curl_multi for concurrent requests
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        
        $results = [];
        $completed = 0;
        $errors = 0;
        
        $start = microtime(true);
        
        // Process requests in batches
        $batches = ceil($totalRequests / $concurrency);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $batchSize = min($concurrency, $totalRequests - ($batch * $concurrency));
            
            // Initialize batch
            for ($i = 0; $i < $batchSize; $i++) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HEADER, false);
                
                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[] = $ch;
            }
            
            // Execute batch
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);
            
            // Collect results
            foreach ($curlHandles as $ch) {
                $info = curl_getinfo($ch);
                if ($info['http_code'] === 200) {
                    $results[] = $info['total_time'] * 1000; // Convert to ms
                    $completed++;
                } else {
                    $errors++;
                }
                
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }
            
            $curlHandles = [];
        }
        
        curl_multi_close($multiHandle);
        
        $end = microtime(true);
        $duration = $end - $start;
        
        // Calculate metrics
        sort($results);
        
        return [
            'total_requests' => $totalRequests,
            'concurrency' => $concurrency,
            'successful' => $completed,
            'errors' => $errors,
            'duration' => round($duration, 3),
            'throughput' => $completed > 0 ? round($completed / $duration, 2) : 0,
            'avg_latency' => $completed > 0 ? round(array_sum($results) / count($results), 3) : 0,
            'min_latency' => $completed > 0 ? round(min($results), 3) : 0,
            'max_latency' => $completed > 0 ? round(max($results), 3) : 0,
            'p50_latency' => $this->percentile($results, 50),
            'p95_latency' => $this->percentile($results, 95),
            'p99_latency' => $this->percentile($results, 99)
        ];
    }
    
    private function makeRequest(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    private function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return round($values[$index] ?? 0, 3);
    }
    
    private function getFrameworkPort(string $framework): int
    {
        $ports = [
            'pivotphp-core' => 8001,
            'pivotphp-orm' => 8002,
            'slim4' => 8003,
            'lumen' => 8004,
            'flight' => 8005
        ];
        
        return $ports[$framework] ?? 8000;
    }
    
    private function generateReport(): void
    {
        $timestamp = date('Y-m-d-His');
        $filename = __DIR__ . "/../results/stress-test-{$timestamp}.json";
        
        $report = [
            'test' => 'Comprehensive Framework Stress Test',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'results' => $this->results
        ];
        
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        
        // Generate summary
        echo "\n\n=== STRESS TEST SUMMARY ===\n\n";
        
        // Synchronous performance summary
        echo "SYNCHRONOUS PERFORMANCE (10,000 requests):\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-20s %15s %15s %15s\n", "Framework", "Simple", "JSON", "Complex");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->frameworks as $key => $name) {
            if (isset($this->results[$key]['synchronous'][10000])) {
                $simple = $this->results[$key]['synchronous'][10000]['simple']['throughput'] ?? 0;
                $json = $this->results[$key]['synchronous'][10000]['json']['throughput'] ?? 0;
                $complex = $this->results[$key]['synchronous'][10000]['complex']['throughput'] ?? 0;
                
                echo sprintf(
                    "%-20s %15s %15s %15s\n",
                    $name,
                    number_format($simple, 2) . " req/s",
                    number_format($json, 2) . " req/s",
                    number_format($complex, 2) . " req/s"
                );
            }
        }
        
        // Concurrent performance summary
        echo "\n\nCONCURRENT PERFORMANCE (100 concurrent connections):\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-20s %15s %15s %15s\n", "Framework", "Simple", "JSON", "Complex");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->frameworks as $key => $name) {
            if (isset($this->results[$key]['concurrent'][100])) {
                $simple = $this->results[$key]['concurrent'][100]['simple']['throughput'] ?? 0;
                $json = $this->results[$key]['concurrent'][100]['json']['throughput'] ?? 0;
                $complex = $this->results[$key]['concurrent'][100]['complex']['throughput'] ?? 0;
                
                echo sprintf(
                    "%-20s %15s %15s %15s\n",
                    $name,
                    number_format($simple, 2) . " req/s",
                    number_format($json, 2) . " req/s",
                    number_format($complex, 2) . " req/s"
                );
            }
        }
        
        echo "\n\nDetailed results saved to: {$filename}\n";
    }
}

// Run the stress test
$test = new FrameworkStressTest();
$test->run();