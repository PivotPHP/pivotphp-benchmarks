<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\HighPerformance;

use PivotPHP\Core\Core\Application as PivotApplication;

/**
 * Aplicação PivotPHP simplificada para alta performance
 *
 * Versão simplificada que funciona com qualquer versão do PivotPHP
 * e implementa otimizações baseadas na documentação oficial
 */
class SimpleApplication
{
    private PivotApplication $app;
    private array $config;
    private array $performanceMetrics = [];
    private float $startTime;

    public function __construct(array $config = [])
    {
        $this->startTime = microtime(true);
        $this->config = array_merge([
            'performance_profile' => 'HIGH',
            'minimal_middleware' => true,
            'memory_optimization' => true
        ], $config);

        $this->optimizePHPSettings();
        $this->initializeApplication();
        $this->registerOptimizedRoutes();
    }

    /**
     * Otimiza configurações PHP para máxima performance
     */
    private function optimizePHPSettings(): void
    {
        // Memory management otimizado
        ini_set('memory_limit', '512M');

        // Timezone para evitar system calls
        date_default_timezone_set('UTC');

        // Garbage collection otimizado
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }

    /**
     * Inicializa aplicação PivotPHP
     */
    private function initializeApplication(): void
    {
        $this->app = new PivotApplication();
        $this->app->boot();

        if ($this->config['minimal_middleware']) {
            $this->configureMinimalMiddleware();
        }
    }

    /**
     * Configura middleware mínimo para máxima performance
     */
    private function configureMinimalMiddleware(): void
    {
        // Performance monitoring middleware (lightweight)
        $this->app->use(function ($request, $response, $next) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            $response = $next($request, $response);

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $processingTime = ($endTime - $startTime) * 1000; // ms
            $memoryUsed = $endMemory - $startMemory;

            // Headers para debugging
            $response = $response->withHeader('X-Processing-Time-Ms', number_format($processingTime, 3));
            $response = $response->withHeader('X-Memory-Used', $memoryUsed);
            $response = $response->withHeader('X-Performance-Mode', $this->config['performance_profile']);

            // Coletar métricas
            $this->performanceMetrics[] = [
                'processing_time_ms' => $processingTime,
                'memory_used' => $memoryUsed,
                'timestamp' => $endTime
            ];

            return $response;
        });

        // CORS mínimo
        $this->app->use(function ($request, $response, $next) {
            $response = $next($request, $response);

            return $response->withHeader('Access-Control-Allow-Origin', '*')
                          ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                          ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        });
    }

    /**
     * Registra rotas otimizadas
     */
    private function registerOptimizedRoutes(): void
    {
        // Health check otimizado
        $this->app->get('/health', fn($request, $response) => $this->healthCheck($request, $response));

        // Metadata
        $this->app->get('/meta', fn($request, $response) => $this->getMetadata($request, $response));

        // Performance tests
        $this->app->get('/performance/json/:size', fn($request, $response) => $this->jsonPerformanceTest($request, $response));
        $this->app->post('/performance/json/:size', fn($request, $response) => $this->jsonPerformanceTestPost($request, $response));
        $this->app->get('/performance/throughput', fn($request, $response) => $this->throughputTest($request, $response));
        $this->app->get('/performance/pool-stats', fn($request, $response) => $this->getPoolStats($request, $response));

        // Ultra fast endpoint
        $this->app->get('/ultra-fast', fn($request, $response) => $this->ultraFastEndpoint($request, $response));

        // Metrics
        $this->app->get('/metrics', fn($request, $response) => $this->getMetrics($request, $response));
    }

    /**
     * Health check otimizado
     */
    public function healthCheck($request, $response)
    {
        return $response->json([
            'status' => 'healthy',
            'timestamp' => microtime(true),
            'uptime' => microtime(true) - $this->startTime,
            'performance_mode' => $this->config['performance_profile'],
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'php_version' => PHP_VERSION
        ]);
    }

    /**
     * Teste de performance JSON
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
            'test' => 'json_performance',
            'size' => $size,
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / (($endTime - $startTime) ?: 0.001)),
            'performance_mode' => $this->config['performance_profile'],
            'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024
        ]);
    }

    /**
     * Teste POST
     */
    public function jsonPerformanceTestPost($request, $response)
    {
        $size = $request->param('size');
        $body = $request->getBody()->getContents();
        $receivedData = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $response->status(400)->json([
                'error' => 'Invalid JSON',
                'message' => json_last_error_msg()
            ]);
        }

        $startTime = microtime(true);
        $reencoded = json_encode($receivedData);
        $endTime = microtime(true);

        return $response->json([
            'received_data' => $receivedData,
            'reencoding_time_ms' => ($endTime - $startTime) * 1000,
            'data_size_bytes' => strlen($body),
            'performance_mode' => $this->config['performance_profile']
        ]);
    }

    /**
     * Endpoint ultra-rápido
     */
    public function ultraFastEndpoint($request, $response)
    {
        return $response->json(['ok' => true]);
    }

    /**
     * Teste de throughput
     */
    public function throughputTest($request, $response)
    {
        $operations = (int)($request->query('operations') ?? 5000);

        $startTime = microtime(true);
        $results = [];

        for ($i = 0; $i < $operations; $i++) {
            $data = ['id' => $i, 'value' => rand(1, 1000), 'processed_at' => $startTime + $i];
            $encoded = json_encode($data);
            $results[] = json_decode($encoded, true);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        return $response->json([
            'throughput_test' => [
                'operations' => $operations,
                'total_time_ms' => $totalTime,
                'operations_per_second' => round($operations / (($endTime - $startTime) ?: 0.001)),
                'avg_time_per_operation_ms' => $totalTime / $operations,
                'results_count' => count($results)
            ],
            'performance_mode' => $this->config['performance_profile'],
            'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024
        ]);
    }

    /**
     * Pool stats (simulado)
     */
    public function getPoolStats($request, $response)
    {
        return $response->json([
            'performance_mode' => $this->config,
            'internal_metrics' => [
                'total_requests' => count($this->performanceMetrics),
                'avg_processing_time_ms' => !empty($this->performanceMetrics) ?
                    array_sum(array_column($this->performanceMetrics, 'processing_time_ms')) / count($this->performanceMetrics) : 0,
                'uptime' => microtime(true) - $this->startTime
            ],
            'system' => [
                'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
                'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024
            ]
        ]);
    }

    /**
     * Métricas
     */
    public function getMetrics($request, $response)
    {
        return $response->json([
            'performance_metrics' => $this->performanceMetrics,
            'system_metrics' => [
                'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
                'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024,
                'uptime' => microtime(true) - $this->startTime,
                'php_version' => PHP_VERSION
            ],
            'performance_config' => $this->config
        ]);
    }

    /**
     * Metadata
     */
    public function getMetadata($request, $response)
    {
        return $response->json([
            'application' => [
                'name' => 'PivotPHP High Performance Benchmark (Simple)',
                'version' => '1.0.0',
                'performance_profile' => $this->config['performance_profile']
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() && opcache_get_status()['opcache_enabled'],
                'framework_version' => PivotApplication::VERSION ?? 'unknown'
            ],
            'optimizations' => [
                'minimal_middleware' => $this->config['minimal_middleware'],
                'memory_optimization' => $this->config['memory_optimization']
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
                return ['id' => 1, 'name' => 'Test', 'value' => 123.45];

            case 'medium':
                return [
                    'id' => 1,
                    'data' => array_fill(0, 50, ['item' => rand(1, 1000), 'value' => microtime(true)]),
                    'metadata' => ['generated_at' => date('c'), 'size' => 'medium']
                ];

            case 'large':
                return [
                    'id' => 1,
                    'large_dataset' => array_fill(0, 500, [
                        'id' => rand(1, 10000),
                        'data' => str_repeat('x', 100),
                        'timestamp' => microtime(true)
                    ]),
                    'metadata' => ['generated_at' => date('c'), 'size' => 'large']
                ];

            default:
                return ['error' => 'Invalid size parameter'];
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
