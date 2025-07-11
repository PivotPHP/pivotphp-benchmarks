<?php

/**
 * PivotPHP Full Stack Entry Point
 * Combines Core + ORM + ReactPHP capabilities
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Bootstrap the application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Handle the request
try {
    $app->run();
} catch (Throwable $e) {
    // Error handling
    http_response_code(500);
    
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'framework' => 'pivotphp-full'
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'error' => true,
            'message' => 'Internal Server Error',
            'framework' => 'pivotphp-full'
        ]);
    }
}