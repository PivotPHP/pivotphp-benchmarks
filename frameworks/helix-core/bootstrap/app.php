<?php

declare(strict_types=1);

use Helix\Core\Application;
use Helix\Core\Middleware\ErrorHandlerMiddleware;
use Helix\Core\Middleware\JsonResponseMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

// Create application instance
$app = new Application();

// Global middleware
$app->addMiddleware(new ErrorHandlerMiddleware());
$app->addMiddleware(new JsonResponseMiddleware());

// Load routes
require __DIR__ . '/../routes/api.php';

return $app;