<?php

declare(strict_types=1);

/**
 * Entry point para aplicação de benchmark PivotPHP Core - Fase 1
 * 
 * Este arquivo inicializa a aplicação com configurações científicas
 * para benchmarks reproduzíveis e determinísticos.
 */

use PivotPHP\Benchmark\Phase1\Application;

// Carregar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Configurar environment para benchmark
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(E_ERROR | E_PARSE);

// Configurar timezone UTC para consistência
date_default_timezone_set('UTC');

// Carregar configurações
$config = json_decode(file_get_contents(__DIR__ . '/../config/benchmark.json'), true)['phase1_config'];
$seeds = json_decode(file_get_contents(__DIR__ . '/../config/seeds.json'), true)['deterministic_seeds'];

// Configurar seed global se especificado
$globalSeed = getenv('BENCHMARK_SEED') ?: $seeds['primary_seed'];
mt_srand((int)$globalSeed);

// Headers para benchmark
header('X-Benchmark-Phase: 1');
header('X-Framework: pivotphp-core');
header('X-Framework-Version: ' . (defined('PivotPHP\Core\Core\Application::VERSION') ? \PivotPHP\Core\Core\Application::VERSION : '1.1.0'));
header('X-PHP-Version: ' . PHP_VERSION);
header('X-Benchmark-Seed: ' . $globalSeed);

// Inicializar e executar aplicação
try {
    $app = new Application($config, $seeds);
    $app->run();
} catch (Throwable $e) {
    // Log de erro mínimo para benchmark
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => getenv('BENCHMARK_PHASE') === '1' ? 'Benchmark error' : $e->getMessage(),
        'timestamp' => microtime(true),
        'error_id' => 'err_' . uniqid()
    ]);
}