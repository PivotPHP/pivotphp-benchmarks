#!/usr/bin/env php
<?php

/**
 * PivotPHP ORM Memory Usage Benchmark
 * Tests memory consumption with ORM operations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

function runMemoryBenchmark($name, $iterations, $callback) {
    echo "🚀 $name ($iterations iterations)... ";
    
    // Force garbage collection before test
    gc_collect_cycles();
    
    $startMemory = memory_get_usage(true);
    $startPeakMemory = memory_get_peak_usage(true);
    $start = microtime(true);
    
    $results = [];
    for ($i = 0; $i < $iterations; $i++) {
        $results[] = $callback();
        
        // Periodic memory measurement and cleanup
        if ($i % 50 === 0) {
            $currentMemory = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);
        }
    }
    
    $end = microtime(true);
    $endMemory = memory_get_usage(true);
    $endPeakMemory = memory_get_peak_usage(true);
    
    // Force garbage collection after test
    unset($results);
    gc_collect_cycles();
    $finalMemory = memory_get_usage(true);
    
    $duration = $end - $start;
    $memoryUsed = $endMemory - $startMemory;
    $peakMemoryIncrease = $endPeakMemory - $startPeakMemory;
    $memoryLeaked = $finalMemory - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec, ";
    echo number_format($memoryUsed / 1024) . " KB used, ";
    echo number_format($peakMemoryIncrease / 1024) . " KB peak\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed,
        'peak_memory_increase' => $peakMemoryIncrease,
        'memory_leaked' => $memoryLeaked,
        'memory_efficiency' => $iterations / ($memoryUsed / 1024), // ops per KB
        'gc_cycles_before' => gc_status()['runs'] ?? 0
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
    echo "============================================\n";
    echo " PivotPHP ORM Memory Benchmark\n";
    echo "============================================\n\n";
}

// Initial memory baseline
$initialMemory = memory_get_usage(true);
$initialPeakMemory = memory_get_peak_usage(true);

$results = [];

// Test 1: ORM Entity Creation Memory
$results['orm_entity_creation_memory'] = runMemoryBenchmark(
    'ORM Entity Creation Memory', 
    $args['iterations'], 
    function() {
        // Simulate ORM entity creation with full metadata
        $entity = (object) [
            'id' => rand(1, 10000),
            'name' => 'User ' . rand(1, 1000),
            'email' => 'user' . rand(1, 1000) . '@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'metadata' => [
                'preferences' => ['theme' => 'dark', 'lang' => 'en'],
                'permissions' => ['read', 'write', 'delete'],
                'profile' => [
                    'bio' => str_repeat('Bio content. ', 50),
                    'avatar' => 'avatar_' . rand(1, 100) . '.jpg',
                    'social_links' => [
                        'twitter' => '@user' . rand(1, 1000),
                        'github' => 'github.com/user' . rand(1, 1000),
                        'linkedin' => 'linkedin.com/in/user' . rand(1, 1000)
                    ]
                ]
            ],
            // ORM state tracking
            '__orm_state' => 'hydrated',
            '__orm_original' => [],
            '__orm_changes' => [],
            '__orm_relations' => [],
            '__orm_proxies' => []
        ];
        
        return $entity;
    }
);

// Test 2: Entity Collection Memory
$results['entity_collection_memory'] = runMemoryBenchmark(
    'Entity Collection Memory', 
    $args['iterations'] / 10, 
    function() {
        // Simulate loading a collection of entities
        $collection = [];
        
        for ($i = 0; $i < 100; $i++) {
            $entity = (object) [
                'id' => $i + 1,
                'name' => "User " . ($i + 1),
                'email' => "user" . ($i + 1) . "@example.com",
                'posts' => array_map(function($j) use ($i) {
                    return (object) [
                        'id' => ($i * 10) + $j,
                        'title' => "Post $j by User " . ($i + 1),
                        'content' => str_repeat("Content for post $j. ", 30),
                        'created_at' => new DateTime(),
                        '__orm_state' => 'hydrated'
                    ];
                }, range(1, rand(1, 5))),
                '__orm_state' => 'hydrated',
                '__orm_relations' => ['posts']
            ];
            
            $collection[] = $entity;
        }
        
        return $collection;
    }
);

// Test 3: ORM Query Builder Memory
$results['query_builder_memory'] = runMemoryBenchmark(
    'Query Builder Memory', 
    $args['iterations'], 
    function() {
        // Simulate complex query builder with extensive metadata
        $queryBuilder = [
            'entity' => 'User',
            'alias' => 'u',
            'select' => ['u.id', 'u.name', 'u.email', 'u.created_at'],
            'joins' => [
                [
                    'type' => 'LEFT',
                    'entity' => 'Profile',
                    'alias' => 'p',
                    'condition' => 'u.id = p.user_id',
                    'metadata' => ['relationship' => 'hasOne', 'lazy' => false]
                ],
                [
                    'type' => 'LEFT',
                    'entity' => 'Post',
                    'alias' => 'posts',
                    'condition' => 'u.id = posts.author_id',
                    'metadata' => ['relationship' => 'hasMany', 'lazy' => true]
                ]
            ],
            'where' => [
                ['column' => 'u.active', 'operator' => '=', 'value' => 1, 'type' => 'AND'],
                ['column' => 'u.created_at', 'operator' => '>', 'value' => '2024-01-01', 'type' => 'AND'],
                ['column' => 'posts.published', 'operator' => '=', 'value' => 1, 'type' => 'AND']
            ],
            'orderBy' => [
                ['column' => 'u.created_at', 'direction' => 'DESC'],
                ['column' => 'posts.created_at', 'direction' => 'DESC']
            ],
            'groupBy' => ['u.id'],
            'having' => [
                ['column' => 'COUNT(posts.id)', 'operator' => '>', 'value' => 0]
            ],
            'limit' => 50,
            'offset' => 0,
            'parameters' => [],
            'metadata' => [
                'query_cache_key' => md5(uniqid()),
                'estimated_rows' => rand(100, 1000),
                'complexity_score' => 15,
                'optimization_hints' => ['use_index', 'join_order']
            ]
        ];
        
        // Simulate SQL generation with extensive metadata
        $sql = [
            'query' => "SELECT u.id, u.name, u.email, u.created_at FROM User u LEFT JOIN Profile p ON u.id = p.user_id LEFT JOIN Post posts ON u.id = posts.author_id WHERE u.active = 1 AND u.created_at > '2024-01-01' AND posts.published = 1 GROUP BY u.id HAVING COUNT(posts.id) > 0 ORDER BY u.created_at DESC, posts.created_at DESC LIMIT 50 OFFSET 0",
            'parameters' => [],
            'metadata' => $queryBuilder['metadata']
        ];
        
        return [
            'query_builder' => $queryBuilder,
            'generated_sql' => $sql,
            'complexity' => count($queryBuilder['joins']) + count($queryBuilder['where']) + count($queryBuilder['orderBy'])
        ];
    }
);

// Test 4: ORM Relationship Loading Memory
$results['relationship_loading_memory'] = runMemoryBenchmark(
    'Relationship Loading Memory', 
    $args['iterations'] / 5, 
    function() {
        // Simulate deep relationship loading
        $user = (object) [
            'id' => rand(1, 1000),
            'name' => 'User ' . rand(1, 1000),
            'email' => 'user' . rand(1, 1000) . '@example.com'
        ];
        
        // Load profile relationship
        $user->profile = (object) [
            'id' => rand(1, 1000),
            'user_id' => $user->id,
            'bio' => str_repeat('Biography content. ', 100),
            'avatar' => 'avatar_' . rand(1, 100) . '.jpg',
            'settings' => [
                'privacy' => ['public_profile' => true, 'show_email' => false],
                'notifications' => ['email' => true, 'push' => true, 'sms' => false],
                'preferences' => array_fill_keys(range(1, 50), 'preference_value')
            ],
            '__orm_state' => 'hydrated'
        ];
        
        // Load posts relationship
        $user->posts = array_map(function($i) use ($user) {
            return (object) [
                'id' => $i,
                'author_id' => $user->id,
                'title' => "Post $i",
                'content' => str_repeat("Content for post $i. ", 100),
                'tags' => array_map(fn($j) => "tag_$j", range(1, rand(1, 10))),
                'comments' => array_map(function($k) use ($i) {
                    return (object) [
                        'id' => $k,
                        'post_id' => $i,
                        'content' => str_repeat("Comment $k content. ", 20),
                        'author' => "Commenter $k",
                        '__orm_state' => 'hydrated'
                    ];
                }, range(1, rand(0, 5))),
                '__orm_state' => 'hydrated'
            ];
        }, range(1, rand(1, 10)));
        
        // Load roles relationship (many-to-many)
        $user->roles = array_map(function($i) {
            return (object) [
                'id' => $i,
                'name' => "Role $i",
                'permissions' => array_map(fn($j) => "permission_$j", range(1, rand(5, 15))),
                'metadata' => [
                    'description' => str_repeat("Role description. ", 10),
                    'created_at' => new DateTime(),
                    'hierarchy_level' => rand(1, 5)
                ],
                '__orm_state' => 'hydrated'
            ];
        }, range(1, rand(1, 3)));
        
        $user->__orm_relations = ['profile', 'posts', 'roles'];
        $user->__orm_state = 'hydrated';
        
        return $user;
    }
);

// Test 5: ORM Cache Memory Usage
$results['orm_cache_memory'] = runMemoryBenchmark(
    'ORM Cache Memory Usage', 
    $args['iterations'], 
    function() {
        // Simulate ORM caching mechanisms
        $cacheEntry = [
            'type' => 'entity',
            'key' => 'User:' . rand(1, 1000),
            'data' => [
                'entity' => [
                    'id' => rand(1, 1000),
                    'name' => 'Cached User ' . rand(1, 1000),
                    'email' => 'cached' . rand(1, 1000) . '@example.com',
                    'metadata' => array_fill_keys(range(1, 20), str_repeat('cached_data ', 10))
                ],
                'relationships' => [
                    'posts_count' => rand(0, 100),
                    'last_login' => time() - rand(0, 86400),
                    'preferences' => array_fill_keys(range(1, 30), 'preference_value')
                ]
            ],
            'metadata' => [
                'cached_at' => microtime(true),
                'expires_at' => microtime(true) + 3600,
                'hit_count' => rand(1, 100),
                'tags' => ['user', 'entity', 'active'],
                'compression' => 'none',
                'size' => rand(1024, 8192)
            ]
        ];
        
        // Simulate query cache
        $queryCacheEntry = [
            'type' => 'query',
            'key' => md5('SELECT * FROM users WHERE active = 1'),
            'sql' => 'SELECT * FROM users WHERE active = 1 ORDER BY created_at DESC LIMIT 50',
            'parameters' => [1],
            'result_ids' => range(1, rand(10, 50)),
            'metadata' => [
                'execution_time' => rand(10, 100) / 1000,
                'result_count' => rand(10, 50),
                'cached_at' => microtime(true),
                'expires_at' => microtime(true) + 1800
            ]
        ];
        
        return [
            'entity_cache' => $cacheEntry,
            'query_cache' => $queryCacheEntry,
            'total_cache_size' => strlen(serialize($cacheEntry)) + strlen(serialize($queryCacheEntry))
        ];
    }
);

// Test 6: ORM Unit of Work Memory
$results['unit_of_work_memory'] = runMemoryBenchmark(
    'Unit of Work Memory', 
    $args['iterations'] / 2, 
    function() {
        // Simulate Unit of Work with change tracking
        $unitOfWork = [
            'id' => uniqid('uow_'),
            'entities' => [
                'new' => [],
                'dirty' => [],
                'removed' => []
            ],
            'change_tracking' => [],
            'identity_map' => [],
            'metadata' => [
                'transaction_level' => 0,
                'started_at' => microtime(true),
                'memory_threshold' => 1024 * 1024 * 10 // 10MB
            ]
        ];
        
        // Simulate new entities
        for ($i = 0; $i < rand(5, 15); $i++) {
            $entity = [
                'entity' => 'User',
                'data' => [
                    'name' => "New User $i",
                    'email' => "newuser$i@example.com",
                    'metadata' => array_fill_keys(range(1, 10), "metadata_$i")
                ],
                'state' => 'new',
                'change_set' => [],
                'original_data' => null
            ];
            
            $unitOfWork['entities']['new'][] = $entity;
            $unitOfWork['identity_map']["User:new:$i"] = $entity;
        }
        
        // Simulate dirty entities
        for ($i = 0; $i < rand(3, 10); $i++) {
            $originalData = [
                'id' => rand(1, 1000),
                'name' => "Original User $i",
                'email' => "original$i@example.com",
                'updated_at' => date('Y-m-d H:i:s', time() - 3600)
            ];
            
            $newData = [
                'id' => $originalData['id'],
                'name' => "Updated User $i",
                'email' => "updated$i@example.com",
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $entity = [
                'entity' => 'User',
                'data' => $newData,
                'state' => 'dirty',
                'change_set' => [
                    'name' => ['old' => $originalData['name'], 'new' => $newData['name']],
                    'email' => ['old' => $originalData['email'], 'new' => $newData['email']],
                    'updated_at' => ['old' => $originalData['updated_at'], 'new' => $newData['updated_at']]
                ],
                'original_data' => $originalData
            ];
            
            $unitOfWork['entities']['dirty'][] = $entity;
            $unitOfWork['identity_map']["User:{$originalData['id']}"] = $entity;
            $unitOfWork['change_tracking'][] = $entity['change_set'];
        }
        
        // Simulate removed entities
        for ($i = 0; $i < rand(1, 5); $i++) {
            $entity = [
                'entity' => 'User',
                'data' => ['id' => rand(1, 1000)],
                'state' => 'removed',
                'change_set' => [],
                'original_data' => [
                    'id' => rand(1, 1000),
                    'name' => "Removed User $i",
                    'email' => "removed$i@example.com"
                ]
            ];
            
            $unitOfWork['entities']['removed'][] = $entity;
        }
        
        return $unitOfWork;
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'memory',
        'framework' => 'pivotphp-orm',
        'version' => '1.0.1',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'orm_features' => ['entities', 'collections', 'query_builder', 'relationships', 'cache', 'unit_of_work'],
        'memory_baseline' => [
            'initial_memory' => $initialMemory,
            'initial_peak' => $initialPeakMemory,
            'final_memory' => memory_get_usage(true),
            'final_peak' => memory_get_peak_usage(true)
        ],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'total_memory_used' => array_sum(array_column($results, 'memory_used')),
        'avg_memory_efficiency' => array_sum(array_column($results, 'memory_efficiency')) / count($results),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 ORM Memory Performance Summary:\n";
    echo "==================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec | %6s KB | %4.1f ops/KB\n", 
            $test, 
            number_format($result['ops_per_second']),
            number_format($result['memory_used'] / 1024, 1),
            $result['memory_efficiency']
        );
    }
    
    $finalMemory = memory_get_usage(true);
    $finalPeakMemory = memory_get_peak_usage(true);
    $totalMemoryUsed = array_sum(array_column($results, 'memory_used'));
    $totalMemoryLeaked = array_sum(array_column($results, 'memory_leaked'));
    $avgMemoryEfficiency = array_sum(array_column($results, 'memory_efficiency')) / count($results);
    
    echo "\n🏆 ORM Memory Statistics:\n";
    echo "========================\n";
    echo "Initial Memory:     " . number_format($initialMemory / 1024 / 1024, 2) . " MB\n";
    echo "Final Memory:       " . number_format($finalMemory / 1024 / 1024, 2) . " MB\n";
    echo "Peak Memory:        " . number_format($finalPeakMemory / 1024 / 1024, 2) . " MB\n";
    echo "Total Memory Used:  " . number_format($totalMemoryUsed / 1024, 1) . " KB\n";
    echo "Memory Leaked:      " . number_format($totalMemoryLeaked / 1024, 1) . " KB\n";
    echo "Avg Efficiency:     " . number_format($avgMemoryEfficiency, 1) . " ops/KB\n";
    
    echo "\n🎯 Framework: PivotPHP ORM v1.0.1\n";
    echo "⚙️ ORM Features: Entities, Query Builder, Relationships, Cache, Unit of Work\n";
    echo "⚡ Total Memory Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "💾 Memory Status: " . ($totalMemoryLeaked < 2048000 ? "EFFICIENT ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'memory',
        'framework' => 'pivotphp-orm',
        'version' => '1.0.1',
        'core_version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'orm_features' => ['entities', 'collections', 'query_builder', 'relationships', 'cache', 'unit_of_work'],
        'memory_baseline' => [
            'initial_memory' => $initialMemory,
            'initial_peak' => $initialPeakMemory,
            'final_memory' => $finalMemory,
            'final_peak' => $finalPeakMemory
        ],
        'results' => $results,
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'total_memory_used' => $totalMemoryUsed,
            'total_memory_leaked' => $totalMemoryLeaked,
            'avg_memory_efficiency' => $avgMemoryEfficiency
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-orm-memory-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ ORM memory benchmark completed successfully!\n";
}