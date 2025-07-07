#!/usr/bin/env php
<?php

/**
 * Single Middleware Benchmark
 * 
 * Tests the performance of a single middleware execution
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Psr7\Factory\ServerRequestFactory;
use PivotPHP\Core\Http\Psr7\Factory\UriFactory;
use PivotPHP\Core\Http\Psr15\AbstractMiddleware;
use PivotBenchmark\BenchmarkBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TimingMiddleware extends AbstractMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);
        
        $response = $handler->handle($request);
        
        $duration = (microtime(true) - $start) * 1000;
        
        return $response->withHeader('X-Response-Time', sprintf('%.3fms', $duration));
    }
}

class SingleMiddlewareBenchmark extends BenchmarkBase
{
    private Application $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    
    protected function getName(): string
    {
        return 'middleware/single-middleware';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Add single middleware as callable
        $this->app->use(function($request, $response, $next) {
            $start = microtime(true);
            $response = $next($request, $response);
            $duration = (microtime(true) - $start) * 1000;
            return $response->withHeader('X-Response-Time', sprintf('%.3fms', $duration));
        });
        
        // Setup test routes
        $this->app->get('/ping', function($request, $response) {
            return $response->json(['status' => 'pong']);
        });
        
        $this->app->post('/echo', function($request, $response) {
            $body = json_decode((string) $request->getBody(), true);
            return $response->json(['echo' => $body]);
        });
        
        $this->app->get('/user/{id}', function($request, $response, $args) {
            return $response->json([
                'user' => [
                    'id' => $args['id'],
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ]
            ]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $routes = [
            ['GET', '/ping'],
            ['POST', '/echo'],
            ['GET', '/user/' . rand(1, 1000)]
        ];
        
        $route = $routes[array_rand($routes)];
        $path = $route[1];
        
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = $route[0];
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        if ($route[0] === 'POST') {
            $_POST = ['data' => 'test-' . rand(1, 100)];
            $_SERVER['CONTENT_TYPE'] = 'application/json';
        } else {
            $_POST = [];
        }
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SingleMiddlewareBenchmark($options);
$benchmark->run();