<?php

declare(strict_types=1);

/**
 * Entry point otimizado para PivotPHP v1.1.2 (Consolidation Edition)
 */

use PivotPHP\Benchmark\Phase2\HighPerformance\OptimizedApplication;

// Carregar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Configurações otimizadas para v1.1.2
ini_set('display_errors', '0');
ini_set('log_errors', '0');
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('UTC');

// Headers específicos para v1.1.2
header('X-Benchmark-Phase: 2');
header('X-Framework: pivotphp-core');
header('X-Framework-Version: 1.1.2');
header('X-Framework-Edition: Consolidation');
header('X-Performance-Profile: EXTREME');
header('X-PHP-Version: ' . PHP_VERSION);

try {
    $app = new OptimizedApplication([
        'performance_profile' => 'EXTREME',
        'consolidation_mode' => true,
        'memory_optimization' => true,
        'enhanced_pooling' => true
    ]);
    
    $app->run();
    
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'version' => '1.1.2',
        'timestamp' => microtime(true),
        'error_id' => 'v112_err_' . uniqid()
    ]);
}