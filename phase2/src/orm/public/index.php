<?php

declare(strict_types=1);

/**
 * Entry point para aplicação de benchmark PivotPHP + Cycle ORM - Fase 2
 * 
 * Este arquivo inicializa a aplicação com configurações científicas
 * para benchmarks reproduzíveis e determinísticos com ORM.
 */

use PivotPHP\Benchmark\Phase2\ORM\Application;

// Carregar autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Configurar environment para benchmark
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(E_ERROR | E_PARSE);

// Configurar timezone UTC para consistência
date_default_timezone_set('UTC');

// Carregar configurações
$config = json_decode(file_get_contents(__DIR__ . '/../../../config/benchmark-phase2.json'), true)['phase2_config'];
$dbConfig = require __DIR__ . '/../../../config/database.php';

// Configurar seed global se especificado
$seeds = json_decode(file_get_contents(__DIR__ . '/../../../config/seeds-phase2.json'), true)['deterministic_seeds'];
$globalSeed = getenv('BENCHMARK_SEED') ?: $seeds['primary_seed'];
mt_srand((int)$globalSeed);

// Headers para benchmark
header('X-Benchmark-Phase: 2');
header('X-Framework: pivotphp-core');
header('X-Extension: cycle-orm');
header('X-PHP-Version: ' . PHP_VERSION);
header('X-Benchmark-Seed: ' . $globalSeed);

// Inicializar e executar aplicação
try {
    $app = new Application($config, $dbConfig['databases']['mysql']);
    $app->run();
} catch (Throwable $e) {
    // Log de erro mínimo para benchmark
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => getenv('BENCHMARK_PHASE') === '2' ? 'ORM Benchmark error' : $e->getMessage(),
        'timestamp' => microtime(true),
        'error_id' => 'orm_err_' . uniqid(),
        'extension' => 'cycle-orm'
    ]);
}