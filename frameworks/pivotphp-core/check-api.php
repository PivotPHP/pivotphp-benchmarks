#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();
$router = $app->getRouter();

echo "Router class: " . get_class($router) . "\n";
echo "Available methods:\n";

$reflection = new ReflectionClass($router);
foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if (!in_array($method->name, ['__construct', '__destruct'])) {
        echo "- " . $method->name . "()\n";
    }
}

echo "\nApplication methods:\n";
$appReflection = new ReflectionClass($app);
foreach ($appReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if (!in_array($method->name, ['__construct', '__destruct']) && !str_starts_with($method->name, '__')) {
        echo "- " . $method->name . "()\n";
    }
}