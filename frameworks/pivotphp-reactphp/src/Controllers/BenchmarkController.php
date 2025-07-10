<?php

namespace App\Controllers;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class BenchmarkController
{
    public function cpu(Request $req, Response $res): void
    {
        $iterations = (int) ($req->query('iterations') ?? 10000);
        
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        // CPU-intensive benchmark
        $result = 0;
        for ($i = 0; $i < $iterations; $i++) {
            $result += sqrt($i) * sin($i) * cos($i);
        }
        
        $duration = microtime(true) - $start;
        $memoryUsed = memory_get_usage() - $startMemory;
        
        $res->json([
            'benchmark' => 'cpu',
            'iterations' => $iterations,
            'duration' => $duration,
            'ops_per_second' => round($iterations / $duration),
            'memory_used' => $memoryUsed,
            'result' => $result,
            'framework' => 'pivotphp-reactphp'
        ]);
    }

    public function memory(Request $req, Response $res): void
    {
        $size = (int) ($req->query('size') ?? 1000);
        
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        // Memory benchmark - array operations
        $array = [];
        for ($i = 0; $i < $size; $i++) {
            $array[] = [
                'id' => $i,
                'data' => str_repeat('x', 100),
                'hash' => md5((string)$i),
                'timestamp' => microtime(true)
            ];
        }
        
        // Process the array
        $processed = array_map(function($item) {
            return array_merge($item, ['processed' => true]);
        }, $array);
        
        $duration = microtime(true) - $start;
        $memoryUsed = memory_get_usage() - $startMemory;
        
        $res->json([
            'benchmark' => 'memory',
            'size' => $size,
            'duration' => $duration,
            'items_per_second' => round($size / $duration),
            'memory_used' => $memoryUsed,
            'memory_peak' => memory_get_peak_usage(true),
            'items_processed' => count($processed),
            'framework' => 'pivotphp-reactphp'
        ]);
    }

    public function io(Request $req, Response $res): void
    {
        $operations = (int) ($req->query('operations') ?? 10);
        
        $start = microtime(true);
        $startMemory = memory_get_usage();
        
        // I/O simulation - JSON encoding/decoding
        $totalBytes = 0;
        for ($i = 0; $i < $operations; $i++) {
            $data = [
                'operation' => $i,
                'timestamp' => microtime(true),
                'data' => array_fill(0, 100, 'benchmark_data_' . $i),
                'metadata' => [
                    'size' => 100,
                    'type' => 'benchmark',
                    'framework' => 'pivotphp-reactphp'
                ]
            ];
            
            $json = json_encode($data);
            $totalBytes += strlen($json);
            
            // Decode back
            $decoded = json_decode($json, true);
        }
        
        $duration = microtime(true) - $start;
        $memoryUsed = memory_get_usage() - $startMemory;
        $throughputMBps = ($totalBytes / (1024 * 1024)) / $duration;
        
        $res->json([
            'benchmark' => 'io',
            'operations' => $operations,
            'duration' => $duration,
            'bytes_processed' => $totalBytes,
            'throughput_mb_per_sec' => round($throughputMBps, 2),
            'memory_used' => $memoryUsed,
            'framework' => 'pivotphp-reactphp'
        ]);
    }
}