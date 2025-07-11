<?php

require __DIR__ . '/../vendor/autoload.php';

// Configure Flight for HTTP testing
Flight::set('flight.log_errors', false);
Flight::set('flight.handle_errors', true);

// JSON response helper
Flight::map('json', function($data, $code = 200) {
    Flight::response()
        ->status($code)
        ->header('Content-Type', 'application/json')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->write(json_encode($data, JSON_PRETTY_PRINT))
        ->send();
    Flight::halt();
});

// CORS preflight handler
Flight::route('OPTIONS /*', function() {
    Flight::response()
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->send();
    Flight::halt();
});

// Load HTTP routes
require __DIR__ . '/../routes/api-http-real.php';

// Start Flight
Flight::start();