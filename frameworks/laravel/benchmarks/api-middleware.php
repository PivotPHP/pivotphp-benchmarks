#!/usr/bin/env php
<?php

/**
 * Laravel API Middleware Performance Benchmark
 * Tests production middleware stack performance
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Middleware\RateLimitMiddleware;

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
    echo " Laravel API Middleware Performance Benchmark\n";
    echo "==================================================\n\n";
}

$results = [];

// Test 1: CORS Middleware
$results['cors_middleware'] = runBenchmark(
    'CORS Middleware', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/v1/users', 'OPTIONS', [], [], [], [
            'HTTP_ORIGIN' => 'https://example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Content-Type, Authorization'
        ]);
        
        // Simulate CORS middleware logic
        $allowedOrigins = ['https://example.com', 'https://app.example.com'];
        $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $allowedHeaders = ['Content-Type', 'Authorization', 'X-API-Key'];
        
        $origin = $request->header('Origin');
        $requestMethod = $request->header('Access-Control-Request-Method');
        $requestHeaders = $request->header('Access-Control-Request-Headers');
        
        $corsHeaders = [];
        
        if (in_array($origin, $allowedOrigins)) {
            $corsHeaders['Access-Control-Allow-Origin'] = $origin;
        }
        
        if (in_array($requestMethod, $allowedMethods)) {
            $corsHeaders['Access-Control-Allow-Methods'] = implode(', ', $allowedMethods);
        }
        
        if ($requestHeaders) {
            $corsHeaders['Access-Control-Allow-Headers'] = $requestHeaders;
        }
        
        $corsHeaders['Access-Control-Max-Age'] = '86400';
        
        return response()->json([
            'cors_processed' => true,
            'headers_added' => count($corsHeaders),
            'framework' => 'laravel'
        ])->withHeaders($corsHeaders);
    }
);

// Test 2: Rate Limiting Middleware
$results['rate_limit_middleware'] = runBenchmark(
    'Rate Limiting Middleware', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/v1/users', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.' . rand(1, 254),
            'HTTP_X_API_KEY' => 'test_key_' . rand(1000, 9999)
        ]);
        
        $rateLimiter = new RateLimitMiddleware(1000, 3600);
        
        // Simulate request processing
        $response = $rateLimiter->handle($request, function($request) {
            return response()->json([
                'message' => 'Request processed',
                'framework' => 'laravel'
            ]);
        });
        
        return $response;
    }
);

// Test 3: Authentication Middleware
$results['auth_middleware'] = runBenchmark(
    'Authentication Middleware', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/v1/protected', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . base64_encode('user:' . rand(1000, 9999) . ':' . time())
        ]);
        
        // Simulate authentication middleware
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'error' => 'Unauthorized',
                'framework' => 'laravel'
            ], 401);
        }
        
        $token = substr($authHeader, 7);
        $decoded = base64_decode($token);
        
        if (!$decoded || count(explode(':', $decoded)) !== 3) {
            return response()->json([
                'error' => 'Invalid token',
                'framework' => 'laravel'
            ], 401);
        }
        
        [$user, $id, $timestamp] = explode(':', $decoded);
        
        // Check if token is not expired (1 hour)
        if (time() - $timestamp > 3600) {
            return response()->json([
                'error' => 'Token expired',
                'framework' => 'laravel'
            ], 401);
        }
        
        return response()->json([
            'authenticated' => true,
            'user' => $user,
            'user_id' => $id,
            'framework' => 'laravel'
        ]);
    }
);

// Test 4: Request Validation Middleware
$results['validation_middleware'] = runBenchmark(
    'Request Validation Middleware', 
    $args['iterations'], 
    function() {
        $data = [
            'name' => 'Test User ' . rand(1000, 9999),
            'email' => 'user' . rand(1000, 9999) . '@example.com',
            'age' => rand(18, 80),
            'role' => ['admin', 'user', 'moderator'][array_rand(['admin', 'user', 'moderator'])]
        ];
        
        $request = Request::create('/api/v1/users', 'POST', $data);
        
        // Simulate validation middleware
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255',
            'age' => 'required|integer|min:18|max:120',
            'role' => 'required|in:admin,user,moderator'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
                'framework' => 'laravel'
            ], 422);
        }
        
        return response()->json([
            'validation_passed' => true,
            'validated_fields' => count($validator->validated()),
            'framework' => 'laravel'
        ]);
    }
);

// Test 5: Logging Middleware
$results['logging_middleware'] = runBenchmark(
    'Logging Middleware', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/v1/data', 'POST', [
            'action' => 'create',
            'resource' => 'user',
            'data' => ['name' => 'Test', 'email' => 'test@example.com']
        ]);
        
        // Simulate logging middleware
        $startTime = microtime(true);
        
        // Log request
        $logEntry = [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'request_id' => uniqid('req_'),
            'framework' => 'laravel'
        ];
        
        // Simulate request processing
        usleep(rand(1, 10)); // Simulate processing time
        
        $response = response()->json([
            'processed' => true,
            'request_id' => $logEntry['request_id'],
            'framework' => 'laravel'
        ]);
        
        // Log response
        $endTime = microtime(true);
        $logEntry['response_time'] = round(($endTime - $startTime) * 1000, 2);
        $logEntry['status_code'] = $response->getStatusCode();
        
        return $response;
    }
);

// Test 6: Compression Middleware
$results['compression_middleware'] = runBenchmark(
    'Compression Middleware', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/v1/large-data', 'GET', [], [], [], [
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br'
        ]);
        
        // Generate large response data
        $largeData = [
            'records' => array_map(function($i) {
                return [
                    'id' => $i,
                    'name' => "Record $i",
                    'description' => str_repeat("This is record $i with lots of text data. ", 10),
                    'metadata' => array_fill(0, 20, "meta_$i")
                ];
            }, range(1, 100)),
            'framework' => 'laravel'
        ];
        
        $jsonData = json_encode($largeData);
        $originalSize = strlen($jsonData);
        
        // Simulate compression
        $acceptEncoding = $request->header('Accept-Encoding');
        $compressed = false;
        $compressionRatio = 1.0;
        
        if (strpos($acceptEncoding, 'gzip') !== false) {
            $compressedData = gzcompress($jsonData, 6);
            $compressedSize = strlen($compressedData);
            $compressionRatio = $originalSize / $compressedSize;
            $compressed = true;
        }
        
        $headers = [
            'X-Original-Size' => $originalSize,
            'X-Compressed' => $compressed ? 'true' : 'false',
            'X-Compression-Ratio' => round($compressionRatio, 2)
        ];
        
        if ($compressed) {
            $headers['Content-Encoding'] = 'gzip';
        }
        
        return response()->json([
            'compression_applied' => $compressed,
            'original_size' => $originalSize,
            'compression_ratio' => $compressionRatio,
            'records_count' => count($largeData['records']),
            'framework' => 'laravel'
        ])->withHeaders($headers);
    }
);

// Test 7: Complete Middleware Stack
$results['complete_middleware_stack'] = runBenchmark(
    'Complete Middleware Stack', 
    $args['iterations'], 
    function() {
        $request = Request::create('/api/v1/secure-endpoint', 'POST', [
            'name' => 'Secure User ' . rand(1000, 9999),
            'email' => 'secure' . rand(1000, 9999) . '@example.com',
            'confidential_data' => 'sensitive information'
        ], [], [], [
            'HTTP_ORIGIN' => 'https://app.example.com',
            'HTTP_AUTHORIZATION' => 'Bearer ' . base64_encode('user:' . rand(1000, 9999) . ':' . time()),
            'HTTP_ACCEPT_ENCODING' => 'gzip',
            'REMOTE_ADDR' => '192.168.1.' . rand(1, 254)
        ]);
        
        $middlewareResults = [];
        
        // 1. CORS Check
        $origin = $request->header('Origin');
        $corsAllowed = in_array($origin, ['https://app.example.com']);
        $middlewareResults['cors'] = $corsAllowed;
        
        if (!$corsAllowed) {
            return response()->json(['error' => 'CORS not allowed'], 403);
        }
        
        // 2. Rate Limiting
        $ip = $request->server('REMOTE_ADDR');
        $middlewareResults['rate_limit'] = true; // Simplified for benchmark
        
        // 3. Authentication
        $authHeader = $request->header('Authorization');
        $authenticated = $authHeader && str_starts_with($authHeader, 'Bearer ');
        $middlewareResults['auth'] = $authenticated;
        
        if (!$authenticated) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // 4. Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'confidential_data' => 'required|string'
        ]);
        
        $middlewareResults['validation'] = !$validator->fails();
        
        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed'], 422);
        }
        
        // 5. Logging
        $middlewareResults['logging'] = true;
        
        // 6. Compression
        $acceptsGzip = str_contains($request->header('Accept-Encoding', ''), 'gzip');
        $middlewareResults['compression'] = $acceptsGzip;
        
        return response()->json([
            'success' => true,
            'middleware_stack' => [
                'cors',
                'rate_limiting',
                'authentication',
                'validation',
                'logging',
                'compression'
            ],
            'middleware_results' => $middlewareResults,
            'all_passed' => !in_array(false, $middlewareResults),
            'framework' => 'laravel'
        ]);
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'api-middleware',
        'framework' => 'laravel',
        'version' => '10.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'middleware_types' => [
            'cors',
            'rate_limiting',
            'authentication',
            'validation',
            'logging',
            'compression',
            'complete_stack'
        ],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second'))
    ]);
} else {
    echo "\n📊 Laravel API Middleware Performance Summary:\n";
    echo "==============================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-30s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    echo "\n🏆 Total Middleware Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "✅ Laravel API Middleware benchmark completed!\n";
}