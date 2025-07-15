<?php

declare(strict_types=1);

/**
 * PivotPHP v1.1.2 - Versão Simplificada e Funcional
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Headers básicos
header('X-Framework: pivotphp-core');
header('X-Version: 1.1.2');

$startTime = microtime(true);
$requestCount = 0;

try {
    $app = new Application();
    $app->boot();
    
    // Health check básico
    $app->get('/health', function ($req, $res) use ($startTime, &$requestCount) {
        $requestCount++;
        
        return $res->json([
            'status' => 'healthy',
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'timestamp' => microtime(true),
            'uptime' => microtime(true) - $startTime,
            'requests_served' => $requestCount,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'php_version' => PHP_VERSION
        ]);
    });
    
    // Ultra fast
    $app->get('/ultra-fast', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        
        return $res->json([
            'ok' => true,
            'framework' => 'pivotphp',
            'request_id' => $requestCount
        ]);
    });
    
    // JSON Small Test with Buffer Pooling
    $app->get('/performance/json/small', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        $iterations = (int)($req->query('iterations') ?? 1000);
        
        $testData = [
            'id' => 1,
            'name' => 'Test PivotPHP',
            'value' => 123.45,
            'framework' => 'pivotphp'
        ];
        
        $startTime = microtime(true);
        
        // Test with JsonBufferPool for better performance
        for ($i = 0; $i < $iterations; $i++) {
            $encoded = JsonBufferPool::encodeWithPool($testData);
            $decoded = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        
        // Get pool statistics
        $poolStats = JsonBufferPool::getStatistics();
        
        return $res->json([
            'test' => 'json_performance_pivotphp',
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'size' => 'small',
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / (($endTime - $startTime) ?: 0.001)),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'json_pool_stats' => [
                'reuse_rate' => $poolStats['reuse_rate'],
                'total_operations' => $poolStats['total_operations']
            ]
        ]);
    });
    
    // JSON Medium Test with Buffer Pooling
    $app->get('/performance/json/medium', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        $iterations = (int)($req->query('iterations') ?? 1000);
        
        $testData = [
            'id' => 1,
            'framework' => 'pivotphp',
            'data' => array_fill(0, 50, [
                'item' => rand(1, 1000),
                'value' => microtime(true)
            ])
        ];
        
        $startTime = microtime(true);
        
        // Test with JsonBufferPool for better performance
        for ($i = 0; $i < $iterations; $i++) {
            $encoded = JsonBufferPool::encodeWithPool($testData);
            $decoded = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        
        // Get pool statistics
        $poolStats = JsonBufferPool::getStatistics();
        
        return $res->json([
            'test' => 'json_performance_pivotphp',
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'size' => 'medium',
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / (($endTime - $startTime) ?: 0.001)),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'json_pool_stats' => [
                'reuse_rate' => $poolStats['reuse_rate'],
                'total_operations' => $poolStats['total_operations']
            ]
        ]);
    });
    
    // JSON Large Test with Buffer Pooling
    $app->get('/performance/json/large', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        $iterations = (int)($req->query('iterations') ?? 500);
        
        $testData = [
            'id' => 1,
            'framework' => 'pivotphp',
            'large_dataset' => array_fill(0, 500, [
                'id' => rand(1, 10000),
                'data' => str_repeat('x', 100)
            ])
        ];
        
        $startTime = microtime(true);
        
        // Test with JsonBufferPool for better performance
        for ($i = 0; $i < $iterations; $i++) {
            $encoded = JsonBufferPool::encodeWithPool($testData);
            $decoded = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        
        // Get pool statistics
        $poolStats = JsonBufferPool::getStatistics();
        
        return $res->json([
            'test' => 'json_performance_pivotphp',
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'size' => 'large',
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / (($endTime - $startTime) ?: 0.001)),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'json_pool_stats' => [
                'reuse_rate' => $poolStats['reuse_rate'],
                'total_operations' => $poolStats['total_operations']
            ]
        ]);
    });
    
    // Throughput test
    $app->get('/performance/throughput', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        $operations = (int)($req->query('operations') ?? 5000);
        
        $startTime = microtime(true);
        $results = [];
        
        for ($i = 0; $i < $operations; $i++) {
            $data = ['id' => $i, 'value' => rand(1, 1000)];
            $encoded = json_encode($data);
            $results[] = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        return $res->json([
            'throughput_test_pivotphp' => [
                'operations' => $operations,
                'total_time_ms' => $totalTime,
                'operations_per_second' => round($operations / (($endTime - $startTime) ?: 0.001)),
                'results_count' => count($results)
            ],
            'framework' => 'pivotphp',
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    });
    
    // Pool stats with JsonBufferPool statistics
    $app->get('/performance/pool-stats', function ($req, $res) use ($startTime, &$requestCount) {
        $requestCount++;
        
        // Get JsonBufferPool statistics
        $poolStats = JsonBufferPool::getStatistics();
        
        return $res->json([
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'performance_metrics' => [
                'total_requests' => $requestCount,
                'uptime_seconds' => microtime(true) - $startTime
            ],
            'system' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'php_version' => PHP_VERSION
            ],
            'json_buffer_pool' => [
                'reuse_rate' => $poolStats['reuse_rate'],
                'total_operations' => $poolStats['total_operations'],
                'current_usage' => $poolStats['current_usage'],
                'peak_usage' => $poolStats['peak_usage'],
                'active_pools' => $poolStats['active_pool_count'],
                'pool_sizes' => $poolStats['pool_sizes']
            ]
        ]);
    });
    
    // Metadata
    $app->get('/meta', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        
        return $res->json([
            'application' => [
                'name' => 'PivotPHP Simple Corrected',
                'framework' => 'pivotphp',
                'version' => '1.1.2'
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit')
            ]
        ]);
    });
    
    // Database simulation
    $app->get('/db/health', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        
        return $res->json([
            'status' => 'healthy',
            'framework' => 'pivotphp',
            'database' => ['simulated' => true]
        ]);
    });
    
    $app->get('/db/users', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        $limit = (int)($req->query('limit') ?? 100);
        
        $users = [];
        for ($i = 1; $i <= $limit; $i++) {
            $users[] = ['id' => $i, 'name' => "User $i"];
        }
        
        return $res->json([
            'users' => $users,
            'count' => count($users),
            'framework' => 'pivotphp'
        ]);
    });
    
    $app->get('/db/complex-query', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        
        return $res->json([
            'query_type' => 'complex_simulation',
            'results' => array_fill(0, 100, ['id' => rand(1, 1000)]),
            'framework' => 'pivotphp'
        ]);
    });
    
    $app->get('/db/stats', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        
        return $res->json([
            'framework' => 'pivotphp',
            'database_stats' => ['simulated' => true]
        ]);
    });
    
    // Middleware tests
    $app->get('/middleware/stack', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        return $res->json([
            'middleware_test' => 'stack', 
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'request_count' => $requestCount
        ]);
    });
    
    $app->get('/middleware/cors', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        return $res->json([
            'cors_test' => 'applied', 
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'request_count' => $requestCount
        ]);
    });
    
    $app->get('/middleware/auth', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        return $res->json([
            'auth_test' => 'simulated', 
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'request_count' => $requestCount
        ]);
    });
    
    // Additional endpoints for v1.1.2 features
    $app->get('/v112/consolidation', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        return $res->json([
            'feature' => 'consolidation_edition',
            'version' => '1.1.2',
            'improvements' => [
                'zero_duplications' => true,
                'organized_middlewares' => true,
                'backward_compatible' => true,
                'phpstan_level_9' => true
            ],
            'framework' => 'pivotphp',
            'request_count' => $requestCount
        ]);
    });
    
    $app->get('/v112/architecture', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        return $res->json([
            'architecture' => 'consolidated',
            'version' => '1.1.2',
            'structure' => [
                'middleware_security' => 'src/Middleware/Security/',
                'middleware_performance' => 'src/Middleware/Performance/',
                'middleware_http' => 'src/Middleware/Http/',
                'json_pooling' => 'src/Json/Pool/',
                'utils_consolidated' => 'src/Utils/'
            ],
            'aliases_count' => 12,
            'framework' => 'pivotphp',
            'request_count' => $requestCount
        ]);
    });
    
    // Advanced performance test
    $app->get('/performance/advanced', function ($req, $res) use (&$requestCount) {
        $requestCount++;
        $operations = (int)($req->query('operations') ?? 10000);
        
        $startTime = microtime(true);
        
        // Mixed operations test
        $results = [];
        for ($i = 0; $i < $operations; $i++) {
            $data = [
                'operation' => $i,
                'type' => $i % 4 === 0 ? 'large' : ($i % 2 === 0 ? 'medium' : 'small'),
                'payload' => $i % 4 === 0 ? array_fill(0, 100, 'data') : ['simple' => $i]
            ];
            
            $encoded = JsonBufferPool::encodeWithPool($data);
            $results[] = strlen($encoded);
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        $poolStats = JsonBufferPool::getStatistics();
        
        return $res->json([
            'test' => 'advanced_performance',
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'operations' => $operations,
            'total_time_ms' => $totalTime,
            'operations_per_second' => round($operations / (($endTime - $startTime) ?: 0.001)),
            'average_response_size' => round(array_sum($results) / count($results), 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'json_pool_efficiency' => [
                'reuse_rate' => $poolStats['reuse_rate'],
                'total_operations' => $poolStats['total_operations'],
                'active_pools' => $poolStats['active_pool_count']
            ]
        ]);
    });
    
    $app->run();
    
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'framework' => 'pivotphp',
        'error_id' => 'simple_err_' . uniqid()
    ]);
}