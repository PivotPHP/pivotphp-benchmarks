#!/usr/bin/env php
<?php

/**
 * Middleware Pipeline Benchmark for PivotPHP Core
 *
 * Tests middleware execution performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use Helix\Core\Application;
use Helix\Http\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Interfaces\BenchmarkInterface;

class MiddlewareBenchmark implements BenchmarkInterface
{
    private Application $app;
    private ServerRequestFactory $requestFactory;

    public function __construct()
    {
        $this->requestFactory = new ServerRequestFactory();
        $this->setup();
    }

    private function setup(): void
    {
        $this->app = new Application();

        // Add multiple middleware layers
        for ($i = 1; $i <= 10; $i++) {
            $this->app->addMiddleware(new BenchmarkMiddleware($i));
        }

        // Add routes
        $this->app->get('/test', function($request, $response) {
            return $response->json(['result' => 'ok']);
        });

        $this->app->get('/protected', function($request, $response) {
            return $response->json(['user' => $request->getAttribute('user')]);
        })->addMiddleware(new AuthMiddleware());
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
        $routes = ['/test', '/protected'];
        $path = $routes[array_rand($routes)];

        // Create request
        $request = $this->requestFactory->createServerRequest('GET', $path);
        if ($path === '/protected') {
            $request = $request->withHeader('Authorization', 'Bearer test-token');
        }

        // Handle request
        $response = $this->app->handle($request);

        // Consume response
        $response->getBody()->getContents();
    }

    public function getName(): string
    {
        return 'middleware/pipeline';
    }

    public function getDescription(): string
    {
        return 'Tests middleware pipeline execution with 10 middleware layers';
    }
}

// Simple benchmark middleware
class BenchmarkMiddleware implements MiddlewareInterface
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Add header before
        $request = $request->withAttribute("middleware-{$this->id}", true);

        // Process next
        $response = $handler->handle($request);

        // Add header after
        return $response->withHeader("X-Middleware-{$this->id}", 'processed');
    }
}

// Auth middleware
class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getHeaderLine('Authorization');

        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            return new \Helix\Http\Response(401, [], json_encode(['error' => 'Unauthorized']));
        }

        $token = substr($auth, 7);
        $request = $request->withAttribute('user', ['id' => 1, 'token' => $token]);

        return $handler->handle($request);
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['iterations:', 'warmup:', 'json']);

    $iterations = isset($options['iterations']) ? (int)$options['iterations'] : 10000;
    $warmup = isset($options['warmup']) ? (int)$options['warmup'] : 1000;

    $benchmark = new MiddlewareBenchmark();
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
