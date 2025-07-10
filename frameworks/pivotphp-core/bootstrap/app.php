<?php

declare(strict_types=1);

use PivotPHP\Core\Core\Application;

require_once __DIR__ . '/../vendor/autoload.php';

// Create application instance
$app = new Application();

// Load routes
require __DIR__ . '/../routes/api.php';

return $app;