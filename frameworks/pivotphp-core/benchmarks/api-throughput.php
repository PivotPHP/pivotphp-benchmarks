#!/usr/bin/env php
<?php

/**
 * API Throughput Benchmark - Real HTTP Requests
 * 
 * Tests actual HTTP API performance with req/sec metrics
 * Focuses on real-world API usage patterns
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Check if Guzzle is available
if (!class_exists('GuzzleHttp\Client')) {
    echo "⚠️  Guzzle HTTP client not found. Installing...\n";
    exec('cd ' . __DIR__ . '/../ && composer require guzzlehttp/guzzle');
}

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Exception\RequestException;

class ApiThroughputBenchmark 
{
    private Client $client;
    private string $baseUrl;
    private $serverProcess;
    private array $results = [];

    public function __construct(string $baseUrl = 'http://127.0.0.1:8001')
    {
        $this->baseUrl = $baseUrl;
        $this->client = new Client([
            'timeout' => 10,
            'http_errors' => false,
            'verify' => false,
            'allow_redirects' => false
        ]);
    }

    public function startServer(): bool 
    {
        $publicDir = __DIR__ . '/../public';
        $command = "php -S 127.0.0.1:8001 -t $publicDir > /dev/null 2>&1 & echo $!";
        
        $pid = shell_exec($command);
        $this->serverProcess = (int) trim($pid);
        
        // Wait for server to start
        sleep(2);
        
        return $this->isServerRunning();
    }

    public function isServerRunning(): bool 
    {
        try {
            $response = $this->client->get($this->baseUrl . '/');
            return $response->getStatusCode() < 500;
        } catch (RequestException $e) {
            return false;
        }
    }

    public function stopServer(): void 
    {
        if ($this->serverProcess) {
            exec("kill {$this->serverProcess}");
            $this->serverProcess = null;
        }
    }

    public function measureThroughput(string $endpoint, string $method = 'GET', int $requests = 1000, int $concurrency = 10): array 
    {
        $url = $this->baseUrl . $endpoint;
        $batchSize = $concurrency;
        $batches = ceil($requests / $batchSize);
        
        $totalRequests = 0;
        $totalTime = 0;
        $latencies = [];
        $errors = 0;
        
        echo "🚀 Testing $method $endpoint ($requests requests, $concurrency concurrent)...\n";
        
        $overallStart = microtime(true);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $currentBatchSize = min($batchSize, $requests - ($batch * $batchSize));
            
            $promises = [];
            $batchStart = microtime(true);
            
            // Create concurrent requests
            for ($i = 0; $i < $currentBatchSize; $i++) {
                $requestStart = microtime(true);
                
                if ($method === 'GET') {
                    $promise = $this->client->getAsync($url);
                } elseif ($method === 'POST') {
                    $promise = $this->client->postAsync($url, [
                        'json' => ['name' => 'Test User', 'email' => 'test@example.com']
                    ]);
                } elseif ($method === 'PUT') {
                    $promise = $this->client->putAsync($url, [
                        'json' => ['id' => 1, 'name' => 'Updated User']
                    ]);
                } elseif ($method === 'DELETE') {
                    $promise = $this->client->deleteAsync($url);
                } else {
                    throw new InvalidArgumentException("Unsupported HTTP method: $method");
                }
                
                $promises[] = $promise->then(
                    function ($response) use ($requestStart, &$latencies) {
                        $latency = (microtime(true) - $requestStart) * 1000; // Convert to ms
                        $latencies[] = $latency;
                        return $response;
                    }
                );
            }
            
            // Wait for all requests to complete
            $responses = Utils::settle($promises)->wait();
            
            $batchEnd = microtime(true);
            $batchTime = $batchEnd - $batchStart;
            
            // Count errors
            foreach ($responses as $response) {
                if ($response['state'] === 'rejected' || 
                    (isset($response['value']) && $response['value']->getStatusCode() >= 400)) {
                    $errors++;
                }
            }
            
            $totalRequests += $currentBatchSize;
            $totalTime += $batchTime;
            
            // Progress indicator
            $progress = round(($batch + 1) / $batches * 100);
            echo "  Progress: $progress% ($totalRequests/$requests requests)\r";
        }
        
        $overallEnd = microtime(true);
        $overallTime = $overallEnd - $overallStart;
        
        echo "\n";
        
        // Calculate statistics
        $requestsPerSecond = $totalRequests / $overallTime;
        $avgLatency = array_sum($latencies) / count($latencies);
        
        sort($latencies);
        $p95Index = (int) ceil(count($latencies) * 0.95) - 1;
        $p99Index = (int) ceil(count($latencies) * 0.99) - 1;
        
        $p95Latency = $latencies[$p95Index] ?? 0;
        $p99Latency = $latencies[$p99Index] ?? 0;
        
        $errorRate = ($errors / $totalRequests) * 100;
        
        return [
            'endpoint' => $endpoint,
            'method' => $method,
            'requests_per_second' => round($requestsPerSecond, 2),
            'avg_latency_ms' => round($avgLatency, 2),
            'p95_latency_ms' => round($p95Latency, 2),
            'p99_latency_ms' => round($p99Latency, 2),
            'error_rate_percent' => round($errorRate, 2),
            'total_requests' => $totalRequests,
            'total_time_seconds' => round($overallTime, 2),
            'concurrent_connections' => $concurrency,
            'successful_requests' => $totalRequests - $errors,
            'failed_requests' => $errors
        ];
    }

    public function runFullBenchmark(): array 
    {
        $endpoints = [
            ['/', 'GET'],
            ['/api/users', 'GET'],
            ['/api/users', 'POST'],
            ['/api/users/1', 'PUT'],
            ['/api/users/1', 'DELETE'],
        ];
        
        $concurrencyLevels = [1, 10, 50];
        $results = [];
        
        foreach ($endpoints as [$endpoint, $method]) {
            foreach ($concurrencyLevels as $concurrency) {
                $result = $this->measureThroughput($endpoint, $method, 1000, $concurrency);
                $results[] = $result;
                
                // Small delay between tests
                usleep(100000); // 100ms
            }
        }
        
        return $results;
    }

    public function printResults(array $results): void 
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 API Throughput Benchmark Results - PivotPHP Core v1.1.1\n";
        echo str_repeat("=", 80) . "\n\n";
        
        echo sprintf("%-20s %-8s %-12s %-12s %-12s %-12s %-10s\n", 
            'Endpoint', 'Method', 'Req/sec', 'Avg Latency', 'P95 Latency', 'P99 Latency', 'Error %');
        echo str_repeat("-", 80) . "\n";
        
        foreach ($results as $result) {
            echo sprintf("%-20s %-8s %-12s %-12s %-12s %-12s %-10s\n",
                $result['endpoint'],
                $result['method'],
                number_format($result['requests_per_second']),
                $result['avg_latency_ms'] . 'ms',
                $result['p95_latency_ms'] . 'ms',
                $result['p99_latency_ms'] . 'ms',
                $result['error_rate_percent'] . '%'
            );
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        
        // Summary
        $totalReqPerSec = array_sum(array_column($results, 'requests_per_second'));
        $avgLatency = array_sum(array_column($results, 'avg_latency_ms')) / count($results);
        $totalRequests = array_sum(array_column($results, 'total_requests'));
        $totalTime = array_sum(array_column($results, 'total_time_seconds'));
        
        echo "📈 Summary:\n";
        echo "  Total Throughput: " . number_format($totalReqPerSec) . " req/sec\n";
        echo "  Average Latency: " . round($avgLatency, 2) . "ms\n";
        echo "  Total Requests: " . number_format($totalRequests) . "\n";
        echo "  Total Time: " . round($totalTime, 2) . "s\n";
        echo "  Framework: PivotPHP Core v1.1.1\n";
        echo "  Test Date: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n";
    }

    public function saveResults(array $results): void 
    {
        $filename = '/tmp/api-throughput-' . date('Y-m-d-H-i-s') . '.json';
        
        $data = [
            'benchmark' => 'api-throughput',
            'framework' => 'pivotphp-core',
            'version' => '1.1.1',
            'timestamp' => date('c'),
            'results' => $results
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "💾 Results saved to: $filename\n";
    }
}

// Parse command line arguments
function parseArgs($argv): array 
{
    $args = [
        'endpoint' => '/',
        'method' => 'GET',
        'requests' => 1000,
        'concurrency' => 10,
        'full' => false
    ];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--endpoint=') === 0) $args['endpoint'] = substr($arg, 11);
        if (strpos($arg, '--method=') === 0) $args['method'] = strtoupper(substr($arg, 9));
        if (strpos($arg, '--requests=') === 0) $args['requests'] = (int) substr($arg, 11);
        if (strpos($arg, '--concurrency=') === 0) $args['concurrency'] = (int) substr($arg, 14);
        if ($arg === '--full') $args['full'] = true;
    }
    
    return $args;
}

// Main execution
if (php_sapi_name() === 'cli') {
    $args = parseArgs($argv);
    
    echo "🔥 API Throughput Benchmark - PivotPHP Core v1.1.1\n";
    echo "====================================================\n";
    
    $benchmark = new ApiThroughputBenchmark();
    
    echo "🚀 Starting PHP built-in server...\n";
    if (!$benchmark->startServer()) {
        echo "❌ Failed to start server\n";
        exit(1);
    }
    
    echo "✅ Server started successfully\n\n";
    
    try {
        if ($args['full']) {
            $results = $benchmark->runFullBenchmark();
        } else {
            $results = [$benchmark->measureThroughput(
                $args['endpoint'], 
                $args['method'], 
                $args['requests'], 
                $args['concurrency']
            )];
        }
        
        $benchmark->printResults($results);
        $benchmark->saveResults($results);
        
    } finally {
        echo "\n🛑 Stopping server...\n";
        $benchmark->stopServer();
        echo "✅ Benchmark completed\n";
    }
}