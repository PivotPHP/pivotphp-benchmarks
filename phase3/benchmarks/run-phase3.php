<?php

declare(strict_types=1);

/**
 * Benchmark Runner - Fase 3: PivotPHP vs Slim 4
 * 
 * Executa comparação direta entre PivotPHP v1.1.2 e Slim 4
 * com os mesmos endpoints e metodologia científica
 */

require_once __DIR__ . '/../vendor/autoload.php';

class Phase3ComparativeBenchmark
{
    private const ITERATIONS = 200;
    private const WARMUP = 50;
    
    private array $frameworks = [
        'pivotphp' => [
            'name' => 'PivotPHP v1.1.2',
            'url' => 'http://localhost:8083',
            'version' => '1.1.2'
        ],
        'slim4' => [
            'name' => 'Slim 4',
            'url' => 'http://localhost:8084', 
            'version' => '4.14'
        ]
    ];
    
    private array $results = [];
    private array $comparison = [];
    
    public function __construct()
    {
        echo "🚀 BENCHMARK FASE 3 - COMPARATIVO DIRETO\n";
        echo "========================================\n";
        echo "PivotPHP v1.1.2 vs Slim 4\n\n";
    }
    
    public function run(): void
    {
        // Verificar se ambos servidores estão funcionando
        if (!$this->checkServers()) {
            echo "❌ Nem todos os servidores estão disponíveis\n";
            return;
        }
        
        echo "📊 Executando benchmarks comparativos...\n\n";
        
        // Executar testes para ambos frameworks
        foreach ($this->frameworks as $key => $framework) {
            echo "🔥 Testando {$framework['name']}\n";
            echo "=" . str_repeat("=", strlen($framework['name']) + 10) . "\n\n";
            
            $this->results[$key] = $this->runFrameworkTests($key, $framework);
            
            echo "\n";
        }
        
        // Análise comparativa
        $this->performComparison();
        
        // Salvar resultados
        $this->saveResults();
        
        // Gerar relatório
        $this->generateReport();
    }
    
    private function checkServers(): bool
    {
        $allAvailable = true;
        
        foreach ($this->frameworks as $key => $framework) {
            echo "🔍 Verificando {$framework['name']}...";
            
            try {
                $response = $this->makeRequest($framework['url'] . '/health');
                $data = json_decode($response, true);
                
                if ($data && $data['status'] === 'healthy') {
                    echo " ✅ OK\n";
                } else {
                    echo " ❌ Error\n";
                    $allAvailable = false;
                }
            } catch (Exception $e) {
                echo " ❌ " . $e->getMessage() . "\n";
                $allAvailable = false;
            }
        }
        
        echo "\n";
        return $allAvailable;
    }
    
    private function runFrameworkTests(string $frameworkKey, array $framework): array
    {
        $results = [];
        
        // Warm-up
        $this->warmUpFramework($framework['url']);
        
        // Testes de alta performance
        $results['high_performance'] = $this->runHighPerformanceTests($framework['url']);
        
        // Testes de database 
        $results['database'] = $this->runDatabaseTests($framework['url']);
        
        // Testes de middleware
        $results['middleware'] = $this->runMiddlewareTests($framework['url']);
        
        return $results;
    }
    
    private function warmUpFramework(string $baseUrl): void
    {
        echo "🔥 Aquecimento (" . self::WARMUP . " requests)...";
        
        for ($i = 0; $i < self::WARMUP; $i++) {
            try {
                $this->makeRequest($baseUrl . '/health');
                if ($i % 10 === 0) echo ".";
            } catch (Exception $e) {
                echo "!";
            }
        }
        
        echo " Concluído!\n\n";
        sleep(1);
    }
    
    private function runHighPerformanceTests(string $baseUrl): array
    {
        echo "⚡ High Performance Tests\n";
        
        $tests = [
            'health' => '/health',
            'ultra_fast' => '/ultra-fast', 
            'json_small' => '/performance/json/small?iterations=1000',
            'json_medium' => '/performance/json/medium?iterations=1000',
            'json_large' => '/performance/json/large?iterations=500',
            'throughput' => '/performance/throughput?operations=5000',
            'pool_stats' => '/performance/pool-stats',
            'metadata' => '/meta'
        ];
        
        $results = [];
        
        foreach ($tests as $testName => $endpoint) {
            echo "  📊 $testName: ";
            $results[$testName] = $this->benchmarkEndpoint($baseUrl . $endpoint, $testName);
            echo "\n";
        }
        
        return $results;
    }
    
    private function runDatabaseTests(string $baseUrl): array
    {
        echo "🗄️ Database Tests\n";
        
        $tests = [
            'db_health' => '/db/health',
            'list_users' => '/db/users?limit=100',
            'complex_query' => '/db/complex-query',
            'db_stats' => '/db/stats'
        ];
        
        $results = [];
        
        foreach ($tests as $testName => $endpoint) {
            echo "  📊 $testName: ";
            $results[$testName] = $this->benchmarkEndpoint($baseUrl . $endpoint, $testName);
            echo "\n";
        }
        
        return $results;
    }
    
    private function runMiddlewareTests(string $baseUrl): array
    {
        echo "🔄 Middleware Tests\n";
        
        $tests = [
            'stack_test' => '/middleware/stack',
            'cors_test' => '/middleware/cors',
            'auth_test' => '/middleware/auth'
        ];
        
        $results = [];
        
        foreach ($tests as $testName => $endpoint) {
            echo "  📊 $testName: ";
            $results[$testName] = $this->benchmarkEndpoint($baseUrl . $endpoint, $testName);
            echo "\n";
        }
        
        return $results;
    }
    
    private function benchmarkEndpoint(string $url, string $testName): array
    {
        $responseTimes = [];
        $successCount = 0;
        $errorCount = 0;
        
        $startBenchmark = microtime(true);
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $startTime = microtime(true);
            
            try {
                $response = $this->makeRequest($url);
                $endTime = microtime(true);
                
                $responseTime = ($endTime - $startTime) * 1000; // ms
                $responseTimes[] = $responseTime;
                $successCount++;
                
                if ($i % 50 === 0) {
                    echo ".";
                }
                
            } catch (Exception $e) {
                $errorCount++;
                echo "!";
            }
        }
        
        $endBenchmark = microtime(true);
        
        if (empty($responseTimes)) {
            echo " ❌ All failed";
            return [
                'success_rate' => 0,
                'error_rate' => 100,
                'avg_response_time_ms' => 0,
                'throughput_req_per_sec' => 0
            ];
        }
        
        sort($responseTimes);
        $count = count($responseTimes);
        
        $result = [
            'iterations' => self::ITERATIONS,
            'successful_requests' => $successCount,
            'failed_requests' => $errorCount,
            'success_rate_percent' => ($successCount / self::ITERATIONS) * 100,
            'error_rate_percent' => ($errorCount / self::ITERATIONS) * 100,
            'avg_response_time_ms' => array_sum($responseTimes) / $count,
            'min_response_time_ms' => min($responseTimes),
            'max_response_time_ms' => max($responseTimes),
            'p50_response_time_ms' => $responseTimes[intval($count * 0.5)],
            'p95_response_time_ms' => $responseTimes[intval($count * 0.95)],
            'p99_response_time_ms' => $responseTimes[intval($count * 0.99)],
            'throughput_req_per_sec' => $successCount / ($endBenchmark - $startBenchmark),
            'avg_memory_usage' => 10 * 1024 * 1024 // Estimado
        ];
        
        echo " ✅ " . number_format($result['throughput_req_per_sec'], 1) . " req/s";
        
        return $result;
    }
    
    private function performComparison(): void
    {
        echo "🔍 ANÁLISE COMPARATIVA\n";
        echo "=====================\n\n";
        
        $pivotData = $this->results['pivotphp'] ?? [];
        $slimData = $this->results['slim4'] ?? [];
        
        // Comparar cada categoria
        foreach (['high_performance', 'database', 'middleware'] as $category) {
            if (!isset($pivotData[$category]) || !isset($slimData[$category])) {
                continue;
            }
            
            echo "📊 " . ucfirst(str_replace('_', ' ', $category)) . ":\n";
            
            foreach ($pivotData[$category] as $testName => $pivotResult) {
                if (!isset($slimData[$category][$testName])) {
                    continue;
                }
                
                $slimResult = $slimData[$category][$testName];
                $this->compareTestResults($testName, $pivotResult, $slimResult);
            }
            
            echo "\n";
        }
    }
    
    private function compareTestResults(string $testName, array $pivotResult, array $slimResult): void
    {
        $pivotThroughput = $pivotResult['throughput_req_per_sec'] ?? 0;
        $slimThroughput = $slimResult['throughput_req_per_sec'] ?? 0;
        
        $pivotLatency = $pivotResult['p50_response_time_ms'] ?? 0;
        $slimLatency = $slimResult['p50_response_time_ms'] ?? 0;
        
        // Calcular diferenças
        $throughputDiff = $slimThroughput > 0 ? 
            (($pivotThroughput - $slimThroughput) / $slimThroughput) * 100 : 0;
            
        $latencyDiff = $slimLatency > 0 ? 
            (($slimLatency - $pivotLatency) / $slimLatency) * 100 : 0;
        
        // Determinar vencedor
        $throughputWinner = $pivotThroughput > $slimThroughput ? 'PivotPHP' : 'Slim 4';
        $latencyWinner = $pivotLatency < $slimLatency ? 'PivotPHP' : 'Slim 4';
        
        echo "  🎯 $testName:\n";
        echo "     Throughput: PivotPHP " . number_format($pivotThroughput, 1) . " vs Slim4 " . number_format($slimThroughput, 1) . " req/s";
        echo " (" . ($throughputDiff >= 0 ? "+" : "") . number_format($throughputDiff, 1) . "% - $throughputWinner wins)\n";
        echo "     Latência P50: PivotPHP " . number_format($pivotLatency, 2) . " vs Slim4 " . number_format($slimLatency, 2) . "ms";
        echo " (" . ($latencyDiff >= 0 ? "+" : "") . number_format($latencyDiff, 1) . "% - $latencyWinner wins)\n";
        
        // Armazenar comparação
        $this->comparison[$testName] = [
            'pivotphp' => $pivotResult,
            'slim4' => $slimResult,
            'throughput_diff_percent' => $throughputDiff,
            'latency_diff_percent' => $latencyDiff,
            'throughput_winner' => $throughputWinner,
            'latency_winner' => $latencyWinner
        ];
    }
    
    private function makeRequest(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'header' => [
                    'User-Agent: PivotPHP-Benchmark-Phase3',
                    'Accept: application/json'
                ]
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to fetch $url");
        }
        
        return $response;
    }
    
    private function saveResults(): void
    {
        $timestamp = date('Y-m-d-H-i-s');
        $filename = __DIR__ . "/../results/phase3-comparative-benchmark-{$timestamp}.json";
        
        $report = [
            'report_metadata' => [
                'phase' => 3,
                'comparison' => 'PivotPHP v1.1.2 vs Slim 4',
                'timestamp' => time(),
                'date' => date('c'),
                'iterations_per_test' => self::ITERATIONS,
                'warmup_iterations' => self::WARMUP
            ],
            'frameworks' => $this->frameworks,
            'results' => $this->results,
            'comparison' => $this->comparison,
            'summary' => $this->generateSummary()
        ];
        
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        echo "💾 Resultados salvos em: $filename\n\n";
    }
    
    private function generateSummary(): array
    {
        $pivotWins = 0;
        $slimWins = 0;
        $totalTests = count($this->comparison);
        
        foreach ($this->comparison as $test) {
            if ($test['throughput_winner'] === 'PivotPHP') {
                $pivotWins++;
            } else {
                $slimWins++;
            }
        }
        
        return [
            'total_tests' => $totalTests,
            'pivotphp_wins' => $pivotWins,
            'slim4_wins' => $slimWins,
            'pivotphp_win_rate' => $totalTests > 0 ? ($pivotWins / $totalTests) * 100 : 0,
            'slim4_win_rate' => $totalTests > 0 ? ($slimWins / $totalTests) * 100 : 0,
            'overall_winner' => $pivotWins > $slimWins ? 'PivotPHP' : 'Slim 4'
        ];
    }
    
    private function generateReport(): void
    {
        $summary = $this->generateSummary();
        
        echo "📋 RELATÓRIO FINAL - FASE 3\n";
        echo "===========================\n\n";
        
        echo "🎯 **RESUMO EXECUTIVO**\n";
        echo "- Comparação: PivotPHP v1.1.2 vs Slim 4\n";
        echo "- Testes executados: {$summary['total_tests']}\n";
        echo "- Iterações por teste: " . self::ITERATIONS . "\n\n";
        
        echo "🏆 **RESULTADOS GERAIS**\n";
        echo "- PivotPHP venceu: {$summary['pivotphp_wins']} testes (" . number_format($summary['pivotphp_win_rate'], 1) . "%)\n";
        echo "- Slim 4 venceu: {$summary['slim4_wins']} testes (" . number_format($summary['slim4_win_rate'], 1) . "%)\n";
        echo "- **Vencedor geral: {$summary['overall_winner']}**\n\n";
        
        echo "📊 **DESTAQUES POR TESTE**\n";
        foreach ($this->comparison as $testName => $data) {
            $winner = $data['throughput_winner'];
            $diff = abs($data['throughput_diff_percent']);
            echo "- $testName: $winner (+{$diff}%)\n";
        }
        
        echo "\n✅ **CONCLUSÃO**\n";
        if ($summary['overall_winner'] === 'PivotPHP') {
            echo "PivotPHP v1.1.2 demonstra performance superior ao Slim 4\n";
            echo "em " . number_format($summary['pivotphp_win_rate'], 1) . "% dos testes realizados.\n";
        } else {
            echo "Slim 4 demonstra performance superior ao PivotPHP v1.1.2\n";
            echo "em " . number_format($summary['slim4_win_rate'], 1) . "% dos testes realizados.\n";
        }
        
        echo "\n🎯 Fase 3 - Benchmark comparativo concluído com sucesso!\n";
    }
}

// Executar benchmark
$benchmark = new Phase3ComparativeBenchmark();
$benchmark->run();