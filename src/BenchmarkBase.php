<?php

declare(strict_types=1);

namespace PivotBenchmark;

abstract class BenchmarkBase
{
    protected int $iterations = 10000;
    protected int $warmup = 1000;
    protected bool $jsonOutput = false;
    protected Timer $timer;

    public function __construct(array $options = [])
    {
        $this->iterations = (int) ($options['iterations'] ?? 10000);
        $this->warmup = (int) ($options['warmup'] ?? 1000);
        $this->jsonOutput = isset($options['json']);
        $this->timer = new Timer();
    }

    abstract protected function setup(): void;
    abstract protected function runSingleIteration(): void;
    abstract protected function getName(): string;

    public function run(): array
    {
        $this->setup();
        $this->doWarmup();

        // Capture output to avoid interference
        ob_start();

        $this->timer->start();

        for ($i = 0; $i < $this->iterations; $i++) {
            $this->runSingleIteration();
        }

        $this->timer->stop();

        // Discard captured output
        ob_end_clean();

        return $this->generateResults();
    }

    protected function doWarmup(): void
    {
        if (!$this->jsonOutput) {
            echo "Warming up with {$this->warmup} iterations...\n";
        }

        // Capture output during warmup
        ob_start();

        for ($i = 0; $i < $this->warmup; $i++) {
            $this->runSingleIteration();
        }

        // Discard warmup output
        ob_end_clean();
    }

    protected function generateResults(): array
    {
        $totalTime = $this->timer->getElapsedTime();
        $opsPerSecond = $this->iterations / $totalTime;
        $avgTimePerOp = ($totalTime * 1000) / $this->iterations;

        $results = [
            'benchmark' => $this->getName(),
            'framework' => 'pivotphp-core',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
            'results' => [
                'operations_per_second' => round($opsPerSecond),
                'avg_time_ms' => round($avgTimePerOp, 4),
                'memory_used_mb' => round($this->timer->getMemoryUsage() / 1024 / 1024, 2),
                'memory_peak_mb' => round($this->timer->getPeakMemory() / 1024 / 1024, 2),
                'total_time_seconds' => round($totalTime, 2),
                'iterations' => $this->iterations,
                'warmup' => $this->warmup
            ]
        ];

        $this->outputResults($results);

        return $results;
    }

    protected function outputResults(array $results): void
    {
        if ($this->jsonOutput) {
            echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "\nBenchmark: {$results['benchmark']}\n";
            echo "==================\n";
            echo "Operations/second: " . number_format($results['results']['operations_per_second']) . "\n";
            echo "Avg time per op: {$results['results']['avg_time_ms']} ms\n";
            echo "Memory used: {$results['results']['memory_used_mb']} MB\n";
            echo "Peak memory: {$results['results']['memory_peak_mb']} MB\n";
            echo "Total time: {$results['results']['total_time_seconds']} seconds\n";
        }
    }
}
