<?php

declare(strict_types=1);

use PivotPHP\Core\Application;
use PivotPHP\Core\Middleware\ErrorHandlerMiddleware;
use PivotPHP\Core\Middleware\JsonResponseMiddleware;
use PivotPHP\CycleOrm\CycleOrmServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/database.php';

// Create application instance
$app = new Application();

// Register Cycle ORM service provider
$app->register(new CycleOrmServiceProvider($config));

// Global middleware
$app->addMiddleware(new ErrorHandlerMiddleware());
$app->addMiddleware(new JsonResponseMiddleware());

// Load routes
require __DIR__ . '/../routes/api.php';

return $app;