<?php

declare(strict_types=1);

namespace App\Controllers;

use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Request;

class BenchmarkController
{
    public function index(Request $request, Response $response): void
    {
        $benchmarks = [
            'message' => 'Benchmark Controller',
            'benchmarks' => [
                'response_time' => $this->getResponseTime(),
                'memory_usage' => [
                    'current' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true),
                ],
                'system' => [
                    'cpu_count' => $this->getCpuCount(),
                    'load_average' => sys_getloadavg(),
                ],
            ],
            'endpoints' => [
                'cpu_intensive' => '/api/v1/benchmark/cpu',
                'memory_intensive' => '/api/v1/benchmark/memory',
                'io_intensive' => '/api/v1/benchmark/io',
                'database' => '/api/v1/benchmark/database',
            ],
        ];
        
        $response->json($benchmarks);
    }
    
    private function getResponseTime(): float
    {
        return (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    }
    
    private function getCpuCount(): int
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (int) getenv('NUMBER_OF_PROCESSORS');
        }
        
        $cpuinfo = file_get_contents('/proc/cpuinfo');
        preg_match_all('/^processor/m', $cpuinfo, $matches);
        
        return count($matches[0]);
    }
}