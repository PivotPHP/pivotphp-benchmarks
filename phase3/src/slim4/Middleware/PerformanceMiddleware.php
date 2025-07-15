<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase3\Slim4\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PivotPHP\Benchmark\Phase3\Slim4\SlimApplication;

/**
 * Performance Middleware - Coleta métricas de performance
 */
class PerformanceMiddleware implements MiddlewareInterface
{
    private SlimApplication $app;

    public function __construct(SlimApplication $app)
    {
        $this->app = $app;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Incrementar contador de requests
        $requestId = $this->app->incrementRequestCount();
        
        // Processar request
        $response = $handler->handle($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        // Calcular métricas
        $processingTime = ($endTime - $startTime) * 1000; // ms
        $memoryUsed = $endMemory - $startMemory;
        
        // Registrar métrica
        $this->app->recordMetric([
            'request_id' => $requestId,
            'processing_time_ms' => $processingTime,
            'memory_used_bytes' => $memoryUsed,
            'timestamp' => $endTime,
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'framework' => 'slim4'
        ]);
        
        // Adicionar headers de performance
        return $response
            ->withHeader('X-Processing-Time-Ms', number_format($processingTime, 3))
            ->withHeader('X-Memory-Used-Bytes', (string)$memoryUsed)
            ->withHeader('X-Request-Id', (string)$requestId)
            ->withHeader('X-Timestamp', number_format($endTime, 6));
    }
}