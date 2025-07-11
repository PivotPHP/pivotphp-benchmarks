<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthController
{
    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'ok',
            'framework' => 'slim4',
            'version' => '4.0.0',
            'timestamp' => date('c')
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function health(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'status' => 'healthy',
            'framework' => 'slim4',
            'version' => '4.0.0',
            'timestamp' => date('c'),
            'uptime' => time(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}