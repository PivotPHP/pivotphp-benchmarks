#!/usr/bin/env php
<?php

/**
 * Middleware Pipeline Benchmark
 * 
 * Tests the performance of middleware pipeline execution
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;


class MiddlewarePipelineBenchmark extends BenchmarkBase
{
    private Application $app;
    private int $middlewareCount = 10;
    
    protected function getName(): string
    {
        return 'middleware/pipeline';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // Add middleware to the application
        for ($i = 1; $i <= $this->middlewareCount; $i++) {
            $this->app->use(function($request, $response, $next) use ($i) {
                // Add a header to track middleware execution
                $request = $request->withAttribute("middleware_{$i}", true);
                
                $response = $next($request, $response);
                
                // Add response header
                return $response->withHeader("X-Middleware-{$i}", '1');
            });
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
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_POST = [];
        
        $request = Request::createFromGlobals();
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