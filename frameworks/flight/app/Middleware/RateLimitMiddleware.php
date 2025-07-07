<?php

namespace App\Middleware;

use Flight;

class RateLimitMiddleware
{
    private static array $requests = [];
    private int $limit = 100;
    private int $window = 60;
    
    public function handle($next)
    {
        $ip = Flight::request()->ip ?? '127.0.0.1';
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
            Flight::json([
                'error' => 'Too many requests',
                'retry_after' => $this->window
            ], 429);
            return;
        }
        
        // Record request
        self::$requests[$key][] = $now;
        
        // Set rate limit headers
        Flight::response()
            ->header('X-RateLimit-Limit', (string) $this->limit)
            ->header('X-RateLimit-Remaining', (string) ($this->limit - count(self::$requests[$key])))
            ->header('X-RateLimit-Reset', (string) ($now + $this->window));
        
        // Call next
        $next();
    }
}