<?php

declare(strict_types=1);

namespace App\Controllers;

use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Request;

class TestController
{
    public function index(Request $request, Response $response): void
    {
        $tests = [
            'message' => 'Test Controller Working',
            'tests_available' => [
                'database' => '/test/database',
                'cache' => '/test/cache',
                'session' => '/test/session',
                'auth' => '/test/auth',
                'middleware' => '/test/middleware',
            ],
            'status' => 'ready',
        ];
        
        $response->json($tests);
    }
}