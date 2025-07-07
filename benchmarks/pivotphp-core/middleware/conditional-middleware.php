#!/usr/bin/env php
<?php

/**
 * Conditional Middleware Benchmark
 * 
 * Tests the performance of conditional middleware execution
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Psr7\Factory\ServerRequestFactory;
use Helix\Http\Psr7\Factory\UriFactory;
use Helix\Http\Psr15\AbstractMiddleware;
use HelixBenchmark\BenchmarkBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConditionalAuthMiddleware extends AbstractMiddleware
{
    private array $protectedPaths = ['/admin', '/api/private'];
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        
        // Check if path requires authentication
        $requiresAuth = false;
        foreach ($this->protectedPaths as $protectedPath) {
            if (strpos($path, $protectedPath) === 0) {
                $requiresAuth = true;
                break;
            }
        }
        
        if ($requiresAuth) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (!$authHeader || !$this->validateToken($authHeader)) {
                $response = (new \Helix\Http\Psr7\Factory\ResponseFactory())
                    ->createResponse(401)
                    ->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
                return $response;
            }
            
            $request = $request->withAttribute('user_id', $this->extractUserId($authHeader));
        }
        
        return $handler->handle($request);
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
}

class ConditionalCacheMiddleware extends AbstractMiddleware
{
    private array $cacheablePaths = ['/api/public', '/static'];
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        
        // Only cache GET requests to specific paths
        if ($method !== 'GET') {
            return $handler->handle($request);
        }
        
        $shouldCache = false;
        foreach ($this->cacheablePaths as $cacheablePath) {
            if (strpos($path, $cacheablePath) === 0) {
                $shouldCache = true;
                break;
            }
        }
        
        if ($shouldCache) {
            // Simulate cache check
            $cacheKey = md5($path . serialize($request->getQueryParams()));
            
            // Simulate cache miss (in real scenario, would check actual cache)
            $response = $handler->handle($request);
            
            // Add cache headers
            return $response
                ->withHeader('X-Cache-Status', 'MISS')
                ->withHeader('Cache-Control', 'public, max-age=3600');
        }
        
        return $handler->handle($request);
    }
}

class ConditionalRateLimitMiddleware extends AbstractMiddleware
{
    private array $rateLimitedPaths = ['/api'];
    private array $limits = [
        '/api/public' => 1000,
        '/api/private' => 100,
        '/api' => 500 // default
    ];
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        
        $shouldRateLimit = false;
        foreach ($this->rateLimitedPaths as $limitedPath) {
            if (strpos($path, $limitedPath) === 0) {
                $shouldRateLimit = true;
                break;
            }
        }
        
        if ($shouldRateLimit) {
            $limit = $this->getLimit($path);
            $remaining = rand(0, $limit);
            
            if ($remaining === 0) {
                $response = (new \Helix\Http\Psr7\Factory\ResponseFactory())
                    ->createResponse(429)
                    ->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded']));
                return $response;
            }
            
            $response = $handler->handle($request);
            
            return $response
                ->withHeader('X-RateLimit-Limit', (string) $limit)
                ->withHeader('X-RateLimit-Remaining', (string) $remaining);
        }
        
        return $handler->handle($request);
    }
    
    private function getLimit(string $path): int
    {
        foreach ($this->limits as $limitPath => $limit) {
            if (strpos($path, $limitPath) === 0) {
                return $limit;
            }
        }
        return 500;
    }
}

class ConditionalMiddlewareBenchmark extends BenchmarkBase
{
    private Application $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    
    protected function getName(): string
    {
        return 'middleware/conditional-middleware';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Add conditional middleware
        $this->app->use(new ConditionalAuthMiddleware());
        $this->app->use(new ConditionalCacheMiddleware());
        $this->app->use(new ConditionalRateLimitMiddleware());
        
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
        $uri = $this->uriFactory->createUri($route[1]);
        
        $request = $this->requestFactory->createServerRequest($route[0], $uri);
        
        // Add auth header for protected routes
        if ($route[2]) {
            $token = rand(0, 10) > 1 ? 'valid-token-' . rand(1, 100) : 'invalid-token';
            $request = $request->withHeader('Authorization', 'Bearer ' . $token);
        }
        
        if ($route[0] === 'POST') {
            $request = $request->withHeader('Content-Type', 'application/json');
            $body = json_encode(['setting' => 'value']);
            $request->getBody()->write($body);
            $request->getBody()->rewind();
        }
        
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new ConditionalMiddlewareBenchmark($options);
$benchmark->run();