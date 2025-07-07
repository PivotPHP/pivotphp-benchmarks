#!/usr/bin/env php
<?php

/**
 * JSON API Benchmark for PivotPHP Core
 *
 * Tests JSON serialization and API response performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use PivotPHP\Core\Application;
use PivotPHP\Http\Psr7\Factory\ServerRequestFactory;
use Shared\Interfaces\BenchmarkInterface;

class JsonApiBenchmark implements BenchmarkInterface
{
    private Application $app;
    private ServerRequestFactory $requestFactory;
    private array $sampleData;

    public function __construct()
    {
        $this->requestFactory = new ServerRequestFactory();
        $this->generateSampleData();
        $this->setup();
    }

    private function generateSampleData(): void
    {
        $this->sampleData = [];
        for ($i = 1; $i <= 100; $i++) {
            $this->sampleData[] = [
                'id' => $i,
                'name' => "Item {$i}",
                'description' => str_repeat("Description for item {$i}. ", 10),
                'price' => round(mt_rand(1000, 100000) / 100, 2),
                'stock' => mt_rand(0, 1000),
                'categories' => $this->generateCategories(),
                'metadata' => [
                    'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                    'updated_at' => date('Y-m-d H:i:s', strtotime("-{$i} hours")),
                    'views' => mt_rand(0, 10000),
                    'rating' => round(mt_rand(10, 50) / 10, 1)
                ]
            ];
        }
    }

    private function generateCategories(): array
    {
        $categories = ['Electronics', 'Books', 'Clothing', 'Food', 'Toys', 'Sports', 'Home'];
        $count = mt_rand(1, 3);
        return array_slice($categories, 0, $count);
    }

    private function setup(): void
    {
        $this->app = new Application();

        // Small payload endpoint
        $this->app->get('/api/small', function($request, $response) {
            return $response->json([
                'status' => 'success',
                'data' => array_slice($this->sampleData, 0, 10)
            ]);
        });

        // Medium payload endpoint
        $this->app->get('/api/medium', function($request, $response) {
            return $response->json([
                'status' => 'success',
                'data' => array_slice($this->sampleData, 0, 50),
                'meta' => [
                    'total' => 50,
                    'page' => 1,
                    'per_page' => 50
                ]
            ]);
        });

        // Large payload endpoint
        $this->app->get('/api/large', function($request, $response) {
            return $response->json([
                'status' => 'success',
                'data' => $this->sampleData,
                'meta' => [
                    'total' => count($this->sampleData),
                    'page' => 1,
                    'per_page' => 100
                ],
                'links' => [
                    'self' => '/api/large?page=1',
                    'next' => '/api/large?page=2',
                    'prev' => null
                ]
            ]);
        });

        // POST endpoint for testing request parsing
        $this->app->post('/api/create', function($request, $response) {
            $data = $request->getParsedBody();

            return $response->json([
                'status' => 'created',
                'data' => array_merge(['id' => mt_rand(1000, 9999)], $data),
                'timestamp' => date('c')
            ], 201);
        });
    }

    public function run(int $iterations = 10000, int $warmup = 1000): array
    {
        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->runSingleIteration();
        }

        // Benchmark
        $start = microtime(true);
        $startMemory = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $this->runSingleIteration();
        }

        $end = microtime(true);
        $endMemory = memory_get_peak_usage();

        $totalTime = $end - $start;
        $opsPerSecond = $iterations / $totalTime;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        return [
            'benchmark' => $this->getName(),
            'framework' => 'helix-orm',
            'description' => $this->getDescription(),
            'iterations' => $iterations,
            'warmup' => $warmup,
            'total_time' => $totalTime,
            'ops_per_second' => round($opsPerSecond, 2),
            'memory_mb' => round($memoryUsed, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function runSingleIteration(): void
    {
        $endpoints = [
            ['method' => 'GET', 'path' => '/api/small'],
            ['method' => 'GET', 'path' => '/api/medium'],
            ['method' => 'GET', 'path' => '/api/large'],
            ['method' => 'POST', 'path' => '/api/create']
        ];

        $endpoint = $endpoints[array_rand($endpoints)];

        // Create request
        $request = $this->requestFactory->createServerRequest($endpoint['method'], $endpoint['path']);

        if ($endpoint['method'] === 'POST') {
            $body = json_encode([
                'name' => 'Test Item',
                'price' => 99.99,
                'categories' => ['Test']
            ]);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->requestFactory->createStream($body));
        }

        // Handle request
        $response = $this->app->handle($request);

        // Consume response
        $response->getBody()->getContents();
    }

    public function getName(): string
    {
        return 'json-api/serialization';
    }

    public function getDescription(): string
    {
        return 'Tests JSON API response serialization with various payload sizes';
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['iterations:', 'warmup:', 'json']);

    $iterations = isset($options['iterations']) ? (int)$options['iterations'] : 10000;
    $warmup = isset($options['warmup']) ? (int)$options['warmup'] : 1000;

    $benchmark = new JsonApiBenchmark();
    $results = $benchmark->run($iterations, $warmup);

    if (isset($options['json'])) {
        echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Benchmark: {$results['benchmark']}\n";
        echo "Framework: {$results['framework']}\n";
        echo "Operations/sec: {$results['ops_per_second']}\n";
        echo "Memory used: {$results['memory_mb']} MB\n";
    }
}
