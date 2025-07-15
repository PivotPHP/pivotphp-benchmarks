<?php

declare(strict_types=1);

/**
 * Entry point para Slim 4 - Fase 3 Benchmarks
 */

use PivotPHP\Benchmark\Phase3\Slim4\SlimApplication;

// Carregar autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Headers globais para benchmark
header('X-Benchmark-Phase: 3');
header('X-Framework: slim4');
header('X-Framework-Version: 4.12');
header('X-Performance-Profile: EXTREME');
header('X-PHP-Version: ' . PHP_VERSION);

try {
    $app = new SlimApplication([
        'performance_profile' => 'EXTREME',
        'display_errors' => false,
        'log_errors' => false
    ]);
    
    $app->run();
    
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'framework' => 'slim4',
        'timestamp' => microtime(true),
        'error_id' => 'slim4_err_' . uniqid()
    ]);
}