<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase3\Slim4\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PivotPHP\Benchmark\Phase3\Slim4\SlimApplication;

/**
 * Middleware Controller - Testa performance do pipeline de middleware
 */
class MiddlewareController
{
    private SlimApplication $app;

    public function __construct(SlimApplication $app)
    {
        $this->app = $app;
    }

    /**
     * Teste do stack de middleware
     */
    public function stackTest(Request $request, Response $response): Response
    {
        $startTime = microtime(true);
        
        // Simular processamento de múltiplas camadas de middleware
        $middlewareLayers = [
            'cors' => $this->simulateMiddleware('CORS', 0.1),
            'authentication' => $this->simulateMiddleware('Authentication', 0.2),
            'authorization' => $this->simulateMiddleware('Authorization', 0.15),
            'rate_limiting' => $this->simulateMiddleware('Rate Limiting', 0.05),
            'request_validation' => $this->simulateMiddleware('Request Validation', 0.1),
            'response_transform' => $this->simulateMiddleware('Response Transform', 0.08)
        ];
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        $data = [
            'middleware_stack_test' => [
                'framework' => 'slim4',
                'layers_processed' => count($middlewareLayers),
                'total_time_ms' => $totalTime,
                'individual_layers' => $middlewareLayers,
                'average_layer_time_ms' => $totalTime / count($middlewareLayers)
            ],
            'slim4_middleware_info' => [
                'type' => 'PSR-15 compliant',
                'execution_order' => 'LIFO (Last In, First Out)',
                'double_pass' => false,
                'container_aware' => true
            ],
            'performance' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'overhead_per_layer_ms' => round($totalTime / count($middlewareLayers), 3)
            ],
            'vs_pivotphp' => [
                'middleware_type' => 'Both use PSR-15',
                'performance_note' => 'Similar middleware architecture',
                'framework_overhead' => 'Minimal difference expected'
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Teste específico de CORS
     */
    public function corsTest(Request $request, Response $response): Response
    {
        $startTime = microtime(true);
        
        // Simular processamento CORS
        $corsHeaders = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '3600'
        ];
        
        // Simular validação de origem
        $origin = $request->getHeaderLine('Origin');
        $isValidOrigin = true; // Simulado
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        
        $data = [
            'cors_test' => [
                'framework' => 'slim4',
                'origin_validated' => $isValidOrigin,
                'headers_set' => $corsHeaders,
                'processing_time_ms' => $processingTime,
                'preflight_handled' => $request->getMethod() === 'OPTIONS'
            ],
            'performance' => [
                'cors_overhead_ms' => $processingTime,
                'memory_impact_mb' => round((memory_get_usage(true) - memory_get_usage()) / 1024 / 1024, 4)
            ],
            'comparison' => [
                'vs_pivotphp' => 'Similar CORS implementation',
                'overhead_expected' => 'Minimal - mostly header manipulation'
            ]
        ];

        // Aplicar headers CORS na resposta
        foreach ($corsHeaders as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Simulação de autenticação
     */
    public function authTest(Request $request, Response $response): Response
    {
        $startTime = microtime(true);
        
        // Simular validação de token
        $authHeader = $request->getHeaderLine('Authorization');
        $hasToken = !empty($authHeader);
        
        // Simular processos de autenticação
        $authProcesses = [
            'token_extraction' => $this->simulateAuth('Token extraction', 0.05),
            'token_validation' => $this->simulateAuth('Token validation', 0.3),
            'user_lookup' => $this->simulateAuth('User lookup', 0.2),
            'permission_check' => $this->simulateAuth('Permission check', 0.1)
        ];
        
        $isAuthenticated = $hasToken; // Simular sucesso se token presente
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        $data = [
            'auth_test' => [
                'framework' => 'slim4',
                'has_auth_header' => $hasToken,
                'is_authenticated' => $isAuthenticated,
                'auth_processes' => $authProcesses,
                'total_auth_time_ms' => $totalTime
            ],
            'performance' => [
                'auth_overhead_ms' => $totalTime,
                'processes_count' => count($authProcesses),
                'avg_process_time_ms' => $totalTime / count($authProcesses),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ],
            'slim4_auth_features' => [
                'middleware_based' => true,
                'container_integration' => true,
                'jwt_support' => 'Via packages',
                'session_support' => 'Via packages'
            ],
            'vs_pivotphp' => [
                'auth_architecture' => 'Both middleware-based',
                'performance_impact' => 'Similar overhead expected',
                'integration' => 'PivotPHP has built-in auth, Slim needs packages'
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Simula processamento de middleware
     */
    private function simulateMiddleware(string $name, float $duration): array
    {
        $startTime = microtime(true);
        
        // Simular algum processamento
        usleep((int)($duration * 1000)); // Simular delay
        
        $endTime = microtime(true);
        $actualDuration = ($endTime - $startTime) * 1000;
        
        return [
            'name' => $name,
            'simulated_duration_ms' => $duration,
            'actual_duration_ms' => $actualDuration,
            'status' => 'processed'
        ];
    }

    /**
     * Simula processo de autenticação
     */
    private function simulateAuth(string $process, float $duration): array
    {
        $startTime = microtime(true);
        
        // Simular processamento de auth
        usleep((int)($duration * 1000));
        
        $endTime = microtime(true);
        $actualDuration = ($endTime - $startTime) * 1000;
        
        return [
            'process' => $process,
            'duration_ms' => $actualDuration,
            'success' => true
        ];
    }
}