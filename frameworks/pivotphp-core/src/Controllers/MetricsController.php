<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\PerformanceMiddleware;
use PivotPHP\Core\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MetricsController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $stats = PerformanceMiddleware::getStats();
        $metrics = PerformanceMiddleware::getMetrics();
        
        // Group metrics by endpoint
        $endpointStats = [];
        foreach ($metrics as $metric) {
            $key = $metric['method'] . ' ' . $metric['path'];
            if (!isset($endpointStats[$key])) {
                $endpointStats[$key] = [
                    'count' => 0,
                    'total_duration' => 0,
                    'total_memory' => 0,
                    'min_duration' => PHP_FLOAT_MAX,
                    'max_duration' => 0,
                ];
            }
            
            $endpointStats[$key]['count']++;
            $endpointStats[$key]['total_duration'] += $metric['duration'];
            $endpointStats[$key]['total_memory'] += $metric['memory_used'];
            $endpointStats[$key]['min_duration'] = min($endpointStats[$key]['min_duration'], $metric['duration']);
            $endpointStats[$key]['max_duration'] = max($endpointStats[$key]['max_duration'], $metric['duration']);
        }
        
        // Calculate averages
        $endpointMetrics = [];
        foreach ($endpointStats as $endpoint => $stats) {
            $endpointMetrics[$endpoint] = [
                'requests' => $stats['count'],
                'avg_duration_ms' => round(($stats['total_duration'] / $stats['count']) * 1000, 2),
                'min_duration_ms' => round($stats['min_duration'] * 1000, 2),
                'max_duration_ms' => round($stats['max_duration'] * 1000, 2),
                'avg_memory_mb' => round($stats['total_memory'] / $stats['count'] / 1024 / 1024, 2),
            ];
        }
        
        return Response::json([
            'summary' => $stats,
            'endpoints' => $endpointMetrics,
            'system' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_limit' => $this->getMemoryLimit(),
                'load_average' => sys_getloadavg(),
            ],
        ]);
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return -1;
        }
        
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}