<?php

declare(strict_types=1);

/**
 * Runner para SimpleApplication corrigida
 */

use PivotPHP\Benchmark\Phase2\HighPerformance\SimpleApplication;

require_once __DIR__ . '/../../vendor/autoload.php';

// Headers
header('X-Framework: pivotphp-core');
header('X-Version: 1.1.2');

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
        'framework' => 'pivotphp',
        'version' => '1.1.2',
        'timestamp' => microtime(true),
        'error_id' => 'runner_err_' . uniqid()
    ]);
}