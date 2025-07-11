<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check routes
Route::get('/', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);
Route::get('/info', [HealthController::class, 'info']);

// API v1 routes
Route::prefix('api/v1')->group(function () {
    // User resource routes (REST API)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Additional user endpoints
    Route::get('/users/{id}/posts', function (Request $request, $id) {
        return response()->json([
            'user_id' => $id,
            'posts' => [],
            'framework' => 'laravel'
        ]);
    });

    // Benchmark-specific routes
    Route::get('/benchmark/routing/{param1}/{param2}', function (Request $request, $param1, $param2) {
        return response()->json([
            'benchmark' => 'routing',
            'params' => [$param1, $param2],
            'framework' => 'laravel'
        ]);
    });

    Route::post('/benchmark/json', function (Request $request) {
        return response()->json([
            'benchmark' => 'json-processing',
            'received' => $request->all(),
            'framework' => 'laravel'
        ]);
    });

    Route::get('/benchmark/middleware', function (Request $request) {
        return response()->json([
            'benchmark' => 'middleware',
            'middleware_count' => 2, // CORS + RateLimit
            'framework' => 'laravel'
        ]);
    });
});

// API v2 routes (for versioning tests)
Route::prefix('api/v2')->group(function () {
    Route::get('/users', function (Request $request) {
        return response()->json([
            'version' => 'v2',
            'message' => 'API v2 endpoint',
            'framework' => 'laravel'
        ]);
    });
});

// Catch-all route for testing
Route::get('/test/{path}', function (Request $request, $path) {
    return response()->json([
        'test_route' => true,
        'path' => $path,
        'framework' => 'laravel',
        'version' => '10.0',
        'eloquent_orm' => true,
        'middleware_stack' => true
    ]);
})->where('path', '.*');