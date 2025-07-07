#!/usr/bin/env php
<?php

/**
 * Centralized benchmark runner for all frameworks
 */

declare(strict_types=1);

class BenchmarkRunner
{
    private array $frameworks = [
        'pivotphp-core',
        'pivotphp-orm',
        'slim4',
        'lumen',
        'flight',
        'symfony'
    ];

    private array $benchmarks = [
        'routing.php',
        'middleware.php',
        'json-api.php'
    ];

    private string $resultsDir;
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->resultsDir = dirname(__DIR__) . '/results/raw';

        if (!is_dir($this->resultsDir)) {
            mkdir($this->resultsDir, 0755, true);
        }
    }

    public function run(): void
    {
        $selectedFrameworks = $this->getSelectedFrameworks();
        $selectedBenchmarks = $this->getSelectedBenchmarks();

        echo "Running benchmarks...\n";
        echo "Frameworks: " . implode(', ', $selectedFrameworks) . "\n";
        echo "Benchmarks: " . implode(', ', $selectedBenchmarks) . "\n\n";

        $allResults = [];

        foreach ($selectedFrameworks as $framework) {
            echo "=== Framework: {$framework} ===\n";

            foreach ($selectedBenchmarks as $benchmark) {
                $benchmarkPath = "/app/frameworks/{$framework}/benchmarks/{$benchmark}";

                if (!file_exists($benchmarkPath)) {
                    echo "  [SKIP] {$benchmark} - Not implemented\n";
                    continue;
                }

                echo "  Running {$benchmark}... ";

                $result = $this->runBenchmark($framework, $benchmarkPath);

                if ($result !== null) {
                    $allResults[] = $result;
                    echo "OK (ops/sec: {$result['ops_per_second']})\n";
                    $this->saveResult($framework, $benchmark, $result);
                } else {
                    echo "FAILED\n";
                }
            }

            echo "\n";
        }

        $this->generateSummary($allResults);
    }

    private function runBenchmark(string $framework, string $benchmarkPath): ?array
    {
        $iterations = $this->options['iterations'] ?? 10000;
        $warmup = $this->options['warmup'] ?? 1000;

        $cmd = sprintf(
            'docker-compose exec %s php %s --iterations=%d --warmup=%d --json 2>&1',
            $framework,
            $benchmarkPath,
            $iterations,
            $warmup
        );

        $output = shell_exec($cmd);

        if ($output === null) {
            return null;
        }

        // Try to extract JSON from output
        if (preg_match('/\{[\s\S]*\}/', $output, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        return null;
    }

    private function saveResult(string $framework, string $benchmark, array $result): void
    {
        $filename = sprintf(
            '%s/%s_%s_%s.json',
            $this->resultsDir,
            $framework,
            str_replace('.php', '', $benchmark),
            date('Y-m-d_H-i-s')
        );

        file_put_contents($filename, json_encode($result, JSON_PRETTY_PRINT));
    }

    private function generateSummary(array $results): void
    {
        if (empty($results)) {
            echo "\nNo benchmark results to summarize.\n";
            return;
        }

        echo "\n=== SUMMARY ===\n\n";

        // Group by benchmark type
        $grouped = [];
        foreach ($results as $result) {
            $benchmark = $result['benchmark'];
            $framework = $result['framework'];
            $grouped[$benchmark][$framework] = $result['ops_per_second'];
        }

        // Display comparison
        foreach ($grouped as $benchmark => $frameworks) {
            echo "Benchmark: {$benchmark}\n";

            // Sort by performance
            arsort($frameworks);

            $baseline = reset($frameworks);
            $baselineFramework = key($frameworks);

            foreach ($frameworks as $framework => $opsPerSec) {
                $percentage = round(($opsPerSec / $baseline) * 100, 1);
                $marker = ($framework === $baselineFramework) ? ' (baseline)' : '';
                echo sprintf(
                    "  %-15s: %8.0f ops/sec (%5.1f%%)%s\n",
                    $framework,
                    $opsPerSec,
                    $percentage,
                    $marker
                );
            }

            echo "\n";
        }

        // Save summary
        $summaryFile = $this->resultsDir . '/summary_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($summaryFile, json_encode($grouped, JSON_PRETTY_PRINT));
    }

    private function getSelectedFrameworks(): array
    {
        if (isset($this->options['frameworks'])) {
            return explode(',', $this->options['frameworks']);
        }

        if (isset($this->options['all'])) {
            return $this->frameworks;
        }

        return $this->frameworks;
    }

    private function getSelectedBenchmarks(): array
    {
        if (isset($this->options['benchmarks'])) {
            return explode(',', $this->options['benchmarks']);
        }

        return $this->benchmarks;
    }
}

// Parse command line options
$shortOpts = '';
$longOpts = [
    'all',
    'frameworks:',
    'benchmarks:',
    'iterations:',
    'warmup:',
    'help'
];

$options = getopt($shortOpts, $longOpts);

if (isset($options['help'])) {
    echo <<<HELP
PivotPHP Benchmark Runner

Usage: php run-benchmarks.php [options]

Options:
  --all               Run all benchmarks on all frameworks
  --frameworks=LIST   Comma-separated list of frameworks to test
                      Available: pivotphp-core, pivotphp-orm, slim4, lumen, flight, symfony
  --benchmarks=LIST   Comma-separated list of benchmarks to run
                      Available: routing, middleware, json-api
  --iterations=N      Number of iterations (default: 10000)
  --warmup=N          Number of warmup iterations (default: 1000)
  --help              Show this help message

Examples:
  php run-benchmarks.php --all
  php run-benchmarks.php --frameworks=pivotphp-core,slim4
  php run-benchmarks.php --frameworks=pivotphp-core,pivotphp-orm --iterations=50000

HELP;
    exit(0);
}

$runner = new BenchmarkRunner($options);
$runner->run();
