<?php

namespace App\Providers;

use PivotPHP\Core\Support\ServiceProvider;
use App\Controllers\HealthController;
use App\Controllers\UserController;
use App\Controllers\BenchmarkController;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register route-specific services
    }

    public function boot(): void
    {
        $router = $this->app->make('PivotPHP\\Core\\Routing\\Router');

        // Health and status routes
        $router->get('/', function($request, $response) {
            $controller = new HealthController();
            $controller->index($request, $response);
        });
        
        $router->get('/health', function($request, $response) {
            $controller = new HealthController();
            $controller->health($request, $response);
        });
        
        $router->get('/api/v1/health', function($request, $response) {
            $controller = new HealthController();
            $controller->health($request, $response);
        });

        // User routes for benchmarking
        $router->get('/users', function($request, $response) {
            $controller = new UserController();
            $controller->index($request, $response);
        });
        
        $router->get('/users/:id', function($request, $response) {
            $controller = new UserController();
            $controller->show($request, $response);
        });
        
        $router->post('/users', function($request, $response) {
            $controller = new UserController();
            $controller->store($request, $response);
        });

        // API routes for benchmarking
        $router->get('/api/v1/users', function($request, $response) {
            $controller = new UserController();
            $controller->index($request, $response);
        });
        
        $router->get('/api/v1/users/:id', function($request, $response) {
            $controller = new UserController();
            $controller->show($request, $response);
        });

        // Benchmark-specific routes
        $router->get('/api/v1/benchmark/cpu', function($request, $response) {
            $controller = new BenchmarkController();
            $controller->cpu($request, $response);
        });
        
        $router->get('/api/v1/benchmark/memory', function($request, $response) {
            $controller = new BenchmarkController();
            $controller->memory($request, $response);
        });
        
        $router->get('/api/v1/benchmark/io', function($request, $response) {
            $controller = new BenchmarkController();
            $controller->io($request, $response);
        });
    }
}