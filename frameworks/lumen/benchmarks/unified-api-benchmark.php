#!/usr/bin/env php
<?php

/**
 * Unified API Benchmark - Lumen
 * 
 * Cross-framework compatible benchmark for API performance testing
 * Measures req/sec, latency, and throughput metrics
 */

require_once __DIR__ . '/../vendor/autoload.php';

class UnifiedApiBenchmark 
{
    private string $baseUrl;
    private string $frameworkName;
    private $serverProcess;
    private array $results = [];

    public function __construct(string $baseUrl = 'http://127.0.0.1:8003', string $frameworkName = 'lumen')
    {
        $this->baseUrl = $baseUrl;
        $this->frameworkName = $frameworkName;
    }

    public function startServer(): bool 
    {
        $publicDir = __DIR__ . '/../public';
        $port = parse_url($this->baseUrl, PHP_URL_PORT);
        $command = "php -S 127.0.0.1:$port -t $publicDir > /dev/null 2>&1 & echo $!";
        
        $pid = shell_exec($command);
        $this->serverProcess = (int) trim($pid);
        
        // Wait for server to start
        sleep(2);
        
        return $this->isServerRunning();
    }

    public function isServerRunning(): bool 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode < 500;
    }

    public function stopServer(): void 
    {
        if ($this->serverProcess) {
            exec("kill {$this->serverProcess}");
            $this->serverProcess = null;
        }
    }

    public function makeRequest(string $endpoint, string $method = 'GET'): array 
    {
        $url = $this->baseUrl . $endpoint;
        
        $start = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => 'Test User', 'email' => 'test@example.com']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id' => 1, 'name' => 'Updated User']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        $end = microtime(true);
        $latency = ($end - $start) * 1000; // Convert to milliseconds
        
        return [
            'success' => $httpCode < 400,
            'http_code' => $httpCode,
            'latency_ms' => $latency,
            'curl_time_ms' => $totalTime * 1000,
            'response_length' => strlen($result)
        ];
    }

    public function testEndpoint(string $method, string $path, int $requests = 500): array 
    {
        echo "🚀 Testing $method $path ($requests requests)...\n";
        
        $latencies = [];
        $errors = 0;
        $totalBytes = 0;
        
        $start = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $result = $this->makeRequest($path, $method);
            
            $latencies[] = $result['latency_ms'];
            $totalBytes += $result['response_length'];
            
            if (!$result['success']) {
                $errors++;
            }
            
            // Progress indicator
            if ($i % 100 === 0) {
                $progress = round(($i / $requests) * 100);
                echo "  Progress: $progress% ($i/$requests)\r";
            }
        }
        
        $end = microtime(true);
        $totalTime = $end - $start;
        
        echo "\n";
        
        // Calculate statistics
        $requestsPerSecond = $requests / $totalTime;
        $avgLatency = array_sum($latencies) / count($latencies);
        
        sort($latencies);
        $p95Index = (int) ceil(count($latencies) * 0.95) - 1;
        $p99Index = (int) ceil(count($latencies) * 0.99) - 1;
        
        $p95Latency = $latencies[$p95Index] ?? 0;
        $p99Latency = $latencies[$p99Index] ?? 0;
        $minLatency = min($latencies);
        $maxLatency = max($latencies);
        
        $errorRate = ($errors / $requests) * 100;
        $throughputMBps = ($totalBytes / 1024 / 1024) / $totalTime;
        
        return [
            'framework' => $this->frameworkName,
            'method' => $method,
            'endpoint' => $path,
            'requests_per_second' => round($requestsPerSecond, 2),
            'avg_latency_ms' => round($avgLatency, 2),
            'min_latency_ms' => round($minLatency, 2),
            'max_latency_ms' => round($maxLatency, 2),
            'p95_latency_ms' => round($p95Latency, 2),
            'p99_latency_ms' => round($p99Latency, 2),
            'error_rate_percent' => round($errorRate, 2),
            'total_requests' => $requests,
            'total_time_seconds' => round($totalTime, 2),
            'successful_requests' => $requests - $errors,
            'failed_requests' => $errors,
            'throughput_mbps' => round($throughputMBps, 2),
            'total_bytes' => $totalBytes
        ];
    }

    public function runAllTests(): array 
    {
        $endpoints = [
            ['GET', '/'],
            ['GET', '/api/health'],
            ['GET', '/api/users'],
            ['GET', '/api/users/1'],
            ['POST', '/api/users'],
            ['PUT', '/api/users/1'],
            ['DELETE', '/api/users/1']
        ];
        
        $results = [];
        
        foreach ($endpoints as [$method, $path]) {
            $result = $this->testEndpoint($method, $path, 300);
            $results[] = $result;
            
            // Small delay between tests
            usleep(200000); // 200ms
        }
        
        return $results;
    }

    public function printResults(array $results): void 
    {
        echo "\n" . str_repeat("=", 100) . "\n";
        echo "📊 Unified API Benchmark Results - {$this->frameworkName}\n";
        echo str_repeat("=", 100) . "\n\n";
        
        echo sprintf("%-15s %-8s %-12s %-12s %-10s %-10s %-10s %-10s %-8s\n", 
            'Endpoint', 'Method', 'Req/sec', 'Avg Latency', 'Min Lat', 'Max Lat', 'P95 Lat', 'P99 Lat', 'Error %');
        echo str_repeat("-", 100) . "\n";
        
        foreach ($results as $result) {
            echo sprintf("%-15s %-8s %-12s %-12s %-10s %-10s %-10s %-10s %-8s\n",
                $result['endpoint'],
                $result['method'],
                number_format($result['requests_per_second']),
                $result['avg_latency_ms'] . 'ms',
                $result['min_latency_ms'] . 'ms',
                $result['max_latency_ms'] . 'ms',
                $result['p95_latency_ms'] . 'ms',
                $result['p99_latency_ms'] . 'ms',
                $result['error_rate_percent'] . '%'
            );
        }
        
        echo "\n" . str_repeat("=", 100) . "\n";
        
        // Summary
        $totalReqPerSec = array_sum(array_column($results, 'requests_per_second'));
        $avgLatency = array_sum(array_column($results, 'avg_latency_ms')) / count($results);
        $totalRequests = array_sum(array_column($results, 'total_requests'));
        $totalTime = array_sum(array_column($results, 'total_time_seconds'));
        $totalThroughput = array_sum(array_column($results, 'throughput_mbps'));
        
        echo "📈 API Performance Summary:\n";
        echo "  🚀 Total API Throughput: " . number_format($totalReqPerSec) . " req/sec\n";
        echo "  ⚡ Average Latency: " . round($avgLatency, 2) . "ms\n";
        echo "  📊 Total Requests: " . number_format($totalRequests) . "\n";
        echo "  ⏱️  Total Time: " . round($totalTime, 2) . "s\n";
        echo "  📡 Data Throughput: " . round($totalThroughput, 2) . " MB/s\n";
        echo "  🏷️  Framework: {$this->frameworkName}\n";
        echo "  📅 Test Date: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 100) . "\n";
    }

    public function saveResults(array $results): void 
    {
        $filename = "/tmp/unified-api-benchmark-{$this->frameworkName}-" . date('Y-m-d-H-i-s') . '.json';
        
        $data = [
            'benchmark' => 'unified-api-benchmark',
            'framework' => $this->frameworkName,
            'timestamp' => date('c'),
            'results' => $results,
            'summary' => [
                'total_throughput_req_per_sec' => array_sum(array_column($results, 'requests_per_second')),
                'avg_latency_ms' => array_sum(array_column($results, 'avg_latency_ms')) / count($results),
                'total_requests' => array_sum(array_column($results, 'total_requests')),
                'total_time_seconds' => array_sum(array_column($results, 'total_time_seconds'))
            ]
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "💾 Results saved to: $filename\n";
    }
}

// Parse command line arguments
function parseArgs($argv): array 
{
    $args = [
        'endpoint' => null,
        'method' => 'GET',
        'requests' => 300,
        'full' => false
    ];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--endpoint=') === 0) $args['endpoint'] = substr($arg, 11);
        if (strpos($arg, '--method=') === 0) $args['method'] = strtoupper(substr($arg, 9));
        if (strpos($arg, '--requests=') === 0) $args['requests'] = (int) substr($arg, 11);
        if ($arg === '--full') $args['full'] = true;
    }
    
    return $args;
}

// Main execution
if (php_sapi_name() === 'cli') {
    $args = parseArgs($argv);
    
    echo "🔥 Unified API Benchmark - Lumen\n";
    echo "=================================\n";
    echo "🎯 Cross-framework API performance testing\n\n";
    
    $benchmark = new UnifiedApiBenchmark();
    
    echo "🚀 Starting Lumen server...\n";
    if (!$benchmark->startServer()) {
        echo "❌ Failed to start server\n";
        exit(1);
    }
    
    echo "✅ Server started successfully at http://127.0.0.1:8003\n\n";
    
    try {
        if ($args['full'] || $args['endpoint'] === null) {
            $results = $benchmark->runAllTests();
        } else {
            $results = [$benchmark->testEndpoint(
                $args['method'],
                $args['endpoint'], 
                $args['requests']
            )];
        }
        
        $benchmark->printResults($results);
        $benchmark->saveResults($results);
        
    } finally {
        echo "\n🛑 Stopping server...\n";
        $benchmark->stopServer();
        echo "✅ Unified API Benchmark completed successfully\n";
    }
}