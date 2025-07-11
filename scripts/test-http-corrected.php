#!/usr/bin/env php
<?php

/**
 * Test HTTP Framework with Correct Endpoints
 * 
 * Testa cada framework usando seus endpoints corretos
 */

class CorrectedHttpTest
{
    private array $frameworkEndpoints = [
        'PivotPHP Core' => [
            '/' => 'Home Page',
            '/api/v1/health' => 'Health Check'
        ],
        'Slim 4' => [
            '/' => 'Home Page', 
            '/api/health' => 'Health Check'
        ],
        'Lumen' => [
            '/' => 'Home Page',
            '/api/health' => 'Health Check'
        ],
        'Flight' => [
            '/' => 'Home Page',
            '/api/health' => 'Health Check'
        ]
    ];

    public function testFramework(string $baseUrl, string $frameworkName, int $requests = 1000): array
    {
        echo "🔥 Testando $frameworkName HTTP Performance (Corrigido)\n";
        echo "=================================================\n\n";
        
        $endpoints = $this->frameworkEndpoints[$frameworkName] ?? [
            '/' => 'Home Page',
            '/api/health' => 'Health Check'
        ];
        
        $results = [];
        
        foreach ($endpoints as $endpoint => $description) {
            echo "📊 $description ($endpoint)...\n";
            
            $result = $this->benchmarkEndpoint($baseUrl . $endpoint, $requests / 2);
            $result['framework'] = $frameworkName;
            $result['endpoint'] = $endpoint;
            $result['description'] = $description;
            
            $results[] = $result;
            
            echo "  ✅ " . round($result['requests_per_second']) . " req/sec";
            echo " (Erros: {$result['failed_requests']})\n\n";
        }
        
        $this->printSummary($results, $frameworkName);
        
        return $results;
    }
    
    private function benchmarkEndpoint(string $url, int $requests): array
    {
        $latencies = [];
        $errors = 0;
        $totalBytes = 0;
        
        // Warmup
        for ($i = 0; $i < 10; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }
        
        echo "  🔄 Executando $requests requests...\n";
        
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
            $latency = ($requestEnd - $requestStart) * 1000;
            
            $latencies[] = $latency;
            $totalBytes += $responseSize;
            
            if ($httpCode >= 400 || $response === false) {
                $errors++;
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
    
    private function printSummary(array $results, string $frameworkName): void
    {
        echo "🏆 $frameworkName HTTP Summary (Corrigido):\n";
        echo "=========================================\n";
        
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
        echo "🎯 Framework: $frameworkName\n";
        
        // Detailed breakdown
        echo "\n📊 Detalhes por Endpoint:\n";
        foreach ($results as $result) {
            echo "  • {$result['description']}: ";
            echo number_format($result['requests_per_second']) . " req/sec ";
            echo "({$result['failed_requests']} erros)\n";
        }
        
        // Save results
        $filename = '/tmp/http-test-corrected-' . strtolower(str_replace(' ', '-', $frameworkName)) . '-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
        echo "\n💾 Results saved: $filename\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    if ($argc < 3) {
        echo "Usage: php test-http-corrected.php <base_url> <framework_name> [requests]\n";
        echo "Example: php test-http-corrected.php http://127.0.0.1:8081 'PivotPHP Core' 1000\n";
        exit(1);
    }
    
    $baseUrl = $argv[1];
    $frameworkName = $argv[2];
    $requests = isset($argv[3]) ? (int)$argv[3] : 1000;
    
    $test = new CorrectedHttpTest();
    $test->testFramework($baseUrl, $frameworkName, $requests);
}