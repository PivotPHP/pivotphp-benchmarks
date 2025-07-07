<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private static array $requests = [];
    private int $limit;
    private int $window;
    
    public function __construct(int $limit = 100, int $window = 60)
    {
        $this->limit = $limit;
        $this->window = $window;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // For benchmarks, we'll implement a simple in-memory rate limiter
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
        $now = time();
        $key = $ip;
        
        // Clean old entries
        if (isset(self::$requests[$key])) {
            self::$requests[$key] = array_filter(
                self::$requests[$key],
                fn($timestamp) => $timestamp > ($now - $this->window)
            );
        } else {
            self::$requests[$key] = [];
        }
        
        // Check rate limit
        if (count(self::$requests[$key]) >= $this->limit) {
            $response = new Response(429);
            $response->getBody()->write(json_encode([
                'error' => 'Too many requests',
                'retry_after' => $this->window
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
        
        // Record request
        self::$requests[$key][] = $now;
        
        // Add rate limit headers
        $response = $handler->handle($request);
        
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', (string) ($this->limit - count(self::$requests[$key])))
            ->withHeader('X-RateLimit-Reset', (string) ($now + $this->window));
    }
}