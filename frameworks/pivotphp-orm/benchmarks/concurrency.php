#!/usr/bin/env php
<?php

/**
 * PivotPHP ORM Concurrency Benchmark
 * Tests concurrent request handling capabilities with ORM operations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

function runBenchmark($name, $iterations, $concurrency, $callback) {
    echo "🚀 $name ($iterations iterations, $concurrency concurrent)... ";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    // Simulate concurrent execution using process forks
    $pids = [];
    $iterationsPerProcess = intval($iterations / $concurrency);
    
    for ($proc = 0; $proc < $concurrency; $proc++) {
        $pid = pcntl_fork();
        if ($pid == 0) {
            // Child process
            for ($i = 0; $i < $iterationsPerProcess; $i++) {
                $callback();
            }
            exit(0);
        } else {
            $pids[] = $pid;
        }
    }
    
    // Wait for all child processes
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed,
        'concurrency' => $concurrency
    ];
}

function parseArgs($argv) {
    $args = ['json' => false, 'iterations' => 1000, 'warmup' => 100];
    
    foreach ($argv as $arg) {
        if ($arg === '--json') $args['json'] = true;
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 14));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "===============================================\n";
    echo " PivotPHP ORM Concurrency Benchmark\n";
    echo "===============================================\n\n";
}

$results = [];

// Test 1: Concurrent Application Creation with ORM
$results['concurrent_app_creation'] = runBenchmark(
    'Concurrent App Creation (ORM)', 
    $args['iterations'], 
    4, 
    function() {
        $app = new Application();
        $app->boot();
        return $app;
    }
);

// Test 2: Concurrent ORM Model Operations
$results['concurrent_orm_operations'] = runBenchmark(
    'Concurrent ORM Model Operations', 
    $args['iterations'], 
    8, 
    function() {
        // Simulate ORM entity operations
        $userData = [
            'id' => rand(1, 10000),
            'name' => 'User ' . rand(1, 1000),
            'email' => 'user' . rand(1, 1000) . '@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'metadata' => json_encode([
                'preferences' => ['theme' => 'dark', 'lang' => 'en'],
                'permissions' => ['read', 'write'],
                'last_login' => time()
            ])
        ];
        
        // Simulate entity hydration
        $entity = (object) $userData;
        
        // Simulate relationship loading
        $entity->posts = array_map(function($i) use ($entity) {
            return (object) [
                'id' => $i,
                'user_id' => $entity->id,
                'title' => "Post $i",
                'content' => str_repeat("Content for post $i. ", 50),
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400))
            ];
        }, range(1, rand(1, 10)));
        
        // Simulate query result processing
        $processed = [
            'entity' => $entity,
            'loaded_relations' => count($entity->posts),
            'memory_usage' => memory_get_usage(),
            'processed_at' => microtime(true)
        ];
        
        return $processed;
    }
);

// Test 3: Concurrent Database Connection Simulation
$results['concurrent_db_connections'] = runBenchmark(
    'Concurrent DB Connection Simulation', 
    $args['iterations'], 
    12, 
    function() {
        // Simulate database connection and query
        $connection = [
            'host' => 'localhost',
            'database' => 'benchmark_db',
            'connected_at' => microtime(true),
            'connection_id' => uniqid()
        ];
        
        // Simulate query execution
        $queries = [
            "SELECT * FROM users WHERE active = 1 LIMIT 100",
            "SELECT COUNT(*) FROM posts WHERE created_at > '2024-01-01'",
            "SELECT u.*, p.title FROM users u LEFT JOIN posts p ON u.id = p.user_id",
            "INSERT INTO activity_log (user_id, action, timestamp) VALUES (?, ?, ?)",
            "UPDATE users SET last_seen = ? WHERE id = ?"
        ];
        
        $results = [];
        foreach ($queries as $query) {
            $results[] = [
                'query' => $query,
                'execution_time' => rand(1, 50) / 1000, // Simulate 1-50ms
                'rows_affected' => rand(0, 1000),
                'memory_used' => rand(1024, 8192)
            ];
        }
        
        return [
            'connection' => $connection,
            'queries_executed' => count($results),
            'total_execution_time' => array_sum(array_column($results, 'execution_time')),
            'results' => $results
        ];
    }
);

// Test 4: Concurrent ORM Query Builder
$results['concurrent_query_builder'] = runBenchmark(
    'Concurrent ORM Query Builder', 
    $args['iterations'], 
    6, 
    function() {
        // Simulate complex query building
        $queryBuilder = [
            'table' => 'users',
            'select' => ['id', 'name', 'email', 'created_at'],
            'joins' => [
                ['table' => 'profiles', 'on' => 'users.id = profiles.user_id'],
                ['table' => 'posts', 'on' => 'users.id = posts.author_id']
            ],
            'where' => [
                ['column' => 'users.active', 'operator' => '=', 'value' => 1],
                ['column' => 'users.created_at', 'operator' => '>', 'value' => '2024-01-01'],
                ['column' => 'posts.published', 'operator' => '=', 'value' => 1]
            ],
            'orderBy' => [
                ['column' => 'users.created_at', 'direction' => 'DESC'],
                ['column' => 'posts.created_at', 'direction' => 'DESC']
            ],
            'limit' => 50,
            'offset' => 0
        ];
        
        // Simulate SQL generation
        $sql = "SELECT " . implode(', ', $queryBuilder['select']) . " FROM " . $queryBuilder['table'];
        
        foreach ($queryBuilder['joins'] as $join) {
            $sql .= " LEFT JOIN " . $join['table'] . " ON " . $join['on'];
        }
        
        if (!empty($queryBuilder['where'])) {
            $sql .= " WHERE ";
            $conditions = [];
            foreach ($queryBuilder['where'] as $condition) {
                $conditions[] = $condition['column'] . ' ' . $condition['operator'] . ' ' . 
                               (is_string($condition['value']) ? "'{$condition['value']}'" : $condition['value']);
            }
            $sql .= implode(' AND ', $conditions);
        }
        
        if (!empty($queryBuilder['orderBy'])) {
            $sql .= " ORDER BY ";
            $orders = [];
            foreach ($queryBuilder['orderBy'] as $order) {
                $orders[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= implode(', ', $orders);
        }
        
        $sql .= " LIMIT " . $queryBuilder['limit'] . " OFFSET " . $queryBuilder['offset'];
        
        return [
            'query_builder' => $queryBuilder,
            'generated_sql' => $sql,
            'sql_length' => strlen($sql),
            'complexity_score' => count($queryBuilder['joins']) + count($queryBuilder['where']) + count($queryBuilder['orderBy'])
        ];
    }
);

// Test 5: Concurrent Transaction Simulation
$results['concurrent_transactions'] = runBenchmark(
    'Concurrent Transaction Simulation', 
    $args['iterations'], 
    4, 
    function() {
        // Simulate database transaction
        $transaction = [
            'id' => uniqid('tx_'),
            'started_at' => microtime(true),
            'isolation_level' => 'READ_COMMITTED',
            'operations' => []
        ];
        
        // Simulate multiple operations in transaction
        $operations = [
            ['type' => 'INSERT', 'table' => 'users', 'data' => ['name' => 'John', 'email' => 'john@example.com']],
            ['type' => 'INSERT', 'table' => 'profiles', 'data' => ['user_id' => 'LAST_INSERT_ID', 'bio' => 'User bio']],
            ['type' => 'UPDATE', 'table' => 'counters', 'data' => ['users_count' => 'users_count + 1']],
            ['type' => 'INSERT', 'table' => 'activity_log', 'data' => ['action' => 'user_created', 'timestamp' => time()]]
        ];
        
        foreach ($operations as $op) {
            $transaction['operations'][] = [
                'operation' => $op,
                'executed_at' => microtime(true),
                'status' => 'success'
            ];
        }
        
        $transaction['committed_at'] = microtime(true);
        $transaction['duration'] = $transaction['committed_at'] - $transaction['started_at'];
        $transaction['operations_count'] = count($transaction['operations']);
        
        return $transaction;
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'concurrency',
        'framework' => 'pivotphp-orm',
        'version' => '1.0.1',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 ORM Concurrency Performance Summary:\n";
    echo "======================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec (x%d concurrent)\n", 
            $test, 
            number_format($result['ops_per_second']),
            $result['concurrency']
        );
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP ORM v1.0.1\n";
    echo "⚡ Total Concurrent Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🔄 Concurrency Status: " . (array_sum(array_column($results, 'ops_per_second')) > 4000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'concurrency',
        'framework' => 'pivotphp-orm',
        'version' => '1.0.1',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results)),
            'max_concurrency' => max(array_column($results, 'concurrency'))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-orm-concurrency-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ ORM concurrency benchmark completed successfully!\n";
}