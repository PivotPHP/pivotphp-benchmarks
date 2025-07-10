<?php

namespace App\Controllers;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class HealthController
{
    public function index(Request $req, Response $res): void
    {
        $res->json([
            'framework' => 'PivotPHP ReactPHP',
            'version' => '0.1.0',
            'runtime' => 'continuous',
            'timestamp' => time(),
            'status' => 'running'
        ]);
    }

    public function health(Request $req, Response $res): void
    {
        $res->json([
            'status' => 'healthy',
            'runtime' => 'ReactPHP',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'uptime' => time() - $_SERVER['REQUEST_TIME_FLOAT'],
            'framework' => 'pivotphp-reactphp'
        ]);
    }
}