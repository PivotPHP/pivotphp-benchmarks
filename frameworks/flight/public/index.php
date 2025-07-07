<?php

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Flight
require __DIR__ . '/../app/bootstrap.php';

// Load routes
require __DIR__ . '/../routes/api.php';

// Start Flight
Flight::start();