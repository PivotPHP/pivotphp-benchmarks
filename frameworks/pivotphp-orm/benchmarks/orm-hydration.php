#!/usr/bin/env php
<?php

/**
 * ORM Hydration Benchmark for PivotPHP with Cycle ORM
 *
 * Tests entity hydration performance
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../../shared/Interfaces/BenchmarkInterface.php';

use App\Models\User;
use App\Models\Post;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use PivotPHP\Core\Application;
use PivotPHP\CycleOrm\CycleOrmServiceProvider;
use Shared\Interfaces\BenchmarkInterface;

class OrmHydrationBenchmark implements BenchmarkInterface
{
    private ORMInterface $orm;
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
        $this->userSelect = new Select($this->orm, User::class);
        $this->postSelect = new Select($this->orm, Post::class);
    }

    public function run(int $iterations = 100, int $warmup = 10): array
    {
        // Note: Hydration benchmarks use fewer iterations due to memory constraints

        // Warmup
        for ($i = 0; $i < $warmup; $i++) {
            $this->runSingleIteration();
            // Clear ORM heap to prevent memory issues
            $this->orm->getHeap()->clean();
        }

        // Benchmark
        $start = microtime(true);
        $startMemory = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $this->runSingleIteration();
            // Clear ORM heap between iterations
            $this->orm->getHeap()->clean();
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
        $scenarios = [
            'single_entity',
            'small_collection',
            'medium_collection',
            'large_collection',
            'with_relations',
            'deep_relations'
        ];

        $scenario = $scenarios[array_rand($scenarios)];

        switch ($scenario) {
            case 'single_entity':
                // Hydrate single entity
                $user = $this->userSelect->wherePK(rand(1, 100))->fetchOne();
                if ($user) {
                    $user->toArray(); // Force full hydration
                }
                break;

            case 'small_collection':
                // Hydrate 10 entities
                $users = $this->userSelect->limit(10)->fetchAll();
                foreach ($users as $user) {
                    $user->toArray();
                }
                break;

            case 'medium_collection':
                // Hydrate 50 entities
                $users = $this->userSelect->limit(50)->fetchAll();
                foreach ($users as $user) {
                    $user->toArray();
                }
                break;

            case 'large_collection':
                // Hydrate 100 entities
                $users = $this->userSelect->limit(100)->fetchAll();
                foreach ($users as $user) {
                    $user->toArray();
                }
                break;

            case 'with_relations':
                // Hydrate entities with relations
                $users = $this->userSelect
                    ->with('posts')
                    ->limit(10)
                    ->fetchAll();

                foreach ($users as $user) {
                    $userData = $user->toArray();
                    foreach ($user->getPosts() as $post) {
                        $post->toArray();
                    }
                }
                break;

            case 'deep_relations':
                // Hydrate with nested relations
                $users = $this->userSelect
                    ->with('posts')
                    ->limit(5)
                    ->fetchAll();

                foreach ($users as $user) {
                    $userData = $user->toArray();
                    $posts = $user->getPosts();
                    foreach ($posts as $post) {
                        $postData = $post->toArray();
                        // Access user back-reference
                        $post->getUser()->getName();
                    }
                }
                break;
        }
    }

    public function getName(): string
    {
        return 'orm/hydration';
    }

    public function getDescription(): string
    {
        return 'Tests entity hydration performance with various collection sizes and relation depths';
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $options = getopt('', ['iterations:', 'warmup:', 'json']);

    $iterations = isset($options['iterations']) ? (int)$options['iterations'] : 100;
    $warmup = isset($options['warmup']) ? (int)$options['warmup'] : 10;

    $benchmark = new OrmHydrationBenchmark();
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
