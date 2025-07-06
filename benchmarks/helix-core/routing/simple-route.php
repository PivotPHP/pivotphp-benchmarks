#!/usr/bin/env php
<?php

/**
 * Simple Route Benchmark
 * 
 * Tests the performance of basic route matching and handling
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Psr7\Factory\ServerRequestFactory;
use Helix\Http\Psr7\Factory\UriFactory;
use HelixBenchmark\BenchmarkBase;

class SimpleRouteBenchmark extends BenchmarkBase
{
    private Application $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    
    protected function getName(): string
    {
        return 'routing/simple-route';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Setup routes
        for ($i = 0; $i < 100; $i++) {
            $this->app->get("/route-{$i}", function($request, $response) use ($i) {
                return $response->json(['route' => $i]);
            });
        }
        
        // Add a wildcard route
        $this->app->get('/api/*', function($request, $response) {
            return $response->json(['api' => true]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $routeNum = rand(0, 99);
        $uri = $this->uriFactory->createUri("/route-{$routeNum}");
        
        $request = $this->requestFactory->createServerRequest('GET', $uri);
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SimpleRouteBenchmark($options);
$benchmark->run();