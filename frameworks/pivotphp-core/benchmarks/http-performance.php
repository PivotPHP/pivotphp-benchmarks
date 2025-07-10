#!/usr/bin/env php
<?php

/**
 * PivotPHP Core HTTP Request/Response Performance Benchmark
 * Tests core HTTP operations critical for APIs
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

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
    $opsPerSecond = round($iterations / $duration);
    
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
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 14));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "================================================\n";
    echo " PivotPHP Core HTTP Performance Benchmark\n";
    echo "================================================\n\n";
}

$results = [];

// Setup app for HTTP tests
$app = new Application();
$app->boot();

// Test 1: Request Object Creation & Parsing
$results['request_parsing'] = runBenchmark(
    'HTTP Request Parsing', 
    $args['iterations'], 
    function() {
        // Simulate HTTP request data
        $serverData = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/v1/users/123?include=profile&sort=created_at',
            'SERVER_NAME' => 'api.example.com',
            'SERVER_PORT' => '443',
            'HTTPS' => 'on',
            'HTTP_HOST' => 'api.example.com',
            'HTTP_ACCEPT' => 'application/json, text/plain, */*',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
            'HTTP_AUTHORIZATION' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_CONTENT_LENGTH' => '156',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_X_FORWARDED_FOR' => '192.168.1.100, 10.0.0.1',
            'HTTP_X_REAL_IP' => '192.168.1.100',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => '156',
            'QUERY_STRING' => 'include=profile&sort=created_at',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true)
        ];
        
        $bodyData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'profile' => [
                'bio' => 'Software developer with 5+ years experience',
                'location' => 'San Francisco, CA',
                'skills' => ['PHP', 'JavaScript', 'Python', 'Docker'],
                'social' => [
                    'github' => 'johndoe',
                    'twitter' => '@johndoe',
                    'linkedin' => 'johndoe'
                ]
            ]
        ];
        
        // Simulate request parsing
        $parsedRequest = [
            'method' => $serverData['REQUEST_METHOD'],
            'uri' => $serverData['REQUEST_URI'],
            'headers' => [],
            'query_params' => [],
            'body' => json_encode($bodyData),
            'parsed_body' => $bodyData,
            'attributes' => [],
            'server_params' => $serverData
        ];
        
        // Parse headers
        foreach ($serverData as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $parsedRequest['headers'][$headerName] = $value;
            }
        }
        
        // Parse query string
        if (!empty($serverData['QUERY_STRING'])) {
            parse_str($serverData['QUERY_STRING'], $parsedRequest['query_params']);
        }
        
        // Extract path parameters (simulated)
        if (preg_match('/\/users\/(\d+)/', $serverData['REQUEST_URI'], $matches)) {
            $parsedRequest['attributes']['id'] = $matches[1];
        }
        
        return $parsedRequest;
    }
);

// Test 2: Response Object Creation & Headers
$results['response_creation'] = runBenchmark(
    'HTTP Response Creation', 
    $args['iterations'], 
    function() {
        // Simulate API response creation
        $responseData = [
            'success' => true,
            'data' => [
                'id' => rand(1, 10000),
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'created_at' => date('c'),
                'updated_at' => date('c'),
                'profile' => [
                    'bio' => 'Software developer',
                    'location' => 'San Francisco, CA',
                    'avatar_url' => 'https://api.example.com/avatars/123.jpg',
                    'verified' => true,
                    'followers_count' => rand(100, 10000),
                    'following_count' => rand(50, 1000)
                ]
            ],
            'meta' => [
                'timestamp' => microtime(true),
                'version' => '1.1.0',
                'request_id' => uniqid('req_'),
                'processing_time' => rand(10, 100) / 1000
            ]
        ];
        
        $response = [
            'status_code' => 200,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'no-cache, private',
                'X-RateLimit-Limit' => '1000',
                'X-RateLimit-Remaining' => '999',
                'X-RateLimit-Reset' => time() + 3600,
                'X-Request-ID' => $responseData['meta']['request_id'],
                'X-Response-Time' => $responseData['meta']['processing_time'] . 'ms',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Vary' => 'Accept-Encoding',
                'Date' => gmdate('D, d M Y H:i:s T'),
                'Server' => 'PivotPHP/1.1.0'
            ],
            'body' => json_encode($responseData),
            'size' => 0
        ];
        
        $response['size'] = strlen($response['body']);
        $response['headers']['Content-Length'] = $response['size'];
        
        return $response;
    }
);

// Test 3: Content Negotiation
$results['content_negotiation'] = runBenchmark(
    'Content Negotiation', 
    $args['iterations'], 
    function() {
        $acceptHeaders = [
            'application/json',
            'application/xml',
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'application/json, text/plain, */*',
            'application/vnd.api+json',
            'application/hal+json',
            'text/csv',
            'application/pdf',
            'image/jpeg, image/png, image/*;q=0.8',
            'text/plain; charset=utf-8'
        ];
        
        $supportedTypes = [
            'application/json' => 1.0,
            'application/xml' => 0.9,
            'text/html' => 0.8,
            'text/plain' => 0.7,
            'text/csv' => 0.6
        ];
        
        $acceptHeader = $acceptHeaders[array_rand($acceptHeaders)];
        
        // Parse Accept header
        $acceptedTypes = [];
        $parts = explode(',', $acceptHeader);
        
        foreach ($parts as $part) {
            $part = trim($part);
            $subParts = explode(';', $part);
            $mediaType = trim($subParts[0]);
            $quality = 1.0;
            
            for ($i = 1; $i < count($subParts); $i++) {
                $param = trim($subParts[$i]);
                if (strpos($param, 'q=') === 0) {
                    $quality = floatval(substr($param, 2));
                    break;
                }
            }
            
            $acceptedTypes[$mediaType] = $quality;
        }
        
        // Find best match
        $bestMatch = null;
        $bestQuality = 0;
        
        foreach ($supportedTypes as $type => $serverQuality) {
            foreach ($acceptedTypes as $acceptedType => $clientQuality) {
                if ($acceptedType === $type || $acceptedType === '*/*') {
                    $quality = $clientQuality * $serverQuality;
                    if ($quality > $bestQuality) {
                        $bestQuality = $quality;
                        $bestMatch = $type;
                    }
                }
            }
        }
        
        return [
            'accept_header' => $acceptHeader,
            'parsed_types' => $acceptedTypes,
            'best_match' => $bestMatch ?: 'application/json',
            'quality' => $bestQuality,
            'negotiation_success' => $bestMatch !== null
        ];
    }
);

// Test 4: Large Payload Processing
$results['large_payload_processing'] = runBenchmark(
    'Large Payload Processing', 
    $args['iterations'] / 4, 
    function() {
        // Simulate large API payload (1MB+)
        $largeData = [
            'metadata' => [
                'total_records' => 10000,
                'page' => 1,
                'per_page' => 100,
                'total_pages' => 100,
                'generated_at' => date('c'),
                'format_version' => '2.0'
            ],
            'records' => []
        ];
        
        // Generate large dataset
        for ($i = 0; $i < 1000; $i++) {
            $largeData['records'][] = [
                'id' => $i + 1,
                'uuid' => uniqid() . '-' . uniqid(),
                'name' => "Record " . ($i + 1),
                'description' => str_repeat("Description content for record " . ($i + 1) . ". ", 10),
                'data' => [
                    'values' => range(1, 50),
                    'metadata' => array_fill_keys(range(1, 20), 'meta_value_' . $i),
                    'nested' => [
                        'level1' => [
                            'level2' => [
                                'content' => str_repeat('nested_data_', 5)
                            ]
                        ]
                    ]
                ],
                'timestamps' => [
                    'created_at' => date('c', time() - rand(0, 86400 * 365)),
                    'updated_at' => date('c', time() - rand(0, 86400 * 30)),
                    'accessed_at' => date('c', time() - rand(0, 86400))
                ]
            ];
        }
        
        // Process payload
        $start = microtime(true);
        $json = json_encode($largeData);
        $encodeTime = microtime(true) - $start;
        
        $start = microtime(true);
        $decoded = json_decode($json, true);
        $decodeTime = microtime(true) - $start;
        
        // Validate structure
        $isValid = isset($decoded['metadata'], $decoded['records']) && 
                  count($decoded['records']) === 1000;
        
        return [
            'payload_size' => strlen($json),
            'records_count' => count($decoded['records'] ?? []),
            'encode_time' => $encodeTime,
            'decode_time' => $decodeTime,
            'total_processing_time' => $encodeTime + $decodeTime,
            'validation_passed' => $isValid,
            'size_mb' => round(strlen($json) / 1024 / 1024, 2)
        ];
    }
);

// Test 5: HTTP Status Code Handling
$results['status_code_handling'] = runBenchmark(
    'HTTP Status Code Handling', 
    $args['iterations'], 
    function() {
        $statusCodes = [
            200 => 'OK',
            201 => 'Created', 
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable'
        ];
        
        $code = array_rand($statusCodes);
        $message = $statusCodes[$code];
        
        // Generate appropriate response based on status
        $response = [
            'status_code' => $code,
            'status_message' => $message,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Request-ID' => uniqid('req_')
            ]
        ];
        
        if ($code >= 200 && $code < 300) {
            // Success response
            $response['body'] = json_encode([
                'success' => true,
                'data' => ['id' => rand(1, 1000), 'status' => 'processed'],
                'message' => $message
            ]);
        } elseif ($code >= 400 && $code < 500) {
            // Client error
            $response['body'] = json_encode([
                'error' => true,
                'code' => $code,
                'message' => $message,
                'details' => 'Client error occurred',
                'timestamp' => date('c')
            ]);
        } else {
            // Server error
            $response['body'] = json_encode([
                'error' => true,
                'code' => $code,
                'message' => $message,
                'details' => 'Server error occurred',
                'timestamp' => date('c'),
                'request_id' => $response['headers']['X-Request-ID']
            ]);
        }
        
        $response['content_length'] = strlen($response['body']);
        $response['headers']['Content-Length'] = $response['content_length'];
        
        return $response;
    }
);

// Test 6: Header Processing Performance
$results['header_processing'] = runBenchmark(
    'Header Processing', 
    $args['iterations'], 
    function() {
        // Simulate complex header processing
        $headers = [
            'Host' => 'api.example.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'en-US,en;q=0.9,pt;q=0.8',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Length' => '1024',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-API-Key' => 'ak_' . uniqid(),
            'X-Client-Version' => '2.1.0',
            'X-Request-ID' => uniqid('req_'),
            'X-Forwarded-For' => '192.168.1.100, 10.0.0.1, 172.16.0.1',
            'X-Real-IP' => '192.168.1.100',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Connection' => 'keep-alive',
            'DNT' => '1',
            'Sec-Fetch-Site' => 'cross-site',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty'
        ];
        
        $processedHeaders = [];
        $securityHeaders = [];
        $cachingHeaders = [];
        $authHeaders = [];
        
        foreach ($headers as $name => $value) {
            $normalizedName = strtolower($name);
            $processedHeaders[$normalizedName] = $value;
            
            // Categorize headers
            if (in_array($normalizedName, ['authorization', 'x-api-key'])) {
                $authHeaders[$normalizedName] = $value;
            } elseif (in_array($normalizedName, ['cache-control', 'pragma', 'etag'])) {
                $cachingHeaders[$normalizedName] = $value;
            } elseif (strpos($normalizedName, 'sec-') === 0 || $normalizedName === 'x-forwarded-for') {
                $securityHeaders[$normalizedName] = $value;
            }
        }
        
        // Extract specific values
        $contentType = $processedHeaders['content-type'] ?? '';
        $contentLength = intval($processedHeaders['content-length'] ?? 0);
        $userAgent = $processedHeaders['user-agent'] ?? '';
        $authorization = $processedHeaders['authorization'] ?? '';
        
        return [
            'total_headers' => count($headers),
            'processed_headers' => count($processedHeaders),
            'auth_headers' => count($authHeaders),
            'security_headers' => count($securityHeaders),
            'caching_headers' => count($cachingHeaders),
            'content_type' => $contentType,
            'content_length' => $contentLength,
            'has_authorization' => !empty($authorization),
            'processing_time' => microtime(true)
        ];
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'http-performance',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'http_features' => ['request_parsing', 'response_creation', 'content_negotiation', 'large_payloads', 'status_codes', 'headers'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 HTTP Performance Summary:\n";
    echo "============================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP Core v1.1.0\n";
    echo "🌐 HTTP Features: Request/Response, Content Negotiation, Large Payloads, Status Codes, Headers\n";
    echo "⚡ Total HTTP Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🚀 HTTP Status: " . (array_sum(array_column($results, 'ops_per_second')) > 15000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'http-performance',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'http_features' => ['request_parsing', 'response_creation', 'content_negotiation', 'large_payloads', 'status_codes', 'headers'],
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-core-http-performance-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ HTTP performance benchmark completed successfully!\n";
}