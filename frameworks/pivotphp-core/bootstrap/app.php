<?php

declare(strict_types=1);

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\ErrorHandlerMiddleware;
use PivotPHP\Core\Middleware\JsonResponseMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

// Create application instance
$app = new Application();

// Global middleware
$app->addMiddleware(new ErrorHandlerMiddleware());
$app->addMiddleware(new JsonResponseMiddleware());

// Load routes
require __DIR__ . '/../routes/api.php';

return $app;