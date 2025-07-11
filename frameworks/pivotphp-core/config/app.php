<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'PivotPHP Validation'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    
    'key' => env('APP_KEY'),
    
    'providers' => [
        // Framework providers are registered in bootstrap/app.php
    ],
    
    'aliases' => [
        // Class aliases for convenience
    ],
];