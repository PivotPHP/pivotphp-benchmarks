#!/usr/bin/env php
<?php

/**
 * Security Benchmark for PivotPHP Core
 *
 * Tests security middleware performance (CORS, XSS, CSRF)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\XssMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\CsrfMiddleware;
use Shared\Interfaces\BenchmarkInterface;

class SecurityBenchmark implements BenchmarkInterface
{
    private Application $app;
    private array $middlewares = [];

    public function __construct()
    {
        $this->setup();
    }

    private function setup(): void
    {
        // Initialize middlewares
        $this->middlewares['cors'] = new CorsMiddleware([
            'allowed_origins' => ['https://example.com', 'https://api.example.com'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => ['X-Total-Count', 'X-Page-Count'],
            'max_age' => 3600,
            'credentials' => true
        ]);

        $this->middlewares['xss'] = new XssMiddleware();
        
        $this->middlewares['csrf'] = new CsrfMiddleware([
            'token_name' => 'csrf_token',
            'token_length' => 32,
            'token_lifetime' => 3600
        ]);
    }

    public function getName(): string
    {
        return 'Security Middleware';
    }

    public function getDescription(): string
    {
        return 'Benchmarks CORS, XSS protection, and CSRF token validation';
    }

    public function warmup(): void
    {
        $request = new Request('GET', '/test', '/test');
        
        // Warmup each middleware
        foreach ($this->middlewares as $middleware) {
            $handler = new class {
                public function handle($request) {
                    return new \PivotPHP\Core\Http\Psr7\Response(200, [], 'OK');
                }
            };
            $middleware->process($request, $handler);
        }
    }

    public function run(int $iterations = 10000): array
    {
        $results = [
            'cors' => $this->benchmarkCors($iterations),
            'xss' => $this->benchmarkXss($iterations),
            'csrf' => $this->benchmarkCsrf($iterations),
            'combined' => $this->benchmarkCombined($iterations)
        ];

        $totalTime = array_sum(array_column($results, 'total_time'));
        $avgTime = array_sum(array_column($results, 'average_time')) / count($results);

        return [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'average_time' => $avgTime,
            'operations_per_second' => $iterations / $totalTime,
            'details' => $results
        ];
    }

    private function benchmarkCors(int $iterations): array
    {
        $start = microtime(true);
        $memStart = memory_get_usage();

        $handler = new class {
            public function handle($request) {
                return new \PivotPHP\Http\Psr7\Response(200, [], 'OK');
            }
        };

        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request('OPTIONS', '/api/users', '/api/users');
            $request = $request->withHeader('Origin', 'https://example.com')
                ->withHeader('Access-Control-Request-Method', 'POST')
                ->withHeader('Access-Control-Request-Headers', 'Content-Type, Authorization');

            $this->middlewares['cors']->process($request, $handler);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'CORS Middleware',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }

    private function benchmarkXss(int $iterations): array
    {
        $start = microtime(true);
        $memStart = memory_get_usage();

        $handler = new class {
            public function handle($request) {
                return new \PivotPHP\Core\Http\Psr7\Response(200, [], json_encode([
                    'message' => '<script>alert("XSS")</script>',
                    'user' => '<img src=x onerror=alert(1)>',
                    'data' => 'Safe content'
                ]));
            }
        };

        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request('POST', '/api/data', '/api/data');
            $request = $request->withParsedBody([
                    'input' => '<script>alert("XSS")</script>',
                    'comment' => 'Normal comment',
                    'html' => '<img src=x onerror=alert(1)>'
                ]);

            $this->middlewares['xss']->process($request, $handler);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'XSS Protection',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }

    private function benchmarkCsrf(int $iterations): array
    {
        $start = microtime(true);
        $memStart = memory_get_usage();

        $handler = new class {
            public function handle($request) {
                return new \PivotPHP\Http\Psr7\Response(200, [], 'OK');
            }
        };

        // Generate a valid token
        $token = bin2hex(random_bytes(16));
        $_SESSION['csrf_token'] = $token;

        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request('POST', '/api/update', '/api/update');
            $request = $request->withParsedBody(['csrf_token' => $token])
                ->withHeader('X-CSRF-Token', $token);

            $this->middlewares['csrf']->process($request, $handler);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'CSRF Token Validation',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }

    private function benchmarkCombined(int $iterations): array
    {
        $start = microtime(true);
        $memStart = memory_get_usage();

        $this->app = new Application();
        
        // Add all security middlewares
        $this->app->addMiddleware($this->middlewares['cors']);
        $this->app->addMiddleware($this->middlewares['xss']);
        $this->app->addMiddleware($this->middlewares['csrf']);

        // Add a test route
        $this->app->post('/api/secure', function($request, $response) {
            return $response->json(['status' => 'success']);
        });

        $token = bin2hex(random_bytes(16));
        $_SESSION['csrf_token'] = $token;

        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request('POST', '/api/secure', '/api/secure');
            $request = $request->withHeader('Origin', 'https://example.com')
                ->withParsedBody([
                    'csrf_token' => $token,
                    'data' => '<script>alert("test")</script>'
                ]);

            $this->app->handle($request);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'Combined Security Stack',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }
}

// Run the benchmark
if (php_sapi_name() === 'cli') {
    $benchmark = new SecurityBenchmark();
    $results = $benchmark->run(10000);
    
    echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
}