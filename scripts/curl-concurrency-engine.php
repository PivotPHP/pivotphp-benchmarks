#!/usr/bin/env php
<?php

/**
 * High-Performance cURL Multi-Threading Concurrency Engine
 * Alternative to Apache Bench for high-scale concurrent testing
 */

class ConcurrencyTester {
    private $maxConcurrency;
    private $totalRequests;
    private $url;
    private $timeout;
    private $results = [];
    
    public function __construct($url, $maxConcurrency = 1000, $totalRequests = 10000, $timeout = 30) {
        $this->url = $url;
        $this->maxConcurrency = $maxConcurrency;
        $this->totalRequests = $totalRequests;
        $this->timeout = $timeout;
    }
    
    public function runTest() {
        $startTime = microtime(true);
        $requestsSent = 0;
        $requestsCompleted = 0;
        $errors = 0;
        $responseTimes = [];
        $statusCodes = [];
        
        echo "🚀 Iniciando teste: {$this->totalRequests} requests com {$this->maxConcurrency} concorrência máxima\n";
        
        // Create multi handle
        $multiHandle = curl_multi_init();
        curl_multi_setopt($multiHandle, CURLMOPT_MAXCONNECTS, $this->maxConcurrency);
        
        $curlHandles = [];
        $activeHandles = 0;
        
        // Initialize progress tracking
        $lastProgress = 0;
        
        while ($requestsSent < $this->totalRequests || $activeHandles > 0) {
            // Add new requests up to concurrency limit
            while ($activeHandles < $this->maxConcurrency && $requestsSent < $this->totalRequests) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'PivotPHP-Concurrency-Tester/1.0',
                    CURLOPT_HTTPHEADER => [
                        'Connection: close',
                        'Accept: application/json'
                    ]
                ]);
                
                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[(int)$ch] = [
                    'handle' => $ch,
                    'start_time' => microtime(true)
                ];
                
                $requestsSent++;
                $activeHandles++;
            }
            
            // Execute requests
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle, 0.1);
            } while ($running > 0);
            
            // Process completed requests
            while ($info = curl_multi_info_read($multiHandle)) {
                $ch = $info['handle'];
                $handleId = (int)$ch;
                
                if (isset($curlHandles[$handleId])) {
                    $endTime = microtime(true);
                    $startTime_req = $curlHandles[$handleId]['start_time'];
                    $responseTime = ($endTime - $startTime_req) * 1000; // Convert to milliseconds
                    
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    
                    if ($info['result'] === CURLE_OK && $httpCode >= 200 && $httpCode < 400) {
                        $responseTimes[] = $responseTime;
                        $statusCodes[] = $httpCode;
                    } else {
                        $errors++;
                    }
                    
                    curl_multi_remove_handle($multiHandle, $ch);
                    curl_close($ch);
                    unset($curlHandles[$handleId]);
                    
                    $requestsCompleted++;
                    $activeHandles--;
                    
                    // Progress indicator
                    $progress = round(($requestsCompleted / $this->totalRequests) * 100);
                    if ($progress >= $lastProgress + 10) {
                        echo "📊 Progresso: $progress% ($requestsCompleted/{$this->totalRequests} requests)\n";
                        $lastProgress = $progress;
                    }
                }
            }
        }
        
        curl_multi_close($multiHandle);
        
        $totalTime = microtime(true) - $startTime;
        
        return $this->calculateMetrics($totalTime, $requestsCompleted, $errors, $responseTimes, $statusCodes);
    }
    
    private function calculateMetrics($totalTime, $requestsCompleted, $errors, $responseTimes, $statusCodes) {
        $successfulRequests = count($responseTimes);
        
        if ($successfulRequests === 0) {
            return [
                'requests_per_second' => 0,
                'time_per_request' => 0,
                'failed_requests' => $errors,
                'total_requests' => $this->totalRequests,
                'success_rate' => 0,
                'total_time' => $totalTime,
                'concurrency' => $this->maxConcurrency
            ];
        }
        
        sort($responseTimes);
        $count = count($responseTimes);
        
        return [
            'requests_per_second' => round($successfulRequests / $totalTime, 2),
            'time_per_request' => round(array_sum($responseTimes) / $count, 2),
            'time_per_request_min' => round($responseTimes[0], 2),
            'time_per_request_max' => round($responseTimes[$count - 1], 2),
            'time_per_request_p50' => round($responseTimes[intval($count * 0.5)], 2),
            'time_per_request_p95' => round($responseTimes[intval($count * 0.95)], 2),
            'time_per_request_p99' => round($responseTimes[intval($count * 0.99)], 2),
            'failed_requests' => $errors,
            'successful_requests' => $successfulRequests,
            'total_requests' => $this->totalRequests,
            'success_rate' => round(($successfulRequests / $this->totalRequests) * 100, 2),
            'total_time' => round($totalTime, 2),
            'concurrency' => $this->maxConcurrency,
            'transfer_rate' => round(($successfulRequests * 1024) / $totalTime, 2), // Approximate KB/sec
            'status_codes' => array_count_values($statusCodes)
        ];
    }
}

function runFrameworkConcurrencyTest($framework, $concurrency, $totalRequests, $endpoint = 'health') {
    $urls = [
        'health' => "http://localhost:8080/health",
        'api' => "http://localhost:8080/api/v1/users",
        'heavy' => "http://localhost:8080/api/v1/users?page=1&limit=100"
    ];
    
    $url = $urls[$endpoint] ?? $urls['health'];
    
    echo "🔄 Testando $framework: $concurrency concurrent, $totalRequests total requests\n";
    
    $tester = new ConcurrencyTester($url, $concurrency, $totalRequests, 30);
    $result = $tester->runTest();
    
    if ($result['requests_per_second'] > 0) {
        echo "✅ $framework: {$result['requests_per_second']} RPS | ";
        echo "{$result['time_per_request']}ms avg | ";
        echo "{$result['failed_requests']} failures | ";
        echo "{$result['success_rate']}% success\n";
        
        return array_merge($result, [
            'framework' => $framework,
            'endpoint' => $endpoint,
            'test_timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo "❌ Teste falhou para $framework\n";
        return null;
    }
}

// Test if running directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🧪 Teste do Engine de Concorrência cURL\n";
    echo "Testando localhost:8080/health com 100 concurrent requests...\n\n";
    
    $result = runFrameworkConcurrencyTest('test', 100, 1000, 'health');
    
    if ($result) {
        echo "\n📊 Resultado do teste:\n";
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                echo "  $key: " . json_encode($value) . "\n";
            } else {
                echo "  $key: $value\n";
            }
        }
    }
}