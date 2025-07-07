#!/usr/bin/env php
<?php

/**
 * Memory Usage Benchmark for PivotPHP Core
 *
 * Tests memory consumption patterns of various framework components
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\RateLimitMiddleware;
use Shared\Interfaces\BenchmarkInterface;

class MemoryBenchmark implements BenchmarkInterface
{
    private array $memorySnapshots = [];

    public function __construct()
    {
    }

    public function getName(): string
    {
        return 'Memory Usage Analysis';
    }

    public function getDescription(): string
    {
        return 'Analyzes memory consumption patterns across framework components';
    }

    public function warmup(): void
    {
        // Create and destroy a few instances to stabilize memory
        for ($i = 0; $i < 10; $i++) {
            $app = new Application();
            $request = new Request('GET', '/test', '/test');
            $response = new Response();
            unset($app, $request, $response);
        }
        
        // Force garbage collection
        gc_collect_cycles();
    }

    public function run(int $iterations = 10000): array
    {
        $results = [
            'baseline' => $this->measureBaseline(),
            'application_instances' => $this->benchmarkApplicationInstances($iterations),
            'request_response_cycle' => $this->benchmarkRequestResponseCycle($iterations),
            'middleware_stack' => $this->benchmarkMiddlewareStack($iterations),
            'large_payloads' => $this->benchmarkLargePayloads($iterations),
            'memory_leaks' => $this->checkMemoryLeaks($iterations)
        ];

        return [
            'iterations' => $iterations,
            'peak_memory' => memory_get_peak_usage(true),
            'current_memory' => memory_get_usage(true),
            'details' => $results
        ];
    }

    private function measureBaseline(): array
    {
        gc_collect_cycles();
        $baseline = memory_get_usage(true);
        
        return [
            'name' => 'Baseline Memory',
            'memory_bytes' => $baseline,
            'memory_mb' => $baseline / 1024 / 1024
        ];
    }

    private function benchmarkApplicationInstances(int $iterations): array
    {
        gc_collect_cycles();
        $startMem = memory_get_usage(true);
        $instances = [];
        
        // Create instances
        for ($i = 0; $i < min($iterations, 1000); $i++) {
            $instances[] = new Application();
        }
        
        $peakMem = memory_get_usage(true);
        $memPerInstance = ($peakMem - $startMem) / count($instances);
        
        // Clean up
        $instances = [];
        gc_collect_cycles();
        
        $endMem = memory_get_usage(true);
        
        return [
            'name' => 'Application Instances',
            'instances_created' => min($iterations, 1000),
            'total_memory_used' => $peakMem - $startMem,
            'memory_per_instance' => $memPerInstance,
            'memory_released' => $peakMem - $endMem,
            'memory_leaked' => $endMem - $startMem
        ];
    }

    private function benchmarkRequestResponseCycle(int $iterations): array
    {
        gc_collect_cycles();
        $startMem = memory_get_usage(true);
        $app = new Application();
        
        // Add some routes
        for ($i = 0; $i < 10; $i++) {
            $app->get('/route' . $i, function($request, $response) {
                return $response->json(['route' => $request->getUri()->getPath()]);
            });
        }
        
        $memoryUsage = [];
        $sampleRate = max(1, $iterations / 100); // Sample memory every N iterations
        
        for ($i = 0; $i < $iterations; $i++) {
            $request = new Request('GET', '/route' . ($i % 10), '/route' . ($i % 10));
            $response = $app->handle($request);
            
            if ($i % $sampleRate === 0) {
                $memoryUsage[] = memory_get_usage(true) - $startMem;
            }
            
            unset($request, $response);
        }
        
        gc_collect_cycles();
        $endMem = memory_get_usage(true);
        
        return [
            'name' => 'Request/Response Cycle',
            'requests_processed' => $iterations,
            'memory_samples' => count($memoryUsage),
            'avg_memory_during' => array_sum($memoryUsage) / count($memoryUsage),
            'max_memory_during' => max($memoryUsage),
            'final_memory_usage' => $endMem - $startMem
        ];
    }

    private function benchmarkMiddlewareStack(int $iterations): array
    {
        gc_collect_cycles();
        $startMem = memory_get_usage(true);
        
        $results = [];
        $middlewareCounts = [1, 5, 10, 20];
        
        foreach ($middlewareCounts as $count) {
            $app = new Application();
            
            // Add middleware stack
            for ($i = 0; $i < $count; $i++) {
                $app->addMiddleware(new class implements \Psr\Http\Server\MiddlewareInterface {
                    public function process($request, $handler): \Psr\Http\Message\ResponseInterface {
                        $request = $request->withAttribute('middleware_' . spl_object_id($this), true);
                        return $handler->handle($request);
                    }
                });
            }
            
            $app->get('/test', function($request, $response) {
                return $response->json(['ok' => true]);
            });
            
            $beforeMem = memory_get_usage(true);
            
            // Process requests
            for ($i = 0; $i < $iterations / count($middlewareCounts); $i++) {
                $request = new Request('GET', '/test', '/test');
                $app->handle($request);
            }
            
            $afterMem = memory_get_usage(true);
            
            $results[$count . '_middlewares'] = [
                'memory_used' => $afterMem - $beforeMem,
                'memory_per_request' => ($afterMem - $beforeMem) / ($iterations / count($middlewareCounts))
            ];
        }
        
        gc_collect_cycles();
        
        return [
            'name' => 'Middleware Stack Scaling',
            'iterations_per_stack' => $iterations / count($middlewareCounts),
            'results' => $results
        ];
    }

    private function benchmarkLargePayloads(int $iterations): array
    {
        gc_collect_cycles();
        $startMem = memory_get_usage(true);
        $app = new Application();
        
        // Different payload sizes
        $payloadSizes = [
            '1KB' => 1024,
            '10KB' => 10240,
            '100KB' => 102400,
            '1MB' => 1048576
        ];
        
        $results = [];
        
        foreach ($payloadSizes as $label => $size) {
            $app->post('/upload-' . $label, function($request, $response) use ($size) {
                $body = $request->getParsedBody();
                return $response->json(['received' => strlen($body['data'] ?? '')]);
            });
            
            $payload = str_repeat('x', $size);
            $beforeMem = memory_get_usage(true);
            
            for ($i = 0; $i < min(100, $iterations); $i++) {
                $request = new Request('POST', '/upload-' . $label, '/upload-' . $label);
                $request = $request->withParsedBody(['data' => $payload]);
                
                $app->handle($request);
            }
            
            $afterMem = memory_get_usage(true);
            gc_collect_cycles();
            $finalMem = memory_get_usage(true);
            
            $results[$label] = [
                'payload_size' => $size,
                'iterations' => min(100, $iterations),
                'memory_used' => $afterMem - $beforeMem,
                'memory_released' => $afterMem - $finalMem,
                'memory_per_request' => ($afterMem - $beforeMem) / min(100, $iterations)
            ];
        }
        
        return [
            'name' => 'Large Payload Handling',
            'results' => $results
        ];
    }

    private function checkMemoryLeaks(int $iterations): array
    {
        gc_collect_cycles();
        $initialMem = memory_get_usage(true);
        $measurements = [];
        
        // Run the same operation multiple times and track memory growth
        for ($batch = 0; $batch < 10; $batch++) {
            $app = new Application();
            
            // Add middlewares
            $app->addMiddleware(new CorsMiddleware());
            $app->addMiddleware(new RateLimitMiddleware());
            
            // Add routes
            for ($i = 0; $i < 50; $i++) {
                $app->get('/leak-test-' . $i, function($request, $response) {
                    return $response->json(range(1, 100));
                });
            }
            
            // Process requests
            for ($i = 0; $i < $iterations / 10; $i++) {
                $request = new Request('GET', '/leak-test-' . ($i % 50), '/leak-test-' . ($i % 50));
                $app->handle($request);
            }
            
            gc_collect_cycles();
            $measurements[] = memory_get_usage(true) - $initialMem;
        }
        
        // Calculate memory growth trend
        $firstBatch = array_slice($measurements, 0, 3);
        $lastBatch = array_slice($measurements, -3);
        $avgFirst = array_sum($firstBatch) / count($firstBatch);
        $avgLast = array_sum($lastBatch) / count($lastBatch);
        $growth = $avgLast - $avgFirst;
        
        return [
            'name' => 'Memory Leak Detection',
            'batches' => count($measurements),
            'iterations_per_batch' => $iterations / 10,
            'memory_measurements' => $measurements,
            'initial_avg_memory' => $avgFirst,
            'final_avg_memory' => $avgLast,
            'memory_growth' => $growth,
            'potential_leak' => $growth > 1048576, // More than 1MB growth
            'growth_per_iteration' => $growth / $iterations
        ];
    }
}

// Run the benchmark
if (php_sapi_name() === 'cli') {
    $benchmark = new MemoryBenchmark();
    $results = $benchmark->run(1000); // Fewer iterations for memory tests
    
    echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
}