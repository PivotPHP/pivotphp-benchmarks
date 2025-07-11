<?php

use App\Middleware\RateLimitMiddleware;

// Health check
Flight::route('GET /', function() {
    Flight::healthController()->index();
});

// API Health check
Flight::route('GET /api/health', function() {
    Flight::healthController()->health();
});

// Rate limit middleware for users routes
$rateLimiter = new RateLimitMiddleware();

// Users API
Flight::route('GET /api/users', function() use ($rateLimiter) {
    $rateLimiter->handle(function() {
        Flight::userController()->index();
    });
});

Flight::route('GET /api/users/@id:[0-9]+', function($id) use ($rateLimiter) {
    $rateLimiter->handle(function() use ($id) {
        Flight::userController()->show($id);
    });
});

Flight::route('POST /api/users', function() use ($rateLimiter) {
    $rateLimiter->handle(function() {
        Flight::userController()->store();
    });
});

Flight::route('PUT /api/users/@id:[0-9]+', function($id) use ($rateLimiter) {
    $rateLimiter->handle(function() use ($id) {
        Flight::userController()->update($id);
    });
});

Flight::route('DELETE /api/users/@id:[0-9]+', function($id) use ($rateLimiter) {
    $rateLimiter->handle(function() use ($id) {
        Flight::userController()->destroy($id);
    });
});

Flight::route('GET /api/users/@id:[0-9]+/posts', function($id) use ($rateLimiter) {
    $rateLimiter->handle(function() use ($id) {
        Flight::userController()->posts($id);
    });
});