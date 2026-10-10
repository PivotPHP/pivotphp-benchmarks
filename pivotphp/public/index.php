<?php

declare(strict_types=1);

/** @var \PivotPHP\Core\Core\Application $app */
$app = require dirname(__DIR__) . '/config/bootstrap/app.php';
$app->run();
