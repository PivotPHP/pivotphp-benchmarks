<?php
/**
 * HTTP Requests/sec Benchmark para PivotPHP v1.2.0
 * Mede performance real de HTTP requests em ambiente Docker
 */

class HttpBenchmarkV120 
{
    private string $baseUrl;
    private int $requests;
    private int $concurrent;
    
    public function __construct(string $baseUrl = 'http://localhost:8090', int $requests = 1000, int $concurrent = 10)
    {
        $this->baseUrl = $baseUrl;
        $this->requests = $requests;
        $this->concurrent = $concurrent;
    }
    
    public function runBenchmark(): array
    {
        echo "🚀 PivotPHP v1.2.0 HTTP Requests/sec Benchmark\n";
        echo "===========================================\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo "Total requests: {$this->requests}\n";
        echo "Concurrent: {$this->concurrent}\n\n";
        
        $results = [];
        
        // Teste endpoints principais
        $endpoints = [
            '/api/test' => 'Core API endpoint',
            '/' => 'Main endpoint (JSON response)',
            '/health' => 'Health check endpoint',
            '/openapi.json' => 'OpenAPI specification',
        ];
        
        foreach ($endpoints as $endpoint => $description) {
            echo "📊 Testing: {$description} ({$endpoint})...\n";
            $result = $this->benchmarkEndpoint($endpoint);
            $results[$endpoint] = $result;
            
            echo sprintf(
                "   🎯 %s: %.2f req/sec (avg: %.4fs, success: %.1f%%)\n\n",
                $description,
                $result['requests_per_second'],
                $result['avg_response_time'],
                $result['success_rate']
            );
        }
        
        $this->displaySummary($results);
        return $results;
    }
    
    private function benchmarkEndpoint(string $endpoint): array
    {
        $url = $this->baseUrl . $endpoint;
        $times = [];
        $successes = 0;
        $total_requests = min($this->requests, 100); // Limite para teste rápido
        
        $start_time = microtime(true);
        
        for ($i = 0; $i < $total_requests; $i++) {
            $request_start = microtime(true);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $request_end = microtime(true);
            $times[] = $request_end - $request_start;
            
            if ($http_code >= 200 && $http_code < 300) {
                $successes++;
            }
        }
        
        $total_time = microtime(true) - $start_time;
        $avg_time = array_sum($times) / count($times);
        $requests_per_second = $total_requests / $total_time;
        
        return [
            'endpoint' => $endpoint,
            'total_requests' => $total_requests,
            'total_time' => $total_time,
            'avg_response_time' => $avg_time,
            'requests_per_second' => $requests_per_second,
            'success_rate' => ($successes / $total_requests) * 100,
            'min_time' => min($times),
            'max_time' => max($times),
        ];
    }
    
    private function displaySummary(array $results): void
    {
        echo "🏆 PivotPHP v1.2.0 HTTP Performance Summary\n";
        echo "=========================================\n\n";
        
        $total_rps = 0;
        $count = 0;
        
        foreach ($results as $endpoint => $result) {
            echo sprintf(
                "%-20s: %8.2f req/sec (%.4fs avg, %.1f%% success)\n",
                $endpoint,
                $result['requests_per_second'],
                $result['avg_response_time'], 
                $result['success_rate']
            );
            
            $total_rps += $result['requests_per_second'];
            $count++;
        }
        
        $avg_rps = $total_rps / $count;
        
        echo "\n📈 Average HTTP Performance: " . number_format($avg_rps, 2) . " req/sec\n";
        echo "🎯 Best Performance: " . number_format(max(array_column($results, 'requests_per_second')), 2) . " req/sec\n";
        echo "⚡ Fastest Response: " . number_format(min(array_column($results, 'min_time')) * 1000, 2) . "ms\n";
        
        // Salvar resultados
        $timestamp = date('Y-m-d-H-i-s');
        $filename = "http-benchmark-v120-{$timestamp}.json";
        file_put_contents($filename, json_encode([
            'benchmark_info' => [
                'version' => '1.2.0',
                'date' => date('c'),
                'requests' => $this->requests,
                'concurrent' => $this->concurrent,
            ],
            'results' => $results,
            'summary' => [
                'average_rps' => $avg_rps,
                'best_rps' => max(array_column($results, 'requests_per_second')),
                'fastest_response_ms' => min(array_column($results, 'min_time')) * 1000,
            ]
        ], JSON_PRETTY_PRINT));
        
        echo "\n💾 Results saved to: {$filename}\n";
    }
}

// Executar benchmark
$benchmark = new HttpBenchmarkV120();
$benchmark->runBenchmark();