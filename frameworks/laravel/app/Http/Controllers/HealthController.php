<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'framework' => 'laravel',
            'version' => '10.0',
            'timestamp' => now()->toISOString(),
            'server' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ],
            'laravel' => [
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone')
            ]
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        return response()->json([
            'framework' => 'laravel',
            'version' => '10.0',
            'php_version' => PHP_VERSION,
            'features' => [
                'eloquent_orm',
                'blade_templating',
                'artisan_cli',
                'queues',
                'broadcasting',
                'scheduler',
                'middleware',
                'service_container',
                'facades',
                'events'
            ],
            'capabilities' => [
                'mvc_architecture',
                'dependency_injection',
                'middleware_stack',
                'request_lifecycle',
                'database_migrations',
                'model_relationships',
                'api_resources',
                'form_validation',
                'authentication',
                'authorization'
            ]
        ]);
    }
}