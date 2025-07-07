#!/usr/bin/env php
<?php

/**
 * Authentication Benchmark for PivotPHP Core
 *
 * Tests JWT generation, validation, and authentication flow
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Authentication\JWTHelper;
use PivotPHP\Core\Authentication\Auth;
use PivotPHP\Core\Http\Request;
use Shared\Interfaces\BenchmarkInterface;

class AuthBenchmark implements BenchmarkInterface
{
    private Application $app;
    private JWTHelper $jwtHelper;
    private Auth $auth;
    private string $secretKey = 'benchmark-secret-key-for-testing-only';
    private array $testUser = [
        'id' => 1,
        'username' => 'testuser',
        'email' => 'test@example.com',
        'roles' => ['user', 'admin']
    ];

    public function __construct()
    {
        $this->jwtHelper = new JWTHelper($this->secretKey);
        $this->setup();
    }

    private function setup(): void
    {
        $this->app = new Application();
        
        // Configure authentication
        $this->auth = new Auth([
            'jwt_secret' => $this->secretKey,
            'jwt_lifetime' => 3600,
            'jwt_algorithm' => 'HS256'
        ]);

        // Add auth middleware
        $authMiddleware = new \PivotPHP\Core\Middleware\AuthMiddleware($this->auth);
        
        // Protected routes
        $this->app->group('/api', function($group) {
            $group->get('/profile', function($request, $response) {
                $user = $request->getAttribute('user');
                return $response->json($user);
            });
            
            $group->get('/admin', function($request, $response) {
                return $response->json(['message' => 'Admin access granted']);
            });
        })->addMiddleware($authMiddleware);

        // Public routes
        $this->app->post('/login', function($request, $response) {
            // Simulate authentication
            $token = $this->jwtHelper->generate($this->testUser);
            return $response->json(['token' => $token]);
        });
    }

    public function getName(): string
    {
        return 'Authentication & JWT';
    }

    public function getDescription(): string
    {
        return 'Benchmarks JWT generation, validation, and authentication middleware';
    }

    public function warmup(): void
    {
        // Generate and validate a token
        $token = $this->jwtHelper->generate($this->testUser);
        $this->jwtHelper->validate($token);
        
        // Make authenticated request
        $request = new Request('GET', '/api/profile', '/api/profile');
        $request = $request->withHeader('Authorization', 'Bearer ' . $token);
        $this->app->handle($request);
    }

    public function run(int $iterations = 10000): array
    {
        $results = [
            'jwt_generation' => $this->benchmarkJwtGeneration($iterations),
            'jwt_validation' => $this->benchmarkJwtValidation($iterations),
            'auth_middleware' => $this->benchmarkAuthMiddleware($iterations),
            'full_auth_flow' => $this->benchmarkFullAuthFlow($iterations)
        ];

        $totalTime = array_sum(array_column($results, 'total_time'));
        $avgTime = array_sum(array_column($results, 'average_time')) / count($results);

        return [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'average_time' => $avgTime,
            'operations_per_second' => $iterations / $totalTime,
            'details' => $results
        ];
    }

    private function benchmarkJwtGeneration(int $iterations): array
    {
        $start = microtime(true);
        $memStart = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $payload = [
                'id' => $i,
                'username' => 'user' . $i,
                'email' => 'user' . $i . '@example.com',
                'roles' => ['user'],
                'exp' => time() + 3600
            ];
            
            $this->jwtHelper->generate($payload);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'JWT Generation',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }

    private function benchmarkJwtValidation(int $iterations): array
    {
        // Pre-generate tokens for validation
        $tokens = [];
        for ($i = 0; $i < min(100, $iterations); $i++) {
            $tokens[] = $this->jwtHelper->generate([
                'id' => $i,
                'username' => 'user' . $i,
                'exp' => time() + 3600
            ]);
        }

        $start = microtime(true);
        $memStart = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $token = $tokens[$i % count($tokens)];
            $this->jwtHelper->validate($token);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'JWT Validation',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }

    private function benchmarkAuthMiddleware(int $iterations): array
    {
        // Generate a valid token
        $token = $this->jwtHelper->generate($this->testUser);

        $start = microtime(true);
        $memStart = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request('GET', '/api/profile', '/api/profile');
            $request = $request->withHeader('Authorization', 'Bearer ' . $token);

            $this->app->handle($request);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'Auth Middleware Processing',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }

    private function benchmarkFullAuthFlow(int $iterations): array
    {
        $start = microtime(true);
        $memStart = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            // 1. Login request
            $loginRequest = new Request('POST', '/login', '/login');
            $loginRequest = $loginRequest->withParsedBody([
                    'username' => 'testuser',
                    'password' => 'password'
                ]);

            $loginResponse = $this->app->handle($loginRequest);
            $body = json_decode((string)$loginResponse->getBody(), true);
            $token = $body['token'] ?? '';

            // 2. Authenticated request
            $authRequest = new Request('GET', '/api/profile', '/api/profile');
            $authRequest = $authRequest->withHeader('Authorization', 'Bearer ' . $token);

            $this->app->handle($authRequest);
        }

        $totalTime = microtime(true) - $start;
        $memUsed = memory_get_usage() - $memStart;

        return [
            'name' => 'Full Authentication Flow',
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'ops_per_second' => $iterations / $totalTime,
            'memory_used' => $memUsed
        ];
    }
}

// Run the benchmark
if (php_sapi_name() === 'cli') {
    $benchmark = new AuthBenchmark();
    $results = $benchmark->run(10000);
    
    echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
}