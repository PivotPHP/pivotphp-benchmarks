#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * PivotPHP ReactPHP Server Script
 * 
 * This script starts the ReactPHP server for benchmarking
 */

use PivotPHP\ReactPHP\Server\ReactServer;
use React\EventLoop\Loop;

// Bootstrap the application
$app = require __DIR__ . '/bootstrap/app.php';

// Get server configuration
$config = $app->make('config');
$host = $config['reactphp']['host'] ?? '0.0.0.0';
$port = $config['reactphp']['port'] ?? 8000;
$debug = $config['reactphp']['debug'] ?? false;

// Create event loop
$loop = Loop::get();

// Create and start ReactPHP server
$server = $app->make(ReactServer::class, [
    'app' => $app,
    'loop' => $loop,
    'logger' => null,
    'config' => [
        'host' => $host,
        'port' => $port,
        'debug' => $debug,
    ]
]);

echo "===============================================\n";
echo " PivotPHP ReactPHP Server (Benchmark Mode)\n";
echo "===============================================\n";
echo " Environment: benchmark\n";
echo " Debug Mode: " . ($debug ? 'ON' : 'OFF') . "\n";
echo " PHP Version: " . PHP_VERSION . "\n";
echo " Memory Limit: " . ini_get('memory_limit') . "\n";
echo " OPcache: " . (ini_get('opcache.enable') ? 'ON' : 'OFF') . "\n";
echo " JIT: " . (ini_get('opcache.jit') ? 'ON' : 'OFF') . "\n";
echo "-----------------------------------------------\n";
echo " Server running on http://{$host}:{$port}\n";
echo " Press Ctrl+C to stop the server\n";
echo "===============================================\n\n";

// Start the server
$server->listen("{$host}:{$port}");