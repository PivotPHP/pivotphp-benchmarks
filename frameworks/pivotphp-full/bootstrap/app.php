<?php

/**
 * PivotPHP Full Stack Bootstrap
 * Combines Core + ORM + ReactPHP capabilities
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Core\Container;
use PivotPHP\CycleORM\ORM\DatabaseManager;
use PivotPHP\ReactPHP\ReactPHP\Server;

// Create the main application instance
$app = new Application();

// Get the container
$container = $app->getContainer();

// Configure database (ORM capability)
$container->bind('database', function() {
    return new DatabaseManager([
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'database' => $_ENV['DB_DATABASE'] ?? 'pivotphp_full',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]
        ]
    ]);
});

// Configure ReactPHP (async capability)
$container->bind('react-server', function() {
    return new Server([
        'host' => $_ENV['REACT_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['REACT_PORT'] ?? 8080,
        'workers' => $_ENV['REACT_WORKERS'] ?? 4
    ]);
});

// Register middleware
$app->use(\App\Middleware\CorsMiddleware::class);
$app->use(\App\Middleware\RateLimitMiddleware::class);

// Load routes
require_once __DIR__ . '/../routes/api.php';

return $app;