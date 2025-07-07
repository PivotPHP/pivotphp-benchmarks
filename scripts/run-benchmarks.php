#!/usr/bin/env php
<?php

/**
 * PivotPHP Benchmark Runner
 *
 * This script orchestrates the execution of all benchmarks
 * and collects results in a standardized format.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;

class BenchmarkCommand extends Command
{
    protected static $defaultName = 'benchmark:run';

    private array $benchmarks = [
        'helix-core' => [
            'routing' => [
                'simple-route',
                'route-with-params',
                'wildcard-route'
            ],
            'middleware' => [
                'pipeline',
                'single-middleware',
                'conditional-middleware'
            ],
            'container' => [
                'resolution'
            ],
            'http' => [
                'request-response',
                'json-processing',
                'large-payload'
            ]
        ],
        'helix-orm' => [
            'queries' => [
                'select'
            ],
            'hydration' => [
                'entity'
            ],
            'transactions' => [
                'bulk'
            ]
        ],
        'comparative' => [
            'slim4' => [
                'routing'
            ]
        ]
    ];

    protected function configure(): void
    {
        $this
            ->setDescription('Run PivotPHP benchmarks')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Run all benchmarks')
            ->addOption('framework', 'f', InputOption::VALUE_REQUIRED, 'Run benchmarks for specific framework')
            ->addOption('comparative', 'c', InputOption::VALUE_NONE, 'Run comparative benchmarks only')
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'Number of iterations', 10000)
            ->addOption('warmup', 'w', InputOption::VALUE_REQUIRED, 'Number of warmup iterations', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>PivotPHP Benchmark Runner</info>');
        $output->writeln('PHP Version: ' . PHP_VERSION);
        $output->writeln('Date: ' . date('Y-m-d H:i:s'));
        $output->writeln('');

        $iterations = (int) $input->getOption('iterations');
        $warmup = (int) $input->getOption('warmup');

        $benchmarksToRun = $this->selectBenchmarks($input);
        $totalBenchmarks = $this->countBenchmarks($benchmarksToRun);

        $progressBar = new ProgressBar($output, $totalBenchmarks);
        $progressBar->start();

        $results = [];

        foreach ($benchmarksToRun as $framework => $categories) {
            foreach ($categories as $category => $benchmarks) {
                foreach ($benchmarks as $benchmark) {
                    $progressBar->advance();

                    $result = $this->runBenchmark(
                        $framework,
                        $category,
                        $benchmark,
                        $iterations,
                        $warmup
                    );

                    $results[] = $result;
                    $this->saveResult($result);
                }
            }
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln('');

        $this->displaySummary($output, $results);

        return Command::SUCCESS;
    }

    private function selectBenchmarks(InputInterface $input): array
    {
        if ($input->getOption('all')) {
            return $this->benchmarks;
        }

        if ($input->getOption('comparative')) {
            return ['comparative' => $this->benchmarks['comparative']];
        }

        if ($framework = $input->getOption('framework')) {
            if (!isset($this->benchmarks[$framework])) {
                throw new \InvalidArgumentException("Unknown framework: $framework");
            }
            return [$framework => $this->benchmarks[$framework]];
        }

        // Default: run only PivotPHP benchmarks
        return [
            'helix-core' => $this->benchmarks['helix-core'],
            'helix-orm' => $this->benchmarks['helix-orm']
        ];
    }

    private function countBenchmarks(array $benchmarks): int
    {
        $count = 0;
        foreach ($benchmarks as $categories) {
            foreach ($categories as $tests) {
                $count += count($tests);
            }
        }
        return $count;
    }

    private function runBenchmark(
        string $framework,
        string $category,
        string $benchmark,
        int $iterations,
        int $warmup
    ): array {
        $benchmarkFile = __DIR__ . "/../benchmarks/$framework/$category/$benchmark.php";

        if (!file_exists($benchmarkFile)) {
            return [
                'framework' => $framework,
                'category' => $category,
                'benchmark' => $benchmark,
                'status' => 'skipped',
                'reason' => 'File not found'
            ];
        }

        // Execute benchmark in isolated process
        $command = sprintf(
            'php %s --iterations=%d --warmup=%d --json',
            escapeshellarg($benchmarkFile),
            $iterations,
            $warmup
        );

        $output = shell_exec($command);
        $result = json_decode($output, true);

        if (!$result) {
            return [
                'framework' => $framework,
                'category' => $category,
                'benchmark' => $benchmark,
                'status' => 'error',
                'reason' => 'Failed to parse output'
            ];
        }

        return array_merge([
            'framework' => $framework,
            'category' => $category,
            'benchmark' => $benchmark,
            'status' => 'success',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION
        ], $result);
    }

    private function saveResult(array $result): void
    {
        $resultsDir = __DIR__ . '/../results/raw';
        if (!is_dir($resultsDir)) {
            mkdir($resultsDir, 0777, true);
        }

        $filename = sprintf(
            '%s/%s_%s_%s_%s.json',
            $resultsDir,
            $result['framework'],
            $result['category'],
            $result['benchmark'],
            date('Y-m-d_H-i-s')
        );

        file_put_contents($filename, json_encode($result, JSON_PRETTY_PRINT));
    }

    private function displaySummary(OutputInterface $output, array $results): void
    {
        $output->writeln('<info>Benchmark Summary</info>');

        $table = new Table($output);
        $table->setHeaders(['Framework', 'Category', 'Benchmark', 'Ops/sec', 'Memory (MB)', 'Status']);

        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                $table->addRow([
                    $result['framework'],
                    $result['category'],
                    $result['benchmark'],
                    number_format($result['results']['operations_per_second'] ?? 0),
                    number_format($result['results']['memory_peak_mb'] ?? 0, 2),
                    '<info>✓</info>'
                ]);
            } else {
                $table->addRow([
                    $result['framework'],
                    $result['category'],
                    $result['benchmark'],
                    '-',
                    '-',
                    '<error>' . $result['status'] . '</error>'
                ]);
            }
        }

        $table->render();

        $output->writeln('');
        $output->writeln('Results saved to: /results/raw/');
    }
}

// Create and run the console application
$application = new Application('PivotPHP Benchmark Runner', '1.0.0');
$application->add(new BenchmarkCommand());
$application->setDefaultCommand('benchmark:run', true);
$application->run();
