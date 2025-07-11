#!/usr/bin/env php
<?php

/**
 * Laravel HTTP Performance Benchmark
 * Tests various HTTP protocol operations for API performance
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

function runBenchmark($name, $iterations, $callback) {
    echo "🚀 $name ($iterations iterations)... ";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = $duration > 0 ? round($iterations / $duration) : 0;
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed
    ];
}

function parseArgs($argv) {
    $args = ['json' => false, 'iterations' => 1000, 'warmup' => 100];
    
    foreach ($argv as $arg) {
        if ($arg === '--json') $args['json'] = true;
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 13));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "==================================================\n";
    echo " Laravel HTTP Performance Benchmark\n";
    echo "==================================================\n\n";
}

$results = [];

// Test 1: Request Creation and Parsing
$results['request_parsing'] = runBenchmark(
    'HTTP Request Parsing', 
    $args['iterations'], 
    function() {
        $request = Request::create(
            '/api/v1/users/123',
            'POST',
            ['name' => 'Test User', 'email' => 'test@example.com'],
            [], // cookies
            [], // files
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer token123']
        );
        
        $method = $request->method();
        $path = $request->path();
        $params = $request->all();
        $headers = $request->headers->all();
        
        return compact('method', 'path', 'params', 'headers');
    }
);

// Test 2: Response Creation
$results['response_creation'] = runBenchmark(
    'HTTP Response Creation', 
    $args['iterations'], 
    function() {
        $data = [
            'id' => rand(1, 10000),
            'message' => 'Success',
            'framework' => 'laravel',
            'timestamp' => now()->toISOString()
        ];
        
        $response = response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'X-API-Version' => '1.0',
            'X-Framework' => 'laravel'
        ]);
        
        return $response;
    }
);

// Test 3: Content Negotiation
$results['content_negotiation'] = runBenchmark(
    'Content Negotiation', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/data', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json,application/xml;q=0.9,*/*;q=0.8'
        ]);
        
        $acceptHeader = $request->header('Accept');
        $formats = explode(',', $acceptHeader);
        
        $negotiated = [];
        foreach ($formats as $format) {
            $parts = explode(';', trim($format));
            $mimeType = trim($parts[0]);
            $quality = 1.0;
            
            if (isset($parts[1]) && strpos($parts[1], 'q=') === 0) {
                $quality = floatval(substr($parts[1], 2));
            }
            
            $negotiated[] = ['mime' => $mimeType, 'quality' => $quality];
        }
        
        usort($negotiated, fn($a, $b) => $b['quality'] <=> $a['quality']);
        
        return response()->json([
            'content_type' => $negotiated[0]['mime'] ?? 'application/json',
            'negotiated' => $negotiated
        ]);
    }
);

// Test 4: Large JSON Payload Processing
$results['large_payload_processing'] = runBenchmark(
    'Large Payload Processing', 
    $args['iterations'], 
    function() {
        $largeData = array_fill(0, 1000, [
            'id' => rand(1, 10000),
            'data' => str_repeat('x', 100),
            'nested' => [
                'field1' => 'value1',
                'field2' => 'value2',
                'array' => range(1, 50)
            ]
        ]);
        
        $request = Request::create(
            '/api/bulk',
            'POST',
            $largeData,
            [], [], ['CONTENT_TYPE' => 'application/json']
        );
        
        $processed = $request->all();
        $count = count($processed);
        
        return response()->json([
            'processed_items' => $count,
            'total_size' => strlen(json_encode($processed)),
            'framework' => 'laravel'
        ]);
    }
);

// Test 5: HTTP Status Code Handling
$results['status_code_handling'] = runBenchmark(
    'HTTP Status Code Handling', 
    $args['iterations'], 
    function() {
        $statusCodes = [200, 201, 400, 401, 403, 404, 422, 500];
        $randomStatus = $statusCodes[array_rand($statusCodes)];
        
        $messages = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error'
        ];
        
        $response = response()->json([
            'status' => $randomStatus,
            'message' => $messages[$randomStatus],
            'framework' => 'laravel'
        ], $randomStatus);
        
        return $response;
    }
);

// Test 6: Header Management
$results['header_management'] = runBenchmark(
    'Header Management', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/test', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'HTTP_X_API_KEY' => 'key456',
            'HTTP_X_REQUEST_ID' => uniqid(),
            'HTTP_USER_AGENT' => 'Laravel/10.0 Benchmark Client'
        ]);
        
        $headers = [
            'authorization' => $request->header('Authorization'),
            'api_key' => $request->header('X-API-Key'),
            'request_id' => $request->header('X-Request-ID'),
            'user_agent' => $request->header('User-Agent')
        ];
        
        $response = response()->json(['headers_processed' => $headers])
            ->withHeaders([
                'X-Response-Time' => microtime(true),
                'X-Framework' => 'laravel',
                'X-Server' => 'Laravel/10.0'
            ]);
        
        return $response;
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'http-performance',
        'framework' => 'laravel',
        'version' => '10.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second'))
    ]);
} else {
    echo "\n📊 Laravel HTTP Performance Summary:\n";
    echo "====================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-30s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    echo "\n🏆 Total HTTP Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "✅ Laravel HTTP benchmark completed!\n";
}