#!/usr/bin/env php
<?php

/**
 * ORM Query Benchmark for PivotPHP with Cycle ORM
 *
 * Tests database query performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use App\Models\User;
use App\Models\Post;
use App\Repositories\UserRepository;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use PivotPHP\Core\Application;
use PivotPHP\CycleOrm\CycleOrmServiceProvider;
use Shared\Interfaces\BenchmarkInterface;

class OrmQueriesBenchmark implements BenchmarkInterface
{
    private ORMInterface $orm;
    private UserRepository $userRepository;
    private Select $userSelect;
    private Select $postSelect;

    public function __construct()
    {
        $this->setup();
    }

    private function setup(): void
    {
        // Load configuration
        $config = require __DIR__ . '/../config/database.php';

        // Create application and register ORM
        $app = new Application();
        $app->register(new CycleOrmServiceProvider($config));

        // Get ORM instance
        $this->orm = $app->get(ORMInterface::class);
        $this->userRepository = new UserRepository($this->orm);
        $this->userSelect = new Select($this->orm, User::class);
        $this->postSelect = new Select($this->orm, Post::class);
    }

    public function run(int $iterations = 1000, int $warmup = 100): array
    {
        // Note: ORM benchmarks use fewer iterations due to database overhead

        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->runSingleIteration();
        }

        // Benchmark
        $start = microtime(true);
        $startMemory = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $this->runSingleIteration();
        }

        $end = microtime(true);
        $endMemory = memory_get_peak_usage();

        $totalTime = $end - $start;
        $opsPerSecond = $iterations / $totalTime;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

        return [
            'benchmark' => $this->getName(),
            'framework' => 'helix-orm',
            'description' => $this->getDescription(),
            'iterations' => $iterations,
            'warmup' => $warmup,
            'total_time' => $totalTime,
            'ops_per_second' => round($opsPerSecond, 2),
            'memory_mb' => round($memoryUsed, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function runSingleIteration(): void
    {
        $operations = [
            'findById',
            'findByEmail',
            'paginate',
            'findWithPosts',
            'countUsers',
            'recentUsers',
            'complexQuery'
        ];

        $operation = $operations[array_rand($operations)];

        switch ($operation) {
            case 'findById':
                $this->userRepository->findById(rand(1, 100));
                break;

            case 'findByEmail':
                $this->userRepository->findByEmail('user' . rand(1, 100) . '@example.com');
                break;

            case 'paginate':
                $this->userRepository->paginate(rand(1, 50), 20);
                break;

            case 'findWithPosts':
                $this->userRepository->findByIdWithPosts(rand(1, 50));
                break;

            case 'countUsers':
                $this->userSelect->count();
                break;

            case 'recentUsers':
                $this->userSelect
                    ->where('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                    ->orderBy('created_at', 'DESC')
                    ->limit(10)
                    ->fetchAll();
                break;

            case 'complexQuery':
                // Complex query with joins and conditions
                $this->userSelect
                    ->with('posts')
                    ->where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
                    ->orderBy('id', 'DESC')
                    ->limit(5)
                    ->fetchAll();
                break;
        }
    }

    public function getName(): string
    {
        return 'orm/queries';
    }

    public function getDescription(): string
    {
        return 'Tests various ORM query operations including finds, pagination, and joins';
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['iterations:', 'warmup:', 'json']);

    $iterations = isset($options['iterations']) ? (int)$options['iterations'] : 1000;
    $warmup = isset($options['warmup']) ? (int)$options['warmup'] : 100;

    $benchmark = new OrmQueriesBenchmark();
    $results = $benchmark->run($iterations, $warmup);

    if (isset($options['json'])) {
        echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Benchmark: {$results['benchmark']}\n";
        echo "Framework: {$results['framework']}\n";
        echo "Operations/sec: {$results['ops_per_second']}\n";
        echo "Memory used: {$results['memory_mb']} MB\n";
    }
}
