#!/usr/bin/env php
<?php

/**
 * HTTP Benchmark Real - Requests por Segundo
 * 
 * Testa performance HTTP real medindo req/sec, latência e throughput
 */

class HttpBenchmarkReal
{
    private array $frameworks = [
        'pivotphp-core' => ['port' => 8001, 'name' => 'PivotPHP Core'],
        'pivotphp-orm' => ['port' => 8002, 'name' => 'PivotPHP ORM'],
        'pivotphp-reactphp' => ['port' => 8000, 'name' => 'PivotPHP ReactPHP'],
        'slim4' => ['port' => 8003, 'name' => 'Slim 4'],
        'lumen' => ['port' => 8004, 'name' => 'Lumen'],
        'flight' => ['port' => 8005, 'name' => 'Flight']
    ];
    
    private array $results = [];
    
    public function startServers(): void
    {
        echo "🚀 Iniciando servidores HTTP para todos os frameworks...\n";
        
        // Start PivotPHP Core
        $this->startServer('pivotphp-core', 8001, '/app/public');
        
        // Start PivotPHP ORM  
        $this->startServer('pivotphp-orm', 8002, '/app/public');
        
        // Start Slim 4
        $this->startServer('slim4', 8003, '/app/public');
        
        // Start Lumen
        $this->startServer('lumen', 8004, '/app/public');
        
        // Start Flight
        $this->startServer('flight', 8005, '/app/public');
        
        // PivotPHP ReactPHP already runs continuously
        echo "✅ PivotPHP ReactPHP já está rodando na porta 8000\n";
        
        echo "⏳ Aguardando 5 segundos para estabilização dos servidores...\n";
        sleep(5);
    }
    
    private function startServer(string $framework, int $port, string $docRoot): void
    {
        $containerName = "pivotphp-benchmarks-{$framework}-1";
        $command = "docker exec -d $containerName php -S 0.0.0.0:$port -t $docRoot";
        
        shell_exec($command);
        echo "✅ {$this->frameworks[$framework]['name']} server started on port $port\n";
    }
    
    public function runHttpBenchmarks(): array
    {
        echo "\n🔥 Executando benchmarks HTTP reais...\n";
        echo "=====================================\n\n";
        
        $endpoints = [
            '/' => 'Home Page',
            '/api/health' => 'Health Check',
            '/api/users' => 'Users API',
            '/api/users/1' => 'User Detail'
        ];
        
        foreach ($this->frameworks as $framework => $config) {
            echo "📊 Testando {$config['name']} (porta {$config['port']})...\n";
            
            if (!$this->isServerRunning($config['port'])) {
                echo "❌ Servidor não está rodando na porta {$config['port']}\n";
                continue;
            }
            
            $frameworkResults = [];
            
            foreach ($endpoints as $endpoint => $description) {
                echo "  🎯 $description ($endpoint)...\n";
                
                $result = $this->benchmarkEndpoint($config['port'], $endpoint, 500);
                $result['framework'] = $config['name'];
                $result['endpoint'] = $endpoint;
                $result['description'] = $description;
                
                $frameworkResults[] = $result;
                
                // Small delay between endpoint tests
                usleep(200000);
            }
            
            $this->results[$framework] = $frameworkResults;
            echo "  ✅ {$config['name']} concluído\n\n";
        }
        
        return $this->results;
    }
    
    private function isServerRunning(int $port): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:$port/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode > 0 && $httpCode < 500;
    }
    
    private function benchmarkEndpoint(int $port, string $endpoint, int $requests): array
    {
        $url = "http://127.0.0.1:$port$endpoint";
        $latencies = [];
        $errors = 0;
        $totalBytes = 0;
        
        $start = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $requestStart = microtime(true);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseSize = strlen($response);
            curl_close($ch);
            
            $requestEnd = microtime(true);
            $latency = ($requestEnd - $requestStart) * 1000; // Convert to ms
            
            $latencies[] = $latency;
            $totalBytes += $responseSize;
            
            if ($httpCode >= 400 || $response === false) {
                $errors++;
            }
            
            // Progress indicator
            if ($i % 100 === 0 && $i > 0) {
                $progress = round(($i / $requests) * 100);
                echo "    Progress: {$progress}% ({$i}/{$requests})\r";
            }
        }
        
        $end = microtime(true);
        $totalTime = $end - $start;
        
        // Calculate statistics
        $requestsPerSecond = $requests / $totalTime;
        $avgLatency = array_sum($latencies) / count($latencies);
        
        sort($latencies);
        $p50 = $latencies[intval(count($latencies) * 0.5)] ?? 0;
        $p95 = $latencies[intval(count($latencies) * 0.95)] ?? 0;
        $p99 = $latencies[intval(count($latencies) * 0.99)] ?? 0;
        $minLatency = min($latencies);
        $maxLatency = max($latencies);
        
        $errorRate = ($errors / $requests) * 100;
        $throughputMBps = ($totalBytes / 1024 / 1024) / $totalTime;
        
        echo "    ✅ " . round($requestsPerSecond) . " req/sec\n";
        
        return [
            'requests_per_second' => round($requestsPerSecond, 2),
            'avg_latency_ms' => round($avgLatency, 2),
            'min_latency_ms' => round($minLatency, 2),
            'max_latency_ms' => round($maxLatency, 2),
            'p50_latency_ms' => round($p50, 2),
            'p95_latency_ms' => round($p95, 2),
            'p99_latency_ms' => round($p99, 2),
            'error_rate_percent' => round($errorRate, 2),
            'total_requests' => $requests,
            'total_time_seconds' => round($totalTime, 3),
            'successful_requests' => $requests - $errors,
            'failed_requests' => $errors,
            'throughput_mbps' => round($throughputMBps, 3),
            'total_bytes' => $totalBytes
        ];
    }
    
    public function generateReport(): void
    {
        echo "\n🏆 RELATÓRIO HTTP REQUESTS POR SEGUNDO\n";
        echo "=====================================\n\n";
        
        // Summary table
        echo "📊 Performance Summary (req/sec):\n\n";
        echo sprintf("%-20s %-12s %-12s %-12s %-12s %-10s\n", 
            'Framework', 'Home /', 'Health', 'Users API', 'User Detail', 'Média');
        echo str_repeat('-', 80) . "\n";
        
        $frameworkAverages = [];
        
        foreach ($this->results as $framework => $endpoints) {
            $frameworkName = $this->frameworks[$framework]['name'];
            $reqPerSecValues = [];
            $row = [$frameworkName];
            
            foreach ($endpoints as $result) {
                $reqPerSec = $result['requests_per_second'];
                $reqPerSecValues[] = $reqPerSec;
                $row[] = number_format($reqPerSec);
            }
            
            $average = array_sum($reqPerSecValues) / count($reqPerSecValues);
            $frameworkAverages[$framework] = $average;
            $row[] = number_format($average);
            
            echo sprintf("%-20s %-12s %-12s %-12s %-12s %-10s\n", ...$row);
        }
        
        // Ranking
        arsort($frameworkAverages);
        echo "\n🏅 Ranking por Performance HTTP (req/sec):\n\n";
        
        $position = 1;
        foreach ($frameworkAverages as $framework => $average) {
            $emoji = $position === 1 ? '🥇' : ($position === 2 ? '🥈' : ($position === 3 ? '🥉' : '🔸'));
            $frameworkName = $this->frameworks[$framework]['name'];
            echo "$emoji $position. $frameworkName: " . number_format($average) . " req/sec\n";
            $position++;
        }
        
        // Detailed latency analysis
        echo "\n📈 Análise de Latência Detalhada:\n\n";
        echo sprintf("%-20s %-10s %-10s %-10s %-10s %-10s\n", 
            'Framework', 'Avg (ms)', 'P50 (ms)', 'P95 (ms)', 'P99 (ms)', 'Error %');
        echo str_repeat('-', 70) . "\n";
        
        foreach ($this->results as $framework => $endpoints) {
            $frameworkName = $this->frameworks[$framework]['name'];
            
            // Calculate averages across all endpoints
            $avgLatencies = array_column($endpoints, 'avg_latency_ms');
            $p95Latencies = array_column($endpoints, 'p95_latency_ms');
            $p99Latencies = array_column($endpoints, 'p99_latency_ms');
            $errorRates = array_column($endpoints, 'error_rate_percent');
            
            $avgLatency = array_sum($avgLatencies) / count($avgLatencies);
            $avgP95 = array_sum($p95Latencies) / count($p95Latencies);
            $avgP99 = array_sum($p99Latencies) / count($p99Latencies);
            $avgErrorRate = array_sum($errorRates) / count($errorRates);
            
            // Use P50 from first endpoint as representative
            $p50 = $endpoints[0]['p50_latency_ms'] ?? 0;
            
            echo sprintf("%-20s %-10s %-10s %-10s %-10s %-10s\n",
                $frameworkName,
                round($avgLatency, 1),
                round($p50, 1),
                round($avgP95, 1),
                round($avgP99, 1),
                round($avgErrorRate, 1) . '%'
            );
        }
        
        $this->saveResults();
    }
    
    private function saveResults(): void
    {
        $filename = '/tmp/http-benchmark-real-' . date('Y-m-d-H-i-s') . '.json';
        
        $data = [
            'benchmark_type' => 'http-requests-per-second',
            'timestamp' => date('c'),
            'test_date' => date('Y-m-d H:i:s'),
            'methodology' => 'Real HTTP requests via cURL',
            'frameworks_tested' => count($this->results),
            'requests_per_endpoint' => 500,
            'results' => $this->results
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "\n💾 Resultados HTTP salvos em: $filename\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "🌐 HTTP Benchmark Real - Requests por Segundo\n";
    echo "============================================\n\n";
    
    $benchmark = new HttpBenchmarkReal();
    
    // Start all HTTP servers
    $benchmark->startServers();
    
    // Run HTTP benchmarks
    $results = $benchmark->runHttpBenchmarks();
    
    if (!empty($results)) {
        // Generate comprehensive report
        $benchmark->generateReport();
        echo "\n✅ Benchmark HTTP completo finalizado!\n";
    } else {
        echo "❌ Nenhum resultado obtido\n";
        exit(1);
    }
}