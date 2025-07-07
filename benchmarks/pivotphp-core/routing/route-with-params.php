#!/usr/bin/env php
<?php

/**
 * Route with Parameters Benchmark
 * 
 * Tests the performance of route parameter extraction and handling
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;

class RouteWithParamsBenchmark extends BenchmarkBase
{
    private Application $app;
    
    protected function getName(): string
    {
        return 'routing/route-with-params';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // Routes with single parameter
        $this->app->get('/users/{id}', function($request, $response, $args) {
            return $response->json([
                'user_id' => $args['id']
            ]);
        });
        
        // Routes with multiple parameters
        $this->app->get('/users/{userId}/posts/{postId}', function($request, $response, $args) {
            return $response->json([
                'user_id' => $args['userId'],
                'post_id' => $args['postId']
            ]);
        });
        
        // Routes with optional parameters
        $this->app->get('/api/{version}/resource/{id}[/{format}]', function($request, $response, $args) {
            return $response->json([
                'version' => $args['version'],
                'id' => $args['id'],
                'format' => $args['format'] ?? 'json'
            ]);
        });
        
        // Complex nested routes
        $this->app->get('/organizations/{orgId}/departments/{deptId}/employees/{empId}', function($request, $response, $args) {
            return $response->json([
                'org_id' => $args['orgId'],
                'dept_id' => $args['deptId'],
                'emp_id' => $args['empId']
            ]);
        });
        
        // Routes with regex constraints
        $this->app->get('/products/{id:\d+}', function($request, $response, $args) {
            return $response->json([
                'product_id' => $args['id']
            ]);
        });
        
        $this->app->get('/categories/{slug:[a-z\-]+}', function($request, $response, $args) {
            return $response->json([
                'category_slug' => $args['slug']
            ]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $requests = [
            '/users/' . rand(1, 1000),
            '/users/' . rand(1, 100) . '/posts/' . rand(1, 500),
            '/api/v' . rand(1, 3) . '/resource/' . rand(1, 1000),
            '/api/v2/resource/' . rand(1, 1000) . '/xml',
            '/organizations/' . rand(1, 10) . '/departments/' . rand(1, 50) . '/employees/' . rand(1, 1000),
            '/products/' . rand(1, 10000),
            '/categories/test-category-' . rand(1, 100)
        ];
        
        // Test random route
        $path = $requests[array_rand($requests)];
        
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new RouteWithParamsBenchmark($options);
$benchmark->run();