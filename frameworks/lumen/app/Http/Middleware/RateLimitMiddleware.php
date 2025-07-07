<?php

namespace App\Http\Middleware;

use Closure;

class RateLimitMiddleware
{
    private static array $requests = [];
    private int $limit = 100;
    private int $window = 60;
    
    public function handle($request, Closure $next)
    {
        $ip = $request->ip() ?? '127.0.0.1';
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
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $this->window
            ], 429);
        }
        
        // Record request
        self::$requests[$key][] = $now;
        
        $response = $next($request);
        
        // Add rate limit headers
        return $response
            ->header('X-RateLimit-Limit', (string) $this->limit)
            ->header('X-RateLimit-Remaining', (string) ($this->limit - count(self::$requests[$key])))
            ->header('X-RateLimit-Reset', (string) ($now + $this->window));
    }
}