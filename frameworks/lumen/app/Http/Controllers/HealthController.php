<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class HealthController extends BaseController
{
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'framework' => 'lumen',
            'version' => app()->version(),
            'timestamp' => date('c')
        ]);
    }
    
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'framework' => 'lumen',
            'version' => app()->version(),
            'timestamp' => date('c'),
            'uptime' => time(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
    }
}