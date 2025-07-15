<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ReactPHP;

use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Promise\Promise;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;
use PivotPHP\Benchmark\Phase2\ReactPHP\Handlers\{
    HealthHandler,
    BenchmarkHandler,
    AsyncHandler,
    WebSocketHandler
};

/**
 * Servidor ReactPHP para benchmarks da Fase 2
 * 
 * Características:
 * - Event loop de alta performance
 * - Conexões não-bloqueantes
 * - Gerenciamento de memória otimizado
 * - WebSocket support
 * - Async operations
 */
class Server
{
    private $loop;
    private HttpServer $httpServer;
    private SocketServer $socketServer;
    private array $config;
    private array $stats;
    private float $startTime;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => '0.0.0.0',
            'port' => 8080,
            'websocket_port' => 9090,
            'max_connections' => 1000,
            'memory_limit' => '512M',
            'gc_enabled' => true
        ], $config);

        $this->startTime = microtime(true);
        $this->stats = [
            'requests_count' => 0,
            'connections_count' => 0,
            'memory_peak' => 0,
            'uptime' => 0
        ];

        $this->initializeEventLoop();
        $this->initializeServer();
        $this->initializeHandlers();
    }

    /**
     * Inicializa event loop
     */
    private function initializeEventLoop(): void
    {
        $this->loop = Loop::get();
        
        // Configurar garbage collection periódico
        if ($this->config['gc_enabled']) {
            $this->loop->addPeriodicTimer(10.0, function () {
                if (gc_enabled()) {
                    $collected = gc_collect_cycles();
                    if ($collected > 0) {
                        echo "GC: Collected $collected cycles\n";
                    }
                }
                
                // Atualizar estatísticas
                $this->updateStats();
            });
        }
    }

    /**
     * Inicializa servidor HTTP
     */
    private function initializeServer(): void
    {
        $this->socketServer = new SocketServer(
            $this->config['host'] . ':' . $this->config['port'],
            [],
            $this->loop
        );

        $this->httpServer = new HttpServer(
            $this->loop,
            [$this, 'handleRequest']
        );
    }

    /**
     * Inicializa handlers
     */
    private function initializeHandlers(): void
    {
        // Configurar handlers serão injetados via roteamento
    }

    /**
     * Handler principal de requests
     */
    public function handleRequest(ServerRequestInterface $request): Promise
    {
        $this->stats['requests_count']++;
        
        $startTime = microtime(true);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        
        // Headers de benchmark
        $headers = [
            'X-Benchmark-Phase' => '2',
            'X-Framework' => 'pivotphp-core',
            'X-Extension' => 'reactphp',
            'X-Request-ID' => uniqid('req_'),
            'X-Server-Time' => microtime(true)
        ];

        return $this->routeRequest($method, $path, $request)
            ->then(function ($response) use ($startTime, $headers) {
                $processingTime = (microtime(true) - $startTime) * 1000;
                $headers['X-Processing-Time-Ms'] = number_format($processingTime, 3);
                
                foreach ($headers as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }
                
                return $response;
            })
            ->otherwise(function ($error) use ($headers) {
                $errorResponse = new Response(
                    500,
                    array_merge($headers, ['Content-Type' => 'application/json']),
                    json_encode([
                        'error' => 'Internal Server Error',
                        'message' => $error->getMessage(),
                        'timestamp' => microtime(true)
                    ])
                );
                
                return $errorResponse;
            });
    }

    /**
     * Roteamento de requests
     */
    private function routeRequest(string $method, string $path, ServerRequestInterface $request): Promise
    {
        // Health check
        if ($path === '/health') {
            return $this->handleHealth($request);
        }

        // Metadata
        if ($path === '/meta') {
            return $this->handleMetadata($request);
        }

        // Async benchmarks
        if (str_starts_with($path, '/async/')) {
            return $this->handleAsync($method, $path, $request);
        }

        // Event loop benchmarks
        if (str_starts_with($path, '/event-loop/')) {
            return $this->handleEventLoop($method, $path, $request);
        }

        // Concurrency benchmarks
        if (str_starts_with($path, '/concurrency/')) {
            return $this->handleConcurrency($method, $path, $request);
        }

        // WebSocket benchmarks
        if (str_starts_with($path, '/websocket/')) {
            return $this->handleWebSocket($method, $path, $request);
        }

        // Memory benchmarks
        if (str_starts_with($path, '/memory/')) {
            return $this->handleMemory($method, $path, $request);
        }

        // Promise benchmarks
        if (str_starts_with($path, '/promises/')) {
            return $this->handlePromises($method, $path, $request);
        }

        // 404 Not Found
        return Promise::resolve(
            new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Not Found', 'path' => $path])
            )
        );
    }

    /**
     * Health check endpoint
     */
    private function handleHealth(ServerRequestInterface $request): Promise
    {
        return Promise::resolve(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'status' => 'healthy',
                    'extension' => 'reactphp',
                    'timestamp' => microtime(true),
                    'uptime' => microtime(true) - $this->startTime,
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true),
                    'stats' => $this->stats,
                    'event_loop' => [
                        'running' => $this->loop->running ?? true,
                        'timers_count' => 'N/A', // ReactPHP não expõe isso facilmente
                    ]
                ])
            )
        );
    }

    /**
     * Metadata endpoint
     */
    private function handleMetadata(ServerRequestInterface $request): Promise
    {
        return Promise::resolve(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'application' => [
                        'name' => 'PivotPHP + ReactPHP Benchmark Phase 2',
                        'version' => '1.0.0',
                        'framework' => 'pivotphp-core',
                        'extension' => 'reactphp'
                    ],
                    'environment' => [
                        'php_version' => PHP_VERSION,
                        'memory_limit' => ini_get('memory_limit'),
                        'max_execution_time' => ini_get('max_execution_time'),
                        'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'],
                    ],
                    'server' => [
                        'host' => $this->config['host'],
                        'port' => $this->config['port'],
                        'websocket_port' => $this->config['websocket_port'],
                        'max_connections' => $this->config['max_connections'],
                        'uptime' => microtime(true) - $this->startTime
                    ],
                    'performance' => $this->stats
                ])
            )
        );
    }

    /**
     * Async operations benchmarks
     */
    private function handleAsync(string $method, string $path, ServerRequestInterface $request): Promise
    {
        $operation = str_replace('/async/', '', $path);

        switch ($operation) {
            case 'timer':
                return $this->asyncTimerTest($request);
                
            case 'file-read':
                return $this->asyncFileTest($request);
                
            case 'http-client':
                return $this->asyncHttpTest($request);
                
            case 'parallel':
                return $this->asyncParallelTest($request);
                
            default:
                return Promise::resolve(
                    new Response(200, ['Content-Type' => 'application/json'], 
                    json_encode(['available_operations' => ['timer', 'file-read', 'http-client', 'parallel']]))
                );
        }
    }

    /**
     * Event loop performance tests
     */
    private function handleEventLoop(string $method, string $path, ServerRequestInterface $request): Promise
    {
        $test = str_replace('/event-loop/', '', $path);

        switch ($test) {
            case 'timers':
                return $this->eventLoopTimersTest();
                
            case 'performance':
                return $this->eventLoopPerformanceTest();
                
            case 'stress':
                return $this->eventLoopStressTest();
                
            default:
                return Promise::resolve(
                    new Response(200, ['Content-Type' => 'application/json'],
                    json_encode(['available_tests' => ['timers', 'performance', 'stress']]))
                );
        }
    }

    /**
     * Concurrency benchmarks
     */
    private function handleConcurrency(string $method, string $path, ServerRequestInterface $request): Promise
    {
        $test = str_replace('/concurrency/', '', $path);
        
        switch ($test) {
            case 'requests':
                return $this->concurrentRequestsTest($request);
                
            case 'connections':
                return $this->concurrentConnectionsTest($request);
                
            default:
                return Promise::resolve(
                    new Response(200, ['Content-Type' => 'application/json'],
                    json_encode(['available_tests' => ['requests', 'connections']]))
                );
        }
    }

    /**
     * Teste de timer assíncrono
     */
    private function asyncTimerTest(ServerRequestInterface $request): Promise
    {
        $delay = (float)($request->getQueryParams()['delay'] ?? 0.1);
        $startTime = microtime(true);

        return new Promise(function ($resolve) use ($delay, $startTime) {
            $this->loop->addTimer($delay, function () use ($resolve, $startTime, $delay) {
                $endTime = microtime(true);
                $resolve(new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'test' => 'async_timer',
                        'requested_delay' => $delay,
                        'actual_delay' => $endTime - $startTime,
                        'accuracy' => abs(($endTime - $startTime) - $delay) < 0.01,
                        'timestamp' => $endTime
                    ])
                ));
            });
        });
    }

    /**
     * Event loop timers test
     */
    private function eventLoopTimersTest(): Promise
    {
        $startTime = microtime(true);
        $timerCount = 100;
        $completedTimers = 0;

        return new Promise(function ($resolve) use ($startTime, $timerCount, &$completedTimers) {
            for ($i = 0; $i < $timerCount; $i++) {
                $this->loop->addTimer(0.01 * $i, function () use (&$completedTimers, $timerCount, $resolve, $startTime) {
                    $completedTimers++;
                    if ($completedTimers === $timerCount) {
                        $endTime = microtime(true);
                        $resolve(new Response(
                            200,
                            ['Content-Type' => 'application/json'],
                            json_encode([
                                'test' => 'event_loop_timers',
                                'timers_created' => $timerCount,
                                'timers_completed' => $completedTimers,
                                'total_time' => $endTime - $startTime,
                                'timers_per_second' => $timerCount / ($endTime - $startTime)
                            ])
                        ));
                    }
                });
            }
        });
    }

    /**
     * Concurrent requests test
     */
    private function concurrentRequestsTest(ServerRequestInterface $request): Promise
    {
        $iterations = (int)($request->getQueryParams()['iterations'] ?? 100);
        $startTime = microtime(true);
        
        // Simular processamento concorrente
        $promises = [];
        for ($i = 0; $i < $iterations; $i++) {
            $promises[] = new Promise(function ($resolve) use ($i) {
                $this->loop->addTimer(0.001, function () use ($resolve, $i) {
                    $resolve(['iteration' => $i, 'result' => $i * 2]);
                });
            });
        }

        return \React\Promise\all($promises)->then(function ($results) use ($startTime, $iterations) {
            $endTime = microtime(true);
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'test' => 'concurrent_requests',
                    'iterations' => $iterations,
                    'total_time' => $endTime - $startTime,
                    'requests_per_second' => $iterations / ($endTime - $startTime),
                    'results_count' => count($results),
                    'memory_usage' => memory_get_usage(true)
                ])
            );
        });
    }

    /**
     * Atualiza estatísticas do servidor
     */
    private function updateStats(): void
    {
        $this->stats['uptime'] = microtime(true) - $this->startTime;
        $this->stats['memory_peak'] = max($this->stats['memory_peak'], memory_get_peak_usage(true));
        $this->stats['memory_current'] = memory_get_usage(true);
    }

    /**
     * Inicia o servidor
     */
    public function start(): void
    {
        $this->httpServer->listen($this->socketServer);

        echo "🚀 ReactPHP Server started!\n";
        echo "📡 HTTP Server: http://{$this->config['host']}:{$this->config['port']}\n";
        echo "🌐 WebSocket Server: ws://{$this->config['host']}:{$this->config['websocket_port']}\n";
        echo "💾 Memory Limit: {$this->config['memory_limit']}\n";
        echo "🔄 Max Connections: {$this->config['max_connections']}\n";
        echo "⏰ Started at: " . date('Y-m-d H:i:s') . "\n";
        echo "========================================\n";

        $this->loop->run();
    }

    /**
     * Para o servidor
     */
    public function stop(): void
    {
        $this->loop->stop();
        echo "🛑 ReactPHP Server stopped\n";
    }

    /**
     * Retorna loop de eventos
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * Retorna estatísticas
     */
    public function getStats(): array
    {
        $this->updateStats();
        return $this->stats;
    }
}