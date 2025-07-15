<?php

declare(strict_types=1);

/**
 * Script principal para execução de benchmarks científicos - Fase 1
 * 
 * Executa suite completa de benchmarks do PivotPHP Core com:
 * - Metodologia científica rigorosa
 * - Validação de integridade total
 * - Análise estatística automatizada
 * - Geração de relatórios detalhados
 */

use PivotPHP\Benchmark\Phase1\{
    DataGenerator,
    IntegrityValidator,
    HttpBenchmark,
    MetricsCollector,
    StatisticalAnalyzer
};

require_once __DIR__ . '/../vendor/autoload.php';

class BenchmarkRunner
{
    private array $config;
    private array $seeds;
    private DataGenerator $dataGenerator;
    private IntegrityValidator $validator;
    private HttpBenchmark $httpBenchmark;
    private MetricsCollector $metricsCollector;
    private StatisticalAnalyzer $analyzer;
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
        $this->dataGenerator = new DataGenerator($this->config, $this->seeds);
        $this->validator = new IntegrityValidator($this->config);
        $this->httpBenchmark = new HttpBenchmark($this->config, $this->validator);
        $this->metricsCollector = new MetricsCollector($this->config);
        $this->analyzer = new StatisticalAnalyzer($this->config);
    }

    /**
     * Executa suite completa de benchmarks
     */
    public function runComplete(): array
    {
        echo "🚀 INICIANDO BENCHMARK CIENTÍFICO - FASE 1\n";
        echo "==========================================\n\n";

        $startTime = microtime(true);
        
        // 1. Validar reprodutibilidade
        $this->validateReproducibility();
        
        // 2. Benchmark de health check
        $this->benchmarkHealthCheck();
        
        // 3. Benchmarks de JSON (GET)
        $this->benchmarkJsonScenarios();
        
        // 4. Benchmarks de integridade (POST)
        $this->benchmarkIntegrityScenarios();
        
        // 5. Stress tests
        $this->benchmarkStressTests();
        
        // 6. Análise estatística
        $this->performStatisticalAnalysis();
        
        // 7. Gerar relatório final
        $report = $this->generateFinalReport();
        
        $totalTime = microtime(true) - $startTime;
        
        echo "\n✅ BENCHMARK CONCLUÍDO!\n";
        echo "Tempo total: {$totalTime}s\n";
        echo "Resultados salvos em: results/\n\n";

        return $report;
    }

    /**
     * Valida reprodutibilidade dos geradores
     */
    private function validateReproducibility(): void
    {
        echo "🔍 Validando reprodutibilidade...\n";
        
        $scenarios = array_keys($this->config['json_scenarios']);
        $reproducibilityResults = [];
        
        foreach ($scenarios as $scenario) {
            $isReproducible = $this->dataGenerator->validateReproducibility($scenario);
            $reproducibilityResults[$scenario] = $isReproducible;
            
            echo "  $scenario: " . ($isReproducible ? "✅ Reproduzível" : "❌ Não reproduzível") . "\n";
            
            if (!$isReproducible) {
                throw new \Exception("Falha na validação de reprodutibilidade para cenário: $scenario");
            }
        }
        
        $this->results['reproducibility_validation'] = $reproducibilityResults;
        echo "✅ Reprodutibilidade validada\n\n";
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
     * Benchmarks dos cenários JSON (GET)
     */
    private function benchmarkJsonScenarios(): void
    {
        echo "📊 Benchmarks JSON Scenarios...\n";
        
        $scenarios = array_keys($this->config['json_scenarios']);
        
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
     * Benchmarks de integridade com POST
     */
    private function benchmarkIntegrityScenarios(): void
    {
        echo "🔒 Benchmarks de Integridade (POST)...\n";
        
        $scenarios = ['small', 'medium']; // Limitar para evitar timeout
        
        foreach ($scenarios as $scenario) {
            echo "  Cenário POST: $scenario\n";
            
            // Gerar dados de teste
            $testData = $this->dataGenerator->generateJson($scenario);
            $testData = $this->dataGenerator->finalizeJson($testData);
            
            $this->metricsCollector->startCollection();
            
            $result = $this->httpBenchmark->runBenchmark(
                "http://localhost:8080/json/$scenario",
                $testData
            );
            
            $systemMetrics = $this->metricsCollector->stopCollection();
            $result['system_metrics'] = $systemMetrics;
            $result['scenario'] = $scenario . '_post';
            $result['test_data_size'] = $testData['metadata']['size_bytes'];
            
            $this->results['integrity_scenarios'][$scenario] = $result;
            
            echo "    Throughput: {$result['analysis']['throughput']['requests_per_second']} req/s\n";
            echo "    Taxa de integridade: {$result['analysis']['integrity']['integrity_rate_percent']}%\n";
        }
        
        echo "\n";
    }

    /**
     * Stress tests
     */
    private function benchmarkStressTests(): void
    {
        echo "💪 Stress Tests...\n";
        
        $stressTests = [
            'basic' => 'http://localhost:8080/stress',
            'cpu' => 'http://localhost:8080/stress/cpu',
            'memory' => 'http://localhost:8080/stress/memory'
        ];
        
        foreach ($stressTests as $testName => $endpoint) {
            echo "  Stress test: $testName\n";
            
            $this->metricsCollector->startCollection();
            
            $result = $this->httpBenchmark->runBenchmark($endpoint);
            
            $systemMetrics = $this->metricsCollector->stopCollection();
            $result['system_metrics'] = $systemMetrics;
            $result['stress_type'] = $testName;
            
            $this->results['stress_tests'][$testName] = $result;
            
            echo "    Throughput: {$result['analysis']['throughput']['requests_per_second']} req/s\n";
            echo "    CPU pico: " . ($systemMetrics['cpu_analysis']['cpu_usage_percent']['max'] ?? 'N/A') . "%\n";
        }
        
        echo "\n";
    }

    /**
     * Executa análise estatística completa
     */
    private function performStatisticalAnalysis(): void
    {
        echo "📈 Análise Estatística...\n";
        
        $allBenchmarkResults = [];
        
        // Coletar todos os resultados
        foreach ($this->results as $category => $categoryResults) {
            if (is_array($categoryResults) && isset($categoryResults['measurements'])) {
                $allBenchmarkResults[] = $categoryResults;
            } elseif (is_array($categoryResults)) {
                foreach ($categoryResults as $result) {
                    if (isset($result['measurements'])) {
                        $allBenchmarkResults[] = $result;
                    }
                }
            }
        }
        
        // Gerar relatório estatístico completo
        $statisticalReport = $this->analyzer->generateComprehensiveReport($allBenchmarkResults);
        
        $this->results['statistical_analysis'] = $statisticalReport;
        
        // Mostrar resumo
        if (isset($statisticalReport['summary'])) {
            $summary = $statisticalReport['summary'];
            echo "  Reprodutibilidade geral: {$summary['overall_reproducibility']['reproducibility_rate']}%\n";
            echo "  CV médio: {$summary['overall_reproducibility']['mean_cv']}%\n";
            echo "  Recomendação: {$summary['recommendation']}\n";
        }
        
        echo "\n";
    }

    /**
     * Gera relatório final completo
     */
    private function generateFinalReport(): array
    {
        echo "📋 Gerando relatório final...\n";
        
        $timestamp = date('Y-m-d-H-i-s');
        
        $finalReport = [
            'report_metadata' => [
                'phase' => 1,
                'timestamp' => microtime(true),
                'date' => date('c'),
                'framework' => 'pivotphp-core',
                'benchmark_version' => '1.0.0',
                'environment' => $this->getEnvironmentInfo()
            ],
            'configuration' => [
                'benchmark_config' => $this->config,
                'seeds_used' => $this->seeds
            ],
            'results' => $this->results,
            'summary' => $this->generateSummary(),
            'conclusions' => $this->generateConclusions()
        ];
        
        // Salvar relatório em JSON
        $jsonPath = __DIR__ . "/../results/phase1-benchmark-report-$timestamp.json";
        file_put_contents($jsonPath, json_encode($finalReport, JSON_PRETTY_PRINT));
        
        // Salvar relatório em Markdown
        $markdownPath = __DIR__ . "/../results/phase1-benchmark-report-$timestamp.md";
        file_put_contents($markdownPath, $this->generateMarkdownReport($finalReport));
        
        echo "  Relatório JSON: $jsonPath\n";
        echo "  Relatório MD: $markdownPath\n";
        
        return $finalReport;
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
            'average_latency' => 0,
            'integrity_rate' => 100,
            'reproducibility_validated' => true
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
     * Gera conclusões baseadas nos resultados
     */
    private function generateConclusions(): array
    {
        $summary = $this->generateSummary();
        $statisticalAnalysis = $this->results['statistical_analysis'] ?? [];
        
        $conclusions = [
            'performance_assessment' => 'unknown',
            'reproducibility_assessment' => 'unknown',
            'data_quality_assessment' => 'unknown',
            'recommendations' => [],
            'scientific_validity' => 'unknown'
        ];
        
        // Avaliar performance
        if ($summary['average_throughput'] > 1000) {
            $conclusions['performance_assessment'] = 'excellent';
        } elseif ($summary['average_throughput'] > 500) {
            $conclusions['performance_assessment'] = 'good';
        } else {
            $conclusions['performance_assessment'] = 'needs_improvement';
        }
        
        // Avaliar reprodutibilidade
        if (isset($statisticalAnalysis['summary']['overall_reproducibility']['reproducibility_rate'])) {
            $repRate = $statisticalAnalysis['summary']['overall_reproducibility']['reproducibility_rate'];
            if ($repRate >= 95) {
                $conclusions['reproducibility_assessment'] = 'excellent';
            } elseif ($repRate >= 80) {
                $conclusions['reproducibility_assessment'] = 'good';
            } else {
                $conclusions['reproducibility_assessment'] = 'poor';
            }
        }
        
        // Recomendações
        if ($conclusions['performance_assessment'] === 'excellent' && 
            $conclusions['reproducibility_assessment'] === 'excellent') {
            $conclusions['recommendations'][] = 'Framework ready for comparative benchmarks';
            $conclusions['scientific_validity'] = 'valid';
        } else {
            $conclusions['recommendations'][] = 'Improve methodology before comparative analysis';
            $conclusions['scientific_validity'] = 'questionable';
        }
        
        return $conclusions;
    }

    /**
     * Gera relatório em formato Markdown
     */
    private function generateMarkdownReport(array $report): string
    {
        $md = "# PivotPHP Core Benchmark Report - Phase 1\n\n";
        $md .= "**Generated:** " . $report['report_metadata']['date'] . "\n";
        $md .= "**Framework:** " . $report['report_metadata']['framework'] . "\n";
        $md .= "**Environment:** PHP " . $report['report_metadata']['environment']['php_version'] . "\n\n";
        
        $md .= "## Summary\n\n";
        $summary = $report['summary'];
        $md .= "- **Total Tests:** " . $summary['total_tests_executed'] . "\n";
        $md .= "- **Average Throughput:** " . round($summary['average_throughput'], 2) . " req/s\n";
        $md .= "- **Average Latency:** " . round($summary['average_latency'], 3) . " ms\n";
        $md .= "- **Success Rate:** " . round(($summary['successful_tests'] / $summary['total_tests_executed']) * 100, 2) . "%\n\n";
        
        $md .= "## Conclusions\n\n";
        $conclusions = $report['conclusions'];
        $md .= "- **Performance:** " . $conclusions['performance_assessment'] . "\n";
        $md .= "- **Reproducibility:** " . $conclusions['reproducibility_assessment'] . "\n";
        $md .= "- **Scientific Validity:** " . $conclusions['scientific_validity'] . "\n\n";
        
        if (!empty($conclusions['recommendations'])) {
            $md .= "### Recommendations\n\n";
            foreach ($conclusions['recommendations'] as $rec) {
                $md .= "- $rec\n";
            }
        }
        
        return $md;
    }
}

// Verificar se está sendo executado diretamente
if (php_sapi_name() === 'cli') {
    try {
        $runner = new BenchmarkRunner();
        $report = $runner->runComplete();
        
        echo "\n🎉 Benchmark da Fase 1 concluído com sucesso!\n";
        exit(0);
        
    } catch (Throwable $e) {
        echo "\n❌ Erro durante benchmark: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}