<?php

declare(strict_types=1);

use Helix\Core\Application;
use Helix\Core\Middleware\ErrorHandlerMiddleware;
use Helix\Core\Middleware\JsonResponseMiddleware;
use Helix\CycleOrm\CycleOrmServiceProvider;

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