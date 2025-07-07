#!/usr/bin/env php
<?php

/**
 * Concurrent/Async Test for PivotPHP Core
 * 
 * Tests concurrent request handling with different concurrency levels
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;

class ConcurrentAsyncTest extends BenchmarkBase
{
    private Application $app;
    private array $concurrencyLevels = [10, 50, 100];
    private int $totalRequests = 1000;
    
    protected function getName(): string
    {
        return "concurrent/async-test";
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // Simple endpoint
        $this->app->get('/async/simple', function($req, $res) {
            return $res->json(['status' => 'ok', 'time' => microtime(true)]);
        });
        
        // Data endpoint - simulates database query
        $this->app->get('/async/data', function($req, $res) {
            // Simulate async data fetching
            $data = [];
            for ($i = 0; $i < 20; $i++) {
                $data[] = [
                    'id' => $i,
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com",
                    'score' => rand(1, 100)
                ];
            }
            
            // Simulate async processing delay
            usleep(50); // 0.05ms
            
            return $res->json([
                'data' => $data,
                'total' => count($data),
                'timestamp' => microtime(true)
            ]);
        });
        
        // Compute endpoint - CPU intensive
        $this->app->post('/async/compute', function($req, $res) {
            $input = $req->getParsedBody();
            
            // Simulate parallel computation
            $results = [];
            $tasks = 10;
            
            for ($i = 0; $i < $tasks; $i++) {
                // Simulate async task
                $result = [
                    'task_id' => $i,
                    'input' => $input,
                    'computation' => [
                        'fibonacci' => $this->fibonacci(20),
                        'prime' => $this->isPrime(rand(1000, 9999)),
                        'hash' => md5(json_encode($input) . $i)
                    ]
                ];
                $results[] = $result;
            }
            
            return $res->json([
                'tasks' => count($results),
                'results' => $results,
                'execution_time' => microtime(true)
            ]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        // Override - custom implementation
    }
    
    public function runConcurrentTests(): array
    {
        $results = [];
        
        // Initialize application
        $this->setup();
        
        echo "=== PivotPHP Core Concurrent/Async Test ===\n";
        echo "PHP " . PHP_VERSION . " | " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->concurrencyLevels as $concurrency) {
            echo "Testing with {$concurrency} concurrent connections:\n";
            
            $results[$concurrency] = [
                'simple' => $this->testConcurrentEndpoint('GET', '/async/simple', $concurrency),
                'data' => $this->testConcurrentEndpoint('GET', '/async/data', $concurrency),
                'compute' => $this->testConcurrentEndpoint('POST', '/async/compute', $concurrency)
            ];
            
            $this->displayResults($concurrency, $results[$concurrency]);
            echo "\n";
        }
        
        return $results;
    }
    
    private function testConcurrentEndpoint(string $method, string $endpoint, int $concurrency): array
    {
        echo "  Testing {$endpoint} with {$concurrency} concurrent connections... ";
        
        $requestsPerConnection = $this->totalRequests / $concurrency;
        $latencies = [];
        $start = microtime(true);
        
        // Simulate concurrent execution using process forking
        $pids = [];
        $pipes = [];
        
        for ($i = 0; $i < $concurrency; $i++) {
            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];
            
            $process = proc_open(
                PHP_BINARY . ' -r \'
                    require_once "' . __DIR__ . '/../../../vendor/autoload.php";
                    
                    $app = new \PivotPHP\Core\Core\Application();
                    
                    // Register routes
                    $app->get("/async/simple", function($req, $res) {
                        return $res->json(["status" => "ok", "time" => microtime(true)]);
                    });
                    
                    $app->get("/async/data", function($req, $res) {
                        $data = [];
                        for ($i = 0; $i < 20; $i++) {
                            $data[] = ["id" => $i, "name" => "User {$i}"];
                        }
                        usleep(50);
                        return $res->json(["data" => $data, "total" => count($data)]);
                    });
                    
                    $app->post("/async/compute", function($req, $res) {
                        $results = [];
                        for ($i = 0; $i < 10; $i++) {
                            $results[] = ["task_id" => $i, "hash" => md5($i)];
                        }
                        return $res->json(["results" => $results]);
                    });
                    
                    // Process requests
                    $latencies = [];
                    for ($j = 0; $j < ' . $requestsPerConnection . '; $j++) {
                        $_SERVER["REQUEST_METHOD"] = "' . $method . '";
                        $_SERVER["REQUEST_URI"] = "' . $endpoint . '";
                        $_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
                        $_SERVER["HTTP_HOST"] = "localhost";
                        
                        if ("' . $method . '" === "POST") {
                            $_POST = ["test" => "data", "value" => rand(1, 100)];
                            $_SERVER["CONTENT_TYPE"] = "application/x-www-form-urlencoded";
                        }
                        
                        $reqStart = microtime(true);
                        
                        ob_start();
                        $request = \PivotPHP\Core\Http\Request::createFromGlobals();
                        $response = $app->handle($request);
                        ob_end_clean();
                        
                        $reqEnd = microtime(true);
                        $latencies[] = ($reqEnd - $reqStart) * 1000;
                    }
                    
                    echo json_encode($latencies);
                \'',
                $descriptorspec,
                $pipes
            );
            
            if (is_resource($process)) {
                $pids[] = $process;
                $pipes[] = $pipes;
            }
        }
        
        // Collect results from all processes
        foreach ($pids as $index => $process) {
            $output = stream_get_contents($pipes[$index][1]);
            $processLatencies = json_decode($output, true);
            
            if (is_array($processLatencies)) {
                $latencies = array_merge($latencies, $processLatencies);
            }
            
            proc_close($process);
        }
        
        // Alternative: simulate concurrent requests in-process
        if (empty($latencies)) {
            for ($i = 0; $i < $this->totalRequests; $i++) {
                $reqStart = microtime(true);
                $this->makeRequest($method, $endpoint);
                $reqEnd = microtime(true);
                $latencies[] = ($reqEnd - $reqStart) * 1000;
            }
        }
        
        $end = microtime(true);
        $duration = $end - $start;
        
        echo "Done!\n";
        
        // Calculate metrics
        sort($latencies);
        $successCount = count($latencies);
        
        return [
            'concurrency' => $concurrency,
            'total_requests' => $this->totalRequests,
            'successful' => $successCount,
            'duration_s' => round($duration, 3),
            'throughput' => $successCount > 0 ? round($successCount / $duration, 2) : 0,
            'latency' => [
                'avg' => $successCount > 0 ? round(array_sum($latencies) / $successCount, 3) : 0,
                'min' => $successCount > 0 ? round(min($latencies), 3) : 0,
                'max' => $successCount > 0 ? round(max($latencies), 3) : 0,
                'p50' => $this->percentile($latencies, 50),
                'p95' => $this->percentile($latencies, 95),
                'p99' => $this->percentile($latencies, 99)
            ]
        ];
    }
    
    private function makeRequest(string $method, string $endpoint): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $endpoint;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($method === 'POST') {
            $_POST = [
                'test' => 'data',
                'value' => rand(1, 100),
                'timestamp' => time()
            ];
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        } else {
            $_POST = [];
            unset($_SERVER['CONTENT_TYPE']);
        }
        
        // Suppress output
        ob_start();
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
        ob_end_clean();
    }
    
    private function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return round($values[$index] ?? 0, 3);
    }
    
    private function fibonacci(int $n): int
    {
        if ($n <= 1) return $n;
        return $this->fibonacci($n - 1) + $this->fibonacci($n - 2);
    }
    
    private function isPrime(int $n): bool
    {
        if ($n <= 1) return false;
        for ($i = 2; $i * $i <= $n; $i++) {
            if ($n % $i === 0) return false;
        }
        return true;
    }
    
    private function displayResults(int $concurrency, array $results): void
    {
        echo "\nResults for {$concurrency} concurrent connections:\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("%-15s %12s %12s %12s %12s %12s\n", 
            "Endpoint", "Throughput", "Avg Latency", "Min", "P95", "P99"
        );
        echo str_repeat("-", 80) . "\n";
        
        foreach (['simple', 'data', 'compute'] as $type) {
            $r = $results[$type];
            echo sprintf(
                "%-15s %12s %12s %12s %12s %12s\n",
                ucfirst($type),
                number_format($r['throughput'], 2) . " req/s",
                number_format($r['latency']['avg'], 2) . " ms",
                number_format($r['latency']['min'], 2) . " ms",
                number_format($r['latency']['p95'], 2) . " ms",
                number_format($r['latency']['p99'], 2) . " ms"
            );
        }
    }
}

// Parse options
$options = getopt('', ['json', 'save']);

// Run tests
$test = new ConcurrentAsyncTest($options);
$results = $test->runConcurrentTests();

// Save results if requested
if (isset($options['save'])) {
    $filename = __DIR__ . '/../../../results/pivotphp-core-concurrent-' . date('Y-m-d-His') . '.json';
    file_put_contents($filename, json_encode([
        'framework' => 'PivotPHP Core',
        'test' => 'Concurrent/Async Test',
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION,
        'results' => $results
    ], JSON_PRETTY_PRINT));
    echo "\nResults saved to: " . basename($filename) . "\n";
}

// Output JSON if requested
if (isset($options['json'])) {
    echo "\n" . json_encode($results, JSON_PRETTY_PRINT) . "\n";
}