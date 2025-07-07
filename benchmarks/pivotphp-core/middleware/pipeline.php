#!/usr/bin/env php
<?php

/**
 * Middleware Pipeline Benchmark
 * 
 * Tests the performance of middleware pipeline execution
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

class SimpleMiddleware extends AbstractMiddleware
{
    private int $id;
    
    public function __construct(int $id)
    {
        $this->id = $id;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Add a header to track middleware execution
        $request = $request->withAttribute("middleware_{$this->id}", true);
        
        $response = $handler->handle($request);
        
        // Add response header
        return $response->withHeader("X-Middleware-{$this->id}", '1');
    }
}

class MiddlewarePipelineBenchmark extends BenchmarkBase
{
    private Application $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    private int $middlewareCount = 10;
    
    protected function getName(): string
    {
        return 'middleware/pipeline';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Add middleware to the application
        for ($i = 1; $i <= $this->middlewareCount; $i++) {
            $this->app->use(new SimpleMiddleware($i));
        }
        
        // Add test routes
        $this->app->get('/test', function($request, $response) {
            return $response->json(['status' => 'ok']);
        });
        
        $this->app->post('/data', function($request, $response) {
            return $response->json(['received' => true]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $uri = $this->uriFactory->createUri('/test');
        $request = $this->requestFactory->createServerRequest('GET', $uri);
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json', 'middleware:']);
if (isset($options['middleware'])) {
    $middlewareCount = (int) $options['middleware'];
}

// Run benchmark
$benchmark = new MiddlewarePipelineBenchmark($options);
$benchmark->run();