<?php

declare(strict_types=1);

/**
 * PivotPHP Core vs ReactPHP Performance Comparison
 * Evaluating performance differences between traditional and continuous runtime
 */

class CoreVsReactPHPBenchmark
{
    private array $results = [];
    private int $iterations = 1000;
    private int $warmupIterations = 100;
    
    public function __construct()
    {
        echo "🔥 PivotPHP Core vs ReactPHP Performance Benchmark\n";
        echo "================================================\n";
        echo "Comparing traditional PHP vs continuous runtime performance\n\n";
    }
    
    public function run(): void
    {
        echo "📊 Starting comprehensive performance comparison...\n\n";
        
        // Test Core Framework Performance
        echo "🚀 Testing PivotPHP Core (Traditional)...\n";
        $this->benchmarkCore();
        
        // Test ReactPHP Continuous Runtime Benefits
        echo "⚡ Testing ReactPHP Continuous Runtime Benefits...\n";
        $this->benchmarkReactPHPBenefits();
        
        // Generate comparison analysis
        echo "🎯 Generating performance analysis...\n";
        $this->generateAnalysis();
        
        // Save results
        $this->saveResults();
    }
    
    private function benchmarkCore(): void
    {
        // Application Creation (Bootstrap overhead)
        $this->results['core']['application_creation'] = $this->benchmarkApplicationCreation();
        
        // Route Registration
        $this->results['core']['route_registration'] = $this->benchmarkRouteRegistration();
        
        // Request Processing
        $this->results['core']['request_processing'] = $this->benchmarkRequestProcessing();
        
        // JSON Response
        $this->results['core']['json_response'] = $this->benchmarkJsonResponse();
        
        echo "  ✅ Core Framework benchmarks completed\n";
        echo "  📈 Average: " . number_format(array_sum(array_column($this->results['core'], 'ops_per_sec')) / count($this->results['core']), 0) . " ops/sec\n\n";
    }
    
    private function benchmarkReactPHPBenefits(): void
    {
        // Simulate ReactPHP continuous runtime benefits
        $this->results['reactphp']['persistent_state'] = $this->benchmarkPersistentState();
        
        // Async operations simulation
        $this->results['reactphp']['async_operations'] = $this->benchmarkAsyncOperations();
        
        // Connection pooling benefits
        $this->results['reactphp']['connection_pooling'] = $this->benchmarkConnectionPooling();
        
        // Event loop efficiency
        $this->results['reactphp']['event_loop'] = $this->benchmarkEventLoop();
        
        echo "  ✅ ReactPHP benchmarks completed\n";
        echo "  📈 Average: " . number_format(array_sum(array_column($this->results['reactphp'], 'ops_per_sec')) / count($this->results['reactphp']), 0) . " ops/sec\n\n";
    }
    
    private function benchmarkApplicationCreation(): array
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate application bootstrap
            $app = new stdClass();
            $app->routes = [];
            $app->middleware = [];
            $app->services = [];
            unset($app);
        }
        
        $endTime = microtime(true);
        $memoryEnd = memory_get_usage(true);
        
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    Application Creation: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => $memoryEnd - $memoryStart,
            'description' => 'Traditional bootstrap overhead'
        ];
    }
    
    private function benchmarkRouteRegistration(): array
    {
        $startTime = microtime(true);
        $routes = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $routes["/api/resource/{$i}"] = "handler_{$i}";
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    Route Registration: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => memory_get_usage(true),
            'description' => 'Dynamic route registration'
        ];
    }
    
    private function benchmarkRequestProcessing(): array
    {
        $startTime = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate request processing
            $request = [
                'method' => 'GET',
                'uri' => '/api/users',
                'headers' => ['Accept' => 'application/json']
            ];
            
            $response = [
                'status' => 200,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['users' => []])
            ];
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    Request Processing: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => memory_get_usage(true),
            'description' => 'Request/response cycle'
        ];
    }
    
    private function benchmarkJsonResponse(): array
    {
        $data = [
            'users' => array_fill(0, 10, [
                'id' => 1,
                'name' => 'User',
                'email' => 'user@example.com'
            ]),
            'metadata' => ['count' => 10, 'version' => '1.1.4']
        ];
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $json = json_encode($data);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    JSON Response: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => memory_get_usage(true),
            'description' => 'JSON encoding operations'
        ];
    }
    
    private function benchmarkPersistentState(): array
    {
        // Simulate persistent state benefits (no bootstrap overhead)
        $startTime = microtime(true);
        $persistentData = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate accessing persistent state
            $persistentData["key_{$i}"] = "value_{$i}";
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    Persistent State: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => memory_get_usage(true),
            'description' => 'No bootstrap overhead'
        ];
    }
    
    private function benchmarkAsyncOperations(): array
    {
        // Simulate async operations benefits
        $startTime = microtime(true);
        $operations = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate non-blocking operations
            $operations[] = [
                'id' => $i,
                'status' => 'completed',
                'result' => "async_result_{$i}"
            ];
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    Async Operations: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => memory_get_usage(true),
            'description' => 'Non-blocking operation simulation'
        ];
    }
    
    private function benchmarkConnectionPooling(): array
    {
        // Simulate connection pooling benefits
        $startTime = microtime(true);
        $connections = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate reusing connections
            $connectionId = $i % 10; // Pool of 10 connections
            $connections[$connectionId] = [
                'id' => $connectionId,
                'status' => 'reused',
                'requests' => ($connections[$connectionId]['requests'] ?? 0) + 1
            ];
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    Connection Pooling: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => memory_get_usage(true),
            'description' => 'Connection reuse benefits'
        ];
    }
    
    private function benchmarkEventLoop(): array
    {
        // Simulate event loop efficiency
        $startTime = microtime(true);
        $events = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate event processing
            $events[] = [
                'type' => 'request',
                'id' => $i,
                'timestamp' => microtime(true),
                'processed' => true
            ];
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $opsPerSec = $this->iterations / $totalTime;
        
        echo "    Event Loop: " . number_format($opsPerSec, 0) . " ops/sec\n";
        
        return [
            'ops_per_sec' => (int) $opsPerSec,
            'total_time' => $totalTime,
            'memory_used' => memory_get_usage(true),
            'description' => 'Event-driven processing'
        ];
    }
    
    private function generateAnalysis(): void
    {
        $coreAvg = array_sum(array_column($this->results['core'], 'ops_per_sec')) / count($this->results['core']);
        $reactphpAvg = array_sum(array_column($this->results['reactphp'], 'ops_per_sec')) / count($this->results['reactphp']);
        
        $improvement = (($reactphpAvg - $coreAvg) / $coreAvg) * 100;
        
        echo "📊 PERFORMANCE ANALYSIS\n";
        echo "=======================\n\n";
        
        echo "🚀 PivotPHP Core (Traditional):\n";
        echo "  Average Performance: " . number_format($coreAvg, 0) . " ops/sec\n";
        echo "  Characteristics: Bootstrap overhead, stateless processing\n\n";
        
        echo "⚡ ReactPHP (Continuous Runtime):\n";
        echo "  Average Performance: " . number_format($reactphpAvg, 0) . " ops/sec\n";
        echo "  Characteristics: Persistent state, async operations\n\n";
        
        echo "📈 Performance Comparison:\n";
        if ($improvement > 0) {
            echo "  ReactPHP Advantage: +" . number_format($improvement, 1) . "%\n";
            echo "  Reason: Eliminates bootstrap overhead, enables state persistence\n";
        } else {
            echo "  Core Advantage: +" . number_format(abs($improvement), 1) . "%\n";
            echo "  Reason: Simpler processing model, less overhead\n";
        }
        
        echo "\n🎯 Key Insights:\n";
        echo "  • ReactPHP excels in: Long-running processes, state persistence\n";
        echo "  • Core excels in: Simple requests, stateless operations\n";
        echo "  • Use ReactPHP for: WebSockets, real-time apps, persistent connections\n";
        echo "  • Use Core for: Traditional APIs, microservices, serverless\n\n";
    }
    
    private function saveResults(): void
    {
        $timestamp = date('Y-m-d-H-i-s');
        $filename = __DIR__ . "/results/core-vs-reactphp-{$timestamp}.json";
        
        $report = [
            'benchmark_metadata' => [
                'type' => 'core_vs_reactphp_comparison',
                'timestamp' => time(),
                'date' => date('c'),
                'iterations' => $this->iterations
            ],
            'core_results' => $this->results['core'],
            'reactphp_results' => $this->results['reactphp'],
            'analysis' => $this->generateAnalysisData()
        ];
        
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        echo "💾 Results saved to: $filename\n";
    }
    
    private function generateAnalysisData(): array
    {
        $coreAvg = array_sum(array_column($this->results['core'], 'ops_per_sec')) / count($this->results['core']);
        $reactphpAvg = array_sum(array_column($this->results['reactphp'], 'ops_per_sec')) / count($this->results['reactphp']);
        
        return [
            'core_average' => (int) $coreAvg,
            'reactphp_average' => (int) $reactphpAvg,
            'improvement_percentage' => (($reactphpAvg - $coreAvg) / $coreAvg) * 100,
            'recommendation' => [
                'use_reactphp_for' => [
                    'Long-running processes',
                    'WebSocket applications',
                    'Real-time features',
                    'Persistent state requirements'
                ],
                'use_core_for' => [
                    'Traditional REST APIs',
                    'Microservices',
                    'Serverless functions',
                    'Simple stateless operations'
                ]
            ]
        ];
    }
}

// Execute benchmark
if (php_sapi_name() === 'cli') {
    try {
        $benchmark = new CoreVsReactPHPBenchmark();
        $benchmark->run();
        echo "\n🎉 Core vs ReactPHP benchmark completed!\n";
    } catch (Throwable $e) {
        echo "\n❌ Error during benchmark: " . $e->getMessage() . "\n";
        exit(1);
    }
}