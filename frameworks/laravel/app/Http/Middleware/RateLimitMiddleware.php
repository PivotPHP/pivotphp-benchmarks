<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RateLimitMiddleware
{
    private static array $rateLimitStore = [];
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 1000, int $windowSeconds = 3600)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(Request $request, Closure $next)
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
                'Rate limit exceeded',
                $this->windowSeconds
            );
        }

        // Add current request
        $clientData['requests'][] = $currentTime;

        // Process the request
        $response = $next($request);

        // Add rate limit headers
        $remaining = max(0, $this->maxRequests - $requestCount - 1);
        $resetTime = $currentTime + $this->windowSeconds;

        $response->headers->set('X-RateLimit-Limit', (string) $this->maxRequests);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('X-RateLimit-Window', (string) $this->windowSeconds);

        return $response;
    }

    private function getClientIdentifier(Request $request): string
    {
        // Priority: API Key > IP Address
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return 'api_key:' . $apiKey;
        }

        $ipAddress = $request->header('X-Real-IP') 
                  ?? $request->header('X-Forwarded-For') 
                  ?? $request->ip();

        return 'ip:' . $ipAddress;
    }

    private function createRateLimitResponse(string $message, int $retryAfter): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
            'retry_after' => $retryAfter,
            'timestamp' => now()->toISOString()
        ], 429)->withHeaders([
            'Retry-After' => (string) $retryAfter,
            'X-RateLimit-Limit' => (string) $this->maxRequests,
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => (string) (time() + $retryAfter)
        ]);
    }
}