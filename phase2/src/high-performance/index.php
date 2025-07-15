<?php

declare(strict_types=1);

/**
 * Entry point para aplicação de alta performance - PivotPHP Benchmark Phase 2
 * 
 * Esta configuração implementa todas as otimizações encontradas na documentação:
 * - Modo EXTREME performance
 * - JSON Buffer Pooling v1.1.1
 * - Object pooling para Request/Response
 * - Middleware mínimo para reduzir overhead
 * - Configurações PHP otimizadas
 * 
 * Para usar:
 * php -S localhost:8080 phase2/src/high-performance/index.php
 */

use PivotPHP\Benchmark\Phase2\HighPerformance\Application;

// Carregar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Configurar environment para máxima performance
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(E_ERROR | E_PARSE);

// Configurar timezone UTC para evitar system calls
date_default_timezone_set('UTC');

// Configurar output buffering otimizado
ini_set('output_buffering', '4096');
ini_set('implicit_flush', 'Off');

// Configurações específicas para benchmarks de alta performance
$performanceConfig = [
    'performance_profile' => 'EXTREME',
    'json_pool_enabled' => true,
    'object_pool_enabled' => true,
    'response_caching' => true,
    'minimal_middleware' => true,
    'memory_optimization' => true
];

// Headers globais de benchmark
header('X-Benchmark-Phase: 2');
header('X-Framework: pivotphp-core');
header('X-Performance-Profile: EXTREME');
header('X-Optimizations: json-pooling,object-pooling,minimal-middleware');
header('X-PHP-Version: ' . PHP_VERSION);

// Verificar se extensões de performance estão disponíveis
$extensions = [];
if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    $extensions['opcache'] = $opcache['opcache_enabled'] ? 'enabled' : 'disabled';
    $extensions['jit'] = ($opcache['jit']['enabled'] ?? false) ? 'enabled' : 'disabled';
} else {
    $extensions['opcache'] = 'not-available';
    $extensions['jit'] = 'not-available';
}

header('X-Extensions: ' . json_encode($extensions));

// Inicializar e executar aplicação de alta performance
try {
    $app = new Application($performanceConfig);
    
    // Log de inicialização para desenvolvimento
    if (getenv('BENCHMARK_DEBUG') === '1') {
        error_log("High Performance Application initialized with EXTREME profile");
        error_log("JSON Pooling: " . (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool') ? 'Available' : 'Not Available'));
        error_log("OPcache: " . ($extensions['opcache'] ?? 'unknown'));
        error_log("JIT: " . ($extensions['jit'] ?? 'unknown'));
    }
    
    $app->run();
    
} catch (Throwable $e) {
    // Log de erro mínimo para benchmark
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => getenv('BENCHMARK_DEBUG') === '1' ? $e->getMessage() : 'High Performance Benchmark error',
        'timestamp' => microtime(true),
        'error_id' => 'hp_err_' . uniqid(),
        'performance_profile' => 'EXTREME'
    ]);
    
    if (getenv('BENCHMARK_DEBUG') === '1') {
        error_log("High Performance Application Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}