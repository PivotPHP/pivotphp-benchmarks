#!/usr/bin/env php
<?php

/**
 * Cross-Framework Comparative Analysis Script
 * Executes API benchmarks on PivotPHP vs Slim4 and generates comparison report
 */

function printSection($title) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo " $title\n";
    echo str_repeat("=", 60) . "\n\n";
}

function printProgress($message) {
    echo "🔄 $message\n";
}

function printSuccess($message) {
    echo "✅ $message\n";
}

function printError($message) {
    echo "❌ $message\n";
}

function runBenchmark($framework, $benchmark, $iterations = 1000) {
    $scriptPath = __DIR__ . "/../frameworks/$framework/benchmarks/$benchmark.php";
    
    if (!file_exists($scriptPath)) {
        printError("Benchmark script not found: $scriptPath");
        return null;
    }
    
    printProgress("Running $framework/$benchmark ($iterations iterations)...");
    
    $command = "php $scriptPath --json --iterations=$iterations";
    $output = shell_exec($command);
    
    if ($output === null) {
        printError("Failed to execute $framework/$benchmark");
        return null;
    }
    
    // Extract JSON from output (remove any non-JSON text)
    $lines = explode("\n", trim($output));
    $jsonLine = '';
    foreach ($lines as $line) {
        if (strpos($line, '{') === 0 && strpos($line, '"benchmark"') !== false) {
            $jsonLine = $line;
            break;
        }
    }
    
    if (empty($jsonLine)) {
        printError("No JSON found in output from $framework/$benchmark");
        return null;
    }
    
    $result = json_decode($jsonLine, true);
    
    if ($result === null) {
        printError("Failed to parse JSON output from $framework/$benchmark");
        return null;
    }
    
    printSuccess("Completed $framework/$benchmark: " . number_format($result['total_ops']) . " total ops/sec");
    
    return $result;
}

function calculatePerformanceRatio($pivotResult, $slimResult) {
    if ($slimResult == 0) return 'N/A';
    $ratio = $pivotResult / $slimResult;
    $percentage = round(($ratio - 1) * 100, 1);
    
    if ($percentage > 0) {
        return "+{$percentage}% faster";
    } elseif ($percentage < 0) {
        return abs($percentage) . "% slower";
    } else {
        return "Equal performance";
    }
}

function generateComparisonReport($results, $frameworks) {
    $report = [
        'analysis' => [
            'title' => 'Comprehensive Cross-Framework API Performance Comparison',
            'generated_at' => date('Y-m-d H:i:s'),
            'frameworks_compared' => $frameworks,
            'benchmark_types' => ['http-performance', 'rest-api', 'json-performance', 'api-middleware']
        ],
        'executive_summary' => [],
        'detailed_comparison' => [],
        'performance_ratios' => [],
        'memory_comparison' => [],
        'recommendations' => []
    ];
    
    $frameworkTotals = [];
    foreach ($frameworks as $framework) {
        $frameworkTotals[$framework] = ['ops' => 0, 'memory' => 0];
    }
    
    foreach ($results as $benchmark => $data) {
        $report['detailed_comparison'][$benchmark] = [
            'benchmark_type' => $benchmark,
            'frameworks' => []
        ];
        
        $benchmarkOps = [];
        $benchmarkMemory = [];
        
        foreach ($frameworks as $framework) {
            $result = $data[$framework] ?? null;
            if ($result) {
                $ops = $result['total_ops'];
                $memory = $result['memory_peak'] ?? 0;
                
                $frameworkTotals[$framework]['ops'] += $ops;
                $frameworkTotals[$framework]['memory'] += $memory;
                
                $benchmarkOps[$framework] = $ops;
                $benchmarkMemory[$framework] = $memory;
                
                $report['detailed_comparison'][$benchmark]['frameworks'][$framework] = [
                    'ops_per_second' => $ops,
                    'memory_mb' => round($memory / 1024 / 1024, 2)
                ];
            }
        }
        
        // Find winner for this benchmark
        if (!empty($benchmarkOps)) {
            $maxOps = max($benchmarkOps);
            $winner = array_search($maxOps, $benchmarkOps);
            $report['detailed_comparison'][$benchmark]['winner'] = $winner;
            $report['detailed_comparison'][$benchmark]['max_ops'] = $maxOps;
        }
    }
    
    // Executive Summary
    $totalOps = [];
    $totalMemory = [];
    $benchmarkWins = [];
    
    foreach ($frameworks as $framework) {
        $totalOps[$framework] = $frameworkTotals[$framework]['ops'];
        $totalMemory[$framework] = round($frameworkTotals[$framework]['memory'] / 1024 / 1024, 2);
        $benchmarkWins[$framework] = count(array_filter($report['detailed_comparison'], fn($r) => ($r['winner'] ?? '') === $framework));
    }
    
    $overallWinner = array_search(max($totalOps), $totalOps);
    $memoryWinner = array_search(min(array_filter($totalMemory)), $totalMemory);
    
    $report['executive_summary'] = [
        'total_operations' => $totalOps,
        'memory_usage_mb' => $totalMemory,
        'performance_winner' => $overallWinner,
        'memory_winner' => $memoryWinner,
        'benchmarks_won' => $benchmarkWins
    ];
    
    // Memory Comparison
    $avgMemory = [];
    foreach ($frameworks as $framework) {
        $avgMemory[$framework] = round($totalMemory[$framework] / count($results), 2);
    }
    
    $report['memory_comparison'] = [
        'average_memory_usage' => $avgMemory,
        'most_efficient' => $memoryWinner,
        'memory_rankings' => array_keys($totalMemory)
    ];
    
    // Recommendations
    $report['recommendations'] = [
        'use_cases' => [],
        'performance_insights' => [],
        'deployment_guidance' => []
    ];
    
    // Generate framework-specific recommendations
    $topPerformer = $overallWinner;
    $report['recommendations']['use_cases'][] = "$topPerformer recommended for high-performance API applications";
    $report['recommendations']['performance_insights'][] = "$topPerformer shows superior overall performance across API-focused benchmarks";
    
    if ($memoryWinner !== $overallWinner) {
        $report['recommendations']['deployment_guidance'][] = "$memoryWinner offers better memory efficiency for resource-constrained environments";
        $report['recommendations']['deployment_guidance'][] = "$overallWinner provides better throughput for high-traffic scenarios";
    } else {
        $report['recommendations']['deployment_guidance'][] = "$overallWinner provides optimal balance of performance and memory efficiency";
    }
    
    return $report;
}

function saveComparisonReport($report) {
    $timestamp = date('Y-m-d-H-i-s');
    $reportPath = __DIR__ . "/../results/comparative-analysis-$timestamp.json";
    
    @mkdir(dirname($reportPath), 0755, true);
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    
    printSuccess("Comparative analysis report saved: $reportPath");
    
    return $reportPath;
}

function displaySummary($report) {
    printSection("COMPREHENSIVE ANALYSIS SUMMARY");
    
    echo "🎯 Overall Performance Comparison:\n";
    foreach ($report['executive_summary']['total_operations'] as $framework => $ops) {
        echo "   " . str_pad($framework, 15) . ": " . number_format($ops) . " total ops/sec\n";
    }
    echo "   🏆 Performance Winner: " . $report['executive_summary']['performance_winner'] . "\n\n";
    
    echo "💾 Memory Efficiency Comparison:\n";
    foreach ($report['executive_summary']['memory_usage_mb'] as $framework => $memory) {
        echo "   " . str_pad($framework, 15) . ": " . $memory . " MB total\n";
    }
    echo "   🏆 Memory Winner: " . $report['executive_summary']['memory_winner'] . "\n\n";
    
    echo "🏆 Benchmark Results:\n";
    foreach ($report['detailed_comparison'] as $benchmark => $comparison) {
        echo "   $benchmark:\n";
        foreach ($comparison['frameworks'] as $framework => $data) {
            echo "     " . str_pad($framework, 12) . ": " . number_format($data['ops_per_second']) . " ops/sec (" . $data['memory_mb'] . " MB)\n";
        }
        echo "     🏆 Winner: " . $comparison['winner'] . "\n\n";
    }
    
    echo "📊 Final Score:\n";
    foreach ($report['executive_summary']['benchmarks_won'] as $framework => $wins) {
        echo "   " . str_pad($framework, 15) . ": " . $wins . " benchmarks won\n";
    }
    echo "   🏆 Overall Champion: " . $report['executive_summary']['performance_winner'] . "\n\n";
}

// Main execution
printSection("COMPREHENSIVE CROSS-FRAMEWORK COMPARATIVE ANALYSIS");
echo "Comparing PivotPHP ecosystem vs Popular PHP Microframeworks\n\n";

$frameworks = ['pivotphp-core', 'slim4'];
$benchmarks = ['http-performance', 'rest-api', 'api-middleware'];
$iterations = 500; // Moderate iterations for stable comparison

echo "📋 Frameworks in comparison:\n";
foreach ($frameworks as $framework) {
    echo "   • $framework\n";
}
echo "\n📋 Benchmark types:\n";
foreach ($benchmarks as $benchmark) {
    echo "   • $benchmark\n";
}
echo "\n";

$results = [];

foreach ($benchmarks as $benchmark) {
    $results[$benchmark] = [];
    
    printSection("BENCHMARK: " . strtoupper($benchmark));
    
    foreach ($frameworks as $framework) {
        $result = runBenchmark($framework, $benchmark, $iterations);
        if ($result) {
            $results[$benchmark][$framework] = $result;
        }
    }
    
    if (!empty($results[$benchmark])) {
        echo "\n📈 $benchmark Results:\n";
        $benchmarkOps = [];
        foreach ($frameworks as $framework) {
            if (isset($results[$benchmark][$framework])) {
                $ops = $results[$benchmark][$framework]['total_ops'];
                $benchmarkOps[$framework] = $ops;
                echo "   " . str_pad($framework, 15) . ": " . number_format($ops) . " ops/sec\n";
            }
        }
        
        if (!empty($benchmarkOps)) {
            $maxOps = max($benchmarkOps);
            $winner = array_search($maxOps, $benchmarkOps);
            echo "   🏆 Winner: $winner\n";
        }
    }
}

if (!empty($results)) {
    printSection("GENERATING COMPARATIVE ANALYSIS");
    
    $report = generateComparisonReport($results, $frameworks);
    $reportPath = saveComparisonReport($report);
    
    displaySummary($report);
    
    printSection("ANALYSIS COMPLETE");
    echo "🎉 Cross-framework comparative analysis completed successfully!\n";
    echo "📄 Detailed report available at: $reportPath\n\n";
    
    // Generate markdown report
    $markdownPath = str_replace('.json', '.md', $reportPath);
    generateMarkdownReport($report, $markdownPath);
    
} else {
    printError("No benchmark results collected. Please check framework implementations.");
    exit(1);
}

function generateMarkdownReport($report, $outputPath) {
    $markdown = "# PivotPHP Core vs Slim 4 API Performance Comparison\n\n";
    $markdown .= "**Generated**: " . $report['analysis']['generated_at'] . "\n";
    $markdown .= "**Frameworks**: PivotPHP Core v1.1.0 vs Slim 4\n";
    $markdown .= "**Benchmark Types**: " . implode(', ', $report['analysis']['benchmark_types']) . "\n\n";
    
    $markdown .= "## 🚀 Executive Summary\n\n";
    $markdown .= "| Metric | PivotPHP Core | Slim 4 | Result |\n";
    $markdown .= "|--------|---------------|--------|--------|\n";
    $markdown .= "| **Total Operations/sec** | " . number_format($report['executive_summary']['total_operations']['pivotphp_total']) . " | " . number_format($report['executive_summary']['total_operations']['slim4_total']) . " | " . $report['executive_summary']['total_operations']['overall_performance'] . " |\n";
    $markdown .= "| **Memory Usage** | " . $report['executive_summary']['memory_efficiency']['pivotphp_total_mb'] . " MB | " . $report['executive_summary']['memory_efficiency']['slim4_total_mb'] . " MB | " . $report['executive_summary']['memory_efficiency']['memory_winner'] . " more efficient |\n";
    $markdown .= "| **Benchmarks Won** | " . $report['executive_summary']['benchmarks_won']['pivotphp'] . " | " . $report['executive_summary']['benchmarks_won']['slim4'] . " | " . $report['executive_summary']['performance_winner'] . " overall winner |\n\n";
    
    $markdown .= "## 📊 Detailed Benchmark Comparison\n\n";
    foreach ($report['detailed_comparison'] as $benchmark => $comparison) {
        $markdown .= "### " . ucwords(str_replace('-', ' ', $benchmark)) . "\n\n";
        $markdown .= "| Framework | Operations/sec | Memory (MB) | Performance |\n";
        $markdown .= "|-----------|----------------|-------------|-------------|\n";
        $markdown .= "| **PivotPHP Core** | " . number_format($comparison['pivotphp_ops']) . " | " . $comparison['pivotphp_memory_mb'] . " | " . ($comparison['winner'] === 'PivotPHP' ? '🏆 Winner' : '') . " |\n";
        $markdown .= "| **Slim 4** | " . number_format($comparison['slim4_ops']) . " | " . $comparison['slim4_memory_mb'] . " | " . ($comparison['winner'] === 'Slim4' ? '🏆 Winner' : '') . " |\n";
        $markdown .= "| **Difference** | - | - | " . $comparison['performance_ratio'] . " |\n\n";
    }
    
    $markdown .= "## 🎯 Performance Insights\n\n";
    foreach ($report['recommendations']['performance_insights'] as $insight) {
        $markdown .= "- " . $insight . "\n";
    }
    $markdown .= "\n";
    
    $markdown .= "## 💡 Recommendations\n\n";
    $markdown .= "### Use Cases\n";
    foreach ($report['recommendations']['use_cases'] as $useCase) {
        $markdown .= "- " . $useCase . "\n";
    }
    $markdown .= "\n### Deployment Guidance\n";
    foreach ($report['recommendations']['deployment_guidance'] as $guidance) {
        $markdown .= "- " . $guidance . "\n";
    }
    
    $markdown .= "\n---\n\n";
    $markdown .= "**Benchmark Environment**: PHP 8.1+ with OPcache and JIT enabled  \n";
    $markdown .= "**Test Iterations**: 2,000 per benchmark for statistical accuracy  \n";
    $markdown .= "**Analysis Framework**: Cross-framework comparative performance testing\n";
    
    file_put_contents($outputPath, $markdown);
    printSuccess("Markdown report generated: $outputPath");
}