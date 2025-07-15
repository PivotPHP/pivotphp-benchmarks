<?php

declare(strict_types=1);

/**
 * Phase 3 Docker Simple Test - Cross-framework HTTP comparison
 * PivotPHP v1.1.4 vs Other Frameworks
 */

class Phase3DockerSimpleTest
{
    private array $results = [];
    private string $baseUrl = 'http://localhost';
    
    public function __construct()
    {
        echo "🐳 PHASE 3 DOCKER SIMPLE TEST - Cross-framework HTTP\n";
        echo "==================================================\n";
        echo "Testing real HTTP requests to frameworks in Docker\n\n";
    }
    
    public function run(): void
    {
        // Test endpoints
        $endpoints = [
            'health' => '/health',
            'simple' => '/benchmark/simple',
            'json' => '/benchmark/json',
            'array-callable' => '/benchmark/array-callable'
        ];
        
        // Test PivotPHP v1.1.4 (port 8083)
        echo "🔥 Testing PivotPHP v1.1.4 (port 8083)...\n";
        $this->testFramework('pivotphp-v114', 8083, $endpoints);
        
        // Test Slim 4 (port 8084)
        echo "🔥 Testing Slim 4 (port 8084)...\n";
        $this->testFramework('slim4', 8084, $endpoints);
        
        // Generate comparison
        $this->generateComparison();
        
        // Save results
        $this->saveResults();
    }
    
    private function testFramework(string $name, int $port, array $endpoints): void
    {
        $baseUrl = "{$this->baseUrl}:{$port}";
        $results = [];
        
        foreach ($endpoints as $endpointName => $path) {
            echo "  Testing {$endpointName}... ";
            
            $result = $this->httpRequest($baseUrl . $path);
            
            if ($result['success']) {
                echo "✅ {$result['response_time']}ms\n";
                $results[$endpointName] = $result;
            } else {
                echo "❌ {$result['error']}\n";
                $results[$endpointName] = $result;
            }
        }
        
        $this->results[$name] = $results;
        echo "\n";
    }
    
    private function httpRequest(string $url): array
    {
        $startTime = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        
        curl_close($ch);
        
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        
        if ($response === false || !empty($error)) {
            return [
                'success' => false,
                'error' => $error ?: 'Request failed',
                'response_time' => $responseTime,
                'http_code' => $httpCode
            ];
        }
        
        return [
            'success' => true,
            'response' => $response,
            'response_time' => $responseTime,
            'http_code' => $httpCode,
            'curl_time' => round($totalTime * 1000, 2)
        ];
    }
    
    private function generateComparison(): void
    {
        echo "📊 COMPARISON RESULTS\n";
        echo "====================\n\n";
        
        if (empty($this->results)) {
            echo "❌ No results to compare\n";
            return;
        }
        
        // Compare average response times
        $avgTimes = [];
        foreach ($this->results as $framework => $endpoints) {
            $times = [];
            foreach ($endpoints as $endpoint => $result) {
                if ($result['success']) {
                    $times[] = $result['response_time'];
                }
            }
            $avgTimes[$framework] = !empty($times) ? round(array_sum($times) / count($times), 2) : 0;
        }
        
        // Sort by performance (lower is better)
        asort($avgTimes);
        
        echo "🏆 Average Response Times (lower is better):\n";
        $position = 1;
        foreach ($avgTimes as $framework => $avgTime) {
            $status = $avgTime > 0 ? "✅ {$avgTime}ms" : "❌ Failed";
            echo "  {$position}. {$framework}: {$status}\n";
            $position++;
        }
        
        echo "\n";
        
        // Detailed endpoint comparison
        echo "🔍 Detailed Endpoint Comparison:\n";
        foreach (['health', 'simple', 'json', 'array-callable'] as $endpoint) {
            echo "\n  {$endpoint}:\n";
            foreach ($this->results as $framework => $endpoints) {
                if (isset($endpoints[$endpoint])) {
                    $result = $endpoints[$endpoint];
                    if ($result['success']) {
                        echo "    {$framework}: ✅ {$result['response_time']}ms\n";
                    } else {
                        echo "    {$framework}: ❌ {$result['error']}\n";
                    }
                }
            }
        }
    }
    
    private function saveResults(): void
    {
        $timestamp = date('Y-m-d-H-i-s');
        $filename = __DIR__ . "/../results/phase3-docker-simple-{$timestamp}.json";
        
        $report = [
            'test_metadata' => [
                'type' => 'docker_simple_test',
                'timestamp' => time(),
                'date' => date('c'),
                'description' => 'Cross-framework HTTP comparison'
            ],
            'results' => $this->results,
            'summary' => $this->generateSummary()
        ];
        
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        echo "\n💾 Results saved to: {$filename}\n";
    }
    
    private function generateSummary(): array
    {
        $summary = [];
        
        foreach ($this->results as $framework => $endpoints) {
            $successful = 0;
            $totalTime = 0;
            $count = 0;
            
            foreach ($endpoints as $result) {
                if ($result['success']) {
                    $successful++;
                    $totalTime += $result['response_time'];
                }
                $count++;
            }
            
            $summary[$framework] = [
                'successful_requests' => $successful,
                'total_requests' => $count,
                'success_rate' => round(($successful / $count) * 100, 2),
                'average_response_time' => $successful > 0 ? round($totalTime / $successful, 2) : 0
            ];
        }
        
        return $summary;
    }
}

// Execute test
if (php_sapi_name() === 'cli') {
    try {
        $test = new Phase3DockerSimpleTest();
        $test->run();
        echo "\n🎉 Phase 3 Docker Simple Test completed!\n";
    } catch (Throwable $e) {
        echo "\n❌ Error during test: " . $e->getMessage() . "\n";
        exit(1);
    }
}