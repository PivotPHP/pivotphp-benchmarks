<?php
http_response_code(200);
echo json_encode([
    'status' => 'healthy',
    'service' => 'pivotphp-full-ecosystem',
    'components' => [
        'core' => 'active',
        'reactphp' => 'active',
        'orm' => 'active',
        'database' => 'connected'
    ],
    'timestamp' => time()
]);