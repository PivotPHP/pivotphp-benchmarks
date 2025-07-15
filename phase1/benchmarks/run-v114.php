<?php

declare(strict_types=1);

/**
 * Benchmark Runner - PivotPHP Core v1.1.4 (Enhanced Edition)
 * 
 * Script otimizado para benchmarks em ambiente Docker isolado
 * Compatível com a sintaxe e recursos do PivotPHP v1.1.4
 */

require_once __DIR__ . '/../vendor/autoload.php';

class BenchmarkV114Runner
{
    private array $config;
    private string $baseUrl;
    private array $results = [];
    private int $iterations;
    private int $warmupIterations;

    public function __construct()
    {
        $this->loadConfiguration();
        $this->baseUrl = 'http://localhost:8080';
        $this->iterations = 10000;
        $this->warmupIterations = 1000;
    }

    private function loadConfiguration(): void
    {
        $configFile = __DIR__ . '/../config/benchmark.json';
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            $this->config = $this->getDefaultConfig();
        }
    }

    private function getDefaultConfig(): array
    {
        return [
            'phase1_config' => [
                'framework' => 'pivotphp-core',
                'version' => '1.1.4',
                'edition' => 'Enhanced',
                'test_scenarios' => [
                    'health_check' => true,
                    'json_small' => true,
                    'json_medium' => true,
                    'json_large' => true,
                    'json_variable' => true,
                    'routing_params' => true,
                    'stress_cpu' => true,
                    'stress_memory' => true,
                    'performance' => true
                ]
            ]
        ];
    }

    public function run(): array
    {
        echo "🚀 BENCHMARK PIVOTPHP CORE v1.1.4 (Enhanced Edition)\n";
        echo "===================================================\n\n";

        $startTime = microtime(true);

        // Verificar se servidor está disponível
        if (!$this->checkServerHealth()) {
            throw new RuntimeException('Servidor não está disponível em ' . $this->baseUrl);
        }

        echo "✅ Servidor PivotPHP v1.1.4 disponível\n\n";

        // Executar benchmarks
        $this->runHealthCheckBenchmark();
        $this->runJsonBenchmarks();
        $this->runRoutingBenchmarks();
        $this->runStressBenchmarks();
        $this->runPerformanceBenchmarks();

        $totalTime = microtime(true) - $startTime;

        // Gerar relatório
        $report = $this->generateReport($totalTime);

        echo "\n🎯 BENCHMARK v1.1.4 CONCLUÍDO!\n";
        echo "Tempo total: " . round($totalTime, 2) . "s\n";
        echo "Relatório salvo em: results/\n\n";

        return $report;
    }

    private function checkServerHealth(): bool
    {
        try {
            $response = $this->makeRequest('/health');
            $data = json_decode($response['body'], true);
            
            return $response['http_code'] === 200 && 
                   isset($data['status']) && 
                   $data['status'] === 'healthy' &&
                   isset($data['version']) &&
                   $data['version'] === '1.1.4';
        } catch (Exception $e) {
            return false;
        }
    }

    private function runHealthCheckBenchmark(): void
    {
        echo "🏥 Benchmark: Health Check v1.1.4\n";
        $result = $this->benchmarkEndpoint('/health', 'Health Check v1.1.4');
        $this->results['health_check'] = $result;
        
        echo "  Throughput: " . round($result['throughput'], 2) . " req/s\n";
        echo "  Latência P95: " . round($result['latency_p95'], 3) . " ms\n\n";
    }

    private function runJsonBenchmarks(): void
    {
        echo "📊 Benchmarks JSON v1.1.4\n";
        
        $scenarios = ['small', 'medium', 'large'];
        
        foreach ($scenarios as $scenario) {
            echo "  📄 JSON $scenario\n";
            $result = $this->benchmarkEndpoint("/json/$scenario", "JSON $scenario v1.1.4");
            $this->results["json_$scenario"] = $result;
            
            echo "    Throughput: " . round($result['throughput'], 2) . " req/s\n";
            echo "    Latência média: " . round($result['latency_avg'], 3) . " ms\n";
        }

        // JSON variável
        echo "  📄 JSON variable\n";
        $result = $this->benchmarkEndpoint('/json/variable?size=100', 'JSON variable v1.1.4');
        $this->results['json_variable'] = $result;
        echo "    Throughput: " . round($result['throughput'], 2) . " req/s\n";
        echo "    Latência média: " . round($result['latency_avg'], 3) . " ms\n\n";
    }

    private function runRoutingBenchmarks(): void
    {
        echo "🛣️ Benchmarks Routing v1.1.4\n";
        
        // Parâmetro simples
        echo "  🔗 Routing com parâmetros\n";
        $result = $this->benchmarkEndpoint('/param/123', 'Routing params v1.1.4');
        $this->results['routing_simple'] = $result;
        echo "    Throughput: " . round($result['throughput'], 2) . " req/s\n";

        // Parâmetros múltiplos
        echo "  🔗 Routing múltiplos parâmetros\n";
        $result = $this->benchmarkEndpoint('/param/456/user/john', 'Routing multi-params v1.1.4');
        $this->results['routing_multi'] = $result;
        echo "    Throughput: " . round($result['throughput'], 2) . " req/s\n\n";
    }

    private function runStressBenchmarks(): void
    {
        echo "💪 Benchmarks Stress Testing v1.1.4\n";
        
        // CPU stress
        echo "  🔥 CPU Stress Test\n";
        $result = $this->benchmarkEndpoint('/stress/cpu?iterations=5000', 'CPU Stress v1.1.4');
        $this->results['stress_cpu'] = $result;
        echo "    Throughput: " . round($result['throughput'], 2) . " req/s\n";

        // Memory stress
        echo "  🧠 Memory Stress Test\n";
        $result = $this->benchmarkEndpoint('/stress/memory?size=1000', 'Memory Stress v1.1.4');
        $this->results['stress_memory'] = $result;
        echo "    Throughput: " . round($result['throughput'], 2) . " req/s\n\n";
    }

    private function runPerformanceBenchmarks(): void
    {
        echo "⚡ Benchmarks Performance v1.1.4\n";
        
        $result = $this->benchmarkEndpoint('/performance', 'Performance test v1.1.4');
        $this->results['performance'] = $result;
        echo "  Throughput: " . round($result['throughput'], 2) . " req/s\n";
        echo "  Latência média: " . round($result['latency_avg'], 3) . " ms\n\n";
    }

    private function benchmarkEndpoint(string $endpoint, string $name): array
    {
        // Warmup
        for ($i = 0; $i < $this->warmupIterations; $i++) {
            $this->makeRequest($endpoint);
        }

        // Benchmark
        $times = [];
        $errors = 0;
        $start = microtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $requestStart = microtime(true);
            $response = $this->makeRequest($endpoint);
            $requestEnd = microtime(true);

            if ($response['http_code'] !== 200) {
                $errors++;
            }

            $times[] = ($requestEnd - $requestStart) * 1000; // ms
        }

        $end = microtime(true);
        $totalTime = $end - $start;

        // Calcular estatísticas
        sort($times);
        $count = count($times);
        
        return [
            'name' => $name,
            'endpoint' => $endpoint,
            'iterations' => $this->iterations,
            'total_time' => $totalTime,
            'throughput' => $this->iterations / $totalTime,
            'latency_avg' => array_sum($times) / $count,
            'latency_min' => min($times),
            'latency_max' => max($times),
            'latency_p50' => $times[(int)($count * 0.5)],
            'latency_p95' => $times[(int)($count * 0.95)],
            'latency_p99' => $times[(int)($count * 0.99)],
            'errors' => $errors,
            'success_rate' => (($this->iterations - $errors) / $this->iterations) * 100,
            'version' => '1.1.4'
        ];
    }

    private function makeRequest(string $endpoint): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: PivotPHP-Benchmark-v1.1.4',
                'Accept: application/json',
                'X-Benchmark-Client: v1.1.4'
            ]
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("CURL Error: $error");
        }

        return [
            'body' => $body,
            'http_code' => $httpCode
        ];
    }

    private function generateReport(float $totalTime): array
    {
        $timestamp = date('Y-m-d-H-i-s');
        
        $report = [
            'metadata' => [
                'framework' => 'pivotphp-core',
                'version' => '1.1.4',
                'edition' => 'Enhanced',
                'phase' => 1,
                'timestamp' => microtime(true),
                'date' => date('c'),
                'total_execution_time' => $totalTime,
                'iterations_per_test' => $this->iterations,
                'warmup_iterations' => $this->warmupIterations
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ],
            'results' => $this->results,
            'summary' => $this->generateSummary()
        ];

        // Salvar relatório JSON
        $jsonPath = __DIR__ . "/../results/benchmark-v114-$timestamp.json";
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));

        // Salvar relatório Markdown
        $markdownPath = __DIR__ . "/../results/benchmark-v114-$timestamp.md";
        file_put_contents($markdownPath, $this->generateMarkdownReport($report));

        return $report;
    }

    private function generateSummary(): array
    {
        $totalTests = count($this->results);
        $totalThroughput = 0;
        $totalLatency = 0;
        $totalSuccessRate = 0;

        foreach ($this->results as $result) {
            $totalThroughput += $result['throughput'];
            $totalLatency += $result['latency_avg'];
            $totalSuccessRate += $result['success_rate'];
        }

        return [
            'total_tests' => $totalTests,
            'average_throughput' => $totalThroughput / $totalTests,
            'average_latency' => $totalLatency / $totalTests,
            'average_success_rate' => $totalSuccessRate / $totalTests,
            'framework_version' => '1.1.4'
        ];
    }

    private function generateMarkdownReport(array $report): string
    {
        $md = "# PivotPHP Core v1.1.4 Benchmark Report\n\n";
        $md .= "**Edition:** Enhanced\n";
        $md .= "**Generated:** " . $report['metadata']['date'] . "\n";
        $md .= "**Phase:** " . $report['metadata']['phase'] . "\n";
        $md .= "**Environment:** PHP " . $report['environment']['php_version'] . "\n\n";

        $md .= "## Summary\n\n";
        $summary = $report['summary'];
        $md .= "- **Total Tests:** " . $summary['total_tests'] . "\n";
        $md .= "- **Average Throughput:** " . round($summary['average_throughput'], 2) . " req/s\n";
        $md .= "- **Average Latency:** " . round($summary['average_latency'], 3) . " ms\n";
        $md .= "- **Average Success Rate:** " . round($summary['average_success_rate'], 2) . "%\n\n";

        $md .= "## Detailed Results\n\n";

        foreach ($report['results'] as $testName => $result) {
            $md .= "### " . ucfirst(str_replace('_', ' ', $testName)) . "\n";
            $md .= "- **Endpoint:** `" . $result['endpoint'] . "`\n";
            $md .= "- **Throughput:** " . round($result['throughput'], 2) . " req/s\n";
            $md .= "- **Latency Avg:** " . round($result['latency_avg'], 3) . " ms\n";
            $md .= "- **Latency P95:** " . round($result['latency_p95'], 3) . " ms\n";
            $md .= "- **Success Rate:** " . round($result['success_rate'], 2) . "%\n\n";
        }

        return $md;
    }
}

// Executar se chamado diretamente
if (php_sapi_name() === 'cli') {
    try {
        $runner = new BenchmarkV114Runner();
        $report = $runner->run();
        exit(0);
    } catch (Throwable $e) {
        echo "\n❌ Erro durante benchmark: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}