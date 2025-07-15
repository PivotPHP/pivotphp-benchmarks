<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase3\Slim4\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PivotPHP\Benchmark\Phase3\Slim4\SlimApplication;

/**
 * Controller de Performance - Replica endpoints PivotPHP
 */
class PerformanceController
{
    private SlimApplication $app;

    public function __construct(SlimApplication $app)
    {
        $this->app = $app;
    }

    /**
     * Health check otimizado (replica PivotPHP)
     */
    public function healthCheck(Request $request, Response $response): Response
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $data = [
            'status' => 'healthy',
            'framework' => 'slim4',
            'version' => '4.12',
            'timestamp' => microtime(true),
            'uptime' => microtime(true) - $this->app->getStartTime(),
            'requests_served' => $this->app->getRequestCount(),
            'performance_mode' => $this->app->getConfig()['performance_profile'],
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'php_version' => PHP_VERSION
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Ultra fast endpoint (replica PivotPHP)
     */
    public function ultraFast(Request $request, Response $response): Response
    {
        $data = [
            'ok' => true,
            'framework' => 'slim4',
            'request_id' => $this->app->getRequestCount()
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * JSON performance test (replica PivotPHP)
     */
    public function jsonPerformance(Request $request, Response $response, array $args): Response
    {
        $size = $args['size'] ?? 'small';
        $params = $request->getQueryParams();
        $iterations = (int)($params['iterations'] ?? 1000);
        
        $testData = $this->generateTestData($size);
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $encoded = json_encode($testData);
            $decoded = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        
        $data = [
            'test' => 'json_performance_slim4',
            'framework' => 'slim4',
            'size' => $size,
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / (($endTime - $startTime) ?: 0.001)),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * JSON performance POST test
     */
    public function jsonPerformancePost(Request $request, Response $response, array $args): Response
    {
        $size = $args['size'] ?? 'small';
        $body = (string)$request->getBody();
        $receivedData = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = [
                'error' => 'Invalid JSON',
                'message' => json_last_error_msg(),
                'framework' => 'slim4'
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $startTime = microtime(true);
        $reencoded = json_encode($receivedData);
        $endTime = microtime(true);
        
        $data = [
            'received_data' => $receivedData,
            'reencoding_time_ms' => ($endTime - $startTime) * 1000,
            'data_size_bytes' => strlen($body),
            'framework' => 'slim4'
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Throughput test (replica PivotPHP)
     */
    public function throughputTest(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $operations = (int)($params['operations'] ?? 5000);
        
        $startTime = microtime(true);
        $results = [];
        
        for ($i = 0; $i < $operations; $i++) {
            $data = [
                'id' => $i,
                'value' => rand(1, 1000),
                'framework' => 'slim4',
                'processed_at' => $startTime + $i
            ];
            $encoded = json_encode($data);
            $results[] = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        $data = [
            'throughput_test_slim4' => [
                'operations' => $operations,
                'total_time_ms' => $totalTime,
                'operations_per_second' => round($operations / (($endTime - $startTime) ?: 0.001)),
                'avg_time_per_operation_ms' => $totalTime / $operations,
                'results_count' => count($results)
            ],
            'framework' => 'slim4',
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Pool stats (simulated - Slim 4 doesn't have object pooling)
     */
    public function poolStats(Request $request, Response $response): Response
    {
        $data = [
            'framework' => 'slim4',
            'note' => 'Slim 4 does not have object pooling - simulated data',
            'performance_config' => $this->app->getConfig(),
            'internal_metrics' => [
                'total_requests' => $this->app->getRequestCount(),
                'uptime_seconds' => microtime(true) - $this->app->getStartTime(),
                'avg_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ],
            'system' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'php_version' => PHP_VERSION
            ],
            'pooling_simulation' => [
                'object_pool_enabled' => false,
                'request_pool_enabled' => false,
                'json_buffer_pool_enabled' => false,
                'note' => 'Slim 4 uses standard PHP object creation'
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Metadata do framework (replica PivotPHP)
     */
    public function metadata(Request $request, Response $response): Response
    {
        $data = [
            'application' => [
                'name' => 'Slim 4 Benchmark Comparison',
                'framework' => 'slim4',
                'version' => '4.12',
                'performance_profile' => $this->app->getConfig()['performance_profile']
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() && opcache_get_status()['opcache_enabled'],
                'slim_version' => '4.12'
            ],
            'features' => [
                'psr_compliance' => ['PSR-7', 'PSR-15', 'PSR-11'],
                'container' => 'PHP-DI',
                'routing' => 'FastRoute',
                'middleware_pipeline' => true,
                'object_pooling' => false,
                'json_optimization' => false
            ],
            'comparison_notes' => [
                'vs_pivotphp' => 'Direct feature comparison',
                'pooling_advantage' => 'PivotPHP has object pooling, Slim 4 does not',
                'routing_similarity' => 'Both use FastRoute for routing',
                'middleware_similarity' => 'Both support PSR-15 middleware'
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Slim 4 specific features
     */
    public function slim4Features(Request $request, Response $response): Response
    {
        $data = [
            'framework' => 'slim4',
            'specific_features' => [
                'container' => 'PHP-DI dependency injection',
                'routing' => 'nikic/FastRoute integration',
                'middleware' => 'PSR-15 compliant middleware',
                'error_handling' => 'Advanced error middleware',
                'psr_support' => ['PSR-7', 'PSR-11', 'PSR-15']
            ],
            'advantages' => [
                'mature_ecosystem' => 'Large community and packages',
                'documentation' => 'Extensive documentation',
                'stability' => 'Production proven',
                'psr_compliance' => 'Full PSR standard compliance'
            ],
            'vs_pivotphp' => [
                'express_js_api' => false,
                'object_pooling' => false,
                'json_optimization' => false,
                'hybrid_psr7_api' => false
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Container information
     */
    public function containerInfo(Request $request, Response $response): Response
    {
        $container = $this->app->getApp()->getContainer();
        
        $data = [
            'framework' => 'slim4',
            'container' => [
                'type' => 'PHP-DI',
                'available' => $container !== null,
                'services_count' => 'N/A - Dynamic resolution',
                'note' => 'Slim 4 uses PHP-DI for dependency injection'
            ],
            'performance_impact' => [
                'container_resolution' => 'Dynamic - resolved on demand',
                'caching' => 'Compiled container in production',
                'overhead' => 'Minimal with compiled container'
            ]
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Gera dados de teste (idêntico ao PivotPHP)
     */
    private function generateTestData(string $size): array
    {
        switch ($size) {
            case 'small':
                return [
                    'id' => 1,
                    'name' => 'Test Slim4',
                    'value' => 123.45,
                    'framework' => 'slim4'
                ];
                
            case 'medium':
                return [
                    'id' => 1,
                    'framework' => 'slim4',
                    'data' => array_fill(0, 50, [
                        'item' => rand(1, 1000),
                        'value' => microtime(true),
                        'framework' => 'slim4'
                    ]),
                    'metadata' => [
                        'generated_at' => date('c'),
                        'size' => 'medium',
                        'framework' => 'slim4'
                    ]
                ];
                
            case 'large':
                return [
                    'id' => 1,
                    'framework' => 'slim4',
                    'large_dataset' => array_fill(0, 500, [
                        'id' => rand(1, 10000),
                        'data' => str_repeat('x', 100),
                        'timestamp' => microtime(true),
                        'framework' => 'slim4'
                    ]),
                    'metadata' => [
                        'generated_at' => date('c'),
                        'size' => 'large',
                        'framework' => 'slim4'
                    ]
                ];
                
            default:
                return [
                    'error' => 'Invalid size parameter',
                    'framework' => 'slim4'
                ];
        }
    }
}