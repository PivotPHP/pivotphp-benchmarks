<?php

use App\Controllers\HealthController;
use App\Controllers\UserController;

// Configure Flight
Flight::set('flight.log_errors', false);
Flight::set('flight.handle_errors', false);

// Register controllers
Flight::register('healthController', HealthController::class);
Flight::register('userController', UserController::class);

// JSON response helper
Flight::map('json', function($data, $code = 200) {
    Flight::response()
        ->status($code)
        ->header('Content-Type', 'application/json')
        ->write(json_encode($data))
        ->send();
    Flight::halt();
});