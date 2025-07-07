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
}