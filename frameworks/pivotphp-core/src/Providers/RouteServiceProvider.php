<?php

declare(strict_types=1);

namespace App\Providers;

use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Routing\Router;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Routes are registered in boot method
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        
        // API routes
        $this->registerApiRoutes($router);
        
        // Web routes
        $this->registerWebRoutes($router);
    }

    private function registerApiRoutes(Router $router): void
    {
        // Health check
        $router->get('/api/v1/health', function ($request, $response) {
            $controller = new \App\Controllers\Api\HealthController();
            $controller->index($request, $response);
        });
        
        // Users endpoints
        $router->get('/api/v1/users', function ($request, $response) {
            $controller = new \App\Controllers\Api\UserController();
            $controller->index($request, $response);
        });
        
        $router->get('/api/v1/users/:id', function ($request, $response) {
            $controller = new \App\Controllers\Api\UserController();
            $controller->show($request, $response);
        });
        
        $router->post('/api/v1/users', function ($request, $response) {
            $controller = new \App\Controllers\Api\UserController();
            $controller->store($request, $response);
        });
        
        $router->put('/api/v1/users/:id', function ($request, $response) {
            $controller = new \App\Controllers\Api\UserController();
            $controller->update($request, $response);
        });
        
        $router->delete('/api/v1/users/:id', function ($request, $response) {
            $controller = new \App\Controllers\Api\UserController();
            $controller->destroy($request, $response);
        });
    }

    private function registerWebRoutes(Router $router): void
    {
        $router->get('/', function ($request, $response) {
            $controller = new \App\Controllers\HomeController();
            $controller->index($request, $response);
        });
        
        $router->get('/test', function ($request, $response) {
            $controller = new \App\Controllers\TestController();
            $controller->index($request, $response);
        });
        
        $router->get('/benchmark', function ($request, $response) {
            $controller = new \App\Controllers\BenchmarkController();
            $controller->index($request, $response);
        });
    }
}