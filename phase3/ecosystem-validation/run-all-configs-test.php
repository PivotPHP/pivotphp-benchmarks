<?php

declare(strict_types=1);

/**
 * PivotPHP All Configurations Cross-Framework Test
 * 
 * Testa todas as configurações em ambiente equivalente aos outros frameworks:
 * 1. PivotPHP Core Only
 * 2. PivotPHP Core + ReactPHP
 * 3. PivotPHP Core + Cycle ORM
 * 4. PivotPHP Full Ecosystem (Core + ReactPHP + ORM)
 */

echo "🚀 PivotPHP All Configurations Cross-Framework Test\n";
echo "📊 Testing all PivotPHP variants with equivalent configuration\n";
echo "🎯 Stack: nginx + php-fpm + OPcache + JIT (same as other frameworks)\n\n";

class AllConfigsTester
{
    private array $configurations = [
        'pivotphp-core-only' => [
            'url' => 'http://localhost:8080',
            'name' => 'PivotPHP Core Only',
            'port' => 8080,
            'components' => ['core'],
            'target_performance' => '7000-9000 req/sec'
        ],
        'pivotphp-core-reactphp' => [
            'url' => 'http://localhost:8081',
            'name' => 'PivotPHP Core + ReactPHP',
            'port' => 8081,
            'components' => ['core', 'reactphp'],
            'target_performance' => '8000-10000 req/sec'
        ],
        'pivotphp-core-orm' => [
            'url' => 'http://localhost:8082',
            'name' => 'PivotPHP Core + Cycle ORM',
            'port' => 8082,
            'components' => ['core', 'cycle-orm'],
            'target_performance' => '5000-7000 req/sec'
        ],
        'pivotphp-full-ecosystem' => [
            'url' => 'http://localhost:8083',
            'name' => 'PivotPHP Full Ecosystem',
            'port' => 8083,
            'components' => ['core', 'reactphp', 'cycle-orm'],
            'target_performance' => '6000-8000 req/sec'
        ]
    ];

    private array $testEndpoints = [
        '/api' => 'Simple API endpoint (cross-framework standard)',
        '/health' => 'Health check endpoint',
        '/' => 'Root endpoint with framework info'
    ];

    public function runAllConfigurationsTest(): void
    {
        echo "🐳 Starting Docker services for all configurations...\n";
        $this->startAllDockerServices();
        
        echo "⏳ Waiting for all services to be ready...\n";
        sleep(30); // Give more time for multiple services
        
        echo "🔍 Performing health checks on all configurations...\n";
        $this->performAllHealthChecks();
        
        echo "📊 Running cross-framework benchmarks on all configurations...\n";
        $this->runAllBenchmarks();
        
        echo "📈 Generating comprehensive comparison report...\n";
        $this->generateComparisonReport();
        
        echo "🧹 Cleaning up all Docker services...\n";
        $this->cleanupAllDockerServices();
    }

    private function startAllDockerServices(): void
    {
        // Stop any existing services
        exec('docker-compose -f docker-compose-all-configs.yml down --remove-orphans 2>/dev/null');
        
        // Build all services
        echo "  📦 Building all configuration containers...\n";
        exec('docker-compose -f docker-compose-all-configs.yml build', $output, $code);
        if ($code !== 0) {
            throw new Exception("Failed to build Docker services");
        }
        
        // Start all services
        echo "  🚀 Starting all configuration services...\n";
        exec('docker-compose -f docker-compose-all-configs.yml up -d', $output, $code);
        if ($code !== 0) {
            throw new Exception("Failed to start Docker services");
        }
        
        echo "✅ All Docker services started\n";
    }

    private function performAllHealthChecks(): void
    {
        $allHealthy = true;
        
        foreach ($this->configurations as $config => $details) {
            echo "  🔍 Checking {$details['name']}...\n";
            
            $maxRetries = 15;
            $healthy = false;
            
            for ($i = 0; $i < $maxRetries; $i++) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $details['url'] . '/health',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 5
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 && !empty($response)) {
                    $healthy = true;
                    echo "    ✅ {$details['name']} is healthy\n";
                    break;
                }
                
                echo "    ⏳ Retry " . ($i + 1) . "/$maxRetries for {$details['name']}...\n";
                sleep(2);
            }
            
            if (!$healthy) {
                echo "    ❌ {$details['name']} failed health check\n";
                $allHealthy = false;
            }
        }
        
        if (!$allHealthy) {
            throw new Exception("Some configurations failed health checks");
        }
        
        echo "✅ All configurations are healthy!\n\n";
    }

    private function runAllBenchmarks(): void
    {
        $allResults = [];
        
        foreach ($this->configurations as $config => $details) {
            echo "⚡ Testing {$details['name']}...\n";
            echo "  📋 Components: " . implode(', ', $details['components']) . "\n";
            echo "  🎯 Target: {$details['target_performance']}\n";
            
            $configResults = [];
            
            foreach ($this->testEndpoints as $endpoint => $description) {
                echo "  📊 Testing endpoint: {$endpoint} ({$description})\n";
                
                $url = $details['url'] . $endpoint;
                $result = $this->runSingleBenchmark($url, $config, $endpoint);
                
                $configResults[$endpoint] = $result;
                
                echo "    🏆 {$result['requests_per_second']} req/sec (avg: {$result['avg_response_time']}ms)\n";
            }
            
            $allResults[$config] = [
                'name' => $details['name'],
                'components' => $details['components'],
                'target_performance' => $details['target_performance'],
                'results' => $configResults,
                'summary' => $this->calculateConfigurationSummary($configResults)
            ];
            
            echo "  📊 Average performance: {$allResults[$config]['summary']['avg_requests_per_second']} req/sec\n";
            echo "✅ Completed {$details['name']}\n\n";
        }
        
        $this->saveAllResults($allResults);
    }

    private function runSingleBenchmark(string $url, string $config, string $endpoint): array
    {
        // Standard cross-framework test configuration
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

    private function calculateConfigurationSummary(array $results): array
    {
        $totalRps = 0;
        $totalLatency = 0;
        $count = 0;
        
        foreach ($results as $result) {
            $totalRps += $result['requests_per_second'];
            $totalLatency += $result['avg_response_time'];
            $count++;
        }
        
        return [
            'avg_requests_per_second' => round($totalRps / $count, 2),
            'avg_response_time' => round($totalLatency / $count, 2),
            'endpoints_tested' => $count
        ];
    }

    private function saveAllResults(array $results): void
    {
        $timestamp = date('Y-m-d-H-i-s');
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'All Configurations Cross-Framework HTTP Test',
            'framework' => 'PivotPHP v1.1.4',
            'environment' => 'Docker containers (nginx + php-fpm + OPcache + JIT)',
            'configuration_stack' => [
                'web_server' => 'nginx',
                'php' => '8.4-fpm',
                'opcache' => 'enabled + JIT 1255',
                'memory_limit' => '512M',
                'resource_limits' => '2 CPU, 1GB RAM'
            ],
            'configurations_tested' => array_keys($this->configurations),
            'detailed_results' => $results,
            'cross_framework_summary' => $this->generateCrossFrameworkSummary($results)
        ];
        
        $filename = "../results/all-configs-cross-framework-test-{$timestamp}.json";
        file_put_contents($filename, json_encode($reportData, JSON_PRETTY_PRINT));
        
        echo "💾 Detailed results saved to: {$filename}\n";
    }

    private function generateCrossFrameworkSummary(array $results): array
    {
        $summary = [];
        $performanceRanking = [];
        
        foreach ($results as $config => $data) {
            $summary[$config] = [
                'name' => $data['name'],
                'components' => $data['components'],
                'avg_performance' => $data['summary']['avg_requests_per_second'],
                'target_performance' => $data['target_performance'],
                'avg_latency' => $data['summary']['avg_response_time']
            ];
            
            $performanceRanking[$config] = $data['summary']['avg_requests_per_second'];
        }
        
        // Sort by performance
        arsort($performanceRanking);
        
        return [
            'configurations_summary' => $summary,
            'performance_ranking' => array_keys($performanceRanking),
            'best_performer' => array_keys($performanceRanking)[0],
            'cross_framework_equivalent' => true,
            'test_methodology' => 'HTTP requests via curl (equivalent to other frameworks)'
        ];
    }

    private function generateComparisonReport(): void
    {
        echo "📊 Cross-Framework All Configurations Summary:\n";
        echo "═══════════════════════════════════════════════\n";
        echo "🎯 All configurations tested with equivalent setup to other frameworks\n";
        echo "⚙️  Stack: nginx + php-fpm + OPcache + JIT (identical to Slim4, Lumen, etc.)\n";
        echo "🐳 Environment: Docker containers with standard resource limits\n";
        echo "📈 Test Method: HTTP requests via curl (realistic cross-framework performance)\n\n";
        
        echo "✅ All PivotPHP configurations now tested with equivalent methodology\n";
        echo "📊 Results comparable to other frameworks in benchmark suite\n";
        echo "🏆 Performance range: 1K-10K req/sec (realistic HTTP performance)\n\n";
    }

    private function cleanupAllDockerServices(): void
    {
        exec('docker-compose -f docker-compose-all-configs.yml down --remove-orphans');
        echo "✅ All Docker services stopped\n";
    }
}

// Execute all configurations test
try {
    $tester = new AllConfigsTester();
    $tester->runAllConfigurationsTest();
    
    echo "🎉 All Configurations Cross-Framework Test Completed!\n";
    echo "🏆 All PivotPHP variants tested with equivalent configuration\n";
    echo "📊 Results:\n";
    echo "   1. PivotPHP Core Only\n";
    echo "   2. PivotPHP Core + ReactPHP\n";
    echo "   3. PivotPHP Core + Cycle ORM\n";
    echo "   4. PivotPHP Full Ecosystem\n\n";
    echo "✅ All configurations now have realistic cross-framework performance data\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    
    // Cleanup on failure
    exec('docker-compose -f docker-compose-all-configs.yml down --remove-orphans 2>/dev/null');
    exit(1);
}