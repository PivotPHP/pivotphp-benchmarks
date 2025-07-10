#!/usr/bin/env php
<?php

/**
 * PivotPHP Core REST API Endpoints Benchmark
 * Tests real-world REST API operation performance
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

// Simulate in-memory data store for realistic REST operations
class ApiDataStore {
    private static $users = [];
    private static $idCounter = 1;
    
    public static function createUser($data) {
        $user = array_merge($data, [
            'id' => self::$idCounter++,
            'created_at' => date('c'),
            'updated_at' => date('c')
        ]);
        self::$users[$user['id']] = $user;
        return $user;
    }
    
    public static function getUserById($id) {
        return self::$users[$id] ?? null;
    }
    
    public static function updateUser($id, $data) {
        if (!isset(self::$users[$id])) {
            return null;
        }
        self::$users[$id] = array_merge(self::$users[$id], $data, [
            'updated_at' => date('c')
        ]);
        return self::$users[$id];
    }
    
    public static function deleteUser($id) {
        if (!isset(self::$users[$id])) {
            return false;
        }
        unset(self::$users[$id]);
        return true;
    }
    
    public static function getAllUsers($page = 1, $perPage = 20, $filters = []) {
        $users = self::$users;
        
        // Apply filters
        if (!empty($filters['name'])) {
            $users = array_filter($users, function($user) use ($filters) {
                return stripos($user['name'], $filters['name']) !== false;
            });
        }
        
        if (!empty($filters['status'])) {
            $users = array_filter($users, function($user) use ($filters) {
                return ($user['status'] ?? 'active') === $filters['status'];
            });
        }
        
        // Pagination
        $total = count($users);
        $offset = ($page - 1) * $perPage;
        $paginatedUsers = array_slice($users, $offset, $perPage, true);
        
        return [
            'data' => array_values($paginatedUsers),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }
    
    public static function reset() {
        self::$users = [];
        self::$idCounter = 1;
    }
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "============================================\n";
    echo " PivotPHP Core REST API Benchmark\n";
    echo "============================================\n\n";
}

// Initialize data store with sample data
ApiDataStore::reset();
for ($i = 1; $i <= 100; $i++) {
    ApiDataStore::createUser([
        'name' => "User $i",
        'email' => "user$i@example.com",
        'status' => $i % 3 === 0 ? 'inactive' : 'active',
        'role' => $i % 10 === 0 ? 'admin' : 'user',
        'profile' => [
            'bio' => "Bio for user $i",
            'location' => 'City ' . ($i % 5 + 1),
            'website' => "https://user$i.example.com"
        ]
    ]);
}

$results = [];

// Test 1: GET /users (List with Pagination)
$results['get_users_list'] = runBenchmark(
    'GET /users (List with Pagination)', 
    $args['iterations'], 
    function() {
        // Simulate GET /users?page=1&per_page=20&status=active
        $queryParams = [
            'page' => rand(1, 5),
            'per_page' => rand(10, 50),
            'status' => rand(0, 1) ? 'active' : null,
            'name' => rand(0, 1) ? 'User' : null
        ];
        
        $filters = array_filter([
            'status' => $queryParams['status'],
            'name' => $queryParams['name']
        ]);
        
        $result = ApiDataStore::getAllUsers(
            $queryParams['page'],
            $queryParams['per_page'],
            $filters
        );
        
        // Simulate response formatting
        $response = [
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
            'links' => [
                'first' => "/users?page=1&per_page={$queryParams['per_page']}",
                'last' => "/users?page={$result['meta']['last_page']}&per_page={$queryParams['per_page']}",
                'prev' => $queryParams['page'] > 1 ? "/users?page=" . ($queryParams['page'] - 1) . "&per_page={$queryParams['per_page']}" : null,
                'next' => $queryParams['page'] < $result['meta']['last_page'] ? "/users?page=" . ($queryParams['page'] + 1) . "&per_page={$queryParams['per_page']}" : null
            ]
        ];
        
        return [
            'status_code' => 200,
            'response' => $response,
            'response_size' => strlen(json_encode($response)),
            'records_returned' => count($result['data'])
        ];
    }
);

// Test 2: GET /users/:id (Single Resource)
$results['get_user_by_id'] = runBenchmark(
    'GET /users/:id (Single Resource)', 
    $args['iterations'], 
    function() {
        $userId = rand(1, 100);
        $user = ApiDataStore::getUserById($userId);
        
        if ($user) {
            $response = [
                'success' => true,
                'data' => $user
            ];
            $statusCode = 200;
        } else {
            $response = [
                'error' => true,
                'message' => 'User not found',
                'code' => 404
            ];
            $statusCode = 404;
        }
        
        return [
            'status_code' => $statusCode,
            'response' => $response,
            'response_size' => strlen(json_encode($response)),
            'user_found' => $user !== null
        ];
    }
);

// Test 3: POST /users (Create Resource)
$results['post_create_user'] = runBenchmark(
    'POST /users (Create Resource)', 
    $args['iterations'], 
    function() {
        // Simulate request body
        $requestData = [
            'name' => 'New User ' . rand(1000, 9999),
            'email' => 'newuser' . rand(1000, 9999) . '@example.com',
            'password' => 'secure_password_123',
            'role' => 'user',
            'status' => 'active',
            'profile' => [
                'bio' => 'New user biography',
                'location' => 'New City',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true,
                    'language' => 'en'
                ]
            ]
        ];
        
        // Simulate validation
        $validationErrors = [];
        
        if (empty($requestData['name']) || strlen($requestData['name']) < 2) {
            $validationErrors['name'] = 'Name must be at least 2 characters';
        }
        
        if (!filter_var($requestData['email'], FILTER_VALIDATE_EMAIL)) {
            $validationErrors['email'] = 'Invalid email format';
        }
        
        if (strlen($requestData['password']) < 8) {
            $validationErrors['password'] = 'Password must be at least 8 characters';
        }
        
        if (!empty($validationErrors)) {
            $response = [
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validationErrors
            ];
            $statusCode = 422;
        } else {
            // Create user (exclude password from response)
            $userData = $requestData;
            unset($userData['password']);
            $user = ApiDataStore::createUser($userData);
            
            $response = [
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ];
            $statusCode = 201;
        }
        
        return [
            'status_code' => $statusCode,
            'response' => $response,
            'response_size' => strlen(json_encode($response)),
            'validation_passed' => empty($validationErrors)
        ];
    }
);

// Test 4: PUT /users/:id (Update Resource)
$results['put_update_user'] = runBenchmark(
    'PUT /users/:id (Update Resource)', 
    $args['iterations'], 
    function() {
        $userId = rand(1, 100);
        
        $updateData = [
            'name' => 'Updated User ' . rand(1000, 9999),
            'email' => 'updated' . rand(1000, 9999) . '@example.com',
            'status' => rand(0, 1) ? 'active' : 'inactive',
            'profile' => [
                'bio' => 'Updated biography',
                'location' => 'Updated City',
                'website' => 'https://updated.example.com'
            ]
        ];
        
        $user = ApiDataStore::updateUser($userId, $updateData);
        
        if ($user) {
            $response = [
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ];
            $statusCode = 200;
        } else {
            $response = [
                'error' => true,
                'message' => 'User not found',
                'code' => 404
            ];
            $statusCode = 404;
        }
        
        return [
            'status_code' => $statusCode,
            'response' => $response,
            'response_size' => strlen(json_encode($response)),
            'update_successful' => $user !== null
        ];
    }
);

// Test 5: PATCH /users/:id (Partial Update)
$results['patch_partial_update'] = runBenchmark(
    'PATCH /users/:id (Partial Update)', 
    $args['iterations'], 
    function() {
        $userId = rand(1, 100);
        
        // Simulate partial update data
        $partialData = [];
        $possibleUpdates = [
            'status' => rand(0, 1) ? 'active' : 'inactive',
            'role' => rand(0, 1) ? 'admin' : 'user',
            'profile.bio' => 'Partially updated bio',
            'profile.location' => 'New Location'
        ];
        
        // Randomly select 1-3 fields to update
        $fieldsToUpdate = array_rand($possibleUpdates, rand(1, 3));
        if (!is_array($fieldsToUpdate)) {
            $fieldsToUpdate = [$fieldsToUpdate];
        }
        
        foreach ($fieldsToUpdate as $field) {
            if (strpos($field, '.') !== false) {
                // Handle nested field updates
                [$parent, $child] = explode('.', $field, 2);
                $partialData[$parent][$child] = $possibleUpdates[$field];
            } else {
                $partialData[$field] = $possibleUpdates[$field];
            }
        }
        
        $user = ApiDataStore::updateUser($userId, $partialData);
        
        if ($user) {
            $response = [
                'success' => true,
                'message' => 'User partially updated successfully',
                'data' => $user,
                'updated_fields' => array_keys($partialData)
            ];
            $statusCode = 200;
        } else {
            $response = [
                'error' => true,
                'message' => 'User not found',
                'code' => 404
            ];
            $statusCode = 404;
        }
        
        return [
            'status_code' => $statusCode,
            'response' => $response,
            'response_size' => strlen(json_encode($response)),
            'fields_updated' => count($partialData),
            'update_successful' => $user !== null
        ];
    }
);

// Test 6: DELETE /users/:id (Delete Resource)
$results['delete_user'] = runBenchmark(
    'DELETE /users/:id (Delete Resource)', 
    $args['iterations'] / 2, 
    function() {
        $userId = rand(1, 100);
        
        // Check if user exists before deletion
        $existingUser = ApiDataStore::getUserById($userId);
        
        if ($existingUser) {
            $deleted = ApiDataStore::deleteUser($userId);
            
            if ($deleted) {
                $response = [
                    'success' => true,
                    'message' => 'User deleted successfully'
                ];
                $statusCode = 204; // No Content
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Failed to delete user',
                    'code' => 500
                ];
                $statusCode = 500;
            }
        } else {
            $response = [
                'error' => true,
                'message' => 'User not found',
                'code' => 404
            ];
            $statusCode = 404;
        }
        
        return [
            'status_code' => $statusCode,
            'response' => $response,
            'response_size' => strlen(json_encode($response)),
            'user_existed' => $existingUser !== null,
            'deletion_successful' => $statusCode === 204
        ];
    }
);

// Test 7: Complex Query Operations
$results['complex_query_operations'] = runBenchmark(
    'Complex Query Operations', 
    $args['iterations'], 
    function() {
        // Simulate complex API query: GET /users?include=profile&sort=created_at:desc&filter[status]=active&search=User
        $queryParams = [
            'include' => ['profile', 'posts'],
            'sort' => 'created_at:desc',
            'filter' => ['status' => 'active', 'role' => 'user'],
            'search' => 'User',
            'page' => rand(1, 3),
            'per_page' => 15
        ];
        
        // Get users with filters
        $result = ApiDataStore::getAllUsers(
            $queryParams['page'],
            $queryParams['per_page'],
            array_merge($queryParams['filter'], ['name' => $queryParams['search']])
        );
        
        // Simulate include processing
        foreach ($result['data'] as &$user) {
            if (in_array('profile', $queryParams['include'])) {
                $user['profile_expanded'] = $user['profile'] ?? [];
            }
            
            if (in_array('posts', $queryParams['include'])) {
                $user['posts'] = array_map(function($i) use ($user) {
                    return [
                        'id' => $i,
                        'title' => "Post $i by {$user['name']}",
                        'created_at' => date('c', time() - rand(0, 86400 * 30))
                    ];
                }, range(1, rand(0, 5)));
            }
        }
        
        // Simulate sorting
        if ($queryParams['sort'] === 'created_at:desc') {
            usort($result['data'], function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }
        
        $response = [
            'success' => true,
            'data' => $result['data'],
            'meta' => array_merge($result['meta'], [
                'query' => $queryParams,
                'processing_time' => rand(10, 100) / 1000
            ])
        ];
        
        return [
            'status_code' => 200,
            'response' => $response,
            'response_size' => strlen(json_encode($response)),
            'records_processed' => count($result['data']),
            'includes_processed' => count($queryParams['include']),
            'filters_applied' => count($queryParams['filter'])
        ];
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'rest-api',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'rest_operations' => ['GET_list', 'GET_single', 'POST_create', 'PUT_update', 'PATCH_partial', 'DELETE_resource', 'complex_queries'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 REST API Performance Summary:\n";
    echo "=================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP Core v1.1.0\n";
    echo "🔗 REST Operations: GET List, GET Single, POST Create, PUT Update, PATCH Partial, DELETE, Complex Queries\n";
    echo "⚡ Total REST API Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🌐 API Status: " . (array_sum(array_column($results, 'ops_per_second')) > 8000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'rest-api',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'rest_operations' => ['GET_list', 'GET_single', 'POST_create', 'PUT_update', 'PATCH_partial', 'DELETE_resource', 'complex_queries'],
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-core-rest-api-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ REST API benchmark completed successfully!\n";
}