<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Check if helpers file exists
if (file_exists(__DIR__ . '/helpers.php')) {
    require __DIR__ . '/helpers.php';
}

use PivotPHP\Core\Core\Application;
use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;

// Load environment variables only if package exists
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

// Create application instance
$app = new Application(dirname(__DIR__));

// Register service providers
$app->register(new AppServiceProvider($app));
$app->register(new RouteServiceProvider($app));

// Boot the application (this will initialize services and configurations)
$app->boot();

return $app;