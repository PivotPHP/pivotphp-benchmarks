<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\HighPerformance;

use PivotPHP\Core\Core\Application as PivotApplication;

/**
 * Aplicação otimizada para PivotPHP v1.1.2 (Consolidation Edition)
 * 
 * Implementa as melhorias de arquitetura da versão 1.1.2
 * com configurações específicas para máxima performance
 */
class OptimizedApplication
{
    private PivotApplication $app;
    private array $config;
    private array $performanceMetrics = [];
    private float $startTime;
    private int $requestCount = 0;

    public function __construct(array $config = [])
    {
        $this->startTime = microtime(true);
        $this->config = array_merge([
            'performance_profile' => 'EXTREME',
            'consolidation_mode' => true,
            'memory_optimization' => true,
            'enhanced_pooling' => true
        ], $config);

        $this->optimizeForV112();
        $this->initializeApplication();
        $this->registerOptimizedRoutes();
    }

    /**
     * Otimizações específicas para v1.1.2
     */
    private function optimizeForV112(): void
    {
        // Performance settings
        ini_set('memory_limit', '1G');
        ini_set('opcache.enable', '1');
        
        // Timezone otimizado
        date_default_timezone_set('UTC');

        // Garbage collection para v1.1.2
        if (function_exists('gc_enable')) {
            gc_enable();
            gc_collect_cycles();
        }

        // Buffer optimization
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
    }

    /**
     * Inicialização otimizada para v1.1.2
     */
    private function initializeApplication(): void
    {
        $this->app = new PivotApplication();
        
        // Boot com configurações otimizadas
        $this->app->boot();
        
        // Middleware mínimo para máxima performance
        $this->configureOptimizedMiddleware();
    }

    /**
     * Middleware stack otimizado para v1.1.2
     */
    private function configureOptimizedMiddleware(): void
    {
        // Performance monitoring ultra-leve
        $this->app->use(function ($request, $response, $next) {
            $startTime = microtime(true);
            $this->requestCount++;
            
            $response = $next($request, $response);
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            // Headers mínimos para debugging
            $response = $response->withHeader('X-Processing-Ms', number_format($processingTime, 3));
            $response = $response->withHeader('X-Request-Count', (string)$this->requestCount);
            $response = $response->withHeader('X-PivotPHP-Version', '1.1.2');
            $response = $response->withHeader('X-Profile', $this->config['performance_profile']);
            
            return $response;
        });

        // CORS otimizado
        $this->app->use(function ($request, $response, $next) {
            $response = $next($request, $response);
            
            return $response->withHeader('Access-Control-Allow-Origin', '*')
                          ->withHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS')
                          ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
        });
    }

    /**
     * Rotas otimizadas para v1.1.2
     */
    private function registerOptimizedRoutes(): void
    {
        // Health check ultra-rápido
        $this->app->get('/health', fn($request, $response) => $this->healthCheck($request, $response));
        
        // Metadata com informações v1.1.2
        $this->app->get('/meta', fn($request, $response) => $this->getMetadata($request, $response));
        
        // Performance endpoints
        $this->app->get('/ultra-fast', fn($request, $response) => $this->ultraFastEndpoint($request, $response));
        $this->app->get('/performance/pool-stats', fn($request, $response) => $this->getPoolStats($request, $response));
        $this->app->get('/performance/throughput', fn($request, $response) => $this->throughputTest($request, $response));
        
        // JSON performance tests
        $this->app->get('/performance/json/{size}', fn($request, $response) => $this->jsonPerformanceTest($request, $response));
        $this->app->post('/performance/json/{size}', fn($request, $response) => $this->jsonPerformanceTestPost($request, $response));
        
        // v1.1.2 specific endpoints
        $this->app->get('/v112/consolidation', fn($request, $response) => $this->consolidationTest($request, $response));
        $this->app->get('/v112/architecture', fn($request, $response) => $this->architectureInfo($request, $response));
    }

    /**
     * Health check otimizado
     */
    public function healthCheck($request, $response)
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return $response->json([
            'status' => 'healthy',
            'version' => '1.1.2',
            'edition' => 'Consolidation Edition',
            'timestamp' => microtime(true),
            'uptime' => microtime(true) - $this->startTime,
            'requests_served' => $this->requestCount,
            'performance_mode' => $this->config['performance_profile'],
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'php_version' => PHP_VERSION
        ]);
    }

    /**
     * Endpoint ultra-rápido
     */
    public function ultraFastEndpoint($request, $response)
    {
        return $response->json([
            'ok' => true,
            'version' => '1.1.2',
            'request_id' => $this->requestCount
        ]);
    }

    /**
     * Teste específico das melhorias v1.1.2
     */
    public function consolidationTest($request, $response)
    {
        $startTime = microtime(true);
        
        // Simular operações consolidadas
        $operations = (int)($request->query('operations') ?? 1000);
        $results = [];
        
        for ($i = 0; $i < $operations; $i++) {
            $data = [
                'id' => $i,
                'timestamp' => microtime(true),
                'version' => '1.1.2',
                'consolidation' => true
            ];
            $results[] = $data;
        }
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        
        return $response->json([
            'consolidation_test' => [
                'version' => '1.1.2',
                'operations' => $operations,
                'processing_time_ms' => $processingTime,
                'ops_per_second' => round($operations / (($endTime - $startTime) ?: 0.001)),
                'results_count' => count($results)
            ],
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    }

    /**
     * Informações da arquitetura v1.1.2
     */
    public function architectureInfo($request, $response)
    {
        return $response->json([
            'version' => '1.1.2',
            'edition' => 'Consolidation Edition',
            'improvements' => [
                'architectural_consolidation' => true,
                'enhanced_performance' => true,
                'optimized_memory_management' => true,
                'improved_extensibility' => true
            ],
            'features' => [
                'psr_compliance' => ['PSR-7', 'PSR-15', 'PSR-12', 'PSR-14'],
                'express_js_api' => true,
                'object_pooling' => true,
                'json_optimization' => true,
                'middleware_pipeline' => true
            ],
            'performance_config' => $this->config
        ]);
    }

    /**
     * Performance test JSON
     */
    public function jsonPerformanceTest($request, $response)
    {
        $size = $request->param('size');
        $iterations = (int)($request->query('iterations') ?? 1000);
        
        $testData = $this->generateTestData($size);
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $encoded = json_encode($testData);
            $decoded = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $processingTime = ($endTime - $startTime) * 1000;
        
        return $response->json([
            'test' => 'json_performance_v112',
            'size' => $size,
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / (($endTime - $startTime) ?: 0.001)),
            'version' => '1.1.2',
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    }

    /**
     * POST test
     */
    public function jsonPerformanceTestPost($request, $response)
    {
        $size = $request->param('size');
        $body = $request->getBody()->getContents();
        $receivedData = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $response->status(400)->json([
                'error' => 'Invalid JSON',
                'message' => json_last_error_msg(),
                'version' => '1.1.2'
            ]);
        }
        
        $startTime = microtime(true);
        $reencoded = json_encode($receivedData);
        $endTime = microtime(true);
        
        return $response->json([
            'received_data' => $receivedData,
            'reencoding_time_ms' => ($endTime - $startTime) * 1000,
            'data_size_bytes' => strlen($body),
            'version' => '1.1.2'
        ]);
    }

    /**
     * Throughput test
     */
    public function throughputTest($request, $response)
    {
        $operations = (int)($request->query('operations') ?? 5000);
        
        $startTime = microtime(true);
        $results = [];
        
        for ($i = 0; $i < $operations; $i++) {
            $data = [
                'id' => $i,
                'value' => rand(1, 1000),
                'version' => '1.1.2',
                'processed_at' => $startTime + $i
            ];
            $encoded = json_encode($data);
            $results[] = json_decode($encoded, true);
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        return $response->json([
            'throughput_test_v112' => [
                'operations' => $operations,
                'total_time_ms' => $totalTime,
                'operations_per_second' => round($operations / (($endTime - $startTime) ?: 0.001)),
                'avg_time_per_operation_ms' => $totalTime / $operations,
                'results_count' => count($results)
            ],
            'version' => '1.1.2',
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    }

    /**
     * Pool stats
     */
    public function getPoolStats($request, $response)
    {
        return $response->json([
            'version' => '1.1.2',
            'performance_config' => $this->config,
            'internal_metrics' => [
                'total_requests' => $this->requestCount,
                'uptime_seconds' => microtime(true) - $this->startTime,
                'avg_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ],
            'system' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'php_version' => PHP_VERSION
            ]
        ]);
    }

    /**
     * Metadata
     */
    public function getMetadata($request, $response)
    {
        return $response->json([
            'application' => [
                'name' => 'PivotPHP v1.1.2 Optimized Benchmark',
                'version' => '1.1.2',
                'edition' => 'Consolidation Edition',
                'performance_profile' => $this->config['performance_profile']
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() && opcache_get_status()['opcache_enabled'],
                'framework_version' => '1.1.2'
            ],
            'optimizations' => [
                'consolidation_mode' => $this->config['consolidation_mode'],
                'memory_optimization' => $this->config['memory_optimization'],
                'enhanced_pooling' => $this->config['enhanced_pooling']
            ],
            'improvements' => [
                'architectural_consolidation',
                'enhanced_performance',
                'optimized_memory_management',
                'improved_extensibility'
            ]
        ]);
    }

    /**
     * Gera dados de teste
     */
    private function generateTestData(string $size): array
    {
        switch ($size) {
            case 'small':
                return [
                    'id' => 1,
                    'name' => 'Test v1.1.2',
                    'value' => 123.45,
                    'version' => '1.1.2'
                ];
                
            case 'medium':
                return [
                    'id' => 1,
                    'version' => '1.1.2',
                    'data' => array_fill(0, 50, [
                        'item' => rand(1, 1000),
                        'value' => microtime(true),
                        'version' => '1.1.2'
                    ]),
                    'metadata' => [
                        'generated_at' => date('c'),
                        'size' => 'medium',
                        'version' => '1.1.2'
                    ]
                ];
                
            case 'large':
                return [
                    'id' => 1,
                    'version' => '1.1.2',
                    'large_dataset' => array_fill(0, 500, [
                        'id' => rand(1, 10000),
                        'data' => str_repeat('x', 100),
                        'timestamp' => microtime(true),
                        'version' => '1.1.2'
                    ]),
                    'metadata' => [
                        'generated_at' => date('c'),
                        'size' => 'large',
                        'version' => '1.1.2'
                    ]
                ];
                
            default:
                return [
                    'error' => 'Invalid size parameter',
                    'version' => '1.1.2'
                ];
        }
    }

    /**
     * Executa a aplicação
     */
    public function run(): void
    {
        $this->app->run();
    }
}