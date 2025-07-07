#!/usr/bin/env php
<?php

/**
 * Unified Stress Test Benchmark
 * 
 * Tests all frameworks with identical workloads
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PivotBenchmark\BenchmarkBase;

class UnifiedStressTest extends BenchmarkBase
{
    private array $loadLevels = [100, 1000, 10000];
    private string $frameworkName;
    private $app;
    
    public function __construct(array $options, string $frameworkName)
    {
        $this->frameworkName = $frameworkName;
        parent::__construct($options);
    }
    
    protected function getName(): string
    {
        return "stress-test/{$this->frameworkName}";
    }
    
    protected function setup(): void
    {
        // Framework-specific setup
        switch ($this->frameworkName) {
            case 'pivotphp-core':
                $this->setupPivotPHP();
                break;
            case 'slim4':
                $this->setupSlim();
                break;
            case 'lumen':
                $this->setupLumen();
                break;
            case 'flight':
                $this->setupFlight();
                break;
        }
    }
    
    private function setupPivotPHP(): void
    {
        $this->app = new \PivotPHP\Core\Core\Application();
        
        // Simple endpoint
        $this->app->get('/test/simple', function($req, $res) {
            return $res->json(['status' => 'ok', 'timestamp' => time()]);
        });
        
        // Data endpoint
        $this->app->get('/test/data', function($req, $res) {
            $data = [];
            for ($i = 0; $i < 100; $i++) {
                $data[] = [
                    'id' => $i,
                    'name' => "Item {$i}",
                    'value' => rand(100, 999)
                ];
            }
            return $res->json(['data' => $data, 'count' => count($data)]);
        });
        
        // Complex endpoint
        $this->app->post('/test/complex', function($req, $res) {
            $input = $req->getParsedBody();
            $result = [
                'received' => $input,
                'processed' => array_map(function($v) {
                    return is_string($v) ? strtoupper($v) : $v * 2;
                }, $input ?? []),
                'hash' => md5(json_encode($input)),
                'timestamp' => microtime(true)
            ];
            return $res->json($result);
        });
    }
    
    private function setupSlim(): void
    {
        $this->app = \Slim\Factory\AppFactory::create();
        
        $this->app->get('/test/simple', function($request, $response) {
            $response->getBody()->write(json_encode(['status' => 'ok', 'timestamp' => time()]));
            return $response->withHeader('Content-Type', 'application/json');
        });
        
        $this->app->get('/test/data', function($request, $response) {
            $data = [];
            for ($i = 0; $i < 100; $i++) {
                $data[] = [
                    'id' => $i,
                    'name' => "Item {$i}",
                    'value' => rand(100, 999)
                ];
            }
            $response->getBody()->write(json_encode(['data' => $data, 'count' => count($data)]));
            return $response->withHeader('Content-Type', 'application/json');
        });
        
        $this->app->post('/test/complex', function($request, $response) {
            $input = $request->getParsedBody();
            $result = [
                'received' => $input,
                'processed' => array_map(function($v) {
                    return is_string($v) ? strtoupper($v) : $v * 2;
                }, $input ?? []),
                'hash' => md5(json_encode($input)),
                'timestamp' => microtime(true)
            ];
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }
    
    private function setupLumen(): void
    {
        // Lumen requires different initialization
        // This would be handled by the Lumen bootstrap
    }
    
    private function setupFlight(): void
    {
        $this->app = new \flight\Engine();
        
        $this->app->route('GET /test/simple', function() {
            $this->app->json(['status' => 'ok', 'timestamp' => time()]);
        });
        
        $this->app->route('GET /test/data', function() {
            $data = [];
            for ($i = 0; $i < 100; $i++) {
                $data[] = [
                    'id' => $i,
                    'name' => "Item {$i}",
                    'value' => rand(100, 999)
                ];
            }
            $this->app->json(['data' => $data, 'count' => count($data)]);
        });
        
        $this->app->route('POST /test/complex', function() {
            $input = $this->app->request()->data->getData();
            $result = [
                'received' => $input,
                'processed' => array_map(function($v) {
                    return is_string($v) ? strtoupper($v) : $v * 2;
                }, $input ?? []),
                'hash' => md5(json_encode($input)),
                'timestamp' => microtime(true)
            ];
            $this->app->json($result);
        });
    }
    
    protected function runSingleIteration(): void
    {
        // This will be overridden for load testing
    }
    
    public function runStressTest(): array
    {
        $results = [];
        
        foreach ($this->loadLevels as $load) {
            echo "Testing {$this->frameworkName} with {$load} requests...\n";
            
            $results[$load] = [
                'simple' => $this->testEndpoint('/test/simple', 'GET', $load),
                'data' => $this->testEndpoint('/test/data', 'GET', $load),
                'complex' => $this->testEndpoint('/test/complex', 'POST', $load)
            ];
            
            echo sprintf(
                "  Simple: %.2f req/s | Data: %.2f req/s | Complex: %.2f req/s\n",
                $results[$load]['simple']['throughput'],
                $results[$load]['data']['throughput'],
                $results[$load]['complex']['throughput']
            );
        }
        
        return $results;
    }
    
    private function testEndpoint(string $endpoint, string $method, int $requests): array
    {
        // Warmup
        for ($i = 0; $i < 10; $i++) {
            $this->makeRequest($endpoint, $method);
        }
        
        // Test
        $latencies = [];
        $errors = 0;
        $start = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $reqStart = microtime(true);
            
            try {
                $this->makeRequest($endpoint, $method);
                $latencies[] = (microtime(true) - $reqStart) * 1000;
            } catch (\Exception $e) {
                $errors++;
            }
            
            if ($requests >= 1000 && $i % 1000 === 0 && $i > 0) {
                echo ".";
            }
        }
        
        $end = microtime(true);
        $duration = $end - $start;
        
        sort($latencies);
        $successful = count($latencies);
        
        return [
            'requests' => $requests,
            'successful' => $successful,
            'errors' => $errors,
            'duration' => round($duration, 3),
            'throughput' => $successful > 0 ? round($successful / $duration, 2) : 0,
            'avg_latency' => $successful > 0 ? round(array_sum($latencies) / $successful, 3) : 0,
            'min_latency' => $successful > 0 ? round(min($latencies), 3) : 0,
            'max_latency' => $successful > 0 ? round(max($latencies), 3) : 0,
            'p95_latency' => $this->percentile($latencies, 95),
            'p99_latency' => $this->percentile($latencies, 99)
        ];
    }
    
    private function makeRequest(string $endpoint, string $method): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $endpoint;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($method === 'POST') {
            $_POST = [
                'name' => 'Test',
                'value' => rand(1, 100),
                'timestamp' => time()
            ];
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        }
        
        // Framework-specific request handling
        switch ($this->frameworkName) {
            case 'pivotphp-core':
                $request = \PivotPHP\Core\Http\Request::createFromGlobals();
                $response = $this->app->handle($request);
                break;
                
            case 'slim4':
                $request = \Slim\Factory\ServerRequestCreatorFactory::create()
                    ->createServerRequestFromGlobals();
                $response = $this->app->handle($request);
                break;
                
            case 'flight':
                ob_start();
                $this->app->start();
                ob_end_clean();
                break;
        }
    }
    
    private function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return round($values[$index] ?? 0, 3);
    }
}

// Determine which framework to test
$framework = $argv[1] ?? 'pivotphp-core';
$options = getopt('', ['json']);

// Run the stress test
$test = new UnifiedStressTest($options, $framework);
$results = $test->runStressTest();

// Output results
if (isset($options['json'])) {
    echo json_encode([
        'framework' => $framework,
        'timestamp' => date('c'),
        'results' => $results
    ], JSON_PRETTY_PRINT) . PHP_EOL;
}