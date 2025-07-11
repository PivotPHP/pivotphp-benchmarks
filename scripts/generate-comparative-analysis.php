#!/usr/bin/env php
<?php

/**
 * Comparative Analysis Report Generator
 * 
 * Generates detailed comparative analysis reports from benchmark results
 */

class ComparativeAnalysisGenerator
{
    private array $frameworks = [
        'pivotphp-core' => 'PivotPHP Core v1.1.1',
        'pivotphp-orm' => 'PivotPHP ORM v1.0.1',
        'pivotphp-reactphp' => 'PivotPHP ReactPHP v0.1.0',
        'slim4' => 'Slim 4',
        'lumen' => 'Laravel Lumen',
        'flight' => 'Flight',
        'symfony' => 'Symfony'
    ];
    
    private string $resultsDir;
    private string $timestamp;
    
    public function __construct(string $resultsDir = '/tmp')
    {
        $this->resultsDir = $resultsDir;
        $this->timestamp = date('Y-m-d-H-i-s');
    }
    
    public function findLatestResults(): array
    {
        $results = [];
        $files = glob($this->resultsDir . '/unified-api-benchmark-*-*.json');
        
        if (empty($files)) {
            echo "❌ No benchmark result files found in {$this->resultsDir}\n";
            return [];
        }
        
        // Group by framework and get latest for each
        $latestFiles = [];
        foreach ($files as $file) {
            $basename = basename($file);
            if (preg_match('/unified-api-benchmark-([^-]+)-(\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2})\.json/', $basename, $matches)) {
                $framework = $matches[1];
                $timestamp = $matches[2];
                
                if (!isset($latestFiles[$framework]) || $timestamp > $latestFiles[$framework]['timestamp']) {
                    $latestFiles[$framework] = [
                        'file' => $file,
                        'timestamp' => $timestamp
                    ];
                }
            }
        }
        
        foreach ($latestFiles as $framework => $data) {
            $content = file_get_contents($data['file']);
            $json = json_decode($content, true);
            
            if ($json) {
                $results[$framework] = $json;
                echo "✅ Loaded results for {$this->frameworks[$framework] ?? $framework}\n";
            } else {
                echo "❌ Failed to parse results for $framework\n";
            }
        }
        
        return $results;
    }
    
    public function generateEnterpriseReport(array $results): void
    {
        $reportFile = "{$this->resultsDir}/enterprise-performance-report-{$this->timestamp}.md";
        
        $report = "# PivotPHP Enterprise Performance Analysis\n\n";
        $report .= "## Executive Summary\n\n";
        $report .= "This report presents a comprehensive performance analysis of the PivotPHP microframework ecosystem compared to industry-standard PHP frameworks. The analysis focuses on API throughput, latency characteristics, and production readiness metrics.\n\n";
        
        $report .= "**Key Findings:**\n";
        $report .= "- Test Date: " . date('Y-m-d H:i:s') . "\n";
        $report .= "- Frameworks Analyzed: " . count($results) . "\n";
        $report .= "- Test Methodology: Real HTTP API requests\n";
        $report .= "- Environment: Docker containers with identical resource limits\n\n";
        
        // Performance rankings
        $summaryData = $this->prepareSummaryData($results);
        
        $report .= "## 🏆 Performance Rankings\n\n";
        $report .= "### API Throughput (Requests/Second)\n\n";
        
        foreach ($summaryData as $index => $data) {
            $rank = $index + 1;
            $emoji = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '🔸'));
            $report .= "$emoji **$rank. {$data['framework']}**\n";
            $report .= "   - Total Throughput: " . number_format($data['req_per_sec']) . " req/sec\n";
            $report .= "   - Average Latency: " . round($data['avg_latency'], 2) . "ms\n";
            $report .= "   - Total Requests: " . number_format($data['total_requests']) . "\n\n";
        }
        
        // Detailed performance matrix
        $report .= "## 📊 Performance Matrix\n\n";
        $report .= "| Framework | Total Req/sec | Avg Latency | P95 Latency | P99 Latency | Error Rate |\n";
        $report .= "|-----------|---------------|-------------|-------------|-------------|------------|\n";
        
        foreach ($summaryData as $data) {
            $avgP95 = $this->calculateAverageP95($results[$data['framework_key']]['results']);
            $avgP99 = $this->calculateAverageP99($results[$data['framework_key']]['results']);
            $avgErrorRate = $this->calculateAverageErrorRate($results[$data['framework_key']]['results']);
            
            $report .= sprintf("| %s | %s | %.2fms | %.2fms | %.2fms | %.2f%% |\n",
                $data['framework'],
                number_format($data['req_per_sec']),
                $data['avg_latency'],
                $avgP95,
                $avgP99,
                $avgErrorRate
            );
        }
        
        // PivotPHP ecosystem analysis
        $report .= "\n## 🔥 PivotPHP Ecosystem Analysis\n\n";
        
        $pivotphpResults = array_filter($results, function($key) {
            return strpos($key, 'pivotphp') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        if (!empty($pivotphpResults)) {
            $report .= "### PivotPHP Variants Performance\n\n";
            foreach ($pivotphpResults as $framework => $data) {
                $frameworkName = $this->frameworks[$framework] ?? $framework;
                $summary = $data['summary'];
                
                $report .= "**$frameworkName:**\n";
                $report .= "- API Throughput: " . number_format($summary['total_throughput_req_per_sec']) . " req/sec\n";
                $report .= "- Average Latency: " . round($summary['avg_latency_ms'], 2) . "ms\n";
                $report .= "- Total Requests Processed: " . number_format($summary['total_requests']) . "\n";
                $report .= "- Test Duration: " . round($summary['total_time_seconds'], 2) . " seconds\n\n";
            }
        }
        
        // Endpoint analysis
        $report .= "## 📈 Endpoint Performance Analysis\n\n";
        $report .= "### Critical API Endpoints\n\n";
        
        $endpoints = [
            '/' => 'Home Page',
            '/api/health' => 'Health Check',
            '/api/users' => 'User Listing',
            '/api/users/1' => 'User Detail',
            'POST /api/users' => 'User Creation',
            'PUT /api/users/1' => 'User Update',
            'DELETE /api/users/1' => 'User Deletion'
        ];
        
        foreach ($endpoints as $endpoint => $description) {
            $report .= "#### $description ($endpoint)\n\n";
            $report .= "| Framework | Req/sec | Avg Latency | P95 Latency | Error Rate |\n";
            $report .= "|-----------|---------|-------------|-------------|------------|\n";
            
            $endpointData = [];
            foreach ($results as $framework => $data) {
                $endpointResult = $this->findEndpointResult($data['results'], $endpoint);
                if ($endpointResult) {
                    $endpointData[] = [
                        'framework' => $this->frameworks[$framework] ?? $framework,
                        'req_per_sec' => $endpointResult['requests_per_second'],
                        'avg_latency' => $endpointResult['avg_latency_ms'],
                        'p95_latency' => $endpointResult['p95_latency_ms'],
                        'error_rate' => $endpointResult['error_rate_percent']
                    ];
                }
            }
            
            // Sort by performance
            usort($endpointData, function($a, $b) {
                return $b['req_per_sec'] <=> $a['req_per_sec'];
            });
            
            foreach ($endpointData as $data) {
                $report .= sprintf("| %s | %s | %.2fms | %.2fms | %.2f%% |\n",
                    $data['framework'],
                    number_format($data['req_per_sec']),
                    $data['avg_latency'],
                    $data['p95_latency'],
                    $data['error_rate']
                );
            }
            
            $report .= "\n";
        }
        
        // Technical analysis
        $report .= "## 🔧 Technical Analysis\n\n";
        $report .= "### Test Methodology\n\n";
        $report .= "- **Request Method:** Real HTTP requests via cURL\n";
        $report .= "- **Concurrent Users:** Sequential testing (single-threaded)\n";
        $report .= "- **Requests per Endpoint:** 300\n";
        $report .= "- **Total Endpoints:** 7\n";
        $report .= "- **Environment:** Docker containers with identical resource limits\n";
        $report .= "- **Resource Limits:** 2 CPUs, 1GB RAM per container\n";
        $report .= "- **PHP Version:** 8.2 with OPcache and JIT enabled\n\n";
        
        $report .= "### Performance Metrics\n\n";
        $report .= "- **Throughput:** Requests per second (req/sec)\n";
        $report .= "- **Latency:** Response time in milliseconds\n";
        $report .= "- **P95 Latency:** 95th percentile response time\n";
        $report .= "- **P99 Latency:** 99th percentile response time\n";
        $report .= "- **Error Rate:** Percentage of failed requests (HTTP 4xx/5xx)\n\n";
        
        // Recommendations
        $report .= "## 💡 Recommendations\n\n";
        $report .= "### For Production Deployment\n\n";
        
        if (isset($results['pivotphp-core'])) {
            $pivotphpPerf = $results['pivotphp-core']['summary']['total_throughput_req_per_sec'];
            $topPerf = $summaryData[0]['req_per_sec'];
            
            if ($pivotphpPerf >= $topPerf * 0.9) {
                $report .= "✅ **PivotPHP Core demonstrates competitive performance** suitable for production workloads.\n\n";
            } else {
                $report .= "⚠️ **PivotPHP Core shows room for optimization** compared to top performers.\n\n";
            }
        }
        
        $report .= "### Performance Optimization Areas\n\n";
        $report .= "1. **Response Time Optimization:** Focus on reducing P95 and P99 latencies\n";
        $report .= "2. **Error Rate Reduction:** Implement robust error handling\n";
        $report .= "3. **Throughput Scaling:** Optimize for higher concurrent loads\n";
        $report .= "4. **Resource Utilization:** Monitor memory and CPU usage patterns\n\n";
        
        $report .= "---\n\n";
        $report .= "*Report generated by PivotPHP Benchmarks Suite*\n";
        $report .= "*Test Date: " . date('Y-m-d H:i:s') . "*\n";
        
        file_put_contents($reportFile, $report);
        echo "📄 Enterprise report generated: $reportFile\n";
    }
    
    private function prepareSummaryData(array $results): array
    {
        $summaryData = [];
        
        foreach ($results as $framework => $data) {
            $summary = $data['summary'];
            $frameworkName = $this->frameworks[$framework] ?? $framework;
            
            $summaryData[] = [
                'framework' => $frameworkName,
                'framework_key' => $framework,
                'req_per_sec' => $summary['total_throughput_req_per_sec'],
                'avg_latency' => $summary['avg_latency_ms'],
                'total_requests' => $summary['total_requests'],
                'total_time' => $summary['total_time_seconds']
            ];
        }
        
        // Sort by performance
        usort($summaryData, function($a, $b) {
            return $b['req_per_sec'] <=> $a['req_per_sec'];
        });
        
        return $summaryData;
    }
    
    private function calculateAverageP95(array $results): float
    {
        $p95Values = array_column($results, 'p95_latency_ms');
        return array_sum($p95Values) / count($p95Values);
    }
    
    private function calculateAverageP99(array $results): float
    {
        $p99Values = array_column($results, 'p99_latency_ms');
        return array_sum($p99Values) / count($p99Values);
    }
    
    private function calculateAverageErrorRate(array $results): float
    {
        $errorRates = array_column($results, 'error_rate_percent');
        return array_sum($errorRates) / count($errorRates);
    }
    
    private function findEndpointResult(array $results, string $endpoint): ?array
    {
        foreach ($results as $result) {
            $fullEndpoint = $result['method'] . ' ' . $result['endpoint'];
            if ($fullEndpoint === $endpoint || $result['endpoint'] === $endpoint) {
                return $result;
            }
        }
        return null;
    }
    
    public function generateJsonReport(array $results): void
    {
        $reportFile = "{$this->resultsDir}/comparative-analysis-{$this->timestamp}.json";
        
        $analysis = [
            'benchmark_type' => 'comparative-analysis',
            'timestamp' => date('c'),
            'test_date' => date('Y-m-d H:i:s'),
            'frameworks_analyzed' => count($results),
            'summary' => $this->prepareSummaryData($results),
            'detailed_results' => $results,
            'metadata' => [
                'methodology' => 'Real HTTP API requests',
                'requests_per_endpoint' => 300,
                'total_endpoints' => 7,
                'environment' => 'Docker containers',
                'resource_limits' => '2 CPUs, 1GB RAM',
                'php_version' => '8.2'
            ]
        ];
        
        file_put_contents($reportFile, json_encode($analysis, JSON_PRETTY_PRINT));
        echo "💾 JSON analysis saved: $reportFile\n";
    }
}

// Parse command line arguments
function parseArgs($argv): array
{
    $args = [
        'results_dir' => '/tmp',
        'help' => false
    ];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--results-dir=') === 0) {
            $args['results_dir'] = substr($arg, 14);
        }
        if ($arg === '--help' || $arg === '-h') {
            $args['help'] = true;
        }
    }
    
    return $args;
}

function showHelp(): void
{
    echo "📊 Comparative Analysis Report Generator\n";
    echo "=======================================\n\n";
    echo "Usage: php generate-comparative-analysis.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --results-dir=path   Directory containing benchmark result files\n";
    echo "                       Default: /tmp\n";
    echo "  --help, -h           Show this help message\n\n";
    echo "Examples:\n";
    echo "  php generate-comparative-analysis.php\n";
    echo "  php generate-comparative-analysis.php --results-dir=/path/to/results\n\n";
}

// Main execution
if (php_sapi_name() === 'cli') {
    $args = parseArgs($argv);
    
    if ($args['help']) {
        showHelp();
        exit(0);
    }
    
    echo "📊 Comparative Analysis Report Generator\n";
    echo "=======================================\n\n";
    
    $generator = new ComparativeAnalysisGenerator($args['results_dir']);
    
    echo "🔍 Searching for benchmark results...\n";
    $results = $generator->findLatestResults();
    
    if (empty($results)) {
        echo "❌ No benchmark results found\n";
        exit(1);
    }
    
    echo "\n📈 Generating comparative analysis...\n";
    $generator->generateEnterpriseReport($results);
    $generator->generateJsonReport($results);
    
    echo "\n✅ Comparative analysis completed successfully!\n";
}