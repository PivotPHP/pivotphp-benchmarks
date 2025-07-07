#!/usr/bin/env php
<?php

/**
 * Conditional Middleware Benchmark
 * 
 * Tests the performance of conditional middleware execution
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;


class ConditionalMiddlewareBenchmark extends BenchmarkBase
{
    private Application $app;
    
    protected function getName(): string
    {
        return 'middleware/conditional-middleware';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // Add conditional middleware
        $this->app->use($this->createAuthMiddleware());
        $this->app->use($this->createCacheMiddleware());
        $this->app->use($this->createRateLimitMiddleware());
        
        // Public routes (no auth, but cached and rate limited)
        $this->app->get('/api/public/posts', function($request, $response) {
            return $response->json(['posts' => []]);
        });
        
        $this->app->get('/api/public/categories', function($request, $response) {
            return $response->json(['categories' => []]);
        });
        
        // Private routes (auth required, rate limited)
        $this->app->get('/api/private/profile', function($request, $response) {
            return $response->json(['user_id' => $request->getAttribute('user_id')]);
        });
        
        $this->app->post('/api/private/settings', function($request, $response) {
            return $response->json(['updated' => true]);
        });
        
        // Admin routes (auth required, not cached)
        $this->app->get('/admin/dashboard', function($request, $response) {
            return $response->json(['admin' => true]);
        });
        
        // Static routes (cached, no auth, no rate limit)
        $this->app->get('/static/css/main.css', function($request, $response) {
            return $response->withHeader('Content-Type', 'text/css')
                ->write('body { margin: 0; }');
        });
        
        // Regular routes (no special handling)
        $this->app->get('/home', function($request, $response) {
            return $response->json(['page' => 'home']);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $routes = [
            ['GET', '/api/public/posts', false],
            ['GET', '/api/public/categories', false],
            ['GET', '/api/private/profile', true],
            ['POST', '/api/private/settings', true],
            ['GET', '/admin/dashboard', true],
            ['GET', '/static/css/main.css', false],
            ['GET', '/home', false],
        ];
        
        $route = $routes[array_rand($routes)];
        $path = $route[1];
        
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = $route[0];
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        // Add auth header for protected routes
        if ($route[2]) {
            $token = rand(0, 10) > 1 ? 'valid-token-' . rand(1, 100) : 'invalid-token';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }
        
        if ($route[0] === 'POST') {
            $_POST = ['setting' => 'value'];
            $_SERVER['CONTENT_TYPE'] = 'application/json';
        } else {
            $_POST = [];
        }
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
    
    private function createAuthMiddleware(): callable
    {
        $protectedPaths = ['/admin', '/api/private'];
        
        return function($request, $response, $next) use ($protectedPaths) {
            $path = $request->getUri()->getPath();
            
            // Check if path requires authentication
            $requiresAuth = false;
            foreach ($protectedPaths as $protectedPath) {
                if (strpos($path, $protectedPath) === 0) {
                    $requiresAuth = true;
                    break;
                }
            }
            
            if ($requiresAuth) {
                $authHeader = $request->getHeaderLine('Authorization');
                if (!$authHeader || !$this->validateToken($authHeader)) {
                    return new \PivotPHP\Core\Http\Psr7\Response(401, 
                        ['Content-Type' => 'application/json'], 
                        json_encode(['error' => 'Unauthorized'])
                    );
                }
                
                $request = $request->withAttribute('user_id', $this->extractUserId($authHeader));
            }
            
            return $next($request, $response);
        };
    }
    
    private function createCacheMiddleware(): callable
    {
        $cacheablePaths = ['/api/public', '/static'];
        
        return function($request, $response, $next) use ($cacheablePaths) {
            $path = $request->getUri()->getPath();
            $method = $request->getMethod();
            
            // Only cache GET requests to specific paths
            if ($method !== 'GET') {
                return $next($request, $response);
            }
            
            $shouldCache = false;
            foreach ($cacheablePaths as $cacheablePath) {
                if (strpos($path, $cacheablePath) === 0) {
                    $shouldCache = true;
                    break;
                }
            }
            
            if ($shouldCache) {
                // Simulate cache check
                $cacheKey = md5($path . serialize($request->getQueryParams()));
                
                // Simulate cache miss (in real scenario, would check actual cache)
                $response = $next($request, $response);
                
                // Add cache headers
                return $response
                    ->withHeader('X-Cache-Status', 'MISS')
                    ->withHeader('Cache-Control', 'public, max-age=3600');
            }
            
            return $next($request, $response);
        };
    }
    
    private function createRateLimitMiddleware(): callable
    {
        $rateLimitedPaths = ['/api'];
        $limits = [
            '/api/public' => 1000,
            '/api/private' => 100,
            '/api' => 500 // default
        ];
        
        return function($request, $response, $next) use ($rateLimitedPaths, $limits) {
            $path = $request->getUri()->getPath();
            
            $shouldRateLimit = false;
            foreach ($rateLimitedPaths as $limitedPath) {
                if (strpos($path, $limitedPath) === 0) {
                    $shouldRateLimit = true;
                    break;
                }
            }
            
            if ($shouldRateLimit) {
                $limit = $this->getLimit($path, $limits);
                $remaining = rand(0, $limit);
                
                if ($remaining === 0) {
                    return new \PivotPHP\Core\Http\Psr7\Response(429, 
                        ['Content-Type' => 'application/json'], 
                        json_encode(['error' => 'Rate limit exceeded'])
                    );
                }
                
                $response = $next($request, $response);
                
                return $response
                    ->withHeader('X-RateLimit-Limit', (string) $limit)
                    ->withHeader('X-RateLimit-Remaining', (string) $remaining);
            }
            
            return $next($request, $response);
        };
    }
    
    private function validateToken(string $authHeader): bool
    {
        // Simulate token validation
        return strpos($authHeader, 'Bearer valid-') === 0;
    }
    
    private function extractUserId(string $authHeader): int
    {
        // Simulate user ID extraction
        return rand(1, 1000);
    }
    
    private function getLimit(string $path, array $limits): int
    {
        foreach ($limits as $limitPath => $limit) {
            if (strpos($path, $limitPath) === 0) {
                return $limit;
            }
        }
        return 500;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new ConditionalMiddlewareBenchmark($options);
$benchmark->run();