<?php

declare(strict_types=1);

/**
 * Entry point para aplicação PivotPHP Core v1.1.4 - Phase 1
 * 
 * Configuração otimizada para benchmarks em ambiente Docker isolado
 * Usando sintaxe oficial do PivotPHP v1.1.4
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Application;

// Configurar environment para benchmark
putenv('APP_ENV=benchmark');
putenv('APP_DEBUG=false');

// Configurar otimizações para performance
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '30');
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(E_ERROR | E_PARSE);

// Configurar timezone UTC para consistência
date_default_timezone_set('UTC');

// Headers para identificação
header('X-Framework: PivotPHP-v1.1.4');
header('X-Benchmark-Phase: 1');
header('X-PHP-Version: ' . PHP_VERSION);

// Criar aplicação PivotPHP v1.1.4
$app = new Application();

// Middleware básico para headers de benchmark
$app->use(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response->withHeader('X-Framework-Version', '1.1.4')
                   ->withHeader('X-Benchmark-Mode', 'active');
});

// === ROTAS DE BENCHMARK ===

// Health Check
$app->get('/health', function ($req, $res) {
    return $res->json([
        'status' => 'healthy',
        'version' => '1.1.4',
        'edition' => 'Enhanced',
        'timestamp' => microtime(true),
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
        'php_version' => PHP_VERSION,
        'benchmark_ready' => true
    ]);
});

// Metadata da aplicação
$app->get('/meta', function ($req, $res) {
    return $res->json([
        'application' => [
            'name' => 'PivotPHP Benchmark Phase 1',
            'version' => '1.1.4',
            'edition' => 'Enhanced',
            'framework' => 'pivotphp-core'
        ],
        'environment' => [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'],
            'jit_enabled' => function_exists('opcache_get_status') && (opcache_get_status()['jit']['enabled'] ?? false)
        ],
        'benchmark' => [
            'phase' => 1,
            'datetime' => date('c'),
            'timestamp' => microtime(true),
            'optimized' => true
        ]
    ]);
});

// JSON Pequeno (< 1KB) - Otimizado para v1.1.4
$app->get('/json/small', function ($req, $res) {
    $data = [
        'id' => 1,
        'type' => 'small',
        'name' => 'Small JSON Response v1.1.4',
        'timestamp' => microtime(true),
        'data' => array_fill(0, 10, 'small-data-item'),
        'meta' => [
            'size' => 'small',
            'phase' => 1,
            'version' => '1.1.4',
            'optimized' => true
        ]
    ];
    return $res->json($data);
});

// JSON Médio (1KB - 10KB) - Otimizado para v1.1.4
$app->get('/json/medium', function ($req, $res) {
    $data = [
        'id' => 2,
        'type' => 'medium',
        'name' => 'Medium JSON Response v1.1.4',
        'timestamp' => microtime(true),
        'data' => array_fill(0, 100, str_repeat('medium-data-', 10)),
        'users' => array_map(function($i) {
            return [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'profile' => str_repeat("profile-data-{$i}-", 5),
                'created_at' => date('c')
            ];
        }, range(1, 25)),
        'meta' => [
            'size' => 'medium',
            'phase' => 1,
            'version' => '1.1.4',
            'user_count' => 25
        ]
    ];
    return $res->json($data);
});

// JSON Grande (10KB - 100KB) - Otimizado para v1.1.4
$app->get('/json/large', function ($req, $res) {
    $data = [
        'id' => 3,
        'type' => 'large',
        'name' => 'Large JSON Response v1.1.4',
        'timestamp' => microtime(true),
        'data' => array_fill(0, 500, str_repeat('large-data-item-', 20)),
        'users' => array_map(function($i) {
            return [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'profile' => str_repeat("detailed-profile-data-{$i}-", 15),
                'posts' => array_fill(0, 5, [
                    'id' => $i * 5,
                    'title' => "Post {$i}",
                    'content' => str_repeat("Post content data for user {$i} ", 30),
                    'created_at' => date('c')
                ]),
                'metadata' => [
                    'score' => $i * 10,
                    'level' => floor($i / 10),
                    'active' => $i % 2 === 0
                ]
            ];
        }, range(1, 100)),
        'meta' => [
            'size' => 'large',
            'phase' => 1,
            'version' => '1.1.4',
            'user_count' => 100,
            'post_count' => 500
        ]
    ];
    return $res->json($data);
});

// JSON variável - Otimizado para v1.1.4
$app->get('/json/variable', function ($req, $res) {
    $size = (int)($req->query('size') ?? 50);
    $size = min(max($size, 1), 1000); // Limitar entre 1 e 1000
    
    $data = [
        'id' => 4,
        'type' => 'variable',
        'name' => 'Variable JSON Response v1.1.4',
        'requested_size' => $size,
        'timestamp' => microtime(true),
        'items' => array_map(function($i) {
            return [
                'id' => $i,
                'value' => str_repeat("item-{$i}-", 5),
                'random' => rand(1, 1000),
                'timestamp' => microtime(true)
            ];
        }, range(1, $size)),
        'meta' => [
            'size' => 'variable',
            'phase' => 1,
            'version' => '1.1.4',
            'actual_size' => $size
        ]
    ];
    return $res->json($data);
});

// Echo endpoint para testes POST
$app->post('/json/echo', function ($req, $res) {
    $body = $req->getBody()->getContents();
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $res->withStatus(400)->json([
            'error' => 'Invalid JSON',
            'message' => json_last_error_msg(),
            'version' => '1.1.4'
        ]);
    }
    
    return $res->json([
        'received' => $data,
        'timestamp' => microtime(true),
        'echo_confirmed' => true,
        'body_size' => strlen($body),
        'version' => '1.1.4'
    ]);
});

// Stress test CPU - Otimizado para v1.1.4
$app->get('/stress/cpu', function ($req, $res) {
    $iterations = (int)($req->query('iterations') ?? 10000);
    $iterations = min(max($iterations, 100), 1000000); // Limitar para segurança
    
    $start = microtime(true);
    $result = 0;
    
    for ($i = 0; $i < $iterations; $i++) {
        $result += sin($i / 1000) * cos($i / 1000);
    }
    
    $end = microtime(true);
    $executionTime = $end - $start;
    
    return $res->json([
        'result' => round($result, 6),
        'iterations' => $iterations,
        'execution_time_ms' => $executionTime * 1000,
        'operations_per_second' => round($iterations / $executionTime, 2),
        'memory_usage' => memory_get_usage(true),
        'version' => '1.1.4'
    ]);
});

// Stress test Memory - Otimizado para v1.1.4
$app->get('/stress/memory', function ($req, $res) {
    $size = (int)($req->query('size') ?? 1000);
    $size = min(max($size, 10), 100000); // Limitar para segurança
    
    $memoryBefore = memory_get_usage(true);
    
    // Alocar array
    $data = array_fill(0, $size, str_repeat('x', 100));
    $memoryAfter = memory_get_usage(true);
    
    // Processar dados
    $processed = array_map('strlen', $data);
    $memoryPeak = memory_get_peak_usage(true);
    
    // Limpar memória
    unset($data);
    $memoryAfterCleanup = memory_get_usage(true);
    
    return $res->json([
        'array_size' => $size,
        'memory_before_bytes' => $memoryBefore,
        'memory_after_bytes' => $memoryAfter,
        'memory_peak_bytes' => $memoryPeak,
        'memory_after_cleanup_bytes' => $memoryAfterCleanup,
        'memory_allocated_bytes' => $memoryAfter - $memoryBefore,
        'processed_count' => count($processed),
        'version' => '1.1.4'
    ]);
});

// Teste de roteamento com parâmetros
$app->get('/param/{id}', function ($req, $res) {
    $id = $req->param('id');
    return $res->json([
        'param_id' => $id,
        'type' => gettype($id),
        'timestamp' => microtime(true),
        'version' => '1.1.4'
    ]);
});

$app->get('/param/{id}/user/{name}', function ($req, $res) {
    return $res->json([
        'id' => $req->param('id'),
        'name' => $req->param('name'),
        'all_params' => $req->params(),
        'query_params' => $req->query(),
        'version' => '1.1.4'
    ]);
});

// Performance endpoint
$app->get('/performance', function ($req, $res) {
    $start = microtime(true);
    
    // Simular processamento
    $data = [];
    for ($i = 0; $i < 1000; $i++) {
        $data[] = [
            'id' => $i,
            'value' => $i * 2,
            'computed' => sqrt($i),
            'timestamp' => microtime(true)
        ];
    }
    
    $end = microtime(true);
    
    return $res->json([
        'processing_time_ms' => ($end - $start) * 1000,
        'items_processed' => count($data),
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
        'version' => '1.1.4',
        'timestamp' => microtime(true)
    ]);
});

// Teste de headers e middleware
$app->get('/headers', function ($req, $res) {
    return $res->json([
        'headers' => $req->headers(),
        'user_agent' => $req->header('User-Agent'),
        'content_type' => $req->header('Content-Type'),
        'custom_headers' => array_filter($req->headers(), function($key) {
            return strpos($key, 'X-') === 0;
        }, ARRAY_FILTER_USE_KEY),
        'version' => '1.1.4'
    ]);
});

// Endpoint para testes de erro controlados
$app->get('/error-test', function ($req, $res) {
    $type = $req->query('type') ?? 'none';
    
    switch ($type) {
        case '404':
            return $res->withStatus(404)->json([
                'error' => 'Not Found Test',
                'version' => '1.1.4'
            ]);
        case '500':
            return $res->withStatus(500)->json([
                'error' => 'Internal Server Error Test',
                'version' => '1.1.4'
            ]);
        case 'validation':
            return $res->withStatus(422)->json([
                'error' => 'Validation Error Test',
                'version' => '1.1.4'
            ]);
        default:
            return $res->json([
                'message' => 'No error',
                'available_types' => ['404', '500', 'validation'],
                'version' => '1.1.4'
            ]);
    }
});

// Rota catch-all para testes
$app->get('/test/{endpoint:.*}', function ($req, $res) {
    return $res->json([
        'endpoint' => $req->param('endpoint'),
        'message' => 'Test endpoint',
        'timestamp' => microtime(true),
        'version' => '1.1.4'
    ]);
});

// Executar aplicação
try {
    $app->run();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Application Error',
        'message' => 'Internal server error during benchmark',
        'timestamp' => microtime(true),
        'version' => '1.1.4',
        'error_id' => 'err_' . uniqid()
    ]);
}