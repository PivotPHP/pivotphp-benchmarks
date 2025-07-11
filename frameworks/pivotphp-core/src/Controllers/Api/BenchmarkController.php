<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use PivotPHP\Core\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BenchmarkController
{
    public function cpu(ServerRequestInterface $request): ResponseInterface
    {
        $iterations = (int) ($request->getQueryParams()['iterations'] ?? 100000);
        $start = microtime(true);
        
        // CPU-intensive operations
        $result = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $result += sqrt($i) * sin($i) * cos($i);
            $hash = hash('sha256', (string) $i);
        }
        
        $duration = microtime(true) - $start;
        
        return Response::json([
            'benchmark' => 'cpu',
            'iterations' => $iterations,
            'duration_ms' => round($duration * 1000, 2),
            'ops_per_second' => round($iterations / $duration),
            'result_sample' => substr($hash ?? '', 0, 16),
        ]);
    }

    public function memory(ServerRequestInterface $request): ResponseInterface
    {
        $size = (int) ($request->getQueryParams()['size'] ?? 1000000);
        $memoryBefore = memory_get_usage(true);
        $start = microtime(true);
        
        // Memory-intensive operations
        $data = [];
        for ($i = 0; $i < $size; $i++) {
            $data[] = [
                'id' => $i,
                'uuid' => uniqid('', true),
                'data' => str_repeat('x', 100),
                'timestamp' => microtime(true),
            ];
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $duration = microtime(true) - $start;
        
        // Clean up
        $dataSize = count($data);
        unset($data);
        
        return Response::json([
            'benchmark' => 'memory',
            'items_created' => $dataSize,
            'duration_ms' => round($duration * 1000, 2),
            'memory_used' => $memoryAfter - $memoryBefore,
            'memory_peak' => $memoryPeak,
            'items_per_second' => round($size / $duration),
        ]);
    }

    public function io(ServerRequestInterface $request): ResponseInterface
    {
        $operations = (int) ($request->getQueryParams()['operations'] ?? 1000);
        $tempFile = sys_get_temp_dir() . '/pivotphp_benchmark_' . uniqid() . '.tmp';
        $start = microtime(true);
        
        // I/O-intensive operations
        $bytesWritten = 0;
        $bytesRead = 0;
        
        for ($i = 0; $i < $operations; $i++) {
            $data = str_repeat(uniqid('', true), 100);
            $bytesWritten += file_put_contents($tempFile, $data, FILE_APPEND);
        }
        
        $fileSize = filesize($tempFile);
        $content = file_get_contents($tempFile);
        $bytesRead = strlen($content);
        
        // Clean up
        unlink($tempFile);
        
        $duration = microtime(true) - $start;
        
        return Response::json([
            'benchmark' => 'io',
            'operations' => $operations,
            'duration_ms' => round($duration * 1000, 2),
            'bytes_written' => $bytesWritten,
            'bytes_read' => $bytesRead,
            'file_size' => $fileSize,
            'ops_per_second' => round($operations / $duration),
            'throughput_mb_per_sec' => round(($bytesWritten + $bytesRead) / $duration / 1024 / 1024, 2),
        ]);
    }
}