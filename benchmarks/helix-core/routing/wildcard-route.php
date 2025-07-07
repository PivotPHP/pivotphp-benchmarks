#!/usr/bin/env php
<?php

/**
 * Wildcard Route Benchmark
 * 
 * Tests the performance of wildcard route matching
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Psr7\Factory\ServerRequestFactory;
use Helix\Http\Psr7\Factory\UriFactory;
use HelixBenchmark\BenchmarkBase;

class WildcardRouteBenchmark extends BenchmarkBase
{
    private Application $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    
    protected function getName(): string
    {
        return 'routing/wildcard-route';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Specific routes (should match first)
        $this->app->get('/api/users', function($request, $response) {
            return $response->json(['endpoint' => 'users-list']);
        });
        
        $this->app->get('/api/products', function($request, $response) {
            return $response->json(['endpoint' => 'products-list']);
        });
        
        // Wildcard routes with different depths
        $this->app->get('/api/*', function($request, $response, $args) {
            return $response->json([
                'endpoint' => 'api-wildcard',
                'path' => $args['*'] ?? ''
            ]);
        });
        
        $this->app->get('/static/*', function($request, $response, $args) {
            return $response->json([
                'type' => 'static',
                'file' => $args['*'] ?? ''
            ]);
        });
        
        $this->app->get('/admin/*', function($request, $response, $args) {
            return $response->json([
                'admin' => true,
                'path' => $args['*'] ?? ''
            ]);
        });
        
        // Nested wildcard routes
        $this->app->get('/v1/resources/*', function($request, $response, $args) {
            return $response->json([
                'version' => 1,
                'resource' => $args['*'] ?? ''
            ]);
        });
        
        $this->app->get('/v2/resources/*', function($request, $response, $args) {
            return $response->json([
                'version' => 2,
                'resource' => $args['*'] ?? ''
            ]);
        });
        
        // Mixed parameter and wildcard
        $this->app->get('/files/{type}/*', function($request, $response, $args) {
            return $response->json([
                'file_type' => $args['type'],
                'path' => $args['*'] ?? ''
            ]);
        });
        
        // Catch-all route
        $this->app->get('/*', function($request, $response, $args) {
            return $response->json([
                'catch_all' => true,
                'path' => $args['*'] ?? ''
            ]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $paths = [
            '/api/users',
            '/api/products',
            '/api/orders/123',
            '/api/customers/456/orders',
            '/api/v3/experimental/feature',
            '/static/css/main.css',
            '/static/js/app.min.js',
            '/static/images/logo.png',
            '/admin/dashboard',
            '/admin/users/edit/123',
            '/admin/settings/security/advanced',
            '/v1/resources/users/123',
            '/v2/resources/products/456/reviews',
            '/files/images/uploads/2024/01/photo.jpg',
            '/files/documents/reports/annual.pdf',
            '/random/path/that/should/hit/catchall',
            '/another/deep/nested/path/structure'
        ];
        
        // Test random path
        $uri = $this->uriFactory->createUri($paths[array_rand($paths)]);
        $request = $this->requestFactory->createServerRequest('GET', $uri);
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new WildcardRouteBenchmark($options);
$benchmark->run();