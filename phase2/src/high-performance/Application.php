<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\HighPerformance;

use PivotPHP\Core\Core\Application as PivotApplication;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Aplicação PivotPHP configurada para máxima performance
 * 
 * Baseado nas melhores práticas encontradas na documentação oficial:
 * - Modo EXTREME performance habilitado
 * - JSON Buffer Pooling otimizado  
 * - Object pooling para Request/Response
 * - Configurações de OPcache/JIT otimizadas
 * - Middleware mínimo para reduzir overhead
 */
class Application
{
    private PivotApplication $app;
    private array $config;
    private array $performanceMetrics = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'performance_profile' => 'EXTREME',
            'json_pool_enabled' => true,
            'object_pool_enabled' => true,
            'response_caching' => true,
            'minimal_middleware' => true,
            'memory_optimization' => true
        ], $config);

        $this->initializeHighPerformanceMode();
        $this->initializeApplication();
        $this->registerOptimizedRoutes();
    }

    /**
     * Inicializa modo de alta performance EXTREME
     */
    private function initializeHighPerformanceMode(): void
    {
        // Ativar modo EXTREME conforme documentação
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
        
        // Configurar JSON Buffer Pool para máxima performance
        if ($this->config['json_pool_enabled'] && class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
            JsonBufferPool::configureForHighThroughput([
                'initial_buffer_size' => 8192,
                'max_buffer_size' => 1048576, // 1MB
                'pool_size' => 100,
                'auto_optimize' => true
            ]);
        }

        // Configurações PHP otimizadas para benchmarks
        $this->optimizePHPSettings();
    }

    /**
     * Otimiza configurações PHP para máxima performance
     */
    private function optimizePHPSettings(): void
    {
        // Memory management otimizado
        ini_set('memory_limit', '512M');
        
        // OPcache verification (já deve estar configurado via docker/php.ini)
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if (!$opcache['opcache_enabled']) {
                error_log('WARNING: OPcache not enabled - performance will be significantly reduced');
            }
            if (!($opcache['jit']['enabled'] ?? false)) {
                error_log('WARNING: JIT not enabled - performance will be reduced');
            }
        }

        // Garbage collection otimizado para long-running processes
        gc_enable();
        if (function_exists('gc_mem_caches')) {
            gc_mem_caches(); // PHP 7.0+
        }

        // Timezone para evitar system calls
        date_default_timezone_set('UTC');
    }

    /**
     * Inicializa aplicação PivotPHP
     */
    private function initializeApplication(): void
    {
        $this->app = new PivotApplication();
        
        // Boot otimizado
        $this->app->boot();
        
        // Configurar middleware mínimo para reduzir overhead
        if ($this->config['minimal_middleware']) {
            $this->configureMinimalMiddleware();
        }
    }

    /**
     * Configura middleware mínimo para máxima performance
     */
    private function configureMinimalMiddleware(): void
    {
        // Apenas middleware essencial para benchmarks
        
        // Performance monitoring middleware (lightweight)
        $this->app->use(function ($request, $response, $next) {
            $startTime = hrtime(true); // Mais preciso que microtime
            $startMemory = memory_get_usage(true);
            
            $response = $next($request, $response);
            
            $endTime = hrtime(true);
            $endMemory = memory_get_usage(true);
            
            $processingTimeNs = $endTime - $startTime;
            $processingTimeMs = $processingTimeNs / 1000000; // Convert to milliseconds
            $memoryUsed = $endMemory - $startMemory;
            
            // Headers mínimos para debugging
            $response = $response->withHeader('X-Processing-Time-Ms', number_format($processingTimeMs, 3));
            $response = $response->withHeader('X-Memory-Used', $memoryUsed);
            $response = $response->withHeader('X-Performance-Mode', 'EXTREME');
            
            // Coletar métricas internas
            $this->performanceMetrics[] = [
                'processing_time_ms' => $processingTimeMs,
                'memory_used' => $memoryUsed,
                'timestamp' => microtime(true)
            ];
            
            return $response;
        });

        // CORS mínimo para benchmarks
        $this->app->use(function ($request, $response, $next) {
            $response = $next($request, $response);
            
            return $response->withHeader('Access-Control-Allow-Origin', '*')
                          ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                          ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        });
    }

    /**
     * Registra rotas otimizadas para benchmarks
     */
    private function registerOptimizedRoutes(): void
    {
        // Health check otimizado
        $this->app->get('/health', fn($request, $response) => $this->optimizedHealthCheck($request, $response));
        
        // JSON performance tests
        $this->app->get('/performance/json/{size}', fn($request, $response) => $this->jsonPerformanceTest($request, $response));
        $this->app->post('/performance/json/{size}', fn($request, $response) => $this->jsonPerformanceTestPost($request, $response));
        
        // Memory tests
        $this->app->get('/performance/memory/{scenario}', fn($request, $response) => $this->memoryPerformanceTest($request, $response));
        
        // CPU intensive tests
        $this->app->get('/performance/cpu/{operations}', fn($request, $response) => $this->cpuPerformanceTest($request, $response));
        
        // Throughput tests
        $this->app->get('/performance/throughput', fn($request, $response) => $this->throughputTest($request, $response));
        
        // Pool efficiency tests
        $this->app->get('/performance/pool-stats', fn($request, $response) => $this->poolStatsTest($request, $response));
        
        // Ultimate performance endpoint (minimal overhead)
        $this->app->get('/ultra-fast', fn($request, $response) => $this->ultraFastEndpoint($request, $response));
        
        // Metrics collection
        $this->app->get('/metrics', fn($request, $response) => $this->getMetrics($request, $response));
        
        // Performance metadata
        $this->app->get('/meta', fn($request, $response) => $this->getPerformanceMetadata($request, $response));
    }

    /**
     * Health check otimizado para máxima velocidade
     */
    public function optimizedHealthCheck($request, $response)
    {
        // Usar JSON pooling se disponível
        $data = [
            'status' => 'healthy',
            'timestamp' => microtime(true),
            'performance_mode' => 'EXTREME',
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        if (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
            return $response->json($data, 200, [], JSON_UNESCAPED_UNICODE, true); // Use pooling
        }
        
        return $response->json($data);
    }

    /**
     * Teste de performance JSON com pooling
     */
    public function jsonPerformanceTest($request, $response)
    {
        $size = $request->param('size');
        $iterations = (int)($request->query('iterations') ?? 1000);
        
        $testData = $this->generateTestData($size);
        
        $startTime = hrtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            if (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
                $encoded = JsonBufferPool::encodeWithPool($testData);
            } else {
                $encoded = json_encode($testData);
            }
            $decoded = json_decode($encoded, true);
        }
        
        $endTime = hrtime(true);
        $processingTime = ($endTime - $startTime) / 1000000; // Convert to ms
        
        $result = [
            'test' => 'json_performance',
            'size' => $size,
            'iterations' => $iterations,
            'processing_time_ms' => $processingTime,
            'operations_per_second' => round($iterations / ($processingTime / 1000)),
            'json_pooling_used' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool'),
            'performance_mode' => HighPerformanceMode::getStatus()
        ];
        
        return $response->json($result);
    }

    /**
     * Teste POST com validação de integridade
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
        
        // Re-encode using pooling for performance test
        $startTime = hrtime(true);
        
        if (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
            $reencoded = JsonBufferPool::encodeWithPool($receivedData);
        } else {
            $reencoded = json_encode($receivedData);
        }
        
        $endTime = hrtime(true);
        $processingTime = ($endTime - $startTime) / 1000000;
        
        return $response->json([
            'received_data' => $receivedData,
            'reencoding_time_ms' => $processingTime,
            'data_size_bytes' => strlen($body),
            'performance_mode' => 'EXTREME',
            'json_pooling_used' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')
        ]);
    }

    /**
     * Endpoint ultra-rápido com overhead mínimo
     */
    public function ultraFastEndpoint($request, $response)
    {
        // Minimal response para testar overhead puro do framework
        return $response->json(['ok' => true]);
    }

    /**
     * Teste de throughput com múltiplas operações
     */
    public function throughputTest($request, $response)
    {
        $operations = (int)($request->query('operations') ?? 10000);
        
        $startTime = hrtime(true);
        $results = [];
        
        // Múltiplas operações para simular carga real
        for ($i = 0; $i < $operations; $i++) {
            // Simular processamento típico de API
            $data = ['id' => $i, 'value' => rand(1, 1000), 'processed_at' => microtime(true)];
            
            if (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
                $encoded = JsonBufferPool::encodeWithPool($data);
            } else {
                $encoded = json_encode($data);
            }
            
            $results[] = json_decode($encoded, true);
        }
        
        $endTime = hrtime(true);
        $totalTime = ($endTime - $startTime) / 1000000;
        
        return $response->json([
            'throughput_test' => [
                'operations' => $operations,
                'total_time_ms' => $totalTime,
                'operations_per_second' => round($operations / ($totalTime / 1000)),
                'avg_time_per_operation_ms' => $totalTime / $operations,
                'results_count' => count($results)
            ],
            'performance_optimizations' => [
                'json_pooling' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool'),
                'high_performance_mode' => HighPerformanceMode::getStatus()['enabled'],
                'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024
            ]
        ]);
    }

    /**
     * Estatísticas dos pools de performance
     */
    public function poolStatsTest($request, $response)
    {
        $stats = [
            'high_performance_mode' => HighPerformanceMode::getStatus(),
            'json_pool_stats' => null,
            'internal_metrics' => [
                'total_requests' => count($this->performanceMetrics),
                'avg_processing_time_ms' => 0,
                'avg_memory_usage' => 0
            ]
        ];
        
        // JSON Pool statistics
        if (class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool')) {
            $stats['json_pool_stats'] = JsonBufferPool::getStatistics();
        }
        
        // Internal metrics calculation
        if (!empty($this->performanceMetrics)) {
            $avgTime = array_sum(array_column($this->performanceMetrics, 'processing_time_ms')) / count($this->performanceMetrics);
            $avgMemory = array_sum(array_column($this->performanceMetrics, 'memory_used')) / count($this->performanceMetrics);
            
            $stats['internal_metrics']['avg_processing_time_ms'] = round($avgTime, 3);
            $stats['internal_metrics']['avg_memory_usage'] = $avgMemory;
        }
        
        return $response->json($stats);
    }

    /**
     * Métricas de performance coletadas
     */
    public function getMetrics($request, $response)
    {
        return $response->json([
            'performance_metrics' => $this->performanceMetrics,
            'system_metrics' => [
                'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
                'peak_memory_mb' => memory_get_peak_usage(true) / 1024 / 1024,
                'php_version' => PHP_VERSION,
                'framework_version' => PivotApplication::VERSION ?? 'unknown'
            ],
            'performance_config' => $this->config
        ]);
    }

    /**
     * Metadata da configuração de performance
     */
    public function getPerformanceMetadata($request, $response)
    {
        return $response->json([
            'application' => [
                'name' => 'PivotPHP High Performance Benchmark',
                'version' => '1.0.0',
                'performance_profile' => $this->config['performance_profile']
            ],
            'optimizations' => [
                'high_performance_mode' => HighPerformanceMode::getStatus(),
                'json_buffer_pooling' => class_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool'),
                'minimal_middleware' => $this->config['minimal_middleware'],
                'memory_optimization' => $this->config['memory_optimization']
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'],
                'jit_enabled' => function_exists('opcache_get_status') && (opcache_get_status()['jit']['enabled'] ?? false),
                'memory_limit' => ini_get('memory_limit')
            ]
        ]);
    }

    /**
     * Gera dados de teste baseados no tamanho
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

    /**
     * Retorna instância da aplicação PivotPHP
     */
    public function getApp(): PivotApplication
    {
        return $this->app;
    }

    /**
     * Retorna métricas coletadas
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }
}