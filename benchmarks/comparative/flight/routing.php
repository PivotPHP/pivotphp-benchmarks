#!/usr/bin/env php
<?php

/**
 * Flight Routing Benchmark
 * 
 * Tests the performance of basic route matching and handling in Flight
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotBenchmark\BenchmarkBase;

class FlightRoutingBenchmark extends BenchmarkBase
{
    protected function getName(): string
    {
        return 'comparative/flight/routing';
    }
    
    protected function setup(): void
    {
        // Disable Flight's default error handling
        Flight::set('flight.handle_errors', false);
        Flight::set('flight.log_errors', false);
        
        // Clear any existing routes
        Flight::router()->reset();
        
        // Setup routes
        for ($i = 0; $i < 100; $i++) {
            Flight::route("GET /route-{$i}", function () use ($i) {
                Flight::json(['route' => $i]);
            });
        }
        
        // Add a wildcard route
        Flight::route('GET /api/*', function ($route) {
            $path = trim($route->splat, '/');
            Flight::json(['api' => true, 'path' => $path]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $routeNum = rand(0, 99);
        
        // Reset Flight's state for each iteration
        Flight::response()->clear();
        
        // Mock the request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "/route-{$routeNum}";
        
        // Capture output since Flight writes directly
        ob_start();
        Flight::start();
        ob_end_clean();
    }
    
    protected function teardown(): void
    {
        // Clear routes after benchmark
        Flight::router()->reset();
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'flight';
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new FlightRoutingBenchmark($options);
$benchmark->run();