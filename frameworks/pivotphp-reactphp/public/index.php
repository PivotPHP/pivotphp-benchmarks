<?php

declare(strict_types=1);

/**
 * PivotPHP ReactPHP Application Entry Point
 * 
 * This starts the ReactPHP server with the bootstrapped application
 */

use PivotPHP\ReactPHP\Server\ReactServer;
use React\EventLoop\Loop;

// Bootstrap the application
$app = require __DIR__ . '/../bootstrap/app.php';

// Create event loop
$loop = Loop::get();

// Server configuration
$config = $app->make('config');
$host = $config['reactphp']['host'] ?? '0.0.0.0';
$port = $config['reactphp']['port'] ?? 8000;
$debug = $config['reactphp']['debug'] ?? false;

// Create ReactPHP server
$server = new ReactServer($app, $loop, null, [
    'host' => $host,
    'port' => $port,
    'debug' => $debug,
]);

echo "===============================================\n";
echo " PivotPHP ReactPHP Server (Benchmark Mode)\n";
echo "===============================================\n";
echo " Framework: PivotPHP ReactPHP v0.1.0\n";
echo " PHP Version: " . PHP_VERSION . "\n";
echo " Memory Limit: " . ini_get('memory_limit') . "\n";
echo " OPcache: " . (ini_get('opcache.enable') ? 'ON' : 'OFF') . "\n";
echo "-----------------------------------------------\n";
echo " Server running on http://{$host}:{$port}\n";
echo " Press Ctrl+C to stop the server\n";
echo "===============================================\n\n";

try {
    $address = "{$host}:{$port}";
    $server->listen($address);
    
    // Start the event loop
    $loop->run();
} catch (\Exception $e) {
    echo "Error starting server: " . $e->getMessage() . "\n";
    exit(1);
}