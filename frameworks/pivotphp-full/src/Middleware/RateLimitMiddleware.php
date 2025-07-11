<?php

namespace App\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Middleware\MiddlewareInterface;

/**
 * Rate Limiting Middleware for PivotPHP Full Stack
 * Implements token bucket algorithm
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private static array $rateLimitStore = [];
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 1000, int $windowSeconds = 3600)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function process(Request $request, callable $next): Response
    {
        $clientId = $this->getClientIdentifier($request);
        $currentTime = time();

        // Initialize client if not exists
        if (!isset(self::$rateLimitStore[$clientId])) {
            self::$rateLimitStore[$clientId] = [
                'requests' => [],
                'blocked_until' => 0
            ];
        }

        $clientData = &self::$rateLimitStore[$clientId];

        // Check if client is currently blocked
        if ($clientData['blocked_until'] > $currentTime) {
            return $this->createRateLimitResponse(
                429,
                'Rate limit exceeded. Try again later.',
                $clientData['blocked_until'] - $currentTime
            );
        }

        // Clean old requests outside the window
        $clientData['requests'] = array_filter(
            $clientData['requests'],
            fn($timestamp) => ($currentTime - $timestamp) < $this->windowSeconds
        );

        $requestCount = count($clientData['requests']);

        // Check if limit exceeded
        if ($requestCount >= $this->maxRequests) {
            $clientData['blocked_until'] = $currentTime + $this->windowSeconds;
            
            return $this->createRateLimitResponse(
                429,
                'Rate limit exceeded',
                $this->windowSeconds
            );
        }

        // Add current request
        $clientData['requests'][] = $currentTime;

        // Process the request
        /** @var Response $response */
        $response = $next($request);

        // Add rate limit headers
        $remaining = max(0, $this->maxRequests - $requestCount - 1);
        $resetTime = $currentTime + $this->windowSeconds;

        $response = $response->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
                            ->withHeader('X-RateLimit-Remaining', (string) $remaining)
                            ->withHeader('X-RateLimit-Reset', (string) $resetTime)
                            ->withHeader('X-RateLimit-Window', (string) $this->windowSeconds);

        return $response;
    }

    private function getClientIdentifier(Request $request): string
    {
        // Priority: API Key > IP Address > User Agent
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return 'api_key:' . $apiKey;
        }

        $ipAddress = $request->header('X-Real-IP') 
                  ?? $request->header('X-Forwarded-For') 
                  ?? $request->server('REMOTE_ADDR') 
                  ?? 'unknown';

        return 'ip:' . $ipAddress;
    }

    private function createRateLimitResponse(int $status, string $message, int $retryAfter): Response
    {
        $body = json_encode([
            'error' => true,
            'message' => $message,
            'retry_after' => $retryAfter,
            'timestamp' => date('c')
        ]);

        return new Response($body, $status, [
            'Content-Type' => 'application/json',
            'Retry-After' => (string) $retryAfter,
            'X-RateLimit-Limit' => (string) $this->maxRequests,
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => (string) (time() + $retryAfter)
        ]);
    }
}