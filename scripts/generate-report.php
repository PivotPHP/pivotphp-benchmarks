#!/usr/bin/env php
<?php

/**
 * Report Generator for HelixPHP Benchmarks
 * 
 * Processes raw benchmark results and generates formatted reports
 */

class ReportGenerator
{
    private string $rawDir = '/results/raw';
    private string $processedDir = '/results/processed';
    private string $reportsDir = '/results/reports';
    
    public function run(): void
    {
        echo "Generating benchmark reports...\n";
        
        $results = $this->loadResults();
        $processed = $this->processResults($results);
        
        $this->generateMarkdownReport($processed);
        $this->generateJsonReport($processed);
        $this->generateComparisonChart($processed);
        
        echo "Reports generated successfully!\n";
    }
    
    private function loadResults(): array
    {
        $results = [];
        $files = glob($this->rawDir . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['status'] === 'success') {
                $results[] = $data;
            }
        }
        
        return $results;
    }
    
    private function processResults(array $results): array
    {
        $processed = [
            'summary' => [],
            'by_framework' => [],
            'by_category' => [],
            'comparisons' => []
        ];
        
        // Group by framework
        foreach ($results as $result) {
            $framework = $result['framework'];
            $category = $result['category'];
            $benchmark = $result['benchmark'];
            
            if (!isset($processed['by_framework'][$framework])) {
                $processed['by_framework'][$framework] = [];
            }
            
            if (!isset($processed['by_category'][$category])) {
                $processed['by_category'][$category] = [];
            }
            
            $processed['by_framework'][$framework][] = $result;
            $processed['by_category'][$category][] = $result;
        }
        
        // Calculate averages and comparisons
        $processed['summary'] = $this->calculateSummary($results);
        $processed['comparisons'] = $this->generateComparisons($results);
        
        return $processed;
    }
    
    private function calculateSummary(array $results): array
    {
        $summary = [];
        
        foreach ($results as $result) {
            $framework = $result['framework'];
            
            if (!isset($summary[$framework])) {
                $summary[$framework] = [
                    'total_benchmarks' => 0,
                    'avg_ops_per_second' => 0,
                    'avg_memory_mb' => 0,
                    'benchmarks' => []
                ];
            }
            
            $summary[$framework]['total_benchmarks']++;
            $summary[$framework]['avg_ops_per_second'] += $result['ops_per_second'];
            $summary[$framework]['avg_memory_mb'] += $result['memory_peak_mb'];
            $summary[$framework]['benchmarks'][] = $result['benchmark'];
        }
        
        // Calculate averages
        foreach ($summary as $framework => &$data) {
            $data['avg_ops_per_second'] = round($data['avg_ops_per_second'] / $data['total_benchmarks']);
            $data['avg_memory_mb'] = round($data['avg_memory_mb'] / $data['total_benchmarks'], 2);
        }
        
        return $summary;
    }
    
    private function generateComparisons(array $results): array
    {
        $comparisons = [];
        $benchmarkGroups = [];
        
        // Group similar benchmarks across frameworks
        foreach ($results as $result) {
            $key = $result['category'] . '/' . $result['benchmark'];
            if (!isset($benchmarkGroups[$key])) {
                $benchmarkGroups[$key] = [];
            }
            $benchmarkGroups[$key][$result['framework']] = $result;
        }
        
        // Calculate relative performance
        foreach ($benchmarkGroups as $benchmark => $frameworks) {
            if (count($frameworks) > 1) {
                $baseline = null;
                
                // Use HelixPHP as baseline if available
                if (isset($frameworks['helix-core'])) {
                    $baseline = $frameworks['helix-core']['ops_per_second'];
                } else {
                    // Use the first framework as baseline
                    $baseline = reset($frameworks)['ops_per_second'];
                }
                
                $comparison = [
                    'benchmark' => $benchmark,
                    'frameworks' => []
                ];
                
                foreach ($frameworks as $framework => $result) {
                    $comparison['frameworks'][$framework] = [
                        'ops_per_second' => $result['ops_per_second'],
                        'relative_performance' => round(($result['ops_per_second'] / $baseline) * 100, 1),
                        'memory_mb' => $result['memory_peak_mb']
                    ];
                }
                
                $comparisons[] = $comparison;
            }
        }
        
        return $comparisons;
    }
    
    private function generateMarkdownReport(array $processed): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $filename = $this->reportsDir . '/benchmark-report-' . date('Y-m-d') . '.md';
        
        $markdown = "# HelixPHP Benchmark Report\n\n";
        $markdown .= "Generated: {$timestamp}\n\n";
        
        // Executive Summary
        $markdown .= "## Executive Summary\n\n";
        foreach ($processed['summary'] as $framework => $data) {
            $markdown .= "- **{$framework}**: ";
            $markdown .= number_format($data['avg_ops_per_second']) . " ops/sec average, ";
            $markdown .= "{$data['avg_memory_mb']} MB memory\n";
        }
        $markdown .= "\n";
        
        // Detailed Results by Framework
        $markdown .= "## Detailed Results\n\n";
        foreach ($processed['by_framework'] as $framework => $results) {
            $markdown .= "### {$framework}\n\n";
            $markdown .= "| Benchmark | Category | Ops/sec | Memory (MB) | Avg Time (ms) |\n";
            $markdown .= "|-----------|----------|---------|-------------|---------------|\n";
            
            foreach ($results as $result) {
                $markdown .= sprintf(
                    "| %s | %s | %s | %.2f | %.3f |\n",
                    $result['benchmark'],
                    $result['category'],
                    number_format($result['ops_per_second']),
                    $result['memory_peak_mb'],
                    $result['avg_time_ms']
                );
            }
            $markdown .= "\n";
        }
        
        // Comparisons
        if (!empty($processed['comparisons'])) {
            $markdown .= "## Performance Comparisons\n\n";
            foreach ($processed['comparisons'] as $comparison) {
                $markdown .= "### {$comparison['benchmark']}\n\n";
                $markdown .= "| Framework | Ops/sec | Relative Performance | Memory (MB) |\n";
                $markdown .= "|-----------|---------|---------------------|-------------|\n";
                
                foreach ($comparison['frameworks'] as $framework => $data) {
                    $markdown .= sprintf(
                        "| %s | %s | %s%% | %.2f |\n",
                        $framework,
                        number_format($data['ops_per_second']),
                        $data['relative_performance'],
                        $data['memory_mb']
                    );
                }
                $markdown .= "\n";
            }
        }
        
        file_put_contents($filename, $markdown);
        echo "Markdown report saved to: {$filename}\n";
    }
    
    private function generateJsonReport(array $processed): void
    {
        $filename = $this->reportsDir . '/benchmark-report-' . date('Y-m-d') . '.json';
        
        $report = [
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'summary' => $processed['summary'],
            'comparisons' => $processed['comparisons'],
            'raw_results' => $processed['by_framework']
        ];
        
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        echo "JSON report saved to: {$filename}\n";
    }
    
    private function generateComparisonChart(array $processed): void
    {
        // Generate data for charting libraries (can be used with Chart.js, D3.js, etc.)
        $chartData = [
            'labels' => [],
            'datasets' => []
        ];
        
        $frameworks = [];
        foreach ($processed['comparisons'] as $comparison) {
            $chartData['labels'][] = basename($comparison['benchmark']);
            
            foreach ($comparison['frameworks'] as $framework => $data) {
                if (!isset($frameworks[$framework])) {
                    $frameworks[$framework] = [
                        'label' => $framework,
                        'data' => []
                    ];
                }
                $frameworks[$framework]['data'][] = $data['ops_per_second'];
            }
        }
        
        $chartData['datasets'] = array_values($frameworks);
        
        $filename = $this->reportsDir . '/chart-data-' . date('Y-m-d') . '.json';
        file_put_contents($filename, json_encode($chartData, JSON_PRETTY_PRINT));
        echo "Chart data saved to: {$filename}\n";
    }
}

// Run the report generator
$generator = new ReportGenerator();
$generator->run();