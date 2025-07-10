<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;
use PivotPHP\ReactPHP\Providers\ReactPHPServiceProvider;

// Create application instance
$app = new Application(dirname(__DIR__));

// Set environment to benchmark mode
$app->singleton('config', function() {
    return [
        'app' => [
            'name' => 'PivotPHP ReactPHP Benchmark',
            'env' => 'benchmark',
            'debug' => false,
        ],
        'reactphp' => [
            'host' => '0.0.0.0',
            'port' => 8000,
            'debug' => false,
        ]
    ];
});

// Register service providers
$app->register(new AppServiceProvider($app));
$app->register(new RouteServiceProvider($app));
$app->register(new ReactPHPServiceProvider($app));

// Boot the application
$app->boot();

return $app;