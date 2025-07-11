<?php

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Flight basic
Flight::set('flight.log_errors', false);
Flight::set('flight.handle_errors', false);

// JSON response helper
Flight::map('json', function($data, $code = 200) {
    Flight::response()
        ->status($code)
        ->header('Content-Type', 'application/json')
        ->write(json_encode($data))
        ->send();
    Flight::halt();
});

// Load simplified routes
require __DIR__ . '/../routes/api-simple.php';

// Start Flight
Flight::start();