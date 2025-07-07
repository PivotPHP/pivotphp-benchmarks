#!/usr/bin/env php
<?php

/**
 * Simple Route Benchmark
 * 
 * Tests the performance of basic route matching and handling
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;

class SimpleRouteBenchmark extends BenchmarkBase
{
    private Application $app;
    
    protected function getName(): string
    {
        return 'routing/simple-route';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
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
        $path = "/route-{$routeNum}";
        
        // Create a mock request using PivotPHP's Request class
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
        
        // Consume response to avoid output
        $response->getBody();
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SimpleRouteBenchmark($options);
$benchmark->run();