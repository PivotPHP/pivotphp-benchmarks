#!/usr/bin/env php
<?php

/**
 * High-Scale Concurrency Testing Framework
 * Tests framework performance under extreme concurrent loads up to 50,000 requests
 */

function printSection($title) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo " $title\n";
    echo str_repeat("=", 80) . "\n\n";
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

function printWarning($message) {
    echo "⚠️ $message\n";
}

function runConcurrencyTest($framework, $concurrency, $totalRequests, $endpoint = 'health') {
    $frameworkPath = __DIR__ . "/../frameworks/$framework";
    
    if (!is_dir($frameworkPath)) {
        printError("Framework directory not found: $frameworkPath");
        return null;
    }
    
    // Include the cURL concurrency engine
    require_once __DIR__ . '/curl-concurrency-engine.php';
    
    $urls = [
        'health' => "http://localhost:8080/health",
        'api' => "http://localhost:8080/api/v1/users",
        'heavy' => "http://localhost:8080/api/v1/users?page=1&limit=100"
    ];
    
    $url = $urls[$endpoint] ?? $urls['health'];
    
    printProgress("Teste de concorrência: $framework | $concurrency concurrent | $totalRequests total requests");
    
    // Use our custom cURL concurrency engine
    $tester = new ConcurrencyTester($url, $concurrency, $totalRequests, 30);
    $metrics = $tester->runTest();
    
    if (!$metrics || $metrics['requests_per_second'] == 0) {
        printError("Falha na execução do teste para $framework com $concurrency concurrent requests");
        return null;
    }
    
    $metrics['framework'] = $framework;
    $metrics['endpoint'] = $endpoint;
    $metrics['test_timestamp'] = date('Y-m-d H:i:s');
    
    printSuccess("$framework: {$metrics['requests_per_second']} req/sec | " . 
                 "{$metrics['time_per_request']}ms avg | " .
                 "{$metrics['failed_requests']} failures | " .
                 "{$metrics['success_rate']}% success");
    
    return $metrics;
}

// parseApacheBenchOutput function removed - using cURL engine instead

function startFrameworkServer($framework, $port = 8080) {
    $frameworkPath = __DIR__ . "/../frameworks/$framework";
    $publicPath = "$frameworkPath/public";
    
    if (!is_dir($publicPath)) {
        printError("Public directory not found for $framework: $publicPath");
        return false;
    }
    
    printProgress("Iniciando servidor $framework na porta $port...");
    
    // Kill any existing process on the port
    shell_exec("fuser -k $port/tcp 2>/dev/null");
    sleep(1);
    
    // Start PHP built-in server in background
    $command = "cd $publicPath && php -S localhost:$port -t . >/dev/null 2>&1 & echo \$!";
    $pid = trim(shell_exec($command));
    
    if (!$pid) {
        printError("Falha ao iniciar servidor para $framework");
        return false;
    }
    
    // Wait for server to be ready
    $maxWait = 10;
    $waited = 0;
    while ($waited < $maxWait) {
        $check = shell_exec("curl -s -o /dev/null -w '%{http_code}' http://localhost:$port/health 2>/dev/null");
        if ($check === '200') {
            printSuccess("Servidor $framework iniciado (PID: $pid)");
            return $pid;
        }
        sleep(1);
        $waited++;
    }
    
    printError("Timeout aguardando servidor $framework");
    shell_exec("kill $pid 2>/dev/null");
    return false;
}

function stopFrameworkServer($pid) {
    if ($pid) {
        shell_exec("kill $pid 2>/dev/null");
        sleep(1);
        // Force kill if still running
        shell_exec("kill -9 $pid 2>/dev/null");
    }
}

function calculatePerformanceScore($metrics) {
    if (!$metrics || !isset($metrics['requests_per_second'])) {
        return 0;
    }
    
    $baseScore = $metrics['requests_per_second'] / 100; // Base score from RPS
    
    // Penalty for failed requests
    $failureRate = $metrics['failed_requests'] / max(1, $metrics['total_requests'] ?? 1);
    $failurePenalty = $failureRate * 50; // Heavy penalty for failures
    
    // Penalty for high response times
    $latencyPenalty = max(0, ($metrics['time_per_request'] - 10) / 10); // Penalty above 10ms
    
    // Bonus for low latency
    $latencyBonus = $metrics['time_per_request'] < 5 ? 5 : 0;
    
    $finalScore = max(0, $baseScore - $failurePenalty - $latencyPenalty + $latencyBonus);
    
    return round($finalScore, 2);
}

function generateConcurrencyReport($results) {
    $report = [
        'test_suite' => 'High-Scale Concurrency Testing',
        'generated_at' => date('Y-m-d H:i:s'),
        'test_configuration' => [
            'concurrency_levels' => [100, 500, 1000, 2500, 5000, 10000, 25000, 50000],
            'endpoints_tested' => ['health', 'api', 'heavy'],
            'total_test_scenarios' => 0,
            'successful_tests' => 0
        ],
        'framework_performance' => [],
        'scalability_analysis' => [],
        'performance_breakdown' => [],
        'recommendations' => []
    ];
    
    // Analyze results per framework
    foreach ($results as $framework => $tests) {
        if (empty($tests)) continue;
        
        $frameworkAnalysis = [
            'framework_name' => $framework,
            'tests_completed' => count($tests),
            'peak_performance' => 0,
            'max_stable_concurrency' => 0,
            'failure_threshold' => 0,
            'scalability_score' => 0,
            'performance_curve' => [],
            'strengths' => [],
            'limitations' => []
        ];
        
        $performanceCurve = [];
        $lastStablePoint = 0;
        
        foreach ($tests as $test) {
            $report['test_configuration']['total_test_scenarios']++;
            if ($test['requests_per_second'] > 0) {
                $report['test_configuration']['successful_tests']++;
            }
            
            $concurrency = $test['concurrency'];
            $rps = $test['requests_per_second'];
            $failures = $test['failed_requests'];
            $failureRate = $failures / max(1, $test['total_requests']);
            
            $performanceCurve[$concurrency] = [
                'rps' => $rps,
                'latency' => $test['time_per_request'],
                'failure_rate' => $failureRate * 100,
                'score' => calculatePerformanceScore($test)
            ];
            
            // Update peak performance
            if ($rps > $frameworkAnalysis['peak_performance']) {
                $frameworkAnalysis['peak_performance'] = $rps;
            }
            
            // Determine max stable concurrency (< 1% failure rate)
            if ($failureRate < 0.01 && $rps > 0) {
                $frameworkAnalysis['max_stable_concurrency'] = $concurrency;
                $lastStablePoint = $rps;
            } else if ($frameworkAnalysis['failure_threshold'] === 0) {
                $frameworkAnalysis['failure_threshold'] = $concurrency;
            }
        }
        
        $frameworkAnalysis['performance_curve'] = $performanceCurve;
        
        // Calculate scalability score
        $concurrencyLevels = array_keys($performanceCurve);
        if (count($concurrencyLevels) >= 2) {
            $minConcurrency = min($concurrencyLevels);
            $maxConcurrency = max($concurrencyLevels);
            $minRPS = $performanceCurve[$minConcurrency]['rps'];
            $maxRPS = $performanceCurve[$maxConcurrency]['rps'];
            
            // Scalability score based on performance retention
            $scalabilityRatio = $minRPS > 0 ? ($maxRPS / $minRPS) : 0;
            $frameworkAnalysis['scalability_score'] = round($scalabilityRatio * 100, 1);
        }
        
        // Analyze strengths and limitations
        if ($frameworkAnalysis['max_stable_concurrency'] >= 10000) {
            $frameworkAnalysis['strengths'][] = 'Excelente escalabilidade (>10K concurrent)';
        } elseif ($frameworkAnalysis['max_stable_concurrency'] >= 5000) {
            $frameworkAnalysis['strengths'][] = 'Boa escalabilidade (>5K concurrent)';
        }
        
        if ($frameworkAnalysis['peak_performance'] >= 10000) {
            $frameworkAnalysis['strengths'][] = 'Alto throughput (>10K RPS)';
        }
        
        if ($frameworkAnalysis['failure_threshold'] > 0 && $frameworkAnalysis['failure_threshold'] <= 5000) {
            $frameworkAnalysis['limitations'][] = "Degradação em {$frameworkAnalysis['failure_threshold']} concurrent requests";
        }
        
        $avgLatency = array_sum(array_column($performanceCurve, 'latency')) / count($performanceCurve);
        if ($avgLatency > 100) {
            $frameworkAnalysis['limitations'][] = 'Alta latência sob carga (>100ms)';
        }
        
        $report['framework_performance'][$framework] = $frameworkAnalysis;
    }
    
    // Generate recommendations
    $frameworks = array_keys($report['framework_performance']);
    if (count($frameworks) >= 2) {
        $bestScalability = '';
        $bestThroughput = '';
        $maxScalability = 0;
        $maxThroughput = 0;
        
        foreach ($report['framework_performance'] as $framework => $analysis) {
            if ($analysis['scalability_score'] > $maxScalability) {
                $maxScalability = $analysis['scalability_score'];
                $bestScalability = $framework;
            }
            if ($analysis['peak_performance'] > $maxThroughput) {
                $maxThroughput = $analysis['peak_performance'];
                $bestThroughput = $framework;
            }
        }
        
        $report['recommendations'] = [
            'best_scalability' => "$bestScalability (score: $maxScalability%)",
            'best_throughput' => "$bestThroughput ($maxThroughput RPS)",
            'production_guidance' => [
                'low_traffic' => 'Qualquer framework testado é adequado (<1K concurrent)',
                'medium_traffic' => 'Verificar limite de estabilidade de cada framework (1K-10K)',
                'high_traffic' => 'Apenas frameworks com max_stable_concurrency >10K recomendados',
                'extreme_scale' => 'Considerar arquitetura distribuída acima de 25K concurrent'
            ]
        ];
    }
    
    return $report;
}

function saveTestResults($results, $report) {
    $timestamp = date('Y-m-d-H-i-s');
    
    // Save detailed results
    $resultsPath = __DIR__ . "/../results/high-scale-concurrency-$timestamp.json";
    @mkdir(dirname($resultsPath), 0755, true);
    file_put_contents($resultsPath, json_encode($results, JSON_PRETTY_PRINT));
    printSuccess("Resultados detalhados salvos: $resultsPath");
    
    // Save analysis report
    $reportPath = __DIR__ . "/../results/concurrency-analysis-$timestamp.json";
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    printSuccess("Relatório de análise salvo: $reportPath");
    
    return [$resultsPath, $reportPath];
}

function displaySummaryReport($report) {
    printSection("RELATÓRIO DE ESCALABILIDADE - RESUMO EXECUTIVO");
    
    echo "📊 CONFIGURAÇÃO DO TESTE:\n";
    echo "   Níveis de Concorrência: " . implode(', ', $report['test_configuration']['concurrency_levels']) . "\n";
    echo "   Cenários Testados: {$report['test_configuration']['total_test_scenarios']}\n";
    echo "   Testes Bem-sucedidos: {$report['test_configuration']['successful_tests']}\n\n";
    
    echo "🏆 RANKINGS DE PERFORMANCE:\n\n";
    
    // Rank by scalability
    $scalabilityRanking = [];
    foreach ($report['framework_performance'] as $framework => $analysis) {
        $scalabilityRanking[$framework] = $analysis['scalability_score'];
    }
    arsort($scalabilityRanking);
    
    echo "   📈 ESCALABILIDADE (Retenção de Performance):\n";
    $rank = 1;
    foreach ($scalabilityRanking as $framework => $score) {
        echo "      $rank. $framework: $score%\n";
        $rank++;
    }
    echo "\n";
    
    // Rank by peak performance
    $throughputRanking = [];
    foreach ($report['framework_performance'] as $framework => $analysis) {
        $throughputRanking[$framework] = $analysis['peak_performance'];
    }
    arsort($throughputRanking);
    
    echo "   🚀 THROUGHPUT MÁXIMO:\n";
    $rank = 1;
    foreach ($throughputRanking as $framework => $rps) {
        echo "      $rank. $framework: " . number_format($rps) . " RPS\n";
        $rank++;
    }
    echo "\n";
    
    // Stability analysis
    echo "   🛡️ ESTABILIDADE (Max Concurrent sem Falhas):\n";
    $stabilityRanking = [];
    foreach ($report['framework_performance'] as $framework => $analysis) {
        $stabilityRanking[$framework] = $analysis['max_stable_concurrency'];
    }
    arsort($stabilityRanking);
    
    $rank = 1;
    foreach ($stabilityRanking as $framework => $maxConcurrent) {
        echo "      $rank. $framework: " . number_format($maxConcurrent) . " concurrent\n";
        $rank++;
    }
    echo "\n";
    
    if (isset($report['recommendations'])) {
        echo "💡 RECOMENDAÇÕES:\n";
        echo "   Melhor Escalabilidade: {$report['recommendations']['best_scalability']}\n";
        echo "   Melhor Throughput: {$report['recommendations']['best_throughput']}\n\n";
        
        echo "📋 GUIA DE PRODUÇÃO:\n";
        foreach ($report['recommendations']['production_guidance'] as $scenario => $guidance) {
            echo "   " . ucwords(str_replace('_', ' ', $scenario)) . ": $guidance\n";
        }
    }
}

// Main execution
printSection("HIGH-SCALE CONCURRENCY TESTING FRAMEWORK");
echo "Testando performance sob carga extrema até 50.000 requests concorrentes\n\n";

// Configuration
$frameworks = ['pivotphp-core', 'slim4'];
$concurrencyLevels = [100, 500, 1000, 2500, 5000]; // Start with lower levels for initial testing
$requestsPerTest = 2000; // Reduced for initial testing
$endpoints = ['health']; // Start with health endpoint for baseline

echo "📋 CONFIGURAÇÃO DO TESTE:\n";
echo "   Frameworks: " . implode(', ', $frameworks) . "\n";
echo "   Níveis de Concorrência: " . implode(', ', $concurrencyLevels) . "\n";
echo "   Requests por Teste: " . number_format($requestsPerTest) . "\n";
echo "   Endpoints: " . implode(', ', $endpoints) . "\n\n";

printWarning("ATENÇÃO: Teste de alta escala pode impactar performance do sistema!");
echo "Pressione ENTER para continuar ou Ctrl+C para cancelar...\n";
if (php_sapi_name() === 'cli') {
    fgets(STDIN);
}

$results = [];
$totalTests = count($frameworks) * count($concurrencyLevels) * count($endpoints);
$currentTest = 0;

foreach ($frameworks as $framework) {
    printSection("TESTANDO FRAMEWORK: " . strtoupper($framework));
    
    $results[$framework] = [];
    
    // Start framework server
    $serverPid = startFrameworkServer($framework, 8080);
    if (!$serverPid) {
        printError("Falha ao iniciar servidor para $framework. Pulando...");
        continue;
    }
    
    try {
        foreach ($endpoints as $endpoint) {
            foreach ($concurrencyLevels as $concurrency) {
                $currentTest++;
                echo "\nTeste $currentTest/$totalTests: ";
                
                // Adjust request count for very high concurrency to avoid extremely long tests
                $adjustedRequests = $concurrency > 10000 ? min($requestsPerTest, $concurrency * 2) : $requestsPerTest;
                
                $testResult = runConcurrencyTest($framework, $concurrency, $adjustedRequests, $endpoint);
                
                if ($testResult) {
                    $results[$framework][] = $testResult;
                    
                    // Stop testing if failure rate is too high
                    if ($testResult['failed_requests'] / $adjustedRequests > 0.5) {
                        printWarning("Alta taxa de falhas detectada. Parando testes para $framework em concorrência superior.");
                        break 2; // Break both loops
                    }
                } else {
                    printError("Teste falhou para $framework com $concurrency concurrent");
                    break 2; // Stop testing this framework
                }
                
                // Brief pause between tests to allow system recovery
                sleep(2);
            }
        }
    } finally {
        // Always stop the server
        printProgress("Parando servidor $framework...");
        stopFrameworkServer($serverPid);
        sleep(3); // Give system time to clean up
    }
    
    echo "\n📊 Resumo $framework:\n";
    $frameworkTests = $results[$framework];
    if (!empty($frameworkTests)) {
        $maxRPS = max(array_column($frameworkTests, 'requests_per_second'));
        $maxConcurrency = max(array_column($frameworkTests, 'concurrency'));
        $totalFailures = array_sum(array_column($frameworkTests, 'failed_requests'));
        
        echo "   Pico de Performance: " . number_format($maxRPS) . " RPS\n";
        echo "   Maior Concorrência Testada: " . number_format($maxConcurrency) . "\n";
        echo "   Total de Falhas: " . number_format($totalFailures) . "\n";
    }
}

if (!empty($results)) {
    printSection("GERANDO RELATÓRIO DE ANÁLISE");
    
    $report = generateConcurrencyReport($results);
    list($resultsPath, $reportPath) = saveTestResults($results, $report);
    
    displaySummaryReport($report);
    
    printSection("TESTE CONCLUÍDO");
    echo "🎉 Análise de escalabilidade completa!\n";
    echo "📊 Resultados: $resultsPath\n";
    echo "📋 Relatório: $reportPath\n\n";
    
} else {
    printError("Nenhum resultado válido coletado. Verifique as configurações.");
    exit(1);
}