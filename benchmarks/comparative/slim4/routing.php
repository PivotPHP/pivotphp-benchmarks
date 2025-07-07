#!/usr/bin/env php
<?php

/**
 * Slim 4 Routing Benchmark
 * 
 * Tests the performance of basic route matching and handling in Slim 4
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use HelixBenchmark\BenchmarkBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Slim4RoutingBenchmark extends BenchmarkBase
{
    private $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    
    protected function getName(): string
    {
        return 'comparative/slim4/routing';
    }
    
    protected function setup(): void
    {
        $this->app = AppFactory::create();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Disable error handling for benchmarking
        $this->app->addErrorMiddleware(false, false, false);
        
        // Setup routes
        for ($i = 0; $i < 100; $i++) {
            $this->app->get("/route-{$i}", function (Request $request, Response $response, $args) use ($i) {
                $response->getBody()->write(json_encode(['route' => $i]));
                return $response->withHeader('Content-Type', 'application/json');
            });
        }
        
        // Add a wildcard route
        $this->app->get('/api/{path:.*}', function (Request $request, Response $response, $args) {
            $response->getBody()->write(json_encode(['api' => true, 'path' => $args['path']]));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }
    
    protected function runSingleIteration(): void
    {
        $routeNum = rand(0, 99);
        $uri = $this->uriFactory->createUri("/route-{$routeNum}");
        
        $request = $this->requestFactory->createServerRequest('GET', $uri);
        $response = $this->app->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'slim4'; // Override framework name
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new Slim4RoutingBenchmark($options);
$benchmark->run();