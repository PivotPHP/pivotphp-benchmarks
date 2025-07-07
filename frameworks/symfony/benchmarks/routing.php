#!/usr/bin/env php
<?php

/**
 * Routing Benchmark for Symfony
 * 
 * Tests route matching performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use App\Kernel;
use Shared\Interfaces\BenchmarkInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutingBenchmark implements BenchmarkInterface
{
    private Kernel $kernel;
    
    public function __construct()
    {
        $this->setup();
    }
    
    private function setup(): void
    {
        $_SERVER['APP_ENV'] = 'prod';
        $_SERVER['APP_DEBUG'] = '0';
        
        $this->kernel = new Kernel('prod', false);
        $this->kernel->boot();
        
        // Add dynamic routes
        $router = $this->kernel->getContainer()->get('router');
        $routeCollection = $router->getRouteCollection();
        
        // Setup 100 routes for testing
        for ($i = 0; $i < 100; $i++) {
            $route = new Route("/users/{$i}", ['_controller' => function() use ($i) {
                return new \Symfony\Component\HttpFoundation\JsonResponse(['user_id' => $i]);
            }]);
            $routeCollection->add("user_{$i}", $route);
        }
        
        // Add parameterized routes
        $route = new Route('/users/{id}', ['_controller' => function($id) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['user_id' => $id]);
        }], ['id' => '\d+']);
        $routeCollection->add('user_show', $route);
        
        $route = new Route('/posts/{id}/comments/{comment_id}', ['_controller' => function($id, $comment_id) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'post_id' => $id,
                'comment_id' => $comment_id
            ]);
        }], ['id' => '\d+', 'comment_id' => '\d+']);
        $routeCollection->add('post_comments', $route);
        
        // Add catch-all route
        $route = new Route('/api/{path}', ['_controller' => function() {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['api' => true]);
        }], ['path' => '.*']);
        $routeCollection->add('api_catchall', $route);
    }
    
    public function run(int $iterations = 10000, int $warmup = 1000): array
    {
        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->runSingleIteration();
        }
        
        // Benchmark
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->runSingleIteration();
        }
        
        $end = microtime(true);
        $endMemory = memory_get_peak_usage();
        
        $totalTime = $end - $start;
        $opsPerSecond = $iterations / $totalTime;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
        
        return [
            'benchmark' => $this->getName(),
            'framework' => 'symfony',
            'description' => $this->getDescription(),
            'iterations' => $iterations,
            'warmup' => $warmup,
            'total_time' => $totalTime,
            'ops_per_second' => round($opsPerSecond, 2),
            'memory_mb' => round($memoryUsed, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function runSingleIteration(): void
    {
        $routes = [
            '/users/42',
            '/users/' . rand(0, 99),
            '/posts/10/comments/5',
            '/api/v1/products'
        ];
        
        $path = $routes[array_rand($routes)];
        
        // Create request
        $request = Request::create($path, 'GET');
        
        // Handle request
        $response = $this->kernel->handle($request);
        
        // Consume response
        $response->getContent();
        
        // Terminate kernel
        $this->kernel->terminate($request, $response);
    }
    
    public function getName(): string
    {
        return 'routing/match-and-dispatch';
    }
    
    public function getDescription(): string
    {
        return 'Tests route matching and dispatching performance with 100+ routes';
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['iterations:', 'warmup:', 'json']);
    
    $iterations = isset($options['iterations']) ? (int)$options['iterations'] : 10000;
    $warmup = isset($options['warmup']) ? (int)$options['warmup'] : 1000;
    
    $benchmark = new RoutingBenchmark();
    $results = $benchmark->run($iterations, $warmup);
    
    if (isset($options['json'])) {
        echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Benchmark: {$results['benchmark']}\n";
        echo "Framework: {$results['framework']}\n";
        echo "Operations/sec: {$results['ops_per_second']}\n";
        echo "Memory used: {$results['memory_mb']} MB\n";
    }
}