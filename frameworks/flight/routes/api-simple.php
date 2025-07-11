<?php

// Simplified routes without middleware for HTTP testing

// Health check
Flight::route('GET /', function() {
    Flight::json([
        'status' => 'ok',
        'framework' => 'flight',
        'version' => '3.x',
        'timestamp' => time()
    ]);
});

// API Health check  
Flight::route('GET /api/health', function() {
    Flight::json([
        'status' => 'healthy',
        'framework' => 'flight',
        'version' => '3.x',
        'timestamp' => time(),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true)
    ]);
});

// Simple users endpoint
Flight::route('GET /api/users', function() {
    $users = [];
    for ($i = 1; $i <= 10; $i++) {
        $users[] = [
            'id' => $i,
            'name' => 'User ' . $i,
            'email' => 'user' . $i . '@example.com',
            'active' => $i % 2 === 0
        ];
    }
    
    Flight::json([
        'data' => $users,
        'count' => count($users),
        'framework' => 'flight'
    ]);
});

// User detail
Flight::route('GET /api/users/@id:[0-9]+', function($id) {
    Flight::json([
        'id' => (int)$id,
        'name' => 'User ' . $id,
        'email' => 'user' . $id . '@example.com',
        'active' => true,
        'framework' => 'flight',
        'created_at' => date('Y-m-d H:i:s')
    ]);
});