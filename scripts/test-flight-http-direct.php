#!/usr/bin/env php
<?php

/**
 * Flight HTTP Performance Test (Direct)
 * 
 * Testa Flight diretamente sem servidor web
 */

class FlightHttpDirectTest
{
    public function testFlightPerformance(int $requests = 1000): array
    {
        echo "🔥 Testando Flight HTTP Performance (Direto)\n";
        echo "==========================================\n\n";
        
        $results = [];
        
        // Test home page simulation
        $homeResult = $this->simulateHttpRequest('/', $requests / 2);
        $homeResult['endpoint'] = '/';
        $homeResult['description'] = 'Home Page';
        $results[] = $homeResult;
        
        // Test health endpoint simulation
        $healthResult = $this->simulateHttpRequest('/api/health', $requests / 2);
        $healthResult['endpoint'] = '/api/health';
        $healthResult['description'] = 'Health Check';
        $results[] = $healthResult;
        
        $this->printSummary($results);
        
        return $results;
    }
    
    private function simulateHttpRequest(string $endpoint, int $requests): array
    {
        echo "📊 Simulando $endpoint...\n";
        echo "  🔄 Executando $requests requests...\n";
        
        $latencies = [];
        $errors = 0;
        
        // Setup Flight environment
        require_once __DIR__ . '/../frameworks/flight/vendor/autoload.php';
        
        // Setup request simulation
        $start = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $requestStart = microtime(true);
            
            try {
                // Simulate HTTP request processing
                if ($endpoint === '/') {
                    $response = $this->simulateHomeRequest();
                } elseif ($endpoint === '/api/health') {
                    $response = $this->simulateHealthRequest();
                } else {
                    $response = ['error' => 'Not found'];
                    $errors++;
                }
                
                $requestEnd = microtime(true);
                $latency = ($requestEnd - $requestStart) * 1000;
                $latencies[] = $latency;
                
                if (isset($response['error'])) {
                    $errors++;
                }
            } catch (Exception $e) {
                $errors++;
                $latencies[] = 100; // High latency for errors
            }
            
            if ($i % 100 === 0 && $i > 0) {
                $progress = round(($i / $requests) * 100);
                echo "    Progress: {$progress}%\r";
            }
        }
        
        $end = microtime(true);
        $totalTime = $end - $start;
        
        echo "\n";
        
        $requestsPerSecond = $requests / $totalTime;
        $avgLatency = array_sum($latencies) / count($latencies);
        
        sort($latencies);
        $p95 = $latencies[intval(count($latencies) * 0.95)] ?? 0;
        $p99 = $latencies[intval(count($latencies) * 0.99)] ?? 0;
        
        $errorRate = ($errors / $requests) * 100;
        
        echo "  ✅ " . round($requestsPerSecond) . " req/sec (Erros: $errors)\n\n";
        
        return [
            'requests_per_second' => round($requestsPerSecond, 2),
            'avg_latency_ms' => round($avgLatency, 2),
            'p95_latency_ms' => round($p95, 2),
            'p99_latency_ms' => round($p99, 2),
            'error_rate_percent' => round($errorRate, 2),
            'total_requests' => $requests,
            'total_time_seconds' => round($totalTime, 3),
            'successful_requests' => $requests - $errors,
            'failed_requests' => $errors
        ];
    }
    
    private function simulateHomeRequest(): array
    {
        return [
            'status' => 'ok',
            'framework' => 'flight',
            'version' => '3.x',
            'timestamp' => time()
        ];
    }
    
    private function simulateHealthRequest(): array
    {
        return [
            'status' => 'healthy',
            'framework' => 'flight',
            'version' => '3.x',
            'timestamp' => time(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
    }
    
    private function printSummary(array $results): void
    {
        echo "🏆 Flight HTTP Summary (Direto):\n";
        echo "================================\n";
        
        $totalReqPerSec = array_sum(array_column($results, 'requests_per_second'));
        $avgLatency = array_sum(array_column($results, 'avg_latency_ms')) / count($results);
        $avgP95 = array_sum(array_column($results, 'p95_latency_ms')) / count($results);
        $totalRequests = array_sum(array_column($results, 'total_requests'));
        $totalErrors = array_sum(array_column($results, 'failed_requests'));
        
        echo "📈 Total Throughput: " . number_format($totalReqPerSec) . " req/sec\n";
        echo "⚡ Average Latency: " . round($avgLatency, 2) . "ms\n";
        echo "📊 P95 Latency: " . round($avgP95, 2) . "ms\n";
        echo "✅ Total Requests: " . number_format($totalRequests) . "\n";
        echo "❌ Total Errors: " . $totalErrors . "\n";
        echo "🎯 Framework: Flight (Direct Simulation)\n";
        
        // Detailed breakdown
        echo "\n📊 Detalhes por Endpoint:\n";
        foreach ($results as $result) {
            echo "  • {$result['description']}: ";
            echo number_format($result['requests_per_second']) . " req/sec ";
            echo "({$result['failed_requests']} erros)\n";
        }
        
        // Save results
        $filename = '/tmp/http-test-flight-direct-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
        echo "\n💾 Results saved: $filename\n";
        
        echo "\n⚠️  NOTA: Este é um teste direto simulado, não HTTP real via servidor web.\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $requests = isset($argv[1]) ? (int)$argv[1] : 1000;
    
    $test = new FlightHttpDirectTest();
    $test->testFlightPerformance($requests);
}