<?php

declare(strict_types=1);

/**
 * Script de teste para configuração de alta performance baseada na documentação
 * 
 * Testa a implementação das melhores práticas encontradas em:
 * - /home/cfernandes/pivotphp/examples/05-performance/high-performance.php
 * - Documentação oficial do PivotPHP
 */

use GuzzleHttp\Client;

require_once __DIR__ . '/../vendor/autoload.php';

class HighPerformanceTestRunner
{
    private Client $httpClient;
    private string $baseUrl = 'http://localhost:8083';

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 30]);
    }

    /**
     * Executa testes da configuração de alta performance
     */
    public function runTests(): void
    {
        echo "🚀 TESTE DE CONFIGURAÇÃO ALTA PERFORMANCE\n";
        echo "=========================================\n\n";

        // 1. Verificar se o servidor está disponível
        if (!$this->isServerAvailable()) {
            echo "❌ Servidor de alta performance não está disponível em $this->baseUrl\n";
            echo "💡 Para iniciar: php -S localhost:8083 phase2/src/high-performance/index.php\n";
            return;
        }

        echo "✅ Servidor de alta performance disponível\n\n";

        // 2. Testar endpoints básicos
        $this->testBasicEndpoints();

        // 3. Testar performance JSON
        $this->testJsonPerformance();

        // 4. Testar endpoint ultra-rápido
        $this->testUltraFastEndpoint();

        // 5. Testar throughput
        $this->testThroughput();

        // 6. Verificar estatísticas dos pools
        $this->testPoolStats();

        // 7. Comparar com baseline
        $this->compareWithBaseline();

        echo "\n✅ Testes de alta performance concluídos!\n";
    }

    /**
     * Verifica se o servidor está disponível
     */
    private function isServerAvailable(): bool
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/health');
            return $response->getStatusCode() === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Testa endpoints básicos
     */
    private function testBasicEndpoints(): void
    {
        echo "🔍 Testando endpoints básicos...\n";

        $endpoints = [
            'health' => '/health',
            'metadata' => '/meta',
            'ultra_fast' => '/ultra-fast'
        ];

        foreach ($endpoints as $name => $endpoint) {
            try {
                $startTime = microtime(true);
                $response = $this->httpClient->get($this->baseUrl . $endpoint);
                $endTime = microtime(true);

                $responseTime = ($endTime - $startTime) * 1000;
                $data = json_decode($response->getBody()->getContents(), true);

                echo "  ✅ $name: {$responseTime}ms\n";

                // Verificar headers de performance
                $headers = $response->getHeaders();
                if (isset($headers['X-Performance-Profile'])) {
                    echo "     Performance Profile: " . $headers['X-Performance-Profile'][0] . "\n";
                }
                if (isset($headers['X-Processing-Time-Ms'])) {
                    echo "     Server Processing Time: " . $headers['X-Processing-Time-Ms'][0] . "\n";
                }

            } catch (\Throwable $e) {
                echo "  ❌ $name: Falhou - " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }

    /**
     * Testa performance JSON
     */
    private function testJsonPerformance(): void
    {
        echo "📊 Testando performance JSON...\n";

        $sizes = ['small', 'medium', 'large'];
        $iterations = 1000;

        foreach ($sizes as $size) {
            try {
                $startTime = microtime(true);
                $response = $this->httpClient->get($this->baseUrl . "/performance/json/$size?iterations=$iterations");
                $endTime = microtime(true);

                $clientTime = ($endTime - $startTime) * 1000;
                $data = json_decode($response->getBody()->getContents(), true);

                echo "  📊 JSON $size:\n";
                echo "     Client time: {$clientTime}ms\n";
                echo "     Server processing: {$data['processing_time_ms']}ms\n";
                echo "     Operations/sec: {$data['operations_per_second']}\n";
                echo "     JSON pooling: " . ($data['json_pooling_used'] ? 'YES' : 'NO') . "\n";

            } catch (\Throwable $e) {
                echo "  ❌ JSON $size: Falhou - " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }

    /**
     * Testa endpoint ultra-rápido
     */
    private function testUltraFastEndpoint(): void
    {
        echo "⚡ Testando endpoint ultra-rápido...\n";

        $iterations = 100;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            try {
                $startTime = microtime(true);
                $response = $this->httpClient->get($this->baseUrl . '/ultra-fast');
                $endTime = microtime(true);

                $times[] = ($endTime - $startTime) * 1000;

            } catch (\Throwable $e) {
                echo "  ❌ Iteração $i falhou\n";
            }
        }

        if (!empty($times)) {
            $avgTime = array_sum($times) / count($times);
            $minTime = min($times);
            $maxTime = max($times);
            $throughput = 1000 / $avgTime;

            echo "  📊 Resultados ultra-fast ($iterations iterações):\n";
            echo "     Tempo médio: " . number_format($avgTime, 3) . "ms\n";
            echo "     Tempo mínimo: " . number_format($minTime, 3) . "ms\n";
            echo "     Tempo máximo: " . number_format($maxTime, 3) . "ms\n";
            echo "     Throughput: " . number_format($throughput, 0) . " req/s\n";
        }
        echo "\n";
    }

    /**
     * Testa throughput
     */
    private function testThroughput(): void
    {
        echo "🏃 Testando throughput...\n";

        $operations = 5000;

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->get($this->baseUrl . "/performance/throughput?operations=$operations");
            $endTime = microtime(true);

            $clientTime = ($endTime - $startTime) * 1000;
            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['throughput_test'])) {
                $test = $data['throughput_test'];
                echo "  📊 Teste de throughput:\n";
                echo "     Operations: {$test['operations']}\n";
                echo "     Server time: {$test['total_time_ms']}ms\n";
                echo "     Client time: {$clientTime}ms\n";
                echo "     Operations/sec: {$test['operations_per_second']}\n";
                echo "     Avg per operation: " . number_format($test['avg_time_per_operation_ms'], 4) . "ms\n";
                
                if (isset($data['performance_optimizations'])) {
                    $opts = $data['performance_optimizations'];
                    echo "     JSON pooling: " . ($opts['json_pooling'] ? 'YES' : 'NO') . "\n";
                    echo "     High perf mode: " . ($opts['high_performance_mode'] ? 'YES' : 'NO') . "\n";
                    echo "     Memory usage: " . number_format($opts['memory_usage_mb'], 2) . "MB\n";
                }
            }

        } catch (\Throwable $e) {
            echo "  ❌ Teste de throughput falhou: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    /**
     * Testa estatísticas dos pools
     */
    private function testPoolStats(): void
    {
        echo "🏊 Testando estatísticas dos pools...\n";

        try {
            $response = $this->httpClient->get($this->baseUrl . '/performance/pool-stats');
            $data = json_decode($response->getBody()->getContents(), true);

            echo "  📊 Status dos pools:\n";
            
            if (isset($data['high_performance_mode'])) {
                $hpm = $data['high_performance_mode'];
                echo "     High Performance Mode: " . ($hpm['enabled'] ? 'ENABLED' : 'DISABLED') . "\n";
                if (isset($hpm['profile'])) {
                    echo "     Profile: {$hpm['profile']}\n";
                }
            }

            if (isset($data['json_pool_stats']) && $data['json_pool_stats']) {
                $jps = $data['json_pool_stats'];
                echo "     JSON Pool disponível: YES\n";
                if (isset($jps['total_operations'])) {
                    echo "     Total operations: {$jps['total_operations']}\n";
                }
                if (isset($jps['pool_hits'])) {
                    echo "     Pool hits: {$jps['pool_hits']}\n";
                }
            } else {
                echo "     JSON Pool disponível: NO\n";
            }

            if (isset($data['internal_metrics'])) {
                $im = $data['internal_metrics'];
                echo "     Total requests: {$im['total_requests']}\n";
                echo "     Avg processing time: " . number_format($im['avg_processing_time_ms'], 3) . "ms\n";
            }

        } catch (\Throwable $e) {
            echo "  ❌ Teste de pool stats falhou: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    /**
     * Compara com baseline da Fase 1
     */
    private function compareWithBaseline(): void
    {
        echo "📈 Comparando com baseline da Fase 1...\n";

        // Testar health endpoint para comparação
        try {
            $iterations = 50;
            $times = [];

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $response = $this->httpClient->get($this->baseUrl . '/health');
                $endTime = microtime(true);
                $times[] = ($endTime - $startTime) * 1000;
            }

            $avgTime = array_sum($times) / count($times);
            $throughput = 1000 / $avgTime;

            echo "  📊 Performance atual (High Performance):\n";
            echo "     Throughput: " . number_format($throughput, 0) . " req/s\n";
            echo "     Latência média: " . number_format($avgTime, 3) . "ms\n";

            // Baseline da Fase 1: 423 req/s, 3.82ms P95
            $baselineThroughput = 423;
            $baselineLatency = 2.365; // P50 da Fase 1

            $throughputImprovement = (($throughput - $baselineThroughput) / $baselineThroughput) * 100;
            $latencyImprovement = (($baselineLatency - $avgTime) / $baselineLatency) * 100;

            echo "\n  🎯 Comparação com baseline (Fase 1):\n";
            echo "     Baseline throughput: {$baselineThroughput} req/s\n";
            echo "     Baseline latência: {$baselineLatency}ms\n";
            echo "     Melhoria throughput: " . number_format($throughputImprovement, 1) . "%\n";
            echo "     Melhoria latência: " . number_format($latencyImprovement, 1) . "%\n";

            if ($throughputImprovement > 0) {
                echo "     🚀 PERFORMANCE SUPERIOR ao baseline!\n";
            } else {
                echo "     ⚠️  Performance inferior ao baseline\n";
            }

        } catch (\Throwable $e) {
            echo "  ❌ Comparação com baseline falhou: " . $e->getMessage() . "\n";
        }
    }
}

// Executar se chamado diretamente
if (php_sapi_name() === 'cli') {
    try {
        $runner = new HighPerformanceTestRunner();
        $runner->runTests();
        
        exit(0);
        
    } catch (Throwable $e) {
        echo "\n❌ Erro durante teste: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}