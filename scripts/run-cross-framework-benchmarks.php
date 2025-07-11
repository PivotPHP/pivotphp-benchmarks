#!/usr/bin/env php
<?php

/**
 * Cross-Framework API Benchmark Runner
 * 
 * Executes unified API benchmarks across all frameworks simultaneously
 * Generates comparative reports and analysis
 */

class CrossFrameworkBenchmarkRunner
{
    private array $frameworks = [
        'pivotphp-core' => ['port' => 8001, 'name' => 'PivotPHP Core'],
        'slim4' => ['port' => 8002, 'name' => 'Slim 4'],
        'lumen' => ['port' => 8003, 'name' => 'Lumen'],
        'flight' => ['port' => 8004, 'name' => 'Flight'],
        'symfony' => ['port' => 8005, 'name' => 'Symfony']
    ];
    
    private array $results = [];
    private string $resultsDir;
    private string $timestamp;
    
    public function __construct()
    {
        $this->resultsDir = '/tmp';
        $this->timestamp = date('Y-m-d-H-i-s');
    }
    
    public function checkDockerEnvironment(): bool
    {
        echo "🔍 Checking Docker environment...\n";
        
        // Check if docker-compose is available
        $dockerComposeCheck = shell_exec('docker-compose --version 2>/dev/null');
        if (!$dockerComposeCheck) {
            echo "❌ Docker Compose not found\n";
            return false;
        }
        
        // Check if containers are running
        $runningContainers = shell_exec('docker-compose ps --services --filter "status=running" 2>/dev/null');
        if (!$runningContainers) {
            echo "⚠️  No running containers found. Starting Docker environment...\n";
            $this->startDockerEnvironment();
        }
        
        return true;
    }
    
    public function startDockerEnvironment(): bool
    {
        echo "🚀 Starting Docker environment...\n";
        
        $output = shell_exec('docker-compose up -d 2>&1');
        if (strpos($output, 'error') !== false || strpos($output, 'Error') !== false) {
            echo "❌ Failed to start Docker environment:\n$output\n";
            return false;
        }
        
        echo "✅ Docker environment started successfully\n";
        
        // Wait for containers to be ready
        echo "⏳ Waiting for containers to be ready...\n";
        sleep(10);
        
        return true;
    }
    
    public function checkFrameworkAvailability(): array
    {
        $available = [];
        
        foreach ($this->frameworks as $framework => $config) {
            $url = "http://127.0.0.1:{$config['port']}/";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HEADER, false);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode > 0 && $httpCode < 500) {
                $available[$framework] = $config;
                echo "✅ {$config['name']} available at port {$config['port']}\n";
            } else {
                echo "❌ {$config['name']} not available at port {$config['port']}\n";
            }
        }
        
        return $available;
    }
    
    public function runBenchmark(string $framework, array $config): ?array
    {
        $benchmarkPath = "/app/frameworks/$framework/benchmarks/unified-api-benchmark.php";
        
        if (!file_exists($benchmarkPath)) {
            echo "❌ Benchmark file not found for $framework: $benchmarkPath\n";
            return null;
        }
        
        echo "🔥 Running benchmark for {$config['name']}...\n";
        
        // Use docker exec to run benchmark inside container
        $containerName = "pivotphp-benchmarks-{$framework}-1";
        $command = "docker exec $containerName php /app/benchmarks/unified-api-benchmark.php --full 2>&1";
        
        $output = shell_exec($command);
        
        if (!$output) {
            echo "❌ Failed to run benchmark for $framework\n";
            return null;
        }
        
        // Parse JSON results from output
        $jsonMatch = [];
        if (preg_match('/💾 Results saved to: (.+\.json)/', $output, $jsonMatch)) {
            $jsonFile = $jsonMatch[1];
            
            // Copy results from container to host
            $hostResultFile = "{$this->resultsDir}/cross-framework-{$framework}-{$this->timestamp}.json";
            shell_exec("docker cp $containerName:$jsonFile $hostResultFile");
            
            if (file_exists($hostResultFile)) {
                $results = json_decode(file_get_contents($hostResultFile), true);
                if ($results) {
                    echo "✅ Benchmark completed for {$config['name']}\n";
                    return $results;
                }
            }
        }
        
        echo "❌ Failed to parse results for $framework\n";
        return null;
    }
    
    public function runAllBenchmarks(): array
    {
        $availableFrameworks = $this->checkFrameworkAvailability();
        
        if (empty($availableFrameworks)) {
            echo "❌ No frameworks available for benchmarking\n";
            return [];
        }
        
        $results = [];
        
        foreach ($availableFrameworks as $framework => $config) {
            $result = $this->runBenchmark($framework, $config);
            if ($result) {
                $results[$framework] = $result;
            }
        }
        
        return $results;
    }
    
    public function generateComparativeReport(array $results): void
    {
        $reportFile = "{$this->resultsDir}/cross-framework-comparative-report-{$this->timestamp}.md";
        
        $report = "# Cross-Framework API Performance Comparison\n\n";
        $report .= "**Test Date:** " . date('Y-m-d H:i:s') . "\n";
        $report .= "**Test Type:** Unified API Benchmark\n";
        $report .= "**Frameworks Tested:** " . count($results) . "\n\n";
        
        // Summary table
        $report .= "## 📊 Performance Summary\n\n";
        $report .= "| Framework | Total Req/sec | Avg Latency (ms) | Total Requests | Throughput (MB/s) |\n";
        $report .= "|-----------|---------------|------------------|----------------|-------------------|\n";
        
        $summaryData = [];
        
        foreach ($results as $framework => $data) {
            $summary = $data['summary'];
            $frameworkName = $this->frameworks[$framework]['name'] ?? $framework;
            
            $summaryData[] = [
                'framework' => $frameworkName,
                'req_per_sec' => $summary['total_throughput_req_per_sec'],
                'avg_latency' => $summary['avg_latency_ms'],
                'total_requests' => $summary['total_requests'],
                'total_time' => $summary['total_time_seconds']
            ];
            
            $report .= sprintf("| %s | %s | %.2f | %s | %.2f |\n",
                $frameworkName,
                number_format($summary['total_throughput_req_per_sec']),
                $summary['avg_latency_ms'],
                number_format($summary['total_requests']),
                array_sum(array_column($data['results'], 'throughput_mbps'))
            );
        }
        
        // Sort by performance
        usort($summaryData, function($a, $b) {
            return $b['req_per_sec'] <=> $a['req_per_sec'];
        });
        
        $report .= "\n## 🏆 Performance Ranking\n\n";
        foreach ($summaryData as $index => $data) {
            $rank = $index + 1;
            $emoji = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '🔸'));
            $report .= "$emoji **$rank. {$data['framework']}** - " . number_format($data['req_per_sec']) . " req/sec\n";
        }
        
        // Detailed results for each framework
        $report .= "\n## 📈 Detailed Results\n\n";
        
        foreach ($results as $framework => $data) {
            $frameworkName = $this->frameworks[$framework]['name'] ?? $framework;
            $report .= "### $frameworkName\n\n";
            
            $report .= "| Endpoint | Method | Req/sec | Avg Latency | P95 Latency | P99 Latency | Error Rate |\n";
            $report .= "|----------|--------|---------|-------------|-------------|-------------|------------|\n";
            
            foreach ($data['results'] as $result) {
                $report .= sprintf("| %s | %s | %s | %.2fms | %.2fms | %.2fms | %.2f%% |\n",
                    $result['endpoint'],
                    $result['method'],
                    number_format($result['requests_per_second']),
                    $result['avg_latency_ms'],
                    $result['p95_latency_ms'],
                    $result['p99_latency_ms'],
                    $result['error_rate_percent']
                );
            }
            
            $report .= "\n";
        }
        
        // Technical specifications
        $report .= "## 🔧 Technical Specifications\n\n";
        $report .= "- **Test Method:** Real HTTP requests via cURL\n";
        $report .= "- **Requests per Endpoint:** 300\n";
        $report .= "- **Endpoints Tested:** 7 (GET /, GET /api/health, GET /api/users, GET /api/users/1, POST /api/users, PUT /api/users/1, DELETE /api/users/1)\n";
        $report .= "- **Environment:** Docker containers with identical resource limits\n";
        $report .= "- **PHP Version:** 8.2\n";
        $report .= "- **OPcache:** Enabled\n";
        $report .= "- **JIT:** Enabled\n\n";
        
        $report .= "---\n";
        $report .= "*Generated by PivotPHP Benchmarks Suite*\n";
        
        file_put_contents($reportFile, $report);
        echo "📄 Comparative report saved to: $reportFile\n";
    }
    
    public function saveConsolidatedResults(array $results): void
    {
        $consolidatedFile = "{$this->resultsDir}/cross-framework-consolidated-{$this->timestamp}.json";
        
        $consolidatedData = [
            'benchmark_type' => 'cross-framework-unified-api',
            'timestamp' => date('c'),
            'frameworks_tested' => count($results),
            'results' => $results,
            'metadata' => [
                'test_date' => date('Y-m-d H:i:s'),
                'php_version' => '8.2',
                'environment' => 'docker',
                'requests_per_endpoint' => 300,
                'endpoints_tested' => 7
            ]
        ];
        
        file_put_contents($consolidatedFile, json_encode($consolidatedData, JSON_PRETTY_PRINT));
        echo "💾 Consolidated results saved to: $consolidatedFile\n";
    }
    
    public function printSummary(array $results): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🎯 Cross-Framework Benchmark Summary\n";
        echo str_repeat("=", 80) . "\n\n";
        
        if (empty($results)) {
            echo "❌ No results available\n";
            return;
        }
        
        $summaryData = [];
        foreach ($results as $framework => $data) {
            $summary = $data['summary'];
            $frameworkName = $this->frameworks[$framework]['name'] ?? $framework;
            
            $summaryData[] = [
                'framework' => $frameworkName,
                'req_per_sec' => $summary['total_throughput_req_per_sec'],
                'avg_latency' => $summary['avg_latency_ms'],
                'total_requests' => $summary['total_requests']
            ];
        }
        
        // Sort by performance
        usort($summaryData, function($a, $b) {
            return $b['req_per_sec'] <=> $a['req_per_sec'];
        });
        
        echo "🏆 Performance Ranking:\n\n";
        foreach ($summaryData as $index => $data) {
            $rank = $index + 1;
            $emoji = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '🔸'));
            echo sprintf("%s %d. %-20s %s req/sec (%.2fms avg latency)\n",
                $emoji,
                $rank,
                $data['framework'],
                number_format($data['req_per_sec']),
                $data['avg_latency']
            );
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 Test completed successfully!\n";
        echo "📁 Results saved to: {$this->resultsDir}/\n";
        echo str_repeat("=", 80) . "\n";
    }
}

// Parse command line arguments
function parseArgs($argv): array
{
    $args = [
        'frameworks' => null,
        'skip_docker_check' => false,
        'help' => false
    ];
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--frameworks=') === 0) {
            $args['frameworks'] = explode(',', substr($arg, 13));
        }
        if ($arg === '--skip-docker-check') {
            $args['skip_docker_check'] = true;
        }
        if ($arg === '--help' || $arg === '-h') {
            $args['help'] = true;
        }
    }
    
    return $args;
}

function showHelp(): void
{
    echo "🔥 Cross-Framework API Benchmark Runner\n";
    echo "=====================================\n\n";
    echo "Usage: php run-cross-framework-benchmarks.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --frameworks=list    Comma-separated list of frameworks to test\n";
    echo "                       Available: pivotphp-core,slim4,lumen,flight,symfony\n";
    echo "  --skip-docker-check  Skip Docker environment validation\n";
    echo "  --help, -h           Show this help message\n\n";
    echo "Examples:\n";
    echo "  php run-cross-framework-benchmarks.php\n";
    echo "  php run-cross-framework-benchmarks.php --frameworks=pivotphp-core,slim4\n";
    echo "  php run-cross-framework-benchmarks.php --skip-docker-check\n\n";
}

// Main execution
if (php_sapi_name() === 'cli') {
    $args = parseArgs($argv);
    
    if ($args['help']) {
        showHelp();
        exit(0);
    }
    
    echo "🔥 Cross-Framework API Benchmark Runner\n";
    echo "=====================================\n\n";
    echo "🎯 Running unified API performance tests across multiple frameworks\n\n";
    
    $runner = new CrossFrameworkBenchmarkRunner();
    
    // Check Docker environment
    if (!$args['skip_docker_check']) {
        if (!$runner->checkDockerEnvironment()) {
            echo "❌ Docker environment check failed\n";
            exit(1);
        }
    }
    
    // Run benchmarks
    echo "🚀 Starting cross-framework benchmarks...\n\n";
    $results = $runner->runAllBenchmarks();
    
    if (empty($results)) {
        echo "❌ No benchmarks completed successfully\n";
        exit(1);
    }
    
    // Generate reports
    echo "\n📊 Generating reports...\n";
    $runner->generateComparativeReport($results);
    $runner->saveConsolidatedResults($results);
    
    // Print summary
    $runner->printSummary($results);
    
    echo "\n✅ Cross-framework benchmarks completed successfully!\n";
}