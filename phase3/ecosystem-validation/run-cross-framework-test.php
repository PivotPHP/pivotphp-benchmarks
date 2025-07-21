<?php

declare(strict_types=1);

/**
 * PivotPHP Cross-Framework Compatible Test Runner
 * 
 * Executa testes equivalentes aos outros frameworks do benchmark suite
 * Configuração padrão: nginx + php-fpm + OPcache + JIT
 */

echo "🚀 PivotPHP Cross-Framework Compatible Test\n";
echo "📊 Testing PivotPHP Full Ecosystem vs Core in standardized environment\n";
echo "🎯 Configuration: nginx + php-fpm + OPcache + JIT (same as other frameworks)\n\n";

class CrossFrameworkTester
{
    private array $services = [
        'pivotphp-full-ecosystem' => [
            'url' => 'http://localhost:8080',
            'name' => 'PivotPHP Full Ecosystem',
            'port' => 8080
        ],
        'pivotphp-core' => [
            'url' => 'http://localhost:8081', 
            'name' => 'PivotPHP Core Only',
            'port' => 8081
        ]
    ];

    private array $testEndpoints = [
        '/api' => 'Simple API endpoint (standard cross-framework test)',
        '/health' => 'Health check endpoint',
        '/' => 'Root endpoint with framework info'
    ];

    public function runCrossFrameworkTest(): void
    {
        echo "🐳 Starting Docker services with cross-framework configuration...\n";
        $this->startDockerServices();
        
        echo "⏳ Waiting for services to be ready...\n";
        sleep(15); // Give services time to fully start
        
        echo "🔍 Performing health checks...\n";
        $this->performHealthChecks();
        
        echo "📊 Running cross-framework compatible benchmarks...\n";
        $this->runBenchmarks();
        
        echo "📈 Generating cross-framework comparison report...\n";
        $this->generateComparisonReport();
        
        echo "🧹 Cleaning up Docker services...\n";
        $this->cleanupDockerServices();
    }

    private function startDockerServices(): void
    {
        // Stop any existing services
        exec('docker-compose -f docker-compose-cross-framework.yml down --remove-orphans 2>/dev/null');
        
        // Build and start services
        exec('docker-compose -f docker-compose-cross-framework.yml build', $output, $code);
        if ($code !== 0) {
            throw new Exception("Failed to build Docker services");
        }
        
        exec('docker-compose -f docker-compose-cross-framework.yml up -d', $output, $code);
        if ($code !== 0) {
            throw new Exception("Failed to start Docker services");
        }
        
        echo "✅ Docker services started\n";
    }

    private function performHealthChecks(): void
    {
        $allHealthy = true;
        
        foreach ($this->services as $service => $config) {
            echo "  🔍 Checking {$config['name']}...\n";
            
            $maxRetries = 10;
            $healthy = false;
            
            for ($i = 0; $i < $maxRetries; $i++) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $config['url'] . '/health',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 5
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 && !empty($response)) {
                    $healthy = true;
                    break;
                }
                
                echo "    ⏳ Retry " . ($i + 1) . "/$maxRetries...\n";
                sleep(2);
            }
            
            if ($healthy) {
                echo "    ✅ {$config['name']} is healthy\n";
            } else {
                echo "    ❌ {$config['name']} failed health check\n";
                $allHealthy = false;
            }
        }
        
        if (!$allHealthy) {
            throw new Exception("Some services failed health checks");
        }
        
        echo "✅ All services are healthy!\n\n";
    }

    private function runBenchmarks(): void
    {
        $results = [];
        
        foreach ($this->services as $service => $config) {
            echo "⚡ Testing {$config['name']}...\n";
            
            foreach ($this->testEndpoints as $endpoint => $description) {
                echo "  📊 Testing endpoint: {$endpoint} ({$description})\n";
                
                $url = $config['url'] . $endpoint;
                $result = $this->runSingleBenchmark($url, $service, $endpoint);
                
                $results[$service][$endpoint] = $result;
                
                echo "    🏆 {$result['requests_per_second']} req/sec (avg: {$result['avg_response_time']}ms)\n";
            }
            
            echo "✅ Completed {$config['name']}\n\n";
        }
        
        $this->saveResults($results);
    }

    private function runSingleBenchmark(string $url, string $service, string $endpoint): array
    {
        // Configuration matching other cross-framework tests
        $iterations = 1000;
        $concurrency = 1;
        $warmup = 100;
        
        // Warmup phase
        for ($i = 0; $i < $warmup; $i++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
        
        // Main benchmark
        $startTime = microtime(true);
        $successCount = 0;
        $responseTimes = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $requestStart = microtime(true);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $requestEnd = microtime(true);
            $responseTime = ($requestEnd - $requestStart) * 1000; // ms
            
            if ($httpCode === 200 && !empty($response)) {
                $successCount++;
                $responseTimes[] = $responseTime;
            }
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        return [
            'requests_per_second' => round($iterations / $totalTime, 2),
            'avg_response_time' => round(array_sum($responseTimes) / count($responseTimes), 2),
            'min_response_time' => round(min($responseTimes), 2),
            'max_response_time' => round(max($responseTimes), 2),
            'success_rate' => round(($successCount / $iterations) * 100, 2),
            'total_requests' => $iterations,
            'successful_requests' => $successCount,
            'total_time' => round($totalTime, 3)
        ];
    }

    private function saveResults(array $results): void
    {
        $timestamp = date('Y-m-d-H-i-s');
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'Cross-Framework Compatible HTTP Test',
            'framework_version' => 'PivotPHP v1.1.4',
            'environment' => 'Docker containers (nginx + php-fpm + OPcache + JIT)',
            'configuration' => [
                'php_version' => '8.4',
                'opcache_enabled' => true,
                'jit_enabled' => true,
                'jit_buffer_size' => '256M',
                'memory_limit' => '512M'
            ],
            'test_results' => $results,
            'cross_framework_summary' => $this->generateSummary($results)
        ];
        
        $filename = "../results/cross-framework-compatible-test-{$timestamp}.json";
        file_put_contents($filename, json_encode($reportData, JSON_PRETTY_PRINT));
        
        echo "💾 Results saved to: {$filename}\n";
    }

    private function generateSummary(array $results): array
    {
        $summary = [];
        
        foreach ($results as $service => $endpoints) {
            $avgRps = 0;
            $avgLatency = 0;
            $count = 0;
            
            foreach ($endpoints as $endpoint => $result) {
                $avgRps += $result['requests_per_second'];
                $avgLatency += $result['avg_response_time'];
                $count++;
            }
            
            $summary[$service] = [
                'avg_requests_per_second' => round($avgRps / $count, 2),
                'avg_response_time' => round($avgLatency / $count, 2),
                'endpoints_tested' => $count,
                'framework_type' => str_contains($service, 'full-ecosystem') ? 'Full Ecosystem' : 'Core Only'
            ];
        }
        
        return $summary;
    }

    private function generateComparisonReport(): void
    {
        echo "📊 Cross-Framework Comparison Summary:\n";
        echo "═══════════════════════════════════════\n";
        echo "🎯 Configuration: Equivalent to Slim4, Lumen, Flight benchmarks\n";
        echo "⚙️  Stack: nginx + php-fpm + OPcache + JIT\n";
        echo "🐳 Environment: Docker containers with resource limits\n";
        echo "📈 Test Pattern: HTTP requests via curl (realistic performance)\n\n";
        
        echo "✅ This test provides realistic performance numbers comparable\n";
        echo "   to other frameworks in the cross-framework benchmark suite.\n\n";
        
        echo "📝 Note: Results will be 1K-10K req/sec range (HTTP realistic)\n";
        echo "   not 100K+ req/sec (internal benchmark optimistic)\n\n";
    }

    private function cleanupDockerServices(): void
    {
        exec('docker-compose -f docker-compose-cross-framework.yml down --remove-orphans');
        echo "✅ Docker services stopped\n";
    }
}

// Execute cross-framework test
try {
    $tester = new CrossFrameworkTester();
    $tester->runCrossFrameworkTest();
    
    echo "🎉 Cross-Framework Compatible Test Completed!\n";
    echo "🏆 PivotPHP v1.1.4 tested with equivalent configuration to other frameworks\n";
    echo "📊 Results are now comparable with Slim4, Lumen, Flight, and Symfony benchmarks\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    
    // Cleanup on failure
    exec('docker-compose -f docker-compose-cross-framework.yml down --remove-orphans 2>/dev/null');
    exit(1);
}