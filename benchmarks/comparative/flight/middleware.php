#!/usr/bin/env php
<?php

/**
 * Flight Middleware Benchmark
 * 
 * Tests the performance of middleware execution in Flight
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotBenchmark\BenchmarkBase;

class FlightMiddlewareBenchmark extends BenchmarkBase
{
    protected function getName(): string
    {
        return 'comparative/flight/middleware';
    }
    
    protected function setup(): void
    {
        // Disable Flight's default error handling
        Flight::set('flight.handle_errors', false);
        Flight::set('flight.log_errors', false);
        
        // Clear any existing routes
        Flight::router()->reset();
        
        // Flight doesn't have traditional middleware, but we can use before filters
        for ($i = 0; $i < 5; $i++) {
            Flight::before('start', function (&$params, &$output) use ($i) {
                // Set request attribute simulation
                Flight::set("middleware_{$i}", true);
                
                // Add header to response
                Flight::response()->header("X-Middleware-{$i}", 'processed');
            });
        }
        
        // Add route
        Flight::route('GET /test', function () {
            Flight::json(['status' => 'ok']);
        });
    }
    
    protected function runSingleIteration(): void
    {
        // Reset Flight's state for each iteration
        Flight::response()->clear();
        
        // Mock the request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        
        // Capture output since Flight writes directly
        ob_start();
        Flight::start();
        ob_end_clean();
    }
    
    protected function teardown(): void
    {
        // Clear routes and filters after benchmark
        Flight::router()->reset();
        
        // Clear before filters
        $reflection = new ReflectionClass('Flight');
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue();
        if (isset($filters['before']['start'])) {
            $filters['before']['start'] = [];
            $property->setValue($filters);
        }
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'flight';
        $results['middleware_count'] = 5;
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new FlightMiddlewareBenchmark($options);
$benchmark->run();