<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase3\Slim4;

use Slim\Factory\AppFactory;
use Slim\App;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PivotPHP\Benchmark\Phase3\Slim4\Controllers\PerformanceController;
use PivotPHP\Benchmark\Phase3\Slim4\Controllers\DatabaseController;
use PivotPHP\Benchmark\Phase3\Slim4\Controllers\MiddlewareController;
use PivotPHP\Benchmark\Phase3\Slim4\Middleware\PerformanceMiddleware;

/**
 * Aplicação Slim 4 otimizada para benchmarks comparativos
 * 
 * Replica exatamente os endpoints do PivotPHP v1.1.2 para comparação científica
 */
class SlimApplication
{
    private App $app;
    private array $config;
    private float $startTime;
    private int $requestCount = 0;
    private array $performanceMetrics = [];

    public function __construct(array $config = [])
    {
        $this->startTime = microtime(true);
        $this->config = array_merge([
            'performance_profile' => 'EXTREME',
            'display_errors' => false,
            'log_errors' => false
        ], $config);

        $this->optimizeEnvironment();
        $this->initializeSlimApp();
        $this->registerMiddleware();
        $this->registerRoutes();
    }

    /**
     * Otimiza ambiente para máxima performance
     */
    private function optimizeEnvironment(): void
    {
        // Environment settings
        ini_set('display_errors', $this->config['display_errors'] ? '1' : '0');
        ini_set('log_errors', $this->config['log_errors'] ? '1' : '0');
        error_reporting(E_ERROR | E_PARSE);
        date_default_timezone_set('UTC');

        // Memory optimization
        ini_set('memory_limit', '1G');
        
        // Garbage collection
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }

    /**
     * Inicializa aplicação Slim 4
     */
    private function initializeSlimApp(): void
    {
        $this->app = AppFactory::create();
        
        // Error handling
        $this->app->addErrorMiddleware(
            $this->config['display_errors'],
            $this->config['log_errors'],
            true
        );
    }

    /**
     * Registra middleware personalizado
     */
    private function registerMiddleware(): void
    {
        // Performance monitoring middleware
        $this->app->add(new PerformanceMiddleware($this));
        
        // CORS middleware
        $this->app->add(function (Request $request, $handler) {
            $response = $handler->handle($request);
            
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        });

        // Framework identification headers
        $this->app->add(function (Request $request, $handler) {
            $response = $handler->handle($request);
            
            return $response
                ->withHeader('X-Benchmark-Phase', '3')
                ->withHeader('X-Framework', 'slim4')
                ->withHeader('X-Framework-Version', '4.12')
                ->withHeader('X-Performance-Profile', $this->config['performance_profile'])
                ->withHeader('X-PHP-Version', PHP_VERSION);
        });
    }

    /**
     * Registra todas as rotas para benchmark
     */
    private function registerRoutes(): void
    {
        $performanceController = new PerformanceController($this);
        $databaseController = new DatabaseController($this);
        $middlewareController = new MiddlewareController($this);

        // ============================================
        // HIGH PERFORMANCE ROUTES (matching PivotPHP)
        // ============================================
        
        // Health check otimizado
        $this->app->get('/health', [$performanceController, 'healthCheck']);
        
        // Ultra fast endpoint
        $this->app->get('/ultra-fast', [$performanceController, 'ultraFast']);
        
        // JSON performance tests
        $this->app->get('/performance/json/{size}', [$performanceController, 'jsonPerformance']);
        $this->app->post('/performance/json/{size}', [$performanceController, 'jsonPerformancePost']);
        
        // Throughput test
        $this->app->get('/performance/throughput', [$performanceController, 'throughputTest']);
        
        // Pool stats (simulated)
        $this->app->get('/performance/pool-stats', [$performanceController, 'poolStats']);
        
        // Framework metadata
        $this->app->get('/meta', [$performanceController, 'metadata']);

        // ============================================
        // DATABASE ROUTES (equivalent to ORM)
        // ============================================
        
        // Database health
        $this->app->get('/db/health', [$databaseController, 'health']);
        
        // Users CRUD
        $this->app->get('/db/users', [$databaseController, 'listUsers']);
        
        // Complex query
        $this->app->get('/db/complex-query', [$databaseController, 'complexQuery']);
        
        // Database stats
        $this->app->get('/db/stats', [$databaseController, 'stats']);

        // ============================================
        // MIDDLEWARE ROUTES
        // ============================================
        
        // Middleware stack test
        $this->app->get('/middleware/stack', [$middlewareController, 'stackTest']);
        
        // CORS test
        $this->app->get('/middleware/cors', [$middlewareController, 'corsTest']);
        
        // Auth simulation
        $this->app->get('/middleware/auth', [$middlewareController, 'authTest']);

        // ============================================
        // SLIM 4 SPECIFIC ROUTES
        // ============================================
        
        // Slim 4 specific features
        $this->app->get('/slim4/features', [$performanceController, 'slim4Features']);
        
        // Slim 4 container info
        $this->app->get('/slim4/container', [$performanceController, 'containerInfo']);
    }

    /**
     * Coleta métricas de performance
     */
    public function recordMetric(array $metric): void
    {
        $this->performanceMetrics[] = $metric;
    }

    /**
     * Incrementa contador de requests
     */
    public function incrementRequestCount(): int
    {
        return ++$this->requestCount;
    }

    /**
     * Retorna tempo de start
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * Retorna contagem de requests
     */
    public function getRequestCount(): int
    {
        return $this->requestCount;
    }

    /**
     * Retorna métricas coletadas
     */
    public function getMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * Retorna configuração
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Retorna instância Slim App
     */
    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * Executa a aplicação
     */
    public function run(): void
    {
        $this->app->run();
    }
}