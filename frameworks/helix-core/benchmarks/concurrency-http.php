#!/usr/bin/env php
<?php

/**
 * HTTP Concurrency Benchmark for PivotPHP Core
 *
 * Tests performance under concurrent HTTP load using curl_multi
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use Shared\Interfaces\BenchmarkInterface;

class HttpConcurrencyBenchmark implements BenchmarkInterface
{
    private string $baseUrl;
    private array $endpoints = [
        ['method' => 'GET', 'path' => '/'],
        ['method' => 'GET', 'path' => '/users'],
        ['method' => 'GET', 'path' => '/users/1'],
        ['method' => 'POST', 'path' => '/users']
    ];

    public function __construct(string $baseUrl = 'http://localhost:8080')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function run(int $iterations = 1000, int $warmup = 100): array
    {
        $scenarios = [
            'low' => [
                'concurrency' => 10,
                'total_requests' => 1000,
                'description' => 'Low load - 10 concurrent connections'
            ],
            'normal' => [
                'concurrency' => 50,
                'total_requests' => 5000,
                'description' => 'Normal load - 50 concurrent connections'
            ],
            'high' => [
                'concurrency' => 100,
                'total_requests' => 10000,
                'description' => 'High load - 100 concurrent connections'
            ]
        ];

        $results = [];

        foreach ($scenarios as $scenario => $config) {
            echo "Running {$scenario} concurrency scenario...\n";

            $stats = $this->runConcurrentRequests(
                $config['concurrency'],
                $config['total_requests']
            );

            $results[$scenario] = array_merge($stats, [
                'scenario' => $scenario,
                'description' => $config['description']
            ]);

            echo "  Completed: " . round($stats['requests_per_second'], 2) . " req/sec\n";
            echo "  Success rate: " . round($stats['success_rate'], 2) . "%\n";
            echo "  Avg latency: " . round($stats['latency_ms']['avg'], 2) . "ms\n\n";
        }

        return [
            'benchmark' => $this->getName(),
            'framework' => 'helix-core',
            'description' => $this->getDescription(),
            'base_url' => $this->baseUrl,
            'scenarios' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function runConcurrentRequests(int $concurrency, int $totalRequests): array
    {
        $multiHandle = curl_multi_init();
        curl_multi_setopt($multiHandle, CURLMOPT_MAXCONNECTS, $concurrency);

        $curlHandles = [];
        $responses = [];
        $latencies = [];
        $errors = 0;
        $requestCount = 0;
        $completedCount = 0;

        $startTime = microtime(true);

        // Process requests
        while ($completedCount < $totalRequests) {
            // Add new requests up to concurrency limit
            while (count($curlHandles) < $concurrency && $requestCount < $totalRequests) {
                $endpoint = $this->endpoints[array_rand($this->endpoints)];
                $ch = $this->createCurlHandle($endpoint);

                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[(int)$ch] = [
                    'handle' => $ch,
                    'start_time' => microtime(true),
                    'endpoint' => $endpoint
                ];
                $requestCount++;
            }

            // Execute requests
            $active = null;
            do {
                $status = curl_multi_exec($multiHandle, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            // Check for completed requests
            while ($info = curl_multi_info_read($multiHandle)) {
                $ch = $info['handle'];
                $chId = (int)$ch;

                if (isset($curlHandles[$chId])) {
                    $endTime = microtime(true);
                    $latency = $endTime - $curlHandles[$chId]['start_time'];
                    $latencies[] = $latency;

                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($httpCode >= 400 || $info['result'] !== CURLE_OK) {
                        $errors++;
                    }

                    curl_multi_remove_handle($multiHandle, $ch);
                    curl_close($ch);
                    unset($curlHandles[$chId]);
                    $completedCount++;
                }
            }

            // Wait for activity
            if ($active) {
                curl_multi_select($multiHandle, 0.1);
            }
        }

        curl_multi_close($multiHandle);

        $totalTime = microtime(true) - $startTime;

        // Calculate statistics
        sort($latencies);
        $successCount = $completedCount - $errors;

        return [
            'total_requests' => $totalRequests,
            'concurrency' => $concurrency,
            'total_time' => $totalTime,
            'requests_per_second' => $completedCount / $totalTime,
            'completed_requests' => $completedCount,
            'successful_requests' => $successCount,
            'failed_requests' => $errors,
            'success_rate' => ($successCount / $completedCount) * 100,
            'latency_ms' => [
                'min' => min($latencies) * 1000,
                'max' => max($latencies) * 1000,
                'avg' => (array_sum($latencies) / count($latencies)) * 1000,
                'p50' => $this->percentile($latencies, 0.50) * 1000,
                'p95' => $this->percentile($latencies, 0.95) * 1000,
                'p99' => $this->percentile($latencies, 0.99) * 1000,
            ]
        ];
    }

    private function createCurlHandle(array $endpoint): \CurlHandle
    {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint['path'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        if ($endpoint['method'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'name' => 'Test User ' . rand(1, 1000),
                'email' => 'test' . rand(1, 1000) . '@example.com'
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        return $ch;
    }

    private function percentile(array $values, float $percentile): float
    {
        $count = count($values);
        if ($count === 0) return 0;

        $index = (int) ceil($count * $percentile) - 1;
        return $values[min($index, $count - 1)] ?? 0;
    }

    public function getName(): string
    {
        return 'concurrency/http-load-test';
    }

    public function getDescription(): string
    {
        return 'Tests framework performance under concurrent HTTP load (requires running server)';
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['url:', 'json']);
    $baseUrl = $options['url'] ?? 'http://localhost:8080';

    echo "Testing against: $baseUrl\n\n";

    $benchmark = new HttpConcurrencyBenchmark($baseUrl);
    $results = $benchmark->run();

    if (isset($options['json'])) {
        echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Benchmark: {$results['benchmark']}\n";
        echo "Framework: {$results['framework']}\n";
        echo "Base URL: {$results['base_url']}\n\n";

        foreach ($results['scenarios'] as $scenario => $stats) {
            echo "=== " . strtoupper($scenario) . " Load Scenario ===\n";
            echo "{$stats['description']}\n";
            echo "Total requests: {$stats['total_requests']}\n";
            echo "Total time: " . round($stats['total_time'], 2) . "s\n";
            echo "Requests/sec: " . round($stats['requests_per_second'], 2) . "\n";
            echo "Success rate: " . round($stats['success_rate'], 2) . "%\n";
            echo "Failed requests: {$stats['failed_requests']}\n";
            echo "Latency (ms):\n";
            echo "  Min: " . round($stats['latency_ms']['min'], 2) . "\n";
            echo "  Avg: " . round($stats['latency_ms']['avg'], 2) . "\n";
            echo "  p50: " . round($stats['latency_ms']['p50'], 2) . "\n";
            echo "  p95: " . round($stats['latency_ms']['p95'], 2) . "\n";
            echo "  p99: " . round($stats['latency_ms']['p99'], 2) . "\n";
            echo "  Max: " . round($stats['latency_ms']['max'], 2) . "\n";
            echo "\n";
        }
    }
}
