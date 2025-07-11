<?php

namespace App\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Middleware\MiddlewareInterface;

/**
 * CORS Middleware for PivotPHP Full Stack
 * Handles Cross-Origin Resource Sharing
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // Handle preflight requests
        if ($request->method() === 'OPTIONS') {
            return new Response('', 200, $this->getCorsHeaders());
        }

        /** @var Response $response */
        $response = $next($request);

        // Add CORS headers to all responses
        foreach ($this->getCorsHeaders() as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }

    private function getCorsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-API-Key, Accept',
            'Access-Control-Expose-Headers' => 'X-Total-Count, X-Rate-Limit-Remaining, X-Request-ID',
            'Access-Control-Max-Age' => '86400', // 24 hours
            'Access-Control-Allow-Credentials' => 'true'
        ];
    }
}