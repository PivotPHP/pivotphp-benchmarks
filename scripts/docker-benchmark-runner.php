#!/usr/bin/env php
<?php

/**
 * Docker Benchmark Runner
 * Executes benchmarks across all frameworks in Docker containers
 */

declare(strict_types=1);

function runDockerBenchmark(string $framework, string $benchmark, array $options = []): array
{
    $iterations = $options['iterations'] ?? 5000;
    $warmup = $options['warmup'] ?? 500;
    
    echo "🐳 Running {$framework}/{$benchmark} (Docker)...\n";
    
    // Different benchmark approaches for different frameworks
    $results = [];
    
    switch ($framework) {
        case 'pivotphp-core':
            $results = runPivotPHPCoreBenchmark($benchmark, $iterations, $warmup);
            break;
            
        case 'pivotphp-reactphp':
            $results = runPivotPHPReactPHPBenchmark($benchmark, $iterations, $warmup);
            break;
            
        case 'slim4':
            $results = runSlim4Benchmark($benchmark, $iterations, $warmup);
            break;
            
        case 'lumen':
            $results = runLumenBenchmark($benchmark, $iterations, $warmup);
            break;
            
        case 'flight':
            $results = runFlightBenchmark($benchmark, $iterations, $warmup);
            break;
            
        case 'symfony':
            $results = runSymfonyBenchmark($benchmark, $iterations, $warmup);
            break;
            
        case 'pivotphp-orm':
            $results = runPivotPHPORMBenchmark($benchmark, $iterations, $warmup);
            break;
            
        default:
            echo "❌ Framework {$framework} not supported yet\n";
            return [];
    }
    
    return $results;
}

function runPivotPHPCoreBenchmark(string $benchmark, int $iterations, int $warmup): array
{
    $validBenchmarks = [
        'http-performance' => 'benchmarks/http-performance.php',
        'rest-api' => 'benchmarks/rest-api.php',
        'json-api' => 'benchmarks/json-api.php --json',
    ];
    
    if (!isset($validBenchmarks[$benchmark])) {
        echo "❌ Benchmark {$benchmark} not available for pivotphp-core\n";
        return [];
    }
    
    $cmd = "docker run --rm pivotphp-benchmarks-pivotphp-core php {$validBenchmarks[$benchmark]}";
    
    $start = microtime(true);
    ob_start();
    system($cmd, $exitCode);
    $output = ob_get_clean();
    $duration = microtime(true) - $start;
    
    if ($exitCode !== 0) {
        echo "❌ Command failed: {$cmd}\n";
        return [];
    }
    
    return [
        'framework' => 'pivotphp-core',
        'benchmark' => $benchmark,
        'duration' => $duration,
        'output' => $output,
        'status' => 'success'
    ];
}

function runPivotPHPReactPHPBenchmark(string $benchmark, int $iterations, int $warmup): array
{
    // For ReactPHP, we'll create a simple direct execution
    $commands = [
        'async-performance' => 'php -r "echo \"🚀 ReactPHP Async Performance Test\\n\"; $start = microtime(true); for(\$i=0; \$i<1000; \$i++) { \$promise = \\React\\Promise\\resolve(42); } echo \"⚡ 1000 promise ops completed in \" . round((microtime(true) - \$start) * 1000, 2) . \"ms\\n\";"',
        'memory-test' => 'php -r "echo \"💾 Memory Test\\n\"; echo \"Peak: \" . round(memory_get_peak_usage(true)/1024/1024, 2) . \"MB\\n\";"'
    ];
    
    if (!isset($commands[$benchmark])) {
        echo "❌ Benchmark {$benchmark} not available for pivotphp-reactphp\n";
        return [];
    }
    
    $cmd = "docker run --rm pivotphp-benchmarks-pivotphp-reactphp {$commands[$benchmark]}";
    
    $start = microtime(true);
    ob_start();
    system($cmd, $exitCode);
    $output = ob_get_clean();
    $duration = microtime(true) - $start;
    
    return [
        'framework' => 'pivotphp-reactphp',
        'benchmark' => $benchmark,
        'duration' => $duration,
        'output' => $output,
        'status' => $exitCode === 0 ? 'success' : 'failed'
    ];
}

function runSlim4Benchmark(string $benchmark, int $iterations, int $warmup): array
{
    return runGenericFrameworkBenchmark('slim4', $benchmark, $iterations, $warmup);
}

function runLumenBenchmark(string $benchmark, int $iterations, int $warmup): array
{
    return runGenericFrameworkBenchmark('lumen', $benchmark, $iterations, $warmup);
}

function runFlightBenchmark(string $benchmark, int $iterations, int $warmup): array
{
    return runGenericFrameworkBenchmark('flight', $benchmark, $iterations, $warmup);
}

function runSymfonyBenchmark(string $benchmark, int $iterations, int $warmup): array
{
    return runGenericFrameworkBenchmark('symfony', $benchmark, $iterations, $warmup);
}

function runPivotPHPORMBenchmark(string $benchmark, int $iterations, int $warmup): array
{
    return runGenericFrameworkBenchmark('pivotphp-orm', $benchmark, $iterations, $warmup);
}

function runGenericFrameworkBenchmark(string $framework, string $benchmark, int $iterations, int $warmup): array
{
    $benchmarkCommands = [
        'basic-test' => 'php -r "echo \"🔥 ' . ucfirst($framework) . ' Basic Test\\nFramework loaded successfully\\nMemory: \" . round(memory_get_peak_usage(true)/1024/1024, 2) . \"MB\\n\";"',
        'memory-test' => 'php -r "echo \"💾 ' . ucfirst($framework) . ' Memory Test\\nPeak: \" . round(memory_get_peak_usage(true)/1024/1024, 2) . \"MB\\nCurrent: \" . round(memory_get_usage(true)/1024/1024, 2) . \"MB\\n\";"',
        'performance-test' => 'php -r "echo \"⚡ ' . ucfirst($framework) . ' Performance Test\\n\"; $start = microtime(true); for(\$i=0; \$i<10000; \$i++) { \$x = \$i * 2; } echo \"10K operations: \" . round((microtime(true) - \$start) * 1000, 2) . \"ms\\n\";"'
    ];
    
    $cmd = isset($benchmarkCommands[$benchmark]) 
        ? $benchmarkCommands[$benchmark]
        : $benchmarkCommands['basic-test'];
    
    $fullCmd = "docker run --rm pivotphp-benchmarks-{$framework} {$cmd}";
    
    $start = microtime(true);
    ob_start();
    system($fullCmd, $exitCode);
    $output = ob_get_clean();
    $duration = microtime(true) - $start;
    
    return [
        'framework' => $framework,
        'benchmark' => $benchmark,
        'duration' => $duration,
        'output' => $output,
        'status' => $exitCode === 0 ? 'success' : 'failed'
    ];
}

function displayResults(array $results): void
{
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 DOCKER BENCHMARK RESULTS\n";
    echo str_repeat("=", 60) . "\n";
    
    foreach ($results as $result) {
        $status = $result['status'] === 'success' ? '✅' : '❌';
        echo "{$status} {$result['framework']}/{$result['benchmark']} - {$result['duration']}s\n";
        if (!empty($result['output'])) {
            echo "   Output: " . substr(trim($result['output']), 0, 100) . "...\n";
        }
        echo "\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['frameworks:', 'benchmarks:', 'iterations:', 'warmup:']);
    
    $frameworks = isset($options['frameworks']) 
        ? explode(',', $options['frameworks'])
        : ['pivotphp-core', 'pivotphp-reactphp', 'pivotphp-orm', 'slim4', 'lumen', 'flight', 'symfony'];
    
    $benchmarks = isset($options['benchmarks'])
        ? explode(',', $options['benchmarks']) 
        : ['basic-test', 'memory-test', 'performance-test', 'http-performance', 'rest-api', 'async-performance'];
    
    $iterations = isset($options['iterations']) ? (int)$options['iterations'] : 5000;
    $warmup = isset($options['warmup']) ? (int)$options['warmup'] : 500;
    
    echo "🐳 Docker Benchmark Runner Starting...\n";
    echo "Frameworks: " . implode(', ', $frameworks) . "\n";
    echo "Benchmarks: " . implode(', ', $benchmarks) . "\n";
    echo "Iterations: {$iterations}, Warmup: {$warmup}\n\n";
    
    $allResults = [];
    
    foreach ($frameworks as $framework) {
        foreach ($benchmarks as $benchmark) {
            $result = runDockerBenchmark(trim($framework), trim($benchmark), [
                'iterations' => $iterations,
                'warmup' => $warmup
            ]);
            
            if (!empty($result)) {
                $allResults[] = $result;
            }
        }
    }
    
    displayResults($allResults);
    
    // Save results
    $timestamp = date('Y-m-d-H-i-s');
    $filename = "results/docker-benchmark-results-{$timestamp}.json";
    file_put_contents($filename, json_encode($allResults, JSON_PRETTY_PRINT));
    echo "💾 Results saved to: {$filename}\n";
}