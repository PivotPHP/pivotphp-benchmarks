<?php

declare(strict_types=1);

return [
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'mysql',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_DATABASE'] ?? 'benchmark',
            'username' => $_ENV['DB_USERNAME'] ?? 'benchmark',
            'password' => $_ENV['DB_PASSWORD'] ?? 'benchmark',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]
    ],
    
    'cycle' => [
        'schema_cache' => true,
        'schema_cache_path' => __DIR__ . '/../storage/cache/cycle-schema.php',
    ]
];