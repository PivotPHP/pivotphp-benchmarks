#!/usr/bin/env php
<?php

/**
 * Slim 4 Middleware Benchmark
 * 
 * Tests the performance of middleware execution in Slim 4
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use PivotBenchmark\BenchmarkBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Slim4MiddlewareBenchmark extends BenchmarkBase
{
    private $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    
    protected function getName(): string
    {
        return 'comparative/slim4/middleware';
    }
    
    protected function setup(): void
    {
        $this->app = AppFactory::create();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Disable error handling for benchmarking
        $this->app->addErrorMiddleware(false, false, false);
        
        // Add multiple middleware layers
        for ($i = 0; $i < 5; $i++) {
            $this->app->add(new class($i) implements MiddlewareInterface {
                private int $index;
                
                public function __construct(int $index)
                {
                    $this->index = $index;
                }
                
                public function process(Request $request, RequestHandlerInterface $handler): Response
                {
                    // Simple processing
                    $request = $request->withAttribute("middleware_{$this->index}", true);
                    $response = $handler->handle($request);
                    return $response->withHeader("X-Middleware-{$this->index}", 'processed');
                }
            });
        }
        
        // Add route
        $this->app->get('/test', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode(['status' => 'ok']));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }
    
    protected function runSingleIteration(): void
    {
        $uri = $this->uriFactory->createUri('/test');
        $request = $this->requestFactory->createServerRequest('GET', $uri);
        $response = $this->app->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'slim4';
        $results['middleware_count'] = 5;
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new Slim4MiddlewareBenchmark($options);
$benchmark->run();