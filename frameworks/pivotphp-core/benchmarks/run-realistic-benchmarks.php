#!/usr/bin/env php
<?php

/**
 * Realistic Benchmark Runner
 * Executes all consolidated realistic benchmarks and generates comprehensive report
 */

declare(strict_types=1);

class RealisticBenchmarkRunner
{
    private array $benchmarks = [];
    private array $results = [];
    
    public function __construct()
    {
        $this->benchmarks = [
            'Comprehensive API Benchmark' => __DIR__ . '/comprehensive-realistic-benchmark.php',
            'Realistic API Scenarios' => __DIR__ . '/realistic-api-benchmark.php',
            'Real-World Load Simulation' => __DIR__ . '/real-world-load-simulation.php'
        ];
    }
    
    public function runAllBenchmarks(): array
    {
        echo "🚀 Realistic Benchmark Suite - PivotPHP Core v1.1.1\n";
        echo "===================================================\n\n";
        
        foreach ($this->benchmarks as $name => $scriptPath) {
            echo "🔄 Running: {$name}\n";
            echo "   Script: " . basename($scriptPath) . "\n";
            
            if (!file_exists($scriptPath)) {
                echo "   ❌ ERROR: Script not found\n\n";
                continue;
            }
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            try {
                // Execute benchmark script
                $output = $this->executeBenchmark($scriptPath);
                
                $endTime = microtime(true);
                $endMemory = memory_get_peak_usage();
                
                $duration = $endTime - $startTime;
                $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
                
                $this->results[$name] = [
                    'script' => basename($scriptPath),
                    'duration' => round($duration, 2),
                    'memory_used' => round($memoryUsed, 2),
                    'status' => 'completed',
                    'output_summary' => $this->extractSummary($output)
                ];
                
                echo "   ✅ Completed in {$duration}s, Memory: {$memoryUsed}MB\n";
                
            } catch (Exception $e) {
                $this->results[$name] = [
                    'script' => basename($scriptPath),
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                
                echo "   ❌ ERROR: " . $e->getMessage() . "\n";
            }
            
            echo "\n";
        }
        
        return $this->results;
    }
    
    private function executeBenchmark(string $scriptPath): string
    {
        ob_start();
        
        // Include and execute the benchmark script
        require $scriptPath;
        
        $output = ob_get_clean();
        return $output;
    }
    
    private function extractSummary(string $output): array
    {
        $summary = ['lines' => substr_count($output, "\n")];
        
        // Extract performance metrics from output
        if (preg_match('/(\d+(?:\.\d+)?)\s*ops\/sec/', $output, $matches)) {
            $summary['ops_per_sec'] = (float)$matches[1];
        }
        
        if (preg_match('/(\d+(?:\.\d+)?)\s*MB/', $output, $matches)) {
            $summary['memory_mb'] = (float)$matches[1];
        }
        
        if (preg_match('/(\d+(?:\.\d+)?)\s*ms/', $output, $matches)) {
            $summary['response_time_ms'] = (float)$matches[1];
        }
        
        // Extract success indicators
        if (strpos($output, '✅') !== false) {
            $summary['success_indicators'] = substr_count($output, '✅');
        }
        
        if (strpos($output, '❌') !== false) {
            $summary['error_indicators'] = substr_count($output, '❌');
        }
        
        return $summary;
    }
    
    public function generateConsolidatedReport(): array
    {
        $totalDuration = array_sum(array_column($this->results, 'duration'));
        $totalMemory = array_sum(array_column($this->results, 'memory_used'));
        $completedBenchmarks = count(array_filter($this->results, fn($r) => $r['status'] === 'completed'));
        $totalBenchmarks = count($this->results);
        
        // Extract performance data
        $performanceData = [];
        foreach ($this->results as $name => $result) {
            if (isset($result['output_summary']['ops_per_sec'])) {
                $performanceData[$name] = $result['output_summary']['ops_per_sec'];
            }
        }
        
        $report = [
            'execution_summary' => [
                'total_benchmarks' => $totalBenchmarks,
                'completed_benchmarks' => $completedBenchmarks,
                'success_rate' => round(($completedBenchmarks / $totalBenchmarks) * 100, 1),
                'total_duration' => round($totalDuration, 2),
                'total_memory_used' => round($totalMemory, 2),
                'avg_duration_per_benchmark' => round($totalDuration / $totalBenchmarks, 2)
            ],
            'performance_summary' => [
                'total_ops_per_sec' => array_sum($performanceData),
                'avg_ops_per_sec' => count($performanceData) > 0 ? round(array_sum($performanceData) / count($performanceData), 2) : 0,
                'benchmark_performance' => $performanceData
            ],
            'detailed_results' => $this->results
        ];
        
        return $report;
    }
    
    public function displayReport(array $report): void
    {
        echo "📊 Consolidated Realistic Benchmark Report\n";
        echo "=========================================\n\n";
        
        echo "🎯 Execution Summary:\n";
        echo "  Total Benchmarks: {$report['execution_summary']['total_benchmarks']}\n";
        echo "  Completed: {$report['execution_summary']['completed_benchmarks']}\n";
        echo "  Success Rate: {$report['execution_summary']['success_rate']}%\n";
        echo "  Total Duration: {$report['execution_summary']['total_duration']}s\n";
        echo "  Total Memory: {$report['execution_summary']['total_memory_used']}MB\n";
        echo "  Avg Duration/Benchmark: {$report['execution_summary']['avg_duration_per_benchmark']}s\n\n";
        
        echo "⚡ Performance Summary:\n";
        echo "  Total Operations/sec: " . number_format($report['performance_summary']['total_ops_per_sec'], 2) . "\n";
        echo "  Average Operations/sec: " . number_format($report['performance_summary']['avg_ops_per_sec'], 2) . "\n\n";
        
        echo "📋 Individual Benchmark Results:\n";
        foreach ($report['detailed_results'] as $name => $result) {
            $status = $result['status'] === 'completed' ? '✅' : '❌';
            echo "  {$status} {$name}\n";
            echo "    Script: {$result['script']}\n";
            
            if ($result['status'] === 'completed') {
                echo "    Duration: {$result['duration']}s\n";
                echo "    Memory: {$result['memory_used']}MB\n";
                
                if (isset($result['output_summary']['ops_per_sec'])) {
                    echo "    Performance: " . number_format($result['output_summary']['ops_per_sec'], 2) . " ops/sec\n";
                }
            } else {
                echo "    Error: {$result['error']}\n";
            }
            echo "\n";
        }
    }
    
    public function saveReport(array $report): string
    {
        $timestamp = date('Y-m-d-H-i-s');
        $filename = "/tmp/realistic-benchmark-report-{$timestamp}.json";
        
        $fullReport = array_merge($report, [
            'timestamp' => $timestamp,
            'framework' => 'PivotPHP Core v1.1.1',
            'report_type' => 'consolidated_realistic_benchmarks',
            'system_info' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ]);
        
        file_put_contents($filename, json_encode($fullReport, JSON_PRETTY_PRINT));
        
        return $filename;
    }
}

// Execute consolidated benchmark suite
if (php_sapi_name() === 'cli') {
    $runner = new RealisticBenchmarkRunner();
    
    echo "🎯 Starting Consolidated Realistic Benchmark Suite\n";
    echo "Framework: PivotPHP Core v1.1.1\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    $results = $runner->runAllBenchmarks();
    $report = $runner->generateConsolidatedReport();
    
    $runner->displayReport($report);
    
    $filename = $runner->saveReport($report);
    
    echo "💾 Consolidated report saved to: {$filename}\n";
    echo "🎉 Realistic benchmark suite completed!\n";
    
    // Summary for quick reference
    echo "\n📈 Quick Performance Summary:\n";
    echo "  Framework: PivotPHP Core v1.1.1\n";
    echo "  Total Performance: " . number_format($report['performance_summary']['total_ops_per_sec'], 2) . " ops/sec\n";
    echo "  Success Rate: {$report['execution_summary']['success_rate']}%\n";
    echo "  Benchmarks: {$report['execution_summary']['completed_benchmarks']}/{$report['execution_summary']['total_benchmarks']}\n";
}