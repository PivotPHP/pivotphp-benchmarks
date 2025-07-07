<?php

/**
 * Built-in PHP server for concurrency testing
 */

$app = require __DIR__ . '/bootstrap/app.php';

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string from URI
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

// Handle static files
$path = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($path) && is_file($path)) {
    return false; // Let PHP built-in server handle it
}

// Run application
$app->run();