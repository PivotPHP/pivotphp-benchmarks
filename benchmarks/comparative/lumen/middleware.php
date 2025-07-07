#!/usr/bin/env php
<?php

/**
 * Lumen Middleware Benchmark
 * 
 * Tests the performance of middleware execution in Lumen
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Laravel\Lumen\Application;
use Laravel\Lumen\Http\Request;
use HelixBenchmark\BenchmarkBase;
use Illuminate\Http\Response;

class LumenMiddlewareBenchmark extends BenchmarkBase
{
    private Application $app;
    
    protected function getName(): string
    {
        return 'comparative/lumen/middleware';
    }
    
    protected function setup(): void
    {
        $this->app = new Application(dirname(__DIR__, 3));
        
        // Disable debug mode
        $this->app->configure('app');
        putenv('APP_DEBUG=false');
        
        // Define middleware classes
        for ($i = 0; $i < 5; $i++) {
            $middlewareClass = "BenchmarkMiddleware{$i}";
            
            // Create middleware class dynamically
            if (!class_exists($middlewareClass)) {
                eval("
                    class {$middlewareClass}
                    {
                        public function handle(\$request, \Closure \$next)
                        {
                            \$request->attributes->set('middleware_{$i}', true);
                            \$response = \$next(\$request);
                            \$response->header('X-Middleware-{$i}', 'processed');
                            return \$response;
                        }
                    }
                ");
            }
            
            // Register middleware
            $this->app->middleware([
                $middlewareClass
            ]);
        }
        
        // Add route
        $this->app->router->get('/test', function () {
            return response()->json(['status' => 'ok']);
        });
    }
    
    protected function runSingleIteration(): void
    {
        // Create request
        $request = Request::create('/test', 'GET');
        
        // Handle request
        $response = $this->app->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'lumen';
        $results['middleware_count'] = 5;
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new LumenMiddlewareBenchmark($options);
$benchmark->run();