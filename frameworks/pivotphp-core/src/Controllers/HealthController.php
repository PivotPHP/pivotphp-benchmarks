<?php

declare(strict_types=1);

namespace App\Controllers;

use PivotPHP\Core\Http\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HealthController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return Response::json([
            'status' => 'ok',
            'framework' => 'pivotphp-core',
            'version' => '1.0.0',
            'timestamp' => date('c')
        ]);
    }
}