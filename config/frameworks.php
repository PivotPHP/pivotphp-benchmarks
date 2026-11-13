<?php

declare(strict_types=1);

return [
    'pivotphp' => [
        'name' => 'PivotPHP',
        'dir' => 'pivotphp',
        'port' => 8080,
        'type' => 'fpm',
        'runtime' => 'Nginx + PHP-FPM 8.3',
    ],
    'slim' => [
        'name' => 'Slim 4',
        'dir' => 'slim',
        'port' => 8081,
        'type' => 'fpm',
        'runtime' => 'Nginx + PHP-FPM 8.3',
    ],
    'mezzio' => [
        'name' => 'Mezzio 3',
        'dir' => 'mezzio',
        'port' => 8082,
        'type' => 'fpm',
        'runtime' => 'Nginx + PHP-FPM 8.3',
    ],
    'symfony' => [
        'name' => 'Symfony 7',
        'dir' => 'symfony',
        'port' => 8083,
        'type' => 'fpm',
        'runtime' => 'Nginx + PHP-FPM 8.3',
    ],
    'webman' => [
        'name' => 'Webman',
        'dir' => 'webman',
        'port' => 8084,
        'type' => 'async',
        'runtime' => 'CLI Async / Workerman',
    ],
];
