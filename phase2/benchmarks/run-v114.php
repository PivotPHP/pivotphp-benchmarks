<?php

declare(strict_types=1);

/**
 * Benchmark Runner - Phase 2 com PivotPHP v1.1.4 (Enhanced Edition)
 * 
 * Testa PivotPHP Core + extensões (ORM e ReactPHP) com sintaxe v1.1.4
 * Otimizado para ambiente Docker isolado
 */

require_once __DIR__ . '/../vendor/autoload.php';

class Phase2BenchmarkV114Runner
{
    private array $config;
    private array $results = [];
    private array $containerPorts;

    public function __construct()
    {
        $this->loadConfiguration();
        $this->containerPorts = [
            'core' => 8080,
            'orm' => 8081,
            'reactphp' => 8082
        ];
    }

    private function loadConfiguration(): void
    {
        $configFile = __DIR__ . '/../config/benchmark-phase2.json';
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            $this->config = $this->getDefaultConfig();
        }
    }

    private function getDefaultConfig(): array
    {
        return [
            'framework' => 'pivotphp-core',
            'version' => '1.1.4',
            'edition' => 'Enhanced',
            'extensions' => ['core', 'orm', 'reactphp'],
            'iterations' => 5000,
            'warmup' => 500,
            'timeout' => 30
        ];
    }

    public function run(): array
    {
        echo "🚀 BENCHMARK PHASE 2 - PivotPHP v1.1.4 (Enhanced Edition)\n";
        echo "=========================================================\n\n";

        $startTime = microtime(true);

        // Testar cada extensão
        $this->testPivotPHPCore();
        $this->testPivotPHPORM();
        $this->testPivotPHPReactPHP();

        $totalTime = microtime(true) - $startTime;

        // Gerar relatório comparativo
        $report = $this->generateComparativeReport($totalTime);

        echo "\n🎯 PHASE 2 BENCHMARK v1.1.4 CONCLUÍDO!\n";
        echo "Tempo total: " . round($totalTime, 2) . "s\n";
        echo "Relatório salvo em: results/\n\n";

        return $report;
    }

    private function testPivotPHPCore(): void
    {
        echo "📦 Testando PivotPHP Core v1.1.4\n";
        $baseUrl = "http://localhost:{$this->containerPorts['core']}";
        
        if (!$this->checkHealth($baseUrl)) {
            echo "  ❌ Container Core não disponível\n\n";
            return;
        }

        $this->results['core'] = [
            'health' => $this->benchmarkEndpoint($baseUrl, '/health', 'Core Health'),
            'json_small' => $this->benchmarkEndpoint($baseUrl, '/json/small', 'Core JSON Small'),
            'json_medium' => $this->benchmarkEndpoint($baseUrl, '/json/medium', 'Core JSON Medium'),
            'performance' => $this->benchmarkEndpoint($baseUrl, '/performance', 'Core Performance'),
            'version' => '1.1.4'
        ];

        echo "  ✅ Core benchmarks concluídos\n\n";
    }

    private function testPivotPHPORM(): void
    {
        echo "🗄️ Testando PivotPHP + Cycle ORM v1.1.4\n";
        $baseUrl = "http://localhost:{$this->containerPorts['orm']}";
        
        if (!$this->checkHealth($baseUrl)) {
            echo "  ❌ Container ORM não disponível\n\n";
            return;
        }

        $this->results['orm'] = [
            'health' => $this->benchmarkEndpoint($baseUrl, '/health', 'ORM Health'),
            'users_list' => $this->benchmarkEndpoint($baseUrl, '/api/users', 'ORM Users List'),
            'user_create' => $this->benchmarkORMCreate($baseUrl),
            'user_query' => $this->benchmarkEndpoint($baseUrl, '/api/users/1', 'ORM User Query'),
            'database_stats' => $this->benchmarkEndpoint($baseUrl, '/api/database/stats', 'ORM DB Stats'),
            'version' => '1.1.4'
        ];

        echo "  ✅ ORM benchmarks concluídos\n\n";
    }

    private function testPivotPHPReactPHP(): void
    {
        echo "⚡ Testando PivotPHP + ReactPHP v1.1.4\n";
        $baseUrl = "http://localhost:{$this->containerPorts['reactphp']}";
        
        if (!$this->checkHealth($baseUrl)) {
            echo "  ❌ Container ReactPHP não disponível\n\n";
            return;
        }

        $this->results['reactphp'] = [
            'health' => $this->benchmarkEndpoint($baseUrl, '/health', 'ReactPHP Health'),
            'async_test' => $this->benchmarkEndpoint($baseUrl, '/async/test', 'ReactPHP Async'),
            'concurrent' => $this->benchmarkEndpoint($baseUrl, '/concurrent/test', 'ReactPHP Concurrent'),
            'stream' => $this->benchmarkEndpoint($baseUrl, '/stream/test', 'ReactPHP Stream'),
            'websocket_info' => $this->benchmarkEndpoint($baseUrl, '/websocket/info', 'ReactPHP WebSocket'),
            'version' => '1.1.4'
        ];

        echo "  ✅ ReactPHP benchmarks concluídos\n\n";
    }

    private function checkHealth(string $baseUrl): bool
    {
        try {
            $response = $this->makeRequest($baseUrl . '/health');
            $data = json_decode($response['body'], true);
            
            return $response['http_code'] === 200 && 
                   isset($data['status']) && 
                   $data['status'] === 'healthy';
        } catch (Exception $e) {
            return false;
        }
    }

    private function benchmarkORMCreate(string $baseUrl): array
    {
        $createData = [
            'name' => 'Benchmark User v1.1.4',
            'email' => 'benchmark@example.com',
            'age' => 25
        ];

        $times = [];
        $errors = 0;
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) { // Menos iterações para POST
            $requestStart = microtime(true);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $baseUrl . '/api/users',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($createData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: PivotPHP-Benchmark-v1.1.4'
                ],
                CURLOPT_TIMEOUT => 30
            ]);

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $requestEnd = microtime(true);

            if ($httpCode !== 201 && $httpCode !== 200) {
                $errors++;
            }

            $times[] = ($requestEnd - $requestStart) * 1000;
        }

        $end = microtime(true);
        $totalTime = $end - $start;
        $count = count($times);
        sort($times);

        return [
            'name' => 'ORM User Create',
            'endpoint' => '/api/users (POST)',
            'iterations' => 100,
            'total_time' => $totalTime,
            'throughput' => 100 / $totalTime,
            'latency_avg' => array_sum($times) / $count,
            'latency_p95' => $times[(int)($count * 0.95)],
            'errors' => $errors,
            'success_rate' => ((100 - $errors) / 100) * 100,
            'version' => '1.1.4'
        ];
    }

    private function benchmarkEndpoint(string $baseUrl, string $endpoint, string $name): array
    {
        $iterations = $this->config['iterations'] ?? 1000;
        $warmup = $this->config['warmup'] ?? 100;

        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->makeRequest($baseUrl . $endpoint);
        }

        // Benchmark
        $times = [];
        $errors = 0;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $requestStart = microtime(true);
            $response = $this->makeRequest($baseUrl . $endpoint);
            $requestEnd = microtime(true);

            if ($response['http_code'] !== 200) {
                $errors++;
            }

            $times[] = ($requestEnd - $requestStart) * 1000;
        }

        $end = microtime(true);
        $totalTime = $end - $start;
        $count = count($times);
        sort($times);

        return [
            'name' => $name,
            'endpoint' => $endpoint,
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'throughput' => $iterations / $totalTime,
            'latency_avg' => array_sum($times) / $count,
            'latency_min' => min($times),
            'latency_max' => max($times),
            'latency_p50' => $times[(int)($count * 0.5)],
            'latency_p95' => $times[(int)($count * 0.95)],
            'errors' => $errors,
            'success_rate' => (($iterations - $errors) / $iterations) * 100,
            'version' => '1.1.4'
        ];
    }

    private function makeRequest(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'] ?? 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: PivotPHP-Benchmark-v1.1.4',
                'Accept: application/json',
                'X-Benchmark-Phase: 2'
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

    private function generateComparativeReport(float $totalTime): array
    {
        $timestamp = date('Y-m-d-H-i-s');
        
        $report = [
            'metadata' => [
                'phase' => 2,
                'framework' => 'pivotphp-ecosystem',
                'version' => '1.1.4',
                'edition' => 'Enhanced',
                'timestamp' => microtime(true),
                'date' => date('c'),
                'total_execution_time' => $totalTime,
                'extensions_tested' => array_keys($this->results)
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'containers' => $this->containerPorts
            ],
            'results' => $this->results,
            'comparison' => $this->generateComparison(),
            'summary' => $this->generateSummary()
        ];

        // Salvar relatório
        $jsonPath = __DIR__ . "/../results/phase2-benchmark-v114-$timestamp.json";
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));

        $markdownPath = __DIR__ . "/../results/phase2-benchmark-v114-$timestamp.md";
        file_put_contents($markdownPath, $this->generateMarkdownReport($report));

        return $report;
    }

    private function generateComparison(): array
    {
        $comparison = [];
        
        // Comparar performance base entre Core, ORM e ReactPHP
        if (isset($this->results['core']['health'], $this->results['orm']['health'], $this->results['reactphp']['health'])) {
            $coreHealth = $this->results['core']['health']['throughput'];
            $ormHealth = $this->results['orm']['health']['throughput'];
            $reactHealth = $this->results['reactphp']['health']['throughput'];

            $comparison['health_throughput'] = [
                'core' => $coreHealth,
                'orm' => $ormHealth,
                'reactphp' => $reactHealth,
                'orm_vs_core_ratio' => $ormHealth / $coreHealth,
                'react_vs_core_ratio' => $reactHealth / $coreHealth
            ];
        }

        return $comparison;
    }

    private function generateSummary(): array
    {
        $summary = [
            'extensions_tested' => count($this->results),
            'version' => '1.1.4',
            'total_benchmarks' => 0,
            'successful_tests' => 0
        ];

        foreach ($this->results as $extension => $tests) {
            if ($extension === 'version') continue;
            
            foreach ($tests as $testName => $test) {
                if ($testName === 'version') continue;
                
                $summary['total_benchmarks']++;
                if (isset($test['success_rate']) && $test['success_rate'] > 95) {
                    $summary['successful_tests']++;
                }
            }
        }

        $summary['success_rate'] = $summary['total_benchmarks'] > 0 
            ? ($summary['successful_tests'] / $summary['total_benchmarks']) * 100 
            : 0;

        return $summary;
    }

    private function generateMarkdownReport(array $report): string
    {
        $md = "# PivotPHP Ecosystem v1.1.4 Benchmark Report - Phase 2\n\n";
        $md .= "**Edition:** Enhanced\n";
        $md .= "**Generated:** " . $report['metadata']['date'] . "\n";
        $md .= "**Extensions:** " . implode(', ', $report['metadata']['extensions_tested']) . "\n";
        $md .= "**Environment:** PHP " . $report['environment']['php_version'] . "\n\n";

        $md .= "## Summary\n\n";
        $summary = $report['summary'];
        $md .= "- **Extensions Tested:** " . $summary['extensions_tested'] . "\n";
        $md .= "- **Total Benchmarks:** " . $summary['total_benchmarks'] . "\n";
        $md .= "- **Success Rate:** " . round($summary['success_rate'], 2) . "%\n\n";

        // Resultados por extensão
        foreach ($report['results'] as $extension => $tests) {
            if ($extension === 'version') continue;
            
            $md .= "## " . strtoupper($extension) . " Results\n\n";
            
            foreach ($tests as $testName => $test) {
                if ($testName === 'version') continue;
                
                $md .= "### " . ucfirst(str_replace('_', ' ', $testName)) . "\n";
                $md .= "- **Throughput:** " . round($test['throughput'], 2) . " req/s\n";
                $md .= "- **Latency Avg:** " . round($test['latency_avg'], 3) . " ms\n";
                if (isset($test['latency_p95'])) {
                    $md .= "- **Latency P95:** " . round($test['latency_p95'], 3) . " ms\n";
                }
                $md .= "- **Success Rate:** " . round($test['success_rate'], 2) . "%\n\n";
            }
        }

        return $md;
    }
}

// Executar se chamado diretamente
if (php_sapi_name() === 'cli') {
    try {
        $runner = new Phase2BenchmarkV114Runner();
        $report = $runner->run();
        exit(0);
    } catch (Throwable $e) {
        echo "\n❌ Erro durante benchmark Phase 2: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}