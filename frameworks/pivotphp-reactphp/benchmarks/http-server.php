#!/usr/bin/env php
<?php

/**
 * HTTP Server Benchmark for PivotPHP ReactPHP
 *
 * Tests the actual HTTP server performance using client requests
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use Shared\Interfaces\BenchmarkInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class ReactPHPHttpServerBenchmark implements BenchmarkInterface
{
    private Client $client;
    private string $baseUrl;
    private $serverProcess;

    public function __construct()
    {
        $this->baseUrl = 'http://127.0.0.1:8000';
        $this->client = new Client([
            'timeout' => 10,
            'http_errors' => false,
        ]);
    }

    public function run(int $iterations = 1000, int $warmup = 100): array
    {
        $startMemory = memory_get_usage();
        
        // Start ReactPHP server
        if (!$this->startServer()) {
            throw new \Exception('Failed to start ReactPHP server');
        }

        // Wait for server to be ready
        sleep(2);

        if (!$this->isServerRunning()) {
            throw new \Exception('ReactPHP server is not responding');
        }

        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->client->get($this->baseUrl . '/health');
        }

        // Actual benchmark - Simple requests
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $response = $this->client->get($this->baseUrl . '/health');
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Server returned non-200 status: ' . $response->getStatusCode());
            }
        }
        $end = microtime(true);

        $duration = $end - $start;
        $memoryUsed = memory_get_usage() - $startMemory;

        // Stop server
        $this->stopServer();

        return [
            'framework' => 'pivotphp-reactphp',
            'benchmark' => 'http-server',
            'iterations' => $iterations,
            'duration' => $duration,
            'ops_per_second' => round($iterations / $duration),
            'memory_used' => $memoryUsed,
            'memory_peak' => memory_get_peak_usage(true),
            'runtime_type' => 'continuous',
            'server_type' => 'ReactPHP HTTP Server'
        ];
    }

    private function startServer(): bool
    {
        $serverScript = __DIR__ . '/../server.php';
        
        if (!file_exists($serverScript)) {
            echo "Server script not found: $serverScript\n";
            return false;
        }

        // Start server in background
        $command = "php $serverScript > /dev/null 2>&1 & echo $!";
        $pid = shell_exec($command);
        
        if ($pid) {
            $this->serverProcess = (int) trim($pid);
            return true;
        }
        
        return false;
    }

    private function stopServer(): void
    {
        if ($this->serverProcess) {
            // Kill the server process
            shell_exec("kill {$this->serverProcess} 2>/dev/null");
            $this->serverProcess = null;
        }
    }

    private function isServerRunning(): bool
    {
        try {
            $response = $this->client->get($this->baseUrl . '/health');
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'ReactPHP HTTP Server Benchmark';
    }

    public function getDescription(): string
    {
        return 'Tests HTTP server performance with actual client requests in continuous runtime';
    }

    public function __destruct()
    {
        $this->stopServer();
    }
}

// CLI execution
if (isset($argv)) {
    // Check if GuzzleHttp is available (for server requests)
    if (!class_exists('GuzzleHttp\Client')) {
        echo "Warning: GuzzleHttp not available, using simplified benchmark\n";
        echo "To test with actual HTTP requests, install: composer require guzzlehttp/guzzle\n\n";
        
        // Fall back to routing benchmark
        require_once __DIR__ . '/routing.php';
        $benchmark = new ReactPHPRoutingBenchmark();
    } else {
        $benchmark = new ReactPHPHttpServerBenchmark();
    }
    
    $iterations = 1000; // Lower for HTTP tests
    $warmup = 100;
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
    
    try {
        $result = $benchmark->run($iterations, $warmup);
        
        if ($jsonOutput) {
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "ReactPHP HTTP Server Benchmark Results:\n";
            echo "Framework: {$result['framework']}\n";
            echo "Benchmark: {$result['benchmark']}\n";
            echo "Iterations: {$result['iterations']}\n";
            echo "Duration: " . number_format($result['duration'], 4) . "s\n";
            echo "Ops/sec: " . number_format($result['ops_per_second']) . "\n";
            echo "Memory used: " . number_format($result['memory_used'] / 1024 / 1024, 2) . " MB\n";
            echo "Runtime: {$result['runtime_type']}\n";
            if (isset($result['server_type'])) {
                echo "Server: {$result['server_type']}\n";
            }
        }
    } catch (\Exception $e) {
        echo "Error running benchmark: " . $e->getMessage() . "\n";
        exit(1);
    }
}