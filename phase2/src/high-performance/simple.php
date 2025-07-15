<?php

declare(strict_types=1);

/**
 * Entry point simplificado para alta performance
 */

use PivotPHP\Benchmark\Phase2\HighPerformance\SimpleApplication;

// Carregar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Configurar environment para máxima performance
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('UTC');

// Headers globais
header('X-Benchmark-Phase: 2');
header('X-Framework: pivotphp-core');
header('X-Performance-Profile: HIGH');
header('X-PHP-Version: ' . PHP_VERSION);

try {
    $app = new SimpleApplication([
        'performance_profile' => 'HIGH',
        'minimal_middleware' => true,
        'memory_optimization' => true
    ]);
    
    $app->run();
    
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'timestamp' => microtime(true),
        'error_id' => 'simple_err_' . uniqid()
    ]);
}