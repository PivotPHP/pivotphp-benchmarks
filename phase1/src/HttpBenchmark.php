<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase1;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;

/**
 * Framework de benchmark HTTP real para medições científicas
 * 
 * Características:
 * - Requisições HTTP reais via Guzzle
 * - Medição precisa de latência (microsegundos)
 * - Throughput calculado em tempo real
 * - Warm-up automático antes das medições
 * - Coleta de métricas detalhadas por requisição
 * - Suporte a concorrência controlada
 */
class HttpBenchmark
{
    private Client $httpClient;
    private array $config;
    private IntegrityValidator $validator;
    private array $results = [];
    private array $metrics = [];

    public function __construct(array $config, IntegrityValidator $validator)
    {
        $this->config = $config;
        $this->validator = $validator;
        $this->initializeHttpClient();
    }

    /**
     * Inicializa cliente HTTP com configurações otimizadas
     */
    private function initializeHttpClient(): void
    {
        $this->httpClient = new Client([
            'timeout' => $this->config['benchmark_parameters']['request_timeout'],
            'connect_timeout' => 5.0,
            'read_timeout' => $this->config['benchmark_parameters']['request_timeout'],
            'http_errors' => false, // Não lançar exceção para status HTTP != 2xx
            'verify' => false, // Disable SSL verification para localhost
            'headers' => [
                'User-Agent' => 'PivotPHP-Benchmark/1.0',
                'Accept' => 'application/json',
                'Connection' => 'keep-alive'
            ]
        ]);
    }

    /**
     * Executa benchmark completo para um endpoint
     */
    public function runBenchmark(string $endpoint, array $payload = null): array
    {
        $benchmarkStart = microtime(true);
        
        echo "🔥 Iniciando benchmark: $endpoint\n";
        
        // 1. Warm-up
        $this->performWarmup($endpoint, $payload);
        
        // 2. Medições principais
        $measurements = $this->performMeasurements($endpoint, $payload);
        
        // 3. Análise dos resultados
        $analysis = $this->analyzeMeasurements($measurements);
        
        // 4. Validação de consistência
        $consistency = $this->validateConsistency($measurements);
        
        $benchmarkEnd = microtime(true);
        
        $result = [
            'endpoint' => $endpoint,
            'timestamp' => $benchmarkStart,
            'duration_total_seconds' => $benchmarkEnd - $benchmarkStart,
            'measurements' => $measurements,
            'analysis' => $analysis,
            'consistency' => $consistency,
            'metadata' => $this->generateMetadata()
        ];
        
        $this->results[] = $result;
        
        echo "✅ Benchmark concluído: {$analysis['throughput']['requests_per_second']} req/s\n";
        
        return $result;
    }

    /**
     * Executa warm-up antes das medições principais
     */
    private function performWarmup(string $endpoint, ?array $payload): void
    {
        $warmupCount = $this->config['benchmark_parameters']['warm_up_requests'];
        echo "🔄 Warm-up: $warmupCount requisições...\n";
        
        for ($i = 0; $i < $warmupCount; $i++) {
            try {
                $this->makeRequest($endpoint, $payload, false); // false = não registrar métricas
            } catch (\Exception $e) {
                // Ignorar erros durante warm-up
            }
        }
        
        echo "✅ Warm-up concluído\n";
    }

    /**
     * Executa medições principais do benchmark
     */
    private function performMeasurements(string $endpoint, ?array $payload): array
    {
        $measurementCount = $this->config['benchmark_parameters']['measurement_requests'];
        $measurements = [];
        
        echo "📊 Executando $measurementCount medições...\n";
        
        for ($i = 0; $i < $measurementCount; $i++) {
            $measurement = $this->makeRequest($endpoint, $payload, true);
            $measurements[] = $measurement;
            
            // Progress indicator
            if (($i + 1) % 100 === 0) {
                echo "    Progresso: " . ($i + 1) . "/$measurementCount\n";
            }
        }
        
        return $measurements;
    }

    /**
     * Faz uma requisição HTTP individual e coleta métricas
     */
    private function makeRequest(string $endpoint, ?array $payload, bool $recordMetrics = true): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $measurement = [
            'timestamp' => $startTime,
            'success' => false,
            'http_status' => 0,
            'response_time_ms' => 0,
            'response_size_bytes' => 0,
            'memory_usage_bytes' => 0,
            'integrity_valid' => false,
            'errors' => []
        ];

        try {
            // Determinar método e dados
            $method = $payload ? 'POST' : 'GET';
            $options = [];
            
            if ($payload) {
                $options['json'] = $payload;
                $options['headers']['Content-Type'] = 'application/json';
            }

            // Fazer requisição
            $response = $this->httpClient->request($method, $endpoint, $options);
            
            $endTime = microtime(true);
            $responseBody = $response->getBody()->getContents();
            
            // Métricas básicas
            $measurement['success'] = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
            $measurement['http_status'] = $response->getStatusCode();
            $measurement['response_time_ms'] = ($endTime - $startTime) * 1000;
            $measurement['response_size_bytes'] = strlen($responseBody);
            $measurement['memory_usage_bytes'] = memory_get_usage(true) - $startMemory;
            
            // Validação de integridade (se payload foi enviado)
            if ($payload && $recordMetrics) {
                $integrityResult = $this->validator->validateIntegrity($payload, $responseBody);
                $measurement['integrity_valid'] = $integrityResult['is_valid'];
                $measurement['integrity_details'] = $integrityResult;
                
                if (!$integrityResult['is_valid']) {
                    $measurement['errors'][] = 'Falha na validação de integridade';
                }
            }
            
            // Headers de resposta
            $measurement['response_headers'] = $this->extractRelevantHeaders($response->getHeaders());
            
        } catch (RequestException $e) {
            $endTime = microtime(true);
            $measurement['response_time_ms'] = ($endTime - $startTime) * 1000;
            $measurement['errors'][] = 'Erro HTTP: ' . $e->getMessage();
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $measurement['response_time_ms'] = ($endTime - $startTime) * 1000;
            $measurement['errors'][] = 'Erro geral: ' . $e->getMessage();
        }
        
        return $measurement;
    }

    /**
     * Extrai headers relevantes para análise
     */
    private function extractRelevantHeaders(array $headers): array
    {
        $relevant = [];
        $trackHeaders = ['content-type', 'content-length', 'server', 'x-powered-by'];
        
        foreach ($trackHeaders as $header) {
            if (isset($headers[$header])) {
                $relevant[$header] = is_array($headers[$header]) ? $headers[$header][0] : $headers[$header];
            }
        }
        
        return $relevant;
    }

    /**
     * Analisa medições e calcula estatísticas
     */
    private function analyzeMeasurements(array $measurements): array
    {
        $responseTimes = array_column($measurements, 'response_time_ms');
        $successfulRequests = array_filter($measurements, fn($m) => $m['success']);
        $totalTime = max($responseTimes) > 0 ? array_sum($responseTimes) / 1000 : 1; // Evitar divisão por zero
        
        return [
            'request_count' => count($measurements),
            'successful_requests' => count($successfulRequests),
            'failed_requests' => count($measurements) - count($successfulRequests),
            'success_rate_percent' => (count($successfulRequests) / count($measurements)) * 100,
            
            'throughput' => [
                'requests_per_second' => count($successfulRequests) / ($totalTime > 0 ? $totalTime : 1),
                'total_duration_seconds' => $totalTime
            ],
            
            'latency' => [
                'mean_ms' => $this->calculateMean($responseTimes),
                'median_ms' => $this->calculatePercentile($responseTimes, 50),
                'p95_ms' => $this->calculatePercentile($responseTimes, 95),
                'p99_ms' => $this->calculatePercentile($responseTimes, 99),
                'min_ms' => min($responseTimes),
                'max_ms' => max($responseTimes),
                'std_dev_ms' => $this->calculateStandardDeviation($responseTimes)
            ],
            
            'memory' => [
                'mean_bytes' => $this->calculateMean(array_column($measurements, 'memory_usage_bytes')),
                'peak_bytes' => max(array_column($measurements, 'memory_usage_bytes')),
                'total_bytes' => array_sum(array_column($measurements, 'memory_usage_bytes'))
            ],
            
            'response_sizes' => [
                'mean_bytes' => $this->calculateMean(array_column($measurements, 'response_size_bytes')),
                'total_bytes' => array_sum(array_column($measurements, 'response_size_bytes'))
            ],
            
            'integrity' => [
                'valid_responses' => count(array_filter($measurements, fn($m) => $m['integrity_valid'] ?? false)),
                'invalid_responses' => count(array_filter($measurements, fn($m) => isset($m['integrity_valid']) && !$m['integrity_valid'])),
                'integrity_rate_percent' => $this->calculateIntegrityRate($measurements)
            ]
        ];
    }

    /**
     * Calcula média
     */
    private function calculateMean(array $values): float
    {
        return count($values) > 0 ? array_sum($values) / count($values) : 0;
    }

    /**
     * Calcula percentil
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $values[$lower];
        }
        
        return $values[$lower] + ($index - $lower) * ($values[$upper] - $values[$lower]);
    }

    /**
     * Calcula desvio padrão
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = $this->calculateMean($values);
        $squaredDiffs = array_map(fn($value) => pow($value - $mean, 2), $values);
        
        return sqrt(array_sum($squaredDiffs) / (count($values) - 1));
    }

    /**
     * Calcula taxa de integridade
     */
    private function calculateIntegrityRate(array $measurements): float
    {
        $withIntegrityCheck = array_filter($measurements, fn($m) => isset($m['integrity_valid']));
        if (empty($withIntegrityCheck)) return 100.0;
        
        $valid = array_filter($withIntegrityCheck, fn($m) => $m['integrity_valid']);
        return (count($valid) / count($withIntegrityCheck)) * 100;
    }

    /**
     * Valida consistência dos resultados
     */
    private function validateConsistency(array $measurements): array
    {
        $responseTimes = array_column($measurements, 'response_time_ms');
        $mean = $this->calculateMean($responseTimes);
        $stdDev = $this->calculateStandardDeviation($responseTimes);
        $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0; // Coeficiente de variação
        
        $maxAllowedCV = $this->config['benchmark_parameters']['max_variance_percent'];
        
        return [
            'coefficient_of_variation_percent' => $cv,
            'is_consistent' => $cv <= $maxAllowedCV,
            'max_allowed_cv_percent' => $maxAllowedCV,
            'mean_response_time_ms' => $mean,
            'std_deviation_ms' => $stdDev,
            'message' => $cv <= $maxAllowedCV 
                ? 'Resultados consistentes e reproduzíveis' 
                : "Resultados inconsistentes (CV: {$cv}% > {$maxAllowedCV}%)"
        ];
    }

    /**
     * Gera metadata do benchmark
     */
    private function generateMetadata(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'guzzle_version' => \Composer\InstalledVersions::getVersion('guzzlehttp/guzzle'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'],
            'jit_enabled' => function_exists('opcache_get_status') && (opcache_get_status()['jit']['enabled'] ?? false),
            'server_time' => date('c'),
            'benchmark_config' => $this->config['benchmark_parameters']
        ];
    }

    /**
     * Executa benchmark para cenário específico de JSON
     */
    public function benchmarkJsonScenario(string $scenario, DataGenerator $generator): array
    {
        echo "🎯 Benchmark JSON Scenario: $scenario\n";
        
        // Gerar conjunto de JSONs para o cenário
        $testData = $generator->generateScenarioSet($scenario, 10); // 10 JSONs variados
        
        $scenarioResults = [];
        foreach ($testData as $index => $jsonData) {
            $endpoint = "http://localhost:8080/json/$scenario";
            $result = $this->runBenchmark($endpoint, $jsonData);
            $result['scenario_index'] = $index;
            $result['json_size_bytes'] = $jsonData['metadata']['size_bytes'];
            
            $scenarioResults[] = $result;
        }
        
        return [
            'scenario' => $scenario,
            'test_count' => count($scenarioResults),
            'individual_results' => $scenarioResults,
            'aggregate_analysis' => $this->aggregateScenarioResults($scenarioResults)
        ];
    }

    /**
     * Agrega resultados de um cenário
     */
    private function aggregateScenarioResults(array $results): array
    {
        if (empty($results)) return [];
        
        $allMeasurements = [];
        foreach ($results as $result) {
            $allMeasurements = array_merge($allMeasurements, $result['measurements']);
        }
        
        return $this->analyzeMeasurements($allMeasurements);
    }

    /**
     * Retorna todos os resultados coletados
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Limpa resultados armazenados
     */
    public function clearResults(): void
    {
        $this->results = [];
    }
}