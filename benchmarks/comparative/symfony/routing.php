#!/usr/bin/env php
<?php

/**
 * Symfony Routing Benchmark
 * 
 * Tests the performance of basic route matching and handling in Symfony
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use PivotBenchmark\BenchmarkBase;

class SymfonyRoutingBenchmark extends BenchmarkBase
{
    private RouteCollection $routes;
    private UrlMatcher $matcher;
    private array $controllers = [];
    
    protected function getName(): string
    {
        return 'comparative/symfony/routing';
    }
    
    protected function setup(): void
    {
        $this->routes = new RouteCollection();
        
        // Setup routes
        for ($i = 0; $i < 100; $i++) {
            $route = new Route("/route-{$i}");
            $this->routes->add("route_{$i}", $route);
            
            // Store controller actions
            $this->controllers["route_{$i}"] = function () use ($i) {
                return new JsonResponse(['route' => $i]);
            };
        }
        
        // Add a wildcard route
        $wildcardRoute = new Route('/api/{path}', [], ['path' => '.+']);
        $this->routes->add('api_wildcard', $wildcardRoute);
        
        $this->controllers['api_wildcard'] = function ($path) {
            return new JsonResponse(['api' => true, 'path' => $path]);
        };
        
        // Create matcher
        $context = new RequestContext();
        $this->matcher = new UrlMatcher($this->routes, $context);
    }
    
    protected function runSingleIteration(): void
    {
        $routeNum = rand(0, 99);
        $path = "/route-{$routeNum}";
        
        // Create request
        $request = Request::create($path, 'GET');
        
        // Update context
        $this->matcher->getContext()->fromRequest($request);
        
        try {
            // Match route
            $parameters = $this->matcher->match($path);
            $routeName = $parameters['_route'];
            
            // Execute controller
            $controller = $this->controllers[$routeName];
            if (isset($parameters['path'])) {
                $response = $controller($parameters['path']);
            } else {
                $response = $controller();
            }
        } catch (ResourceNotFoundException $e) {
            // Route not found
            $response = new JsonResponse(['error' => 'Not found'], 404);
        }
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'symfony';
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SymfonyRoutingBenchmark($options);
$benchmark->run();