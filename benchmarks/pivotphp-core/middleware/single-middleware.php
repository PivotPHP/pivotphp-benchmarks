#!/usr/bin/env php
<?php

/**
 * Single Middleware Benchmark
 * 
 * Tests the performance of a single middleware execution
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Psr7\Factory\ServerRequestFactory;
use Helix\Http\Psr7\Factory\UriFactory;
use Helix\Http\Psr15\AbstractMiddleware;
use HelixBenchmark\BenchmarkBase;
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
        
        // Add single middleware
        $this->app->use(new TimingMiddleware());
        
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
        $uri = $this->uriFactory->createUri($route[1]);
        
        if ($route[0] === 'POST') {
            $request = $this->requestFactory->createServerRequest($route[0], $uri)
                ->withHeader('Content-Type', 'application/json');
            
            $body = json_encode(['data' => 'test-' . rand(1, 100)]);
            $request->getBody()->write($body);
            $request->getBody()->rewind();
        } else {
            $request = $this->requestFactory->createServerRequest($route[0], $uri);
        }
        
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SingleMiddlewareBenchmark($options);
$benchmark->run();