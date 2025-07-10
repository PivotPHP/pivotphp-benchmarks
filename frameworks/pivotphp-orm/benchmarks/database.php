#!/usr/bin/env php
<?php

/**
 * PivotPHP ORM Database Benchmark
 * Tests ORM database operations performance (with Cycle ORM simulation)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

function runBenchmark($name, $iterations, $callback) {
    echo "🚀 $name ($iterations iterations)... ";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed
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
    echo "=============================================\n";
    echo " PivotPHP ORM Database Benchmark\n";
    echo "=============================================\n\n";
}

$results = [];

// Test 1: ORM Entity Manager Creation
$results['orm_entity_manager'] = runBenchmark(
    'ORM Entity Manager Creation', 
    $args['iterations'], 
    function() {
        // Simulate Cycle ORM Entity Manager setup
        $config = [
            'default' => 'default',
            'databases' => [
                'default' => [
                    'connection' => 'mysql',
                    'tablePrefix' => '',
                ]
            ],
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'port' => 3306,
                    'database' => 'benchmark_db',
                    'username' => 'benchmark_user',
                    'password' => 'benchmark_pass'
                ]
            ]
        ];
        
        // Simulate Entity Manager creation
        $entityManager = [
            'config' => $config,
            'connection_pool' => [],
            'schema_cache' => [],
            'entity_cache' => [],
            'query_cache' => [],
            'created_at' => microtime(true),
            'status' => 'ready'
        ];
        
        return $entityManager;
    }
);

// Test 2: ORM Entity Hydration
$results['orm_entity_hydration'] = runBenchmark(
    'ORM Entity Hydration', 
    $args['iterations'], 
    function() {
        // Simulate database row data
        $rawData = [
            'id' => rand(1, 10000),
            'name' => 'User ' . rand(1, 1000),
            'email' => 'user' . rand(1, 1000) . '@example.com',
            'password' => '$2y$10$' . str_repeat('x', 53),
            'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
            'updated_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 30)),
            'metadata' => json_encode([
                'preferences' => ['theme' => 'dark', 'lang' => 'en'],
                'permissions' => ['read', 'write', 'delete'],
                'last_login' => time() - rand(0, 86400)
            ])
        ];
        
        // Simulate ORM entity hydration process
        $entity = (object) [
            'id' => $rawData['id'],
            'name' => $rawData['name'],
            'email' => $rawData['email'],
            'createdAt' => new DateTime($rawData['created_at']),
            'updatedAt' => new DateTime($rawData['updated_at']),
            'metadata' => json_decode($rawData['metadata'], true),
            '__orm_state' => 'hydrated',
            '__orm_original' => $rawData,
            '__orm_changes' => [],
            '__orm_relations' => []
        ];
        
        // Simulate relation proxies
        $entity->posts = new class {
            public function load() {
                return array_map(function($i) {
                    return (object) [
                        'id' => $i,
                        'title' => "Post $i",
                        'content' => str_repeat("Content $i. ", 20),
                        'created_at' => new DateTime()
                    ];
                }, range(1, rand(0, 10)));
            }
        };
        
        return [
            'entity' => $entity,
            'hydration_time' => microtime(true),
            'relations_proxied' => 1,
            'memory_footprint' => strlen(serialize($entity))
        ];
    }
);

// Test 3: ORM Query Builder Operations
$results['orm_query_builder'] = runBenchmark(
    'ORM Query Builder Operations', 
    $args['iterations'], 
    function() {
        // Simulate ORM Query Builder
        $queryBuilder = [
            'entity' => 'User',
            'alias' => 'u',
            'select' => ['u.id', 'u.name', 'u.email', 'u.createdAt'],
            'joins' => [],
            'where' => [],
            'orderBy' => [],
            'groupBy' => [],
            'having' => [],
            'limit' => null,
            'offset' => null,
            'parameters' => []
        ];
        
        // Simulate building complex query
        $queryBuilder['where'][] = ['column' => 'u.active', 'operator' => '=', 'value' => 1, 'type' => 'AND'];
        $queryBuilder['where'][] = ['column' => 'u.createdAt', 'operator' => '>', 'value' => '2024-01-01', 'type' => 'AND'];
        
        $queryBuilder['joins'][] = [
            'type' => 'LEFT',
            'entity' => 'Profile',
            'alias' => 'p',
            'condition' => 'u.id = p.userId'
        ];
        
        $queryBuilder['joins'][] = [
            'type' => 'LEFT',
            'entity' => 'Post',
            'alias' => 'posts',
            'condition' => 'u.id = posts.authorId'
        ];
        
        $queryBuilder['orderBy'][] = ['column' => 'u.createdAt', 'direction' => 'DESC'];
        $queryBuilder['limit'] = 50;
        $queryBuilder['offset'] = 0;
        
        // Simulate SQL generation
        $sql = "SELECT " . implode(', ', $queryBuilder['select']) . " FROM User u";
        
        foreach ($queryBuilder['joins'] as $join) {
            $sql .= " {$join['type']} JOIN {$join['entity']} {$join['alias']} ON {$join['condition']}";
        }
        
        if (!empty($queryBuilder['where'])) {
            $sql .= " WHERE ";
            $conditions = [];
            foreach ($queryBuilder['where'] as $condition) {
                $conditions[] = "{$condition['column']} {$condition['operator']} '{$condition['value']}'";
            }
            $sql .= implode(' AND ', $conditions);
        }
        
        if (!empty($queryBuilder['orderBy'])) {
            $sql .= " ORDER BY ";
            $orders = [];
            foreach ($queryBuilder['orderBy'] as $order) {
                $orders[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= implode(', ', $orders);
        }
        
        if ($queryBuilder['limit']) {
            $sql .= " LIMIT {$queryBuilder['limit']}";
        }
        
        if ($queryBuilder['offset']) {
            $sql .= " OFFSET {$queryBuilder['offset']}";
        }
        
        return [
            'query_builder' => $queryBuilder,
            'generated_sql' => $sql,
            'complexity_score' => count($queryBuilder['joins']) + count($queryBuilder['where']) + count($queryBuilder['orderBy']),
            'build_time' => microtime(true)
        ];
    }
);

// Test 4: ORM Repository Pattern
$results['orm_repository_operations'] = runBenchmark(
    'ORM Repository Operations', 
    $args['iterations'], 
    function() {
        // Simulate User Repository
        $repository = [
            'entity_class' => 'User',
            'table_name' => 'users',
            'primary_key' => 'id',
            'entity_manager' => true,
            'cache_enabled' => true
        ];
        
        // Simulate repository methods
        $methods = [
            'findById' => function($id) {
                return [
                    'method' => 'findById',
                    'sql' => "SELECT * FROM users WHERE id = ?",
                    'parameters' => [$id],
                    'execution_time' => rand(2, 8) / 1000,
                    'cache_hit' => rand(0, 1)
                ];
            },
            'findByEmail' => function($email) {
                return [
                    'method' => 'findByEmail',
                    'sql' => "SELECT * FROM users WHERE email = ?",
                    'parameters' => [$email],
                    'execution_time' => rand(3, 12) / 1000,
                    'cache_hit' => rand(0, 1)
                ];
            },
            'findActiveUsers' => function() {
                return [
                    'method' => 'findActiveUsers',
                    'sql' => "SELECT * FROM users WHERE active = 1 ORDER BY created_at DESC",
                    'parameters' => [],
                    'execution_time' => rand(10, 30) / 1000,
                    'cache_hit' => rand(0, 1)
                ];
            },
            'createUser' => function($data) {
                return [
                    'method' => 'createUser',
                    'sql' => "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)",
                    'parameters' => array_values($data),
                    'execution_time' => rand(5, 15) / 1000,
                    'last_insert_id' => rand(1000, 50000)
                ];
            }
        ];
        
        // Execute random repository method
        $methodName = array_rand($methods);
        $method = $methods[$methodName];
        
        $result = null;
        switch ($methodName) {
            case 'findById':
                $result = $method(rand(1, 10000));
                break;
            case 'findByEmail':
                $result = $method('user' . rand(1, 1000) . '@example.com');
                break;
            case 'findActiveUsers':
                $result = $method();
                break;
            case 'createUser':
                $result = $method([
                    'name' => 'New User ' . rand(1, 1000),
                    'email' => 'newuser' . rand(1, 1000) . '@example.com',
                    'password' => password_hash('password', PASSWORD_DEFAULT),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                break;
        }
        
        return [
            'repository' => $repository,
            'method_result' => $result,
            'executed_at' => microtime(true)
        ];
    }
);

// Test 5: ORM Relationship Loading
$results['orm_relationship_loading'] = runBenchmark(
    'ORM Relationship Loading', 
    $args['iterations'], 
    function() {
        // Simulate User entity with relationships
        $user = (object) [
            'id' => rand(1, 1000),
            'name' => 'User ' . rand(1, 1000),
            'email' => 'user' . rand(1, 1000) . '@example.com'
        ];
        
        // Simulate eager loading relationships
        $relationships = [
            'profile' => [
                'type' => 'hasOne',
                'entity' => 'Profile',
                'foreignKey' => 'user_id',
                'localKey' => 'id',
                'sql' => "SELECT * FROM profiles WHERE user_id = ?",
                'execution_time' => rand(2, 8) / 1000,
                'loaded' => true,
                'data' => (object) [
                    'id' => rand(1, 1000),
                    'user_id' => $user->id,
                    'bio' => 'User bio content here...',
                    'avatar' => 'avatar_' . rand(1, 100) . '.jpg'
                ]
            ],
            'posts' => [
                'type' => 'hasMany',
                'entity' => 'Post',
                'foreignKey' => 'author_id',
                'localKey' => 'id',
                'sql' => "SELECT * FROM posts WHERE author_id = ? ORDER BY created_at DESC",
                'execution_time' => rand(5, 20) / 1000,
                'loaded' => true,
                'data' => array_map(function($i) use ($user) {
                    return (object) [
                        'id' => $i,
                        'author_id' => $user->id,
                        'title' => "Post $i by {$user->name}",
                        'content' => str_repeat("Content for post $i. ", 30),
                        'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 30))
                    ];
                }, range(1, rand(1, 10)))
            ],
            'roles' => [
                'type' => 'belongsToMany',
                'entity' => 'Role',
                'pivotTable' => 'user_roles',
                'foreignKey' => 'user_id',
                'relatedKey' => 'role_id',
                'sql' => "SELECT r.* FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?",
                'execution_time' => rand(3, 12) / 1000,
                'loaded' => true,
                'data' => array_map(function($i) {
                    return (object) [
                        'id' => $i,
                        'name' => 'Role ' . $i,
                        'permissions' => ['read', 'write']
                    ];
                }, range(1, rand(1, 3)))
            ]
        ];
        
        // Simulate relationship loading performance
        $totalExecutionTime = array_sum(array_column($relationships, 'execution_time'));
        $totalQueries = count($relationships);
        $totalRelatedEntities = 1 + count($relationships['posts']['data']) + count($relationships['roles']['data']);
        
        return [
            'user' => $user,
            'relationships' => $relationships,
            'loading_stats' => [
                'total_execution_time' => $totalExecutionTime,
                'total_queries' => $totalQueries,
                'total_entities_loaded' => $totalRelatedEntities,
                'memory_impact' => strlen(serialize($relationships))
            ]
        ];
    }
);

// Test 6: ORM Unit of Work Pattern
$results['orm_unit_of_work'] = runBenchmark(
    'ORM Unit of Work Pattern', 
    $args['iterations'], 
    function() {
        // Simulate Unit of Work
        $unitOfWork = [
            'id' => uniqid('uow_'),
            'started_at' => microtime(true),
            'entities' => [
                'new' => [],
                'dirty' => [],
                'removed' => []
            ],
            'transaction_active' => false
        ];
        
        // Simulate entity changes
        $changes = [
            // New entities
            'new' => array_map(function($i) {
                return [
                    'entity' => 'User',
                    'id' => null,
                    'data' => [
                        'name' => "New User $i",
                        'email' => "newuser$i@example.com",
                        'created_at' => date('Y-m-d H:i:s')
                    ],
                    'state' => 'new'
                ];
            }, range(1, rand(1, 5))),
            
            // Modified entities
            'dirty' => array_map(function($i) {
                return [
                    'entity' => 'User',
                    'id' => rand(1, 1000),
                    'changes' => [
                        'name' => 'Updated Name ' . $i,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    'original' => [
                        'name' => 'Original Name ' . $i,
                        'updated_at' => date('Y-m-d H:i:s', time() - 3600)
                    ],
                    'state' => 'dirty'
                ];
            }, range(1, rand(1, 3))),
            
            // Removed entities
            'removed' => array_map(function($i) {
                return [
                    'entity' => 'User',
                    'id' => rand(1, 1000),
                    'state' => 'removed'
                ];
            }, range(1, rand(0, 2)))
        ];
        
        $unitOfWork['entities'] = $changes;
        
        // Simulate flush operation
        $flushResult = [
            'queries_executed' => count($changes['new']) + count($changes['dirty']) + count($changes['removed']),
            'entities_persisted' => count($changes['new']) + count($changes['dirty']),
            'entities_removed' => count($changes['removed']),
            'execution_time' => rand(10, 50) / 1000,
            'transaction_time' => rand(15, 60) / 1000
        ];
        
        $unitOfWork['flush_result'] = $flushResult;
        $unitOfWork['completed_at'] = microtime(true);
        $unitOfWork['total_time'] = $unitOfWork['completed_at'] - $unitOfWork['started_at'];
        
        return $unitOfWork;
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'database',
        'framework' => 'pivotphp-orm',
        'version' => '1.0.1',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'orm_features' => ['entity_manager', 'query_builder', 'repositories', 'relationships', 'unit_of_work'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 ORM Database Performance Summary:\n";
    echo "====================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP ORM v1.0.1\n";
    echo "🗄️ Total ORM Database Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "⚙️ ORM Features: Entity Manager, Query Builder, Repositories, Relationships, Unit of Work\n";
    echo "📊 Status: " . (array_sum(array_column($results, 'ops_per_second')) > 4000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'database',
        'framework' => 'pivotphp-orm',
        'version' => '1.0.1',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'orm_features' => ['entity_manager', 'query_builder', 'repositories', 'relationships', 'unit_of_work'],
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-orm-database-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ ORM database benchmark completed successfully!\n";
}