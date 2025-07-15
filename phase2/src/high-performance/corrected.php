<?php

declare(strict_types=1);

/**
 * PivotPHP v1.1.2 - Implementação CORRIGIDA para Benchmarks
 *
 * Baseado na análise do código fonte oficial e exemplos práticos
 */

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Performance\HighPerformanceMode;

// Carregar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Configurações otimizadas
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('UTC');

// ✅ ATIVAR MODO DE ALTA PERFORMANCE - CRÍTICO!
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

// Headers globais
header('X-Benchmark-Phase: 2');
header('X-Framework: pivotphp-core');
header('X-Framework-Version: 1.1.2');
header('X-Performance-Profile: EXTREME');
header('X-JSON-Pooling: enabled');
header('X-PHP-Version: ' . PHP_VERSION);

$startTime = microtime(true);
$requestCount = 0;

try {
    $app = new Application();
    $app->boot();

    // Middleware básico para CORS e performance
    $app->use(function ($req, $res, $next) use (&$requestCount) {
        $requestCount++;
        $startTime = microtime(true);

        // CORS headers
        $res->header('Access-Control-Allow-Origin', '*');
        $res->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $res->header('Access-Control-Allow-Headers', 'Content-Type');
        $res->header('Content-Type', 'application/json');

        // Handle OPTIONS
        if ($req->method() === 'OPTIONS') {
            return $res->status(200)->send('');
        }

        $response = $next($req, $res);

        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;

        // Performance headers
        $response->header('X-Processing-Time-Ms', number_format($processingTime, 3));
        $response->header('X-Request-Count', (string)$requestCount);

        return $response;
    });

    // ============================================
    // ENDPOINTS CORRIGIDOS - USAR CLOSURES!
    // ============================================

    // Health check
    $app->get('/health', function ($req, $res) use ($startTime, &$requestCount) {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        return $res->json([
            'status' => 'healthy',
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'edition' => 'Consolidation Edition',
            'timestamp' => microtime(true),
            'uptime' => microtime(true) - $startTime,
            'requests_served' => $requestCount,
            'performance_mode' => 'EXTREME',
            'json_pooling' => class_exists('PivotPHP\Core\Json\Pool\JsonBufferPool'),
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'php_version' => PHP_VERSION
        ]);
    });

    // Ultra fast endpoint
    $app->get('/ultra-fast', function ($req, $res) use (&$requestCount) {
        return $res->json([
            'ok' => true,
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'request_id' => $requestCount
        ]);
    });

    // JSON Performance Tests
    $app->get('/performance/json/{size}', function ($req, $res) {
        $size = $req->param('size');
        $iterations = (int)($req->query('iterations') ?? 1000);

        $testData = generateTestData($size);

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $encoded = json_encode($testData);
            $decoded = json_decode($encoded, true);
        }

        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;

        return $res->json([
            'test' => 'json_performance_pivotphp',
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'size' => $size,
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / (($endTime - $startTime) ?: 0.001)),
            'json_pooling_active' => class_exists('PivotPHP\Core\Json\Pool\JsonBufferPool'),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    });

    // POST JSON test
    $app->post('/performance/json/{size}', function ($req, $res) {
        $size = $req->param('size');
        $body = $req->getBody()->getContents();
        $receivedData = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $res->status(400)->json([
                'error' => 'Invalid JSON',
                'message' => json_last_error_msg(),
                'framework' => 'pivotphp'
            ]);
        }

        $startTime = microtime(true);
        $reencoded = json_encode($receivedData);
        $endTime = microtime(true);

        return $res->json([
            'received_data' => $receivedData,
            'reencoding_time_ms' => ($endTime - $startTime) * 1000,
            'data_size_bytes' => strlen($body),
            'framework' => 'pivotphp',
            'version' => '1.1.2'
        ]);
    });

    // Throughput test
    $app->get('/performance/throughput', function ($req, $res) {
        $operations = (int)($req->query('operations') ?? 5000);

        $startTime = microtime(true);
        $results = [];

        for ($i = 0; $i < $operations; $i++) {
            $data = [
                'id' => $i,
                'value' => rand(1, 1000),
                'framework' => 'pivotphp',
                'processed_at' => $startTime + $i
            ];
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
                'avg_time_per_operation_ms' => $totalTime / $operations,
                'results_count' => count($results)
            ],
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    });

    // Pool stats
    $app->get('/performance/pool-stats', function ($req, $res) use ($startTime, &$requestCount) {
        $hasJsonPool = class_exists('PivotPHP\Core\Json\Pool\JsonBufferPool');

        return $res->json([
            'framework' => 'pivotphp',
            'version' => '1.1.2',
            'json_pooling' => [
                'available' => $hasJsonPool,
                'buffer_pool_active' => $hasJsonPool,
                'optimization_level' => 'EXTREME'
            ],
            'performance_metrics' => [
                'total_requests' => $requestCount,
                'uptime_seconds' => microtime(true) - $startTime,
                'avg_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ],
            'system' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'php_version' => PHP_VERSION
            ]
        ]);
    });

    // Metadata
    $app->get('/meta', function ($req, $res) {
        return $res->json([
            'application' => [
                'name' => 'PivotPHP v1.1.2 Corrected Benchmark',
                'framework' => 'pivotphp',
                'version' => '1.1.2',
                'edition' => 'Consolidation Edition'
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() && opcache_get_status()['opcache_enabled'],
                'high_performance_mode' => true
            ],
            'features' => [
                'psr_compliance' => ['PSR-7', 'PSR-15', 'PSR-12', 'PSR-14'],
                'express_js_api' => true,
                'object_pooling' => true,
                'json_optimization' => true,
                'json_buffer_pool' => class_exists('PivotPHP\Core\Json\Pool\JsonBufferPool')
            ],
            'optimizations' => [
                'high_performance_mode' => 'EXTREME',
                'json_pooling' => 'enabled',
                'memory_optimization' => true
            ]
        ]);
    });

    // Database simulation endpoints (para compatibilidade com testes)
    $app->get('/db/health', function ($req, $res) {
        return $res->json([
            'status' => 'healthy',
            'framework' => 'pivotphp',
            'database' => [
                'simulated' => true,
                'connection_time_ms' => 0.1
            ]
        ]);
    });

    $app->get('/db/users', function ($req, $res) {
        $limit = (int)($req->query('limit') ?? 100);

        $users = [];
        for ($i = 1; $i <= $limit; $i++) {
            $users[] = [
                'id' => $i,
                'name' => "User $i",
                'email' => "user$i@example.com"
            ];
        }

        return $res->json([
            'users' => $users,
            'count' => count($users),
            'framework' => 'pivotphp'
        ]);
    });

    $app->get('/db/complex-query', function ($req, $res) {
        // Simular query complexa
        $results = array_fill(0, 100, [
            'user_id' => rand(1, 1000),
            'score' => rand(1, 100),
            'calculated' => rand(1, 1000)
        ]);

        return $res->json([
            'query_type' => 'complex_simulation',
            'results' => $results,
            'framework' => 'pivotphp'
        ]);
    });

    $app->get('/db/stats', function ($req, $res) {
        return $res->json([
            'framework' => 'pivotphp',
            'database_stats' => [
                'simulated' => true,
                'performance' => 'optimized'
            ]
        ]);
    });

    // Middleware tests
    $app->get('/middleware/stack', function ($req, $res) {
        return $res->json([
            'middleware_test' => 'stack_processed',
            'framework' => 'pivotphp'
        ]);
    });

    $app->get('/middleware/cors', function ($req, $res) {
        return $res->json([
            'cors_test' => 'headers_applied',
            'framework' => 'pivotphp'
        ]);
    });

    $app->get('/middleware/auth', function ($req, $res) {
        return $res->json([
            'auth_test' => 'simulated_success',
            'framework' => 'pivotphp'
        ]);
    });

    // Executar aplicação
    $app->run();

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'framework' => 'pivotphp',
        'version' => '1.1.2',
        'timestamp' => microtime(true),
        'error_id' => 'pivotphp_err_' . uniqid()
    ]);
}

/**
 * Gera dados de teste para benchmarks JSON
 */
function generateTestData(string $size): array
{
    switch ($size) {
        case 'small':
            return [
                'id' => 1,
                'name' => 'Test PivotPHP',
                'value' => 123.45,
                'framework' => 'pivotphp',
                'version' => '1.1.2'
            ];

        case 'medium':
            return [
                'id' => 1,
                'framework' => 'pivotphp',
                'version' => '1.1.2',
                'data' => array_fill(0, 50, [
                    'item' => rand(1, 1000),
                    'value' => microtime(true),
                    'framework' => 'pivotphp'
                ]),
                'metadata' => [
                    'generated_at' => date('c'),
                    'size' => 'medium',
                    'framework' => 'pivotphp'
                ]
            ];

        case 'large':
            return [
                'id' => 1,
                'framework' => 'pivotphp',
                'version' => '1.1.2',
                'large_dataset' => array_fill(0, 500, [
                    'id' => rand(1, 10000),
                    'data' => str_repeat('x', 100),
                    'timestamp' => microtime(true),
                    'framework' => 'pivotphp'
                ]),
                'metadata' => [
                    'generated_at' => date('c'),
                    'size' => 'large',
                    'framework' => 'pivotphp'
                ]
            ];

        default:
            return [
                'error' => 'Invalid size parameter',
                'framework' => 'pivotphp'
            ];
    }
}
