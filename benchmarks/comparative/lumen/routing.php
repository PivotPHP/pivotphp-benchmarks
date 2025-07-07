#!/usr/bin/env php
<?php

/**
 * Lumen Routing Benchmark
 * 
 * Tests the performance of basic route matching and handling in Lumen
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Laravel\Lumen\Application;
use Laravel\Lumen\Http\Request;
use PivotBenchmark\BenchmarkBase;

class LumenRoutingBenchmark extends BenchmarkBase
{
    private Application $app;
    
    protected function getName(): string
    {
        return 'comparative/lumen/routing';
    }
    
    protected function setup(): void
    {
        $this->app = new Application(dirname(__DIR__, 3));
        
        // Disable debug mode and unnecessary features for benchmarking
        $this->app->configure('app');
        putenv('APP_DEBUG=false');
        
        // Setup routes
        for ($i = 0; $i < 100; $i++) {
            $this->app->router->get("/route-{$i}", function () use ($i) {
                return response()->json(['route' => $i]);
            });
        }
        
        // Add a wildcard route
        $this->app->router->get('/api/{path:.*}', function ($path) {
            return response()->json(['api' => true, 'path' => $path]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $routeNum = rand(0, 99);
        
        // Create request
        $request = Request::create("/route-{$routeNum}", 'GET');
        
        // Handle request
        $response = $this->app->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'lumen';
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new LumenRoutingBenchmark($options);
$benchmark->run();