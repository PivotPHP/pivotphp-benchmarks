<?php

declare(strict_types=1);

/**
 * Script principal para execução de benchmarks da Fase 2
 * 
 * Executa benchmarks comparativos entre:
 * - PivotPHP Core (baseline da Fase 1)
 * - PivotPHP + Cycle ORM
 * - PivotPHP + ReactPHP
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

class Phase2BenchmarkRunner
{
    private array $config;
    private array $baseline;
    private Client $httpClient;
    private array $results = [];

    public function __construct()
    {
        $this->loadConfiguration();
        $this->httpClient = new Client(['timeout' => 30]);
    }

    private function loadConfiguration(): void
    {
        $this->config = json_decode(file_get_contents(__DIR__ . '/../config/benchmark-phase2.json'), true)['phase2_config'];
        
        // Carregar baseline da Fase 1
        $baselineFile = __DIR__ . '/../../phase1/results/phase1-simple-benchmark-2025-07-11-18-35-33.json';
        if (file_exists($baselineFile)) {
            $this->baseline = json_decode(file_get_contents($baselineFile), true);
        } else {
            echo "⚠️ Baseline da Fase 1 não encontrado: $baselineFile\n";
            $this->baseline = [];
        }
    }

    /**
     * Executa suite completa da Fase 2
     */
    public function runComplete(): array
    {
        echo "🚀 INICIANDO BENCHMARKS DA FASE 2\n";
        echo "================================\n\n";

        $startTime = microtime(true);

        // 1. Verificar status dos serviços
        $this->checkServices();

        // 2. Benchmarks ORM
        if ($this->isServiceAvailable('http://localhost:8081/health')) {
            echo "📊 Executando benchmarks ORM...\n";
            $this->benchmarkORM();
        } else {
            echo "⚠️ Serviço ORM não disponível\n";
        }

        // 3. Benchmarks ReactPHP
        if ($this->isServiceAvailable('http://localhost:8082/health')) {
            echo "📊 Executando benchmarks ReactPHP...\n";
            $this->benchmarkReactPHP();
        } else {
            echo "⚠️ Serviço ReactPHP não disponível\n";
        }

        // 4. Benchmarks High Performance
        if ($this->isServiceAvailable('http://localhost:8083/health')) {
            echo "🚀 Executando benchmarks High Performance...\n";
            $this->benchmarkHighPerformance();
        } else {
            echo "⚠️ Serviço High Performance não disponível\n";
        }

        // 4. Análise comparativa
        $this->performComparativeAnalysis();

        // 5. Gerar relatório final
        $report = $this->generateFinalReport();

        $totalTime = microtime(true) - $startTime;
        echo "\n✅ BENCHMARKS DA FASE 2 CONCLUÍDOS!\n";
        echo "Tempo total: {$totalTime}s\n";

        return $report;
    }

    /**
     * Verifica status dos serviços
     */
    private function checkServices(): void
    {
        echo "🔍 Verificando serviços...\n";

        $services = [
            'ORM' => 'http://localhost:8081/health',
            'ReactPHP' => 'http://localhost:8082/health',
            'High Performance' => 'http://localhost:8083/health'
        ];

        foreach ($services as $name => $url) {
            if ($this->isServiceAvailable($url)) {
                echo "  ✅ $name: Disponível\n";
            } else {
                echo "  ❌ $name: Indisponível\n";
            }
        }
        echo "\n";
    }

    /**
     * Benchmarks específicos do ORM
     */
    private function benchmarkORM(): void
    {
        $ormTests = [
            'health' => '/health',
            'users_list' => '/orm/users?limit=100',
            'complex_join' => '/orm/complex-join',
            'lazy_loading' => '/orm/lazy-loading',
            'eager_loading' => '/orm/eager-loading',
            'hydration_100' => '/orm/hydration/100',
            'hydration_1000' => '/orm/hydration/1000',
            'stats' => '/orm/stats'
        ];

        foreach ($ormTests as $testName => $endpoint) {
            echo "  🔄 Teste ORM: $testName\n";
            $this->results['orm'][$testName] = $this->benchmarkEndpoint('http://localhost:8081' . $endpoint, 100);
        }
    }

    /**
     * Benchmarks específicos do ReactPHP
     */
    private function benchmarkReactPHP(): void
    {
        $reactTests = [
            'health' => '/health',
            'async_timer' => '/async/timer?delay=0.1',
            'event_loop_timers' => '/event-loop/timers',
            'concurrent_requests' => '/concurrency/requests?iterations=100',
            'async_parallel' => '/async/parallel'
        ];

        foreach ($reactTests as $testName => $endpoint) {
            echo "  🔄 Teste ReactPHP: $testName\n";
            $this->results['reactphp'][$testName] = $this->benchmarkEndpoint('http://localhost:8082' . $endpoint, 100);
        }
    }

    /**
     * Benchmarks específicos de Alta Performance
     */
    private function benchmarkHighPerformance(): void
    {
        $highPerfTests = [
            'health' => '/health',
            'ultra_fast' => '/ultra-fast',
            'json_small' => '/performance/json/small?iterations=1000',
            'json_medium' => '/performance/json/medium?iterations=1000',
            'json_large' => '/performance/json/large?iterations=500',
            'throughput' => '/performance/throughput?operations=5000',
            'pool_stats' => '/performance/pool-stats',
            'metadata' => '/meta'
        ];

        foreach ($highPerfTests as $testName => $endpoint) {
            echo "  🚀 Teste High Performance: $testName\n";
            // Usar mais iterações para alta performance pois deve ser mais rápido
            $iterations = str_contains($testName, 'throughput') ? 50 : 200;
            $this->results['high_performance'][$testName] = $this->benchmarkEndpoint('http://localhost:8083' . $endpoint, $iterations);
        }
    }

    /**
     * Benchmarka um endpoint específico
     */
    private function benchmarkEndpoint(string $url, int $iterations = 100): array
    {
        $results = [];
        $errors = 0;

        // Warm-up
        for ($i = 0; $i < 10; $i++) {
            try {
                $this->httpClient->get($url);
            } catch (\Throwable $e) {
                // Ignorar erros de warm-up
            }
        }

        // Medições
        for ($i = 0; $i < $iterations; $i++) {
            try {
                $startTime = microtime(true);
                $response = $this->httpClient->get($url);
                $endTime = microtime(true);

                $results[] = [
                    'response_time_ms' => ($endTime - $startTime) * 1000,
                    'status_code' => $response->getStatusCode(),
                    'response_size' => strlen($response->getBody()->getContents()),
                    'memory_usage' => memory_get_usage(true)
                ];

            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return $this->analyzeResults($results, $errors, $iterations);
    }

    /**
     * Analisa resultados de um benchmark
     */
    private function analyzeResults(array $results, int $errors, int $iterations): array
    {
        if (empty($results)) {
            return [
                'success_rate' => 0,
                'error_rate' => 100,
                'avg_response_time_ms' => 0,
                'throughput_req_per_sec' => 0
            ];
        }

        $responseTimes = array_column($results, 'response_time_ms');
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $successRate = (count($results) / $iterations) * 100;

        sort($responseTimes);
        $p50 = $responseTimes[intval(count($responseTimes) * 0.5)] ?? 0;
        $p95 = $responseTimes[intval(count($responseTimes) * 0.95)] ?? 0;
        $p99 = $responseTimes[intval(count($responseTimes) * 0.99)] ?? 0;

        return [
            'iterations' => $iterations,
            'successful_requests' => count($results),
            'failed_requests' => $errors,
            'success_rate_percent' => $successRate,
            'error_rate_percent' => ($errors / $iterations) * 100,
            'avg_response_time_ms' => $avgResponseTime,
            'min_response_time_ms' => min($responseTimes),
            'max_response_time_ms' => max($responseTimes),
            'p50_response_time_ms' => $p50,
            'p95_response_time_ms' => $p95,
            'p99_response_time_ms' => $p99,
            'throughput_req_per_sec' => count($results) > 0 ? 1000 / $avgResponseTime : 0,
            'avg_memory_usage' => !empty($results) ? array_sum(array_column($results, 'memory_usage')) / count($results) : 0
        ];
    }

    /**
     * Análise comparativa entre extensões
     */
    private function performComparativeAnalysis(): void
    {
        echo "📈 Realizando análise comparativa...\n";

        if (empty($this->baseline)) {
            echo "  ⚠️ Baseline da Fase 1 não disponível\n";
            return;
        }

        // Comparar com baseline da Fase 1
        $this->results['comparative'] = [
            'baseline_comparison' => $this->compareWithBaseline(),
            'extension_comparison' => $this->compareExtensions(),
            'overhead_analysis' => $this->analyzeOverhead()
        ];
    }

    /**
     * Compara com baseline da Fase 1
     */
    private function compareWithBaseline(): array
    {
        if (empty($this->baseline['summary'])) {
            return ['error' => 'Baseline summary not available'];
        }

        $baselineThroughput = $this->baseline['summary']['average_throughput'] ?? 0;
        $baselineLatency = $this->baseline['summary']['average_latency'] ?? 0;

        $comparison = [];

        // Comparar ORM
        if (isset($this->results['orm']['health'])) {
            $ormThroughput = $this->results['orm']['health']['throughput_req_per_sec'] ?? 0;
            $ormLatency = $this->results['orm']['health']['avg_response_time_ms'] ?? 0;

            $comparison['orm_vs_baseline'] = [
                'throughput_change_percent' => $baselineThroughput > 0 ? (($ormThroughput - $baselineThroughput) / $baselineThroughput) * 100 : 0,
                'latency_change_percent' => $baselineLatency > 0 ? (($ormLatency - $baselineLatency) / $baselineLatency) * 100 : 0,
                'performance_impact' => $ormThroughput < $baselineThroughput ? 'negative' : 'positive'
            ];
        }

        // Comparar ReactPHP
        if (isset($this->results['reactphp']['health'])) {
            $reactThroughput = $this->results['reactphp']['health']['throughput_req_per_sec'] ?? 0;
            $reactLatency = $this->results['reactphp']['health']['avg_response_time_ms'] ?? 0;

            $comparison['reactphp_vs_baseline'] = [
                'throughput_change_percent' => $baselineThroughput > 0 ? (($reactThroughput - $baselineThroughput) / $baselineThroughput) * 100 : 0,
                'latency_change_percent' => $baselineLatency > 0 ? (($reactLatency - $baselineLatency) / $baselineLatency) * 100 : 0,
                'performance_impact' => $reactThroughput < $baselineThroughput ? 'negative' : 'positive'
            ];
        }

        return $comparison;
    }

    /**
     * Compara extensões entre si
     */
    private function compareExtensions(): array
    {
        if (!isset($this->results['orm']['health']) || !isset($this->results['reactphp']['health'])) {
            return ['error' => 'Both extensions needed for comparison'];
        }

        $ormThroughput = $this->results['orm']['health']['throughput_req_per_sec'] ?? 0;
        $ormLatency = $this->results['orm']['health']['avg_response_time_ms'] ?? 0;

        $reactThroughput = $this->results['reactphp']['health']['throughput_req_per_sec'] ?? 0;
        $reactLatency = $this->results['reactphp']['health']['avg_response_time_ms'] ?? 0;

        return [
            'winner_throughput' => $ormThroughput > $reactThroughput ? 'orm' : 'reactphp',
            'winner_latency' => $ormLatency < $reactLatency ? 'orm' : 'reactphp',
            'throughput_difference_percent' => $ormThroughput > 0 ? (abs($reactThroughput - $ormThroughput) / $ormThroughput) * 100 : 0,
            'latency_difference_percent' => $ormLatency > 0 ? (abs($reactLatency - $ormLatency) / $ormLatency) * 100 : 0
        ];
    }

    /**
     * Analisa overhead das extensões
     */
    private function analyzeOverhead(): array
    {
        // Implementação básica do overhead
        return [
            'orm_memory_overhead' => 'TBD',
            'reactphp_memory_overhead' => 'TBD',
            'orm_cpu_overhead' => 'TBD',
            'reactphp_cpu_overhead' => 'TBD'
        ];
    }

    /**
     * Gera relatório final da Fase 2
     */
    private function generateFinalReport(): array
    {
        $timestamp = date('Y-m-d-H-i-s');

        $report = [
            'report_metadata' => [
                'phase' => 2,
                'timestamp' => microtime(true),
                'date' => date('c'),
                'extensions_tested' => ['cycle-orm', 'reactphp'],
                'baseline_reference' => !empty($this->baseline)
            ],
            'results' => $this->results,
            'summary' => $this->generateSummary()
        ];

        // Salvar relatório
        $jsonPath = __DIR__ . "/../results/phase2-benchmark-report-$timestamp.json";
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));

        $mdPath = __DIR__ . "/../results/phase2-benchmark-report-$timestamp.md";
        file_put_contents($mdPath, $this->generateMarkdownReport($report));

        echo "  📄 Relatório JSON: $jsonPath\n";
        echo "  📄 Relatório MD: $mdPath\n";

        return $report;
    }

    /**
     * Gera resumo dos resultados
     */
    private function generateSummary(): array
    {
        $summary = [
            'extensions_tested' => [],
            'overall_performance' => 'unknown',
            'best_performer' => 'unknown',
            'recommendations' => []
        ];

        if (isset($this->results['orm'])) {
            $summary['extensions_tested'][] = 'cycle-orm';
        }

        if (isset($this->results['reactphp'])) {
            $summary['extensions_tested'][] = 'reactphp';
        }

        // Análise básica de performance
        if (isset($this->results['comparative']['extension_comparison']['winner_throughput'])) {
            $summary['best_performer'] = $this->results['comparative']['extension_comparison']['winner_throughput'];
        }

        return $summary;
    }

    /**
     * Gera relatório em markdown
     */
    private function generateMarkdownReport(array $report): string
    {
        $md = "# PivotPHP Extensions Benchmark Report - Phase 2\n\n";
        $md .= "**Generated:** " . $report['report_metadata']['date'] . "\n";
        $md .= "**Extensions Tested:** " . implode(', ', $report['report_metadata']['extensions_tested']) . "\n\n";

        $md .= "## Summary\n\n";
        if (isset($report['summary'])) {
            $summary = $report['summary'];
            $md .= "- **Extensions Tested:** " . implode(', ', $summary['extensions_tested']) . "\n";
            $md .= "- **Best Performer:** " . $summary['best_performer'] . "\n";
        }

        $md .= "\n## Results\n\n";
        
        // ORM Results
        if (isset($report['results']['orm'])) {
            $md .= "### Cycle ORM Results\n\n";
            foreach ($report['results']['orm'] as $test => $result) {
                if (isset($result['throughput_req_per_sec'])) {
                    $md .= "- **$test:** " . round($result['throughput_req_per_sec'], 2) . " req/s\n";
                }
            }
            $md .= "\n";
        }

        // ReactPHP Results
        if (isset($report['results']['reactphp'])) {
            $md .= "### ReactPHP Results\n\n";
            foreach ($report['results']['reactphp'] as $test => $result) {
                if (isset($result['throughput_req_per_sec'])) {
                    $md .= "- **$test:** " . round($result['throughput_req_per_sec'], 2) . " req/s\n";
                }
            }
            $md .= "\n";
        }

        return $md;
    }

    /**
     * Verifica se um serviço está disponível
     */
    private function isServiceAvailable(string $url): bool
    {
        try {
            $response = $this->httpClient->get($url, ['timeout' => 5]);
            return $response->getStatusCode() === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

// Executar se chamado diretamente
if (php_sapi_name() === 'cli') {
    try {
        $runner = new Phase2BenchmarkRunner();
        $report = $runner->runComplete();
        
        echo "\n🎉 Benchmarks da Fase 2 concluídos com sucesso!\n";
        exit(0);
        
    } catch (Throwable $e) {
        echo "\n❌ Erro durante benchmarks: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}