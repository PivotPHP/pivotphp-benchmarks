#!/usr/bin/env php
<?php

/**
 * Request Type Comparison
 * 
 * Compares performance between synchronous and different request patterns
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;

class RequestTypeComparison
{
    private Application $app;
    private array $results = [];
    
    public function __construct()
    {
        $this->setupApplication();
    }
    
    private function setupApplication(): void
    {
        $this->app = new Application();
        
        // Light endpoint (minimal processing)
        $this->app->get('/light', function($request, $response) {
            return $response->json(['ok' => true]);
        });
        
        // Normal endpoint (typical API response)
        $this->app->get('/normal', function($request, $response) {
            $data = [
                'users' => [],
                'meta' => [
                    'page' => 1,
                    'total' => 100,
                    'per_page' => 10
                ]
            ];
            
            for ($i = 1; $i <= 10; $i++) {
                $data['users'][] = [
                    'id' => $i,
                    'name' => "User {$i}",
                    'email' => "user{$i}@example.com",
                    'active' => true
                ];
            }
            
            return $response->json($data);
        });
        
        // Heavy endpoint (intensive processing)
        $this->app->get('/heavy', function($request, $response) {
            $results = [
                'calculations' => [],
                'statistics' => [],
                'aggregations' => []
            ];
            
            // Simulate heavy computation
            for ($i = 0; $i < 50; $i++) {
                $results['calculations'][] = [
                    'index' => $i,
                    'sqrt' => sqrt($i * 1000),
                    'hash' => md5($i . microtime()),
                    'random' => array_map(function() {
                        return rand(1, 1000);
                    }, range(1, 10))
                ];
            }
            
            // Calculate statistics
            $values = array_column($results['calculations'], 'sqrt');
            $results['statistics'] = [
                'count' => count($values),
                'sum' => array_sum($values),
                'avg' => array_sum($values) / count($values),
                'min' => min($values),
                'max' => max($values)
            ];
            
            // Simulate aggregations
            for ($j = 0; $j < 20; $j++) {
                $results['aggregations'][] = [
                    'group' => chr(65 + $j),
                    'count' => rand(10, 100),
                    'total' => rand(1000, 10000)
                ];
            }
            
            return $response->json($results);
        });
    }
    
    public function runComparison(array $loads = [500, 1000, 1500]): void
    {
        echo "\n=== Request Type Performance Comparison ===\n";
        echo "Comparing Light, Normal, and Heavy request processing\n\n";
        
        $types = ['light', 'normal', 'heavy'];
        
        foreach ($loads as $load) {
            echo "Load Level: {$load} requests\n";
            echo str_repeat("=", 50) . "\n";
            
            $this->results[$load] = [];
            
            foreach ($types as $type) {
                echo "  Testing {$type} requests... ";
                $result = $this->runBenchmark($type, $load);
                $this->results[$load][$type] = $result;
                
                echo sprintf(
                    "%.2f req/s (%.2fms avg)\n",
                    $result['throughput'],
                    $result['avg_latency']
                );
            }
            
            // Calculate relative performance
            $this->displayRelativePerformance($load);
            echo "\n";
        }
        
        $this->saveDetailedResults();
    }
    
    private function runBenchmark(string $type, int $requests): array
    {
        // Warmup
        for ($i = 0; $i < 50; $i++) {
            $this->makeRequest($type);
        }
        
        // Actual benchmark
        $latencies = [];
        $start = microtime(true);
        
        for ($i = 0; $i < $requests; $i++) {
            $reqStart = microtime(true);
            $this->makeRequest($type);
            $reqEnd = microtime(true);
            $latencies[] = ($reqEnd - $reqStart) * 1000;
        }
        
        $end = microtime(true);
        $duration = $end - $start;
        
        // Calculate metrics
        sort($latencies);
        
        return [
            'type' => $type,
            'requests' => $requests,
            'duration' => round($duration, 3),
            'throughput' => round($requests / $duration, 2),
            'avg_latency' => round(array_sum($latencies) / count($latencies), 3),
            'min_latency' => round(min($latencies), 3),
            'max_latency' => round(max($latencies), 3),
            'p50_latency' => round($this->percentile($latencies, 50), 3),
            'p95_latency' => round($this->percentile($latencies, 95), 3),
            'p99_latency' => round($this->percentile($latencies, 99), 3)
        ];
    }
    
    private function makeRequest(string $type): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/' . $type;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_POST = [];
        
        ob_start();
        try {
            $request = Request::createFromGlobals();
            $response = $this->app->handle($request);
        } catch (\Exception $e) {
            // Ignore
        }
        ob_end_clean();
    }
    
    private function percentile(array $values, int $percentile): float
    {
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[$index] ?? 0;
    }
    
    private function displayRelativePerformance(int $load): void
    {
        $light = $this->results[$load]['light']['throughput'];
        $normal = $this->results[$load]['normal']['throughput'];
        $heavy = $this->results[$load]['heavy']['throughput'];
        
        echo "\n  Relative Performance:\n";
        echo sprintf("    Normal vs Light: %.1f%% (%.1fx slower)\n", 
            ($normal / $light) * 100, 
            $light / $normal
        );
        echo sprintf("    Heavy vs Light: %.1f%% (%.1fx slower)\n", 
            ($heavy / $light) * 100, 
            $light / $heavy
        );
        echo sprintf("    Heavy vs Normal: %.1f%% (%.1fx slower)\n", 
            ($heavy / $normal) * 100, 
            $normal / $heavy
        );
    }
    
    private function saveDetailedResults(): void
    {
        $summary = [
            'test' => 'Request Type Comparison',
            'framework' => 'PivotPHP Core',
            'timestamp' => date('c'),
            'results' => $this->results,
            'analysis' => $this->generateAnalysis()
        ];
        
        $filename = __DIR__ . '/../results/request-comparison-' . date('Y-m-d-His') . '.json';
        file_put_contents($filename, json_encode($summary, JSON_PRETTY_PRINT));
        
        echo "\nDetailed results saved to: " . basename($filename) . "\n";
    }
    
    private function generateAnalysis(): array
    {
        $analysis = [
            'performance_degradation' => [],
            'scalability' => [],
            'recommendations' => []
        ];
        
        // Analyze performance degradation
        foreach ([500, 1000, 1500] as $load) {
            if (!isset($this->results[$load])) continue;
            
            $light = $this->results[$load]['light']['throughput'];
            $normal = $this->results[$load]['normal']['throughput'];
            $heavy = $this->results[$load]['heavy']['throughput'];
            
            $analysis['performance_degradation'][$load] = [
                'normal_vs_light' => round((1 - $normal / $light) * 100, 1) . '%',
                'heavy_vs_light' => round((1 - $heavy / $light) * 100, 1) . '%',
                'heavy_vs_normal' => round((1 - $heavy / $normal) * 100, 1) . '%'
            ];
        }
        
        // Analyze scalability
        if (isset($this->results[500]) && isset($this->results[1500])) {
            foreach (['light', 'normal', 'heavy'] as $type) {
                $throughput500 = $this->results[500][$type]['throughput'];
                $throughput1500 = $this->results[1500][$type]['throughput'];
                
                $analysis['scalability'][$type] = [
                    'throughput_change' => round((($throughput1500 - $throughput500) / $throughput500) * 100, 1) . '%',
                    'maintains_performance' => abs($throughput1500 - $throughput500) / $throughput500 < 0.2
                ];
            }
        }
        
        // Generate recommendations
        $analysis['recommendations'] = [
            'Use light endpoints for health checks and status monitoring',
            'Normal endpoints are suitable for typical API responses',
            'Heavy endpoints should implement caching or async processing',
            'Consider implementing response streaming for large datasets'
        ];
        
        return $analysis;
    }
}

// Run comparison
$comparison = new RequestTypeComparison();
$comparison->runComparison();