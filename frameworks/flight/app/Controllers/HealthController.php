<?php

namespace App\Controllers;

use Flight;

class HealthController
{
    public function index()
    {
        Flight::json([
            'status' => 'ok',
            'framework' => 'flight',
            'version' => '3.0.0',
            'timestamp' => date('c')
        ]);
    }
    
    public function health()
    {
        Flight::json([
            'status' => 'healthy',
            'framework' => 'flight',
            'version' => '3.0.0',
            'timestamp' => date('c'),
            'uptime' => time(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
    }
}