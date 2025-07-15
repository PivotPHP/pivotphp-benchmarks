<?php

declare(strict_types=1);

/**
 * Script simplificado para execução de benchmarks científicos - Fase 1
 * 
 * Versão simplificada focando apenas nos testes que funcionam:
 * - Health check
 * - JSON GET endpoints
 * - Análise estatística básica
 */

use PivotPHP\Benchmark\Phase1\{
    DataGenerator,
    IntegrityValidator,
    HttpBenchmark,
    MetricsCollector,
    StatisticalAnalyzer
};

require_once __DIR__ . '/../vendor/autoload.php';

class SimpleBenchmarkRunner
{
    private array $config;
    private array $seeds;
    private HttpBenchmark $httpBenchmark;
    private MetricsCollector $metricsCollector;
    private array $results = [];

    public function __construct()
    {
        $this->loadConfiguration();
        $this->initializeComponents();
    }

    private function loadConfiguration(): void
    {
        $this->config = json_decode(file_get_contents(__DIR__ . '/../config/benchmark.json'), true)['phase1_config'];
        $this->seeds = json_decode(file_get_contents(__DIR__ . '/../config/seeds.json'), true)['deterministic_seeds'];
        
        // Configurar seed global
        mt_srand($this->seeds['primary_seed']);
    }

    private function initializeComponents(): void
    {
        $validator = new IntegrityValidator($this->config);
        $this->httpBenchmark = new HttpBenchmark($this->config, $validator);
        $this->metricsCollector = new MetricsCollector($this->config);
    }

    /**
     * Executa suite simplificada de benchmarks
     */
    public function runSimple(): array
    {
        echo "🚀 BENCHMARK CIENTÍFICO SIMPLIFICADO - FASE 1\n";
        echo "=============================================\n\n";

        $startTime = microtime(true);
        
        // 1. Benchmark de health check
        $this->benchmarkHealthCheck();
        
        // 2. Benchmarks de JSON (GET apenas)
        $this->benchmarkJsonScenarios();
        
        // 3. Gerar relatório final
        $report = $this->generateSimpleReport();
        
        $totalTime = microtime(true) - $startTime;
        
        echo "\n✅ BENCHMARK SIMPLIFICADO CONCLUÍDO!\n";
        echo "Tempo total: {$totalTime}s\n";
        echo "Resultados salvos em: results/\n\n";

        return $report;
    }

    /**
     * Benchmark do health check
     */
    private function benchmarkHealthCheck(): void
    {
        echo "🏥 Benchmark Health Check...\n";
        
        $this->metricsCollector->startCollection();
        
        $result = $this->httpBenchmark->runBenchmark('http://localhost:8080/health');
        
        $systemMetrics = $this->metricsCollector->stopCollection();
        $result['system_metrics'] = $systemMetrics;
        
        $this->results['health_check'] = $result;
        
        echo "  Throughput: {$result['analysis']['throughput']['requests_per_second']} req/s\n";
        echo "  Latência P95: {$result['analysis']['latency']['p95_ms']} ms\n\n";
    }

    /**
     * Benchmarks dos cenários JSON (GET apenas)
     */
    private function benchmarkJsonScenarios(): void
    {
        echo "📊 Benchmarks JSON Scenarios (GET)...\n";
        
        $scenarios = ['small', 'medium', 'large', 'variable'];
        
        foreach ($scenarios as $scenario) {
            echo "  Cenário: $scenario\n";
            
            $this->metricsCollector->startCollection();
            
            $result = $this->httpBenchmark->runBenchmark("http://localhost:8080/json/$scenario");
            
            $systemMetrics = $this->metricsCollector->stopCollection();
            $result['system_metrics'] = $systemMetrics;
            $result['scenario'] = $scenario;
            
            $this->results['json_scenarios'][$scenario] = $result;
            
            echo "    Throughput: {$result['analysis']['throughput']['requests_per_second']} req/s\n";
            echo "    Latência média: {$result['analysis']['latency']['mean_ms']} ms\n";
        }
        
        echo "\n";
    }

    /**
     * Gera relatório final simplificado
     */
    private function generateSimpleReport(): array
    {
        echo "📋 Gerando relatório simplificado...\n";
        
        $timestamp = date('Y-m-d-H-i-s');
        
        $report = [
            'report_metadata' => [
                'phase' => 1,
                'timestamp' => microtime(true),
                'date' => date('c'),
                'framework' => 'pivotphp-core',
                'benchmark_version' => '1.0.0-simple',
                'environment' => $this->getEnvironmentInfo()
            ],
            'results' => $this->results,
            'summary' => $this->generateSummary()
        ];
        
        // Salvar relatório em JSON
        $jsonPath = __DIR__ . "/../results/phase1-simple-benchmark-$timestamp.json";
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));
        
        // Salvar relatório em Markdown
        $markdownPath = __DIR__ . "/../results/phase1-simple-benchmark-$timestamp.md";
        file_put_contents($markdownPath, $this->generateMarkdownReport($report));
        
        echo "  Relatório JSON: $jsonPath\n";
        echo "  Relatório MD: $markdownPath\n";
        
        return $report;
    }

    /**
     * Obtém informações do ambiente
     */
    private function getEnvironmentInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'operating_system' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'],
            'jit_enabled' => function_exists('opcache_get_status') && (opcache_get_status()['jit']['enabled'] ?? false),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'benchmark_seed' => $this->seeds['primary_seed']
        ];
    }

    /**
     * Gera resumo dos resultados
     */
    private function generateSummary(): array
    {
        $summary = [
            'total_tests_executed' => 0,
            'successful_tests' => 0,
            'average_throughput' => 0,
            'average_latency' => 0
        ];
        
        $throughputs = [];
        $latencies = [];
        
        foreach ($this->results as $category => $categoryResults) {
            if (is_array($categoryResults)) {
                foreach ($categoryResults as $result) {
                    if (isset($result['analysis'])) {
                        $summary['total_tests_executed']++;
                        
                        if ($result['analysis']['success_rate_percent'] > 95) {
                            $summary['successful_tests']++;
                        }
                        
                        $throughputs[] = $result['analysis']['throughput']['requests_per_second'];
                        $latencies[] = $result['analysis']['latency']['mean_ms'];
                    }
                }
            } elseif (isset($categoryResults['analysis'])) {
                $summary['total_tests_executed']++;
                
                if ($categoryResults['analysis']['success_rate_percent'] > 95) {
                    $summary['successful_tests']++;
                }
                
                $throughputs[] = $categoryResults['analysis']['throughput']['requests_per_second'];
                $latencies[] = $categoryResults['analysis']['latency']['mean_ms'];
            }
        }
        
        if (!empty($throughputs)) {
            $summary['average_throughput'] = array_sum($throughputs) / count($throughputs);
        }
        
        if (!empty($latencies)) {
            $summary['average_latency'] = array_sum($latencies) / count($latencies);
        }
        
        return $summary;
    }

    /**
     * Gera relatório em formato Markdown
     */
    private function generateMarkdownReport(array $report): string
    {
        $md = "# PivotPHP Core Benchmark Report - Phase 1 (Simplified)\n\n";
        $md .= "**Generated:** " . $report['report_metadata']['date'] . "\n";
        $md .= "**Framework:** " . $report['report_metadata']['framework'] . "\n";
        $md .= "**Environment:** PHP " . $report['report_metadata']['environment']['php_version'] . "\n\n";
        
        $md .= "## Summary\n\n";
        $summary = $report['summary'];
        $md .= "- **Total Tests:** " . $summary['total_tests_executed'] . "\n";
        $md .= "- **Average Throughput:** " . round($summary['average_throughput'], 2) . " req/s\n";
        $md .= "- **Average Latency:** " . round($summary['average_latency'], 3) . " ms\n";
        $md .= "- **Success Rate:** " . round(($summary['successful_tests'] / $summary['total_tests_executed']) * 100, 2) . "%\n\n";
        
        $md .= "## Test Results\n\n";
        
        // Health Check
        if (isset($report['results']['health_check'])) {
            $health = $report['results']['health_check'];
            $md .= "### Health Check\n";
            $md .= "- **Throughput:** " . round($health['analysis']['throughput']['requests_per_second'], 2) . " req/s\n";
            $md .= "- **Latency P50:** " . round($health['analysis']['latency']['p50_ms'] ?? $health['analysis']['latency']['mean_ms'], 3) . " ms\n";
            $md .= "- **Latency P95:** " . round($health['analysis']['latency']['p95_ms'], 3) . " ms\n\n";
        }
        
        // JSON Scenarios
        if (isset($report['results']['json_scenarios'])) {
            $md .= "### JSON Scenarios\n\n";
            foreach ($report['results']['json_scenarios'] as $scenario => $result) {
                $md .= "#### $scenario\n";
                $md .= "- **Throughput:** " . round($result['analysis']['throughput']['requests_per_second'], 2) . " req/s\n";
                $md .= "- **Latency Mean:** " . round($result['analysis']['latency']['mean_ms'], 3) . " ms\n";
                $md .= "- **Latency P95:** " . round($result['analysis']['latency']['p95_ms'], 3) . " ms\n\n";
            }
        }
        
        return $md;
    }
}

// Verificar se está sendo executado diretamente
if (php_sapi_name() === 'cli') {
    try {
        $runner = new SimpleBenchmarkRunner();
        $report = $runner->runSimple();
        
        echo "\n🎉 Benchmark simplificado da Fase 1 concluído com sucesso!\n";
        exit(0);
        
    } catch (Throwable $e) {
        echo "\n❌ Erro durante benchmark: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}