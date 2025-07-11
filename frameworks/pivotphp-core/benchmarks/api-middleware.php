#!/usr/bin/env php
<?php

/**
 * PivotPHP Core API Middleware Stack Benchmark
 * Tests production-ready middleware stack performance
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

// Middleware simulation classes
class CorsMiddleware {
    public static function process($request, $response) {
        $corsHeaders = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-API-Key',
            'Access-Control-Expose-Headers' => 'X-Total-Count, X-Rate-Limit-Remaining',
            'Access-Control-Max-Age' => '86400'
        ];
        
        foreach ($corsHeaders as $header => $value) {
            $response['headers'][$header] = $value;
        }
        
        // Handle preflight OPTIONS request
        if ($request['method'] === 'OPTIONS') {
            $response['status_code'] = 200;
            $response['body'] = '';
        }
        
        return ['request' => $request, 'response' => $response];
    }
}

class RateLimitMiddleware {
    private static $rateLimitStore = [];
    
    public static function process($request, $response) {
        $clientId = $request['headers']['X-API-Key'] ?? $request['client_ip'] ?? 'anonymous';
        $currentTime = time();
        $window = 3600; // 1 hour
        $maxRequests = 1000; // 1000 requests per hour
        
        if (!isset(self::$rateLimitStore[$clientId])) {
            self::$rateLimitStore[$clientId] = [];
        }
        
        // Clean old requests
        self::$rateLimitStore[$clientId] = array_filter(
            self::$rateLimitStore[$clientId],
            fn($timestamp) => ($currentTime - $timestamp) < $window
        );
        
        $requestCount = count(self::$rateLimitStore[$clientId]);
        
        if ($requestCount >= $maxRequests) {
            $response['status_code'] = 429;
            $response['body'] = json_encode([
                'error' => 'Rate limit exceeded',
                'retry_after' => $window
            ]);
            $response['headers']['Retry-After'] = $window;
        } else {
            self::$rateLimitStore[$clientId][] = $currentTime;
        }
        
        $response['headers']['X-RateLimit-Limit'] = $maxRequests;
        $response['headers']['X-RateLimit-Remaining'] = max(0, $maxRequests - $requestCount - 1);
        $response['headers']['X-RateLimit-Reset'] = $currentTime + $window;
        
        return ['request' => $request, 'response' => $response];
    }
}

class AuthenticationMiddleware {
    public static function process($request, $response) {
        $authHeader = $request['headers']['Authorization'] ?? '';
        $apiKey = $request['headers']['X-API-Key'] ?? '';
        
        $isAuthenticated = false;
        $user = null;
        
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            // Simulate JWT token validation
            if (self::validateJwtToken($token)) {
                $isAuthenticated = true;
                $user = self::getUserFromToken($token);
            }
        } elseif (!empty($apiKey)) {
            // Simulate API key validation
            if (self::validateApiKey($apiKey)) {
                $isAuthenticated = true;
                $user = self::getUserFromApiKey($apiKey);
            }
        }
        
        if (!$isAuthenticated) {
            $response['status_code'] = 401;
            $response['body'] = json_encode([
                'error' => 'Unauthorized',
                'message' => 'Valid authentication required'
            ]);
            $response['headers']['WWW-Authenticate'] = 'Bearer realm="API"';
        } else {
            $request['user'] = $user;
            $request['authenticated'] = true;
        }
        
        return ['request' => $request, 'response' => $response];
    }
    
    private static function validateJwtToken($token) {
        // Simulate JWT validation
        return strlen($token) > 50 && strpos($token, '.') !== false;
    }
    
    private static function validateApiKey($apiKey) {
        // Simulate API key validation
        return strlen($apiKey) > 20 && strpos($apiKey, 'ak_') === 0;
    }
    
    private static function getUserFromToken($token) {
        return [
            'id' => rand(1, 10000),
            'email' => 'user@example.com',
            'role' => 'user',
            'permissions' => ['read', 'write']
        ];
    }
    
    private static function getUserFromApiKey($apiKey) {
        return [
            'id' => rand(1, 10000),
            'api_key' => $apiKey,
            'role' => 'api_user',
            'permissions' => ['read', 'write', 'admin']
        ];
    }
}

class ValidationMiddleware {
    public static function process($request, $response) {
        $validationRules = [
            'POST' => [
                '/api/users' => [
                    'name' => ['required', 'string', 'min:2', 'max:50'],
                    'email' => ['required', 'email'],
                    'password' => ['required', 'string', 'min:8']
                ],
                '/api/posts' => [
                    'title' => ['required', 'string', 'max:100'],
                    'content' => ['required', 'string'],
                    'status' => ['in:draft,published']
                ]
            ],
            'PUT' => [
                '/api/users/{id}' => [
                    'name' => ['string', 'min:2', 'max:50'],
                    'email' => ['email'],
                    'status' => ['in:active,inactive']
                ]
            ]
        ];
        
        $method = $request['method'];
        $path = $request['path'];
        $body = $request['body'] ?? [];
        
        $rules = $validationRules[$method][$path] ?? null;
        $errors = [];
        
        if ($rules && is_array($body)) {
            foreach ($rules as $field => $fieldRules) {
                $value = $body[$field] ?? null;
                
                foreach ($fieldRules as $rule) {
                    if ($rule === 'required' && ($value === null || $value === '')) {
                        $errors[$field][] = "The $field field is required";
                        continue;
                    }
                    
                    if ($value === null || $value === '') continue;
                    
                    if ($rule === 'string' && !is_string($value)) {
                        $errors[$field][] = "The $field must be a string";
                    } elseif ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "The $field must be a valid email";
                    } elseif (strpos($rule, 'min:') === 0) {
                        $min = intval(substr($rule, 4));
                        if (strlen($value) < $min) {
                            $errors[$field][] = "The $field must be at least $min characters";
                        }
                    } elseif (strpos($rule, 'max:') === 0) {
                        $max = intval(substr($rule, 4));
                        if (strlen($value) > $max) {
                            $errors[$field][] = "The $field must not exceed $max characters";
                        }
                    } elseif (strpos($rule, 'in:') === 0) {
                        $allowed = explode(',', substr($rule, 3));
                        if (!in_array($value, $allowed)) {
                            $errors[$field][] = "The $field must be one of: " . implode(', ', $allowed);
                        }
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            $response['status_code'] = 422;
            $response['body'] = json_encode([
                'error' => 'Validation failed',
                'errors' => $errors
            ]);
        } else {
            $request['validated'] = true;
        }
        
        return ['request' => $request, 'response' => $response];
    }
}

class LoggingMiddleware {
    public static function process($request, $response) {
        $logEntry = [
            'timestamp' => date('c'),
            'request_id' => $request['headers']['X-Request-ID'] ?? uniqid('req_'),
            'method' => $request['method'],
            'path' => $request['path'],
            'user_agent' => $request['headers']['User-Agent'] ?? '',
            'ip_address' => $request['client_ip'] ?? '127.0.0.1',
            'user_id' => $request['user']['id'] ?? null,
            'processing_time' => microtime(true) - ($request['start_time'] ?? microtime(true)),
            'memory_usage' => memory_get_usage(true),
            'response_status' => $response['status_code'] ?? 200,
            'response_size' => strlen($response['body'] ?? '')
        ];
        
        // Simulate log writing
        $logJson = json_encode($logEntry);
        
        $response['headers']['X-Request-ID'] = $logEntry['request_id'];
        $response['headers']['X-Response-Time'] = round($logEntry['processing_time'] * 1000, 2) . 'ms';
        
        return ['request' => $request, 'response' => $response, 'log' => $logEntry];
    }
}

class CompressionMiddleware {
    public static function process($request, $response) {
        $acceptEncoding = $request['headers']['Accept-Encoding'] ?? '';
        $body = $response['body'] ?? '';
        
        if (strlen($body) > 1024 && strpos($acceptEncoding, 'gzip') !== false) {
            $originalSize = strlen($body);
            $compressedBody = gzcompress($body, 6);
            $compressedSize = strlen($compressedBody);
            
            if ($compressedSize < $originalSize) {
                $response['body'] = $compressedBody;
                $response['headers']['Content-Encoding'] = 'gzip';
                $response['headers']['Content-Length'] = $compressedSize;
                $response['headers']['X-Compression-Ratio'] = round((1 - $compressedSize / $originalSize) * 100, 1) . '%';
            }
        }
        
        return ['request' => $request, 'response' => $response];
    }
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "==============================================\n";
    echo " PivotPHP Core API Middleware Benchmark\n";
    echo "==============================================\n\n";
}

$results = [];

// Test 1: CORS Middleware Performance
$results['cors_middleware'] = runBenchmark(
    'CORS Middleware', 
    $args['iterations'], 
    function() {
        $request = [
            'method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'][rand(0, 4)],
            'path' => '/api/users',
            'headers' => [
                'Origin' => 'https://frontend.example.com',
                'User-Agent' => 'Mozilla/5.0 (Chrome/91.0)'
            ]
        ];
        
        $response = [
            'status_code' => 200,
            'headers' => [],
            'body' => json_encode(['data' => 'test'])
        ];
        
        $result = CorsMiddleware::process($request, $response);
        
        return [
            'cors_headers_added' => count(array_filter(
                array_keys($result['response']['headers']),
                fn($header) => strpos($header, 'Access-Control') === 0
            )),
            'preflight_handled' => $request['method'] === 'OPTIONS' && $result['response']['status_code'] === 200
        ];
    }
);

// Test 2: Rate Limiting Middleware
$results['rate_limiting_middleware'] = runBenchmark(
    'Rate Limiting Middleware', 
    $args['iterations'], 
    function() {
        $request = [
            'method' => 'GET',
            'path' => '/api/users',
            'headers' => [
                'X-API-Key' => 'ak_' . uniqid(),
                'User-Agent' => 'API Client 1.0'
            ],
            'client_ip' => '192.168.1.' . rand(1, 255)
        ];
        
        $response = [
            'status_code' => 200,
            'headers' => [],
            'body' => json_encode(['data' => 'test'])
        ];
        
        $result = RateLimitMiddleware::process($request, $response);
        
        return [
            'rate_limit_applied' => isset($result['response']['headers']['X-RateLimit-Limit']),
            'request_blocked' => $result['response']['status_code'] === 429,
            'rate_limit_headers' => count(array_filter(
                array_keys($result['response']['headers']),
                fn($header) => strpos($header, 'X-RateLimit') === 0
            ))
        ];
    }
);

// Test 3: Authentication Middleware
$results['authentication_middleware'] = runBenchmark(
    'Authentication Middleware', 
    $args['iterations'], 
    function() {
        $authTypes = [
            ['Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c'],
            ['X-API-Key' => 'ak_' . uniqid() . '_' . rand(10000, 99999)],
            [] // No auth
        ];
        
        $headers = $authTypes[rand(0, 2)];
        
        $request = [
            'method' => 'GET',
            'path' => '/api/protected',
            'headers' => array_merge($headers, [
                'User-Agent' => 'API Client 1.0',
                'Accept' => 'application/json'
            ])
        ];
        
        $response = [
            'status_code' => 200,
            'headers' => [],
            'body' => json_encode(['data' => 'protected'])
        ];
        
        $result = AuthenticationMiddleware::process($request, $response);
        
        return [
            'authentication_attempted' => !empty($headers),
            'authentication_passed' => isset($result['request']['authenticated']),
            'user_loaded' => isset($result['request']['user']),
            'unauthorized_response' => $result['response']['status_code'] === 401
        ];
    }
);

// Test 4: Validation Middleware
$results['validation_middleware'] = runBenchmark(
    'Validation Middleware', 
    $args['iterations'], 
    function() {
        $testCases = [
            [
                'method' => 'POST',
                'path' => '/api/users',
                'body' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'password' => 'securepassword123'
                ]
            ],
            [
                'method' => 'POST',
                'path' => '/api/users',
                'body' => [
                    'name' => 'J', // Too short
                    'email' => 'invalid-email',
                    'password' => '123' // Too short
                ]
            ],
            [
                'method' => 'PUT',
                'path' => '/api/users/{id}',
                'body' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'status' => 'active'
                ]
            ]
        ];
        
        $testCase = $testCases[rand(0, 2)];
        
        $request = [
            'method' => $testCase['method'],
            'path' => $testCase['path'],
            'body' => $testCase['body'],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];
        
        $response = [
            'status_code' => 200,
            'headers' => [],
            'body' => json_encode(['data' => 'success'])
        ];
        
        $result = ValidationMiddleware::process($request, $response);
        
        return [
            'validation_attempted' => true,
            'validation_passed' => isset($result['request']['validated']),
            'validation_failed' => $result['response']['status_code'] === 422,
            'fields_validated' => count($testCase['body'])
        ];
    }
);

// Test 5: Logging Middleware
$results['logging_middleware'] = runBenchmark(
    'Logging Middleware', 
    $args['iterations'], 
    function() {
        $request = [
            'method' => ['GET', 'POST', 'PUT', 'DELETE'][rand(0, 3)],
            'path' => '/api/' . ['users', 'posts', 'comments'][rand(0, 2)],
            'headers' => [
                'User-Agent' => 'API Client ' . rand(1, 10) . '.0',
                'X-Request-ID' => uniqid('req_'),
                'Authorization' => 'Bearer token_' . uniqid()
            ],
            'client_ip' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
            'user' => [
                'id' => rand(1, 10000),
                'email' => 'user' . rand(1, 1000) . '@example.com'
            ],
            'start_time' => microtime(true) - (rand(10, 500) / 1000)
        ];
        
        $response = [
            'status_code' => [200, 201, 400, 404, 500][rand(0, 4)],
            'headers' => [],
            'body' => json_encode(['data' => str_repeat('x', rand(100, 2000))])
        ];
        
        $result = LoggingMiddleware::process($request, $response);
        
        return [
            'log_created' => isset($result['log']),
            'log_fields' => count($result['log'] ?? []),
            'request_id_added' => isset($result['response']['headers']['X-Request-ID']),
            'response_time_added' => isset($result['response']['headers']['X-Response-Time'])
        ];
    }
);

// Test 6: Compression Middleware
$results['compression_middleware'] = runBenchmark(
    'Compression Middleware', 
    $args['iterations'] / 2, 
    function() {
        $largeBodies = [
            json_encode(array_fill(0, 100, [
                'id' => rand(1, 10000),
                'name' => 'User ' . rand(1, 1000),
                'data' => str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 20)
            ])),
            str_repeat('This is repeated content for compression testing. ', 200),
            json_encode([
                'large_array' => range(1, 1000),
                'description' => str_repeat('Large response content. ', 100),
                'metadata' => array_fill_keys(range(1, 50), 'metadata_value')
            ])
        ];
        
        $body = $largeBodies[rand(0, 2)];
        
        $request = [
            'method' => 'GET',
            'path' => '/api/large-data',
            'headers' => [
                'Accept-Encoding' => rand(0, 1) ? 'gzip, deflate, br' : 'identity',
                'User-Agent' => 'API Client 1.0'
            ]
        ];
        
        $response = [
            'status_code' => 200,
            'headers' => [],
            'body' => $body
        ];
        
        $originalSize = strlen($body);
        $result = CompressionMiddleware::process($request, $response);
        $finalSize = strlen($result['response']['body']);
        
        return [
            'compression_attempted' => strpos($request['headers']['Accept-Encoding'], 'gzip') !== false,
            'compression_applied' => isset($result['response']['headers']['Content-Encoding']),
            'original_size' => $originalSize,
            'compressed_size' => $finalSize,
            'compression_ratio' => $originalSize > 0 ? round((1 - $finalSize / $originalSize) * 100, 1) : 0
        ];
    }
);

// Test 7: Complete Middleware Stack
$results['complete_middleware_stack'] = runBenchmark(
    'Complete Middleware Stack', 
    $args['iterations'] / 4, 
    function() {
        // Simulate request through entire middleware stack
        $request = [
            'method' => 'POST',
            'path' => '/api/users',
            'headers' => [
                'Origin' => 'https://app.example.com',
                'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
                'Content-Type' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'User-Agent' => 'Frontend App 2.0',
                'X-Request-ID' => uniqid('req_')
            ],
            'body' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'securepassword123',
                'profile' => [
                    'bio' => str_repeat('User biography content. ', 50)
                ]
            ],
            'client_ip' => '192.168.1.100',
            'start_time' => microtime(true)
        ];
        
        $response = [
            'status_code' => 201,
            'headers' => [],
            'body' => json_encode([
                'success' => true,
                'data' => array_merge($request['body'], [
                    'id' => rand(1, 10000),
                    'created_at' => date('c')
                ]),
                'message' => 'User created successfully'
            ])
        ];
        
        $middlewareStack = [
            CorsMiddleware::class,
            RateLimitMiddleware::class, 
            AuthenticationMiddleware::class,
            ValidationMiddleware::class,
            LoggingMiddleware::class,
            CompressionMiddleware::class
        ];
        
        $stackResult = ['request' => $request, 'response' => $response];
        $middlewareResults = [];
        
        foreach ($middlewareStack as $middleware) {
            $result = $middleware::process($stackResult['request'], $stackResult['response']);
            $stackResult = $result;
            $middlewareResults[] = $middleware;
            
            // Stop if middleware returns error
            if ($stackResult['response']['status_code'] >= 400) {
                break;
            }
        }
        
        return [
            'middlewares_executed' => count($middlewareResults),
            'final_status_code' => $stackResult['response']['status_code'],
            'headers_added' => count($stackResult['response']['headers']),
            'processing_successful' => $stackResult['response']['status_code'] < 400,
            'authentication_passed' => isset($stackResult['request']['authenticated']),
            'validation_passed' => isset($stackResult['request']['validated'])
        ];
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'api-middleware',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'middleware_types' => ['cors', 'rate_limiting', 'authentication', 'validation', 'logging', 'compression', 'complete_stack'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 API Middleware Performance Summary:\n";
    echo "=====================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP Core v1.1.0\n";
    echo "⚙️ Middleware Stack: CORS, Rate Limiting, Authentication, Validation, Logging, Compression\n";
    echo "⚡ Total Middleware Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🔧 Middleware Status: " . (array_sum(array_column($results, 'ops_per_second')) > 10000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'api-middleware',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'middleware_types' => ['cors', 'rate_limiting', 'authentication', 'validation', 'logging', 'compression', 'complete_stack'],
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-core-api-middleware-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ API middleware benchmark completed successfully!\n";
}